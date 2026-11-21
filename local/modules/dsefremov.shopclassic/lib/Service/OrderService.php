<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Service;

use Bitrix\Main\Result;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Dsefremov\ShopClassic\Config;

/**
 * Операции с заказом через Sale\Order (те же, что делает менеджер в /bitrix/admin):
 * смена статуса и отмена (CANCELED = Y — ядро снимает резерв и возвращает остаток).
 */
final class OrderService
{
    public function load(int $orderId): ?Order
    {
        $order = $orderId > 0 ? Order::load($orderId) : null;

        return $order instanceof Order ? $order : null;
    }

    /**
     * Заказ покупателя: чужой или несуществующий — null.
     */
    public function loadForUser(int $orderId, int $userId): ?Order
    {
        $order = $this->load($orderId);

        return null !== $order && $userId > 0 && (int) $order->getUserId() === $userId ? $order : null;
    }

    /**
     * Статус магазина («Принят», «Оплачен», «Выполнен»); прочие коды ядро бы сохранило без проверки.
     */
    public function setStatus(int $orderId, string $statusId): Result
    {
        if (!isset(Config::STATUSES[$statusId])) {
            return new Result()->addError(new \Bitrix\Main\Error('Неизвестный статус заказа.'));
        }
        $order = $this->load($orderId);
        if (null === $order) {
            return new Result()->addError(new \Bitrix\Main\Error('Заказ не найден.'));
        }
        $set = $order->setField('STATUS_ID', $statusId);
        if (!$set->isSuccess()) {
            return $set;
        }

        return $order->save();
    }

    public function cancel(int $orderId, string $reason = ''): Result
    {
        $order = $this->load($orderId);
        if (null === $order) {
            return new Result()->addError(new \Bitrix\Main\Error('Заказ не найден.'));
        }
        if ($order->isCanceled()) {
            return new Result();
        }
        $set = $order->setField('CANCELED', 'Y');
        if (!$set->isSuccess()) {
            return $set;
        }
        if ('' !== $reason) {
            $order->setField('REASON_CANCELED', $reason);
        }

        return $order->save();
    }

    /**
     * Оплата «Тестовой оплатой» (первая, если их несколько).
     */
    public function stubPayment(Order $order): ?Payment
    {
        foreach ($order->getPaymentCollection() as $payment) {
            if ($payment->getPaymentSystemId() === Config::paySystemId()) {
                return $payment;
            }
        }

        return null;
    }

    /**
     * «Оплата отклонена»: последняя попытка оплаты не прошла (оплата помечена ядром, MARKED = Y) и заказ не оплачен.
     */
    public function isDeclined(Order $order): bool
    {
        $payment = $this->stubPayment($order);

        return null !== $payment && !$payment->isPaid() && 'Y' === $payment->getField('MARKED');
    }
}
