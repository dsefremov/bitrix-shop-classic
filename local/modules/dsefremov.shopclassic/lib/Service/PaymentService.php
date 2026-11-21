<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Service;

use Bitrix\Main\HttpRequest;
use Bitrix\Main\Server;
use Bitrix\Sale\Order;
use Bitrix\Sale\PaySystem\Manager as PaySystemManager;
use Bitrix\Sale\PaySystem\Service;
use Dsefremov\ShopClassic\Config;
use Sale\Handlers\PaySystem\Dsefremov_StubHandler;

/**
 * Оплата заказа покупателем через штатный PaySystem\Service::processRequest() «Тестовой оплаты».
 * Магазин проверяет владельца и состояние заказа, остальное делает ядро: setPaid('Y') + статус
 * «Оплачен» при успехе, пометка оплаты и запись в историю заказа при отказе.
 */
final class PaymentService
{
    public const string PAID = 'paid';
    public const string DECLINED = 'declined';

    public function __construct(
        private readonly OrderService $orders = new OrderService(),
    ) {
    }

    /**
     * @return self::PAID|self::DECLINED
     */
    public function process(int $orderId, int $userId, bool $success): string
    {
        $order = $this->orders->loadForUser($orderId, $userId);
        if (null === $order) {
            throw new PaymentException('Заказ не найден.', 404);
        }
        if ($order->isCanceled()) {
            throw new PaymentException('Заказ отменён, оплатить его нельзя.');
        }
        if ($order->isPaid()) {
            throw new PaymentException('Заказ уже оплачен.');
        }
        $payment = $this->orders->stubPayment($order);
        if (null === $payment) {
            throw new PaymentException('У заказа нет оплаты «Тестовая оплата».');
        }
        $service = PaySystemManager::getObjectById((int) $payment->getPaymentSystemId());
        if (!$service instanceof Service) {
            throw new PaymentException('Платёжная система недоступна.');
        }

        $request = new HttpRequest(new Server([]), [], [
            Dsefremov_StubHandler::FIELD_PAYMENT_ID => (string) $payment->getId(),
            Dsefremov_StubHandler::FIELD_RESULT => $success ? Dsefremov_StubHandler::RESULT_SUCCESS : Dsefremov_StubHandler::RESULT_FAIL,
        ], [], []);
        $result = $service->processRequest($request);

        $reloaded = $this->orders->load($orderId);
        if ($success) {
            if (!$result->isSuccess() || null === $reloaded || !$reloaded->isPaid()) {
                throw new PaymentException('Не удалось провести оплату: '.implode('; ', $result->getErrorMessages()));
            }
            $this->unmark($reloaded);

            return self::PAID;
        }
        if ($result->isSuccess() || null === $reloaded || !$this->orders->isDeclined($reloaded)) {
            throw new PaymentException('Отказ оплаты не записан.');
        }

        return self::DECLINED;
    }

    /**
     * Повторная оплата прошла: снимаем с заказа отметку «Оплата отклонена» (её поставило ядро при отказе),
     * запись об отказе остаётся в истории заказа.
     */
    private function unmark(Order $order): void
    {
        if ('Y' !== $order->getField('MARKED')) {
            return;
        }
        $order->setField('MARKED', 'N');
        $order->setField('REASON_MARKED', '');
        $saved = $order->save();
        if (!$saved->isSuccess()) {
            throw new PaymentException('Оплата прошла, но отметка об отказе не снята: '.implode('; ', $saved->getErrorMessages()));
        }
    }

    public static function paySystemId(): int
    {
        return Config::paySystemId();
    }
}
