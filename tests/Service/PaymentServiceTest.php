<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Tests\Service;

use Bitrix\Sale\Internals\OrderChangeTable;
use Bitrix\Sale\Order;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Entity\Row;
use Dsefremov\ShopClassic\Service\OrderService;
use Dsefremov\ShopClassic\Service\PaymentException;
use Dsefremov\ShopClassic\Service\PaymentService;

/**
 * «Тестовая оплата» через штатный PaySystem\Service::processRequest(): успех — setPaid('Y') и статус
 * «Оплачен»; отказ — запись в истории и отметка «Оплата отклонена», заказ не оплачен, резерв
 * сохраняется, можно оплатить повторно.
 */
final class PaymentServiceTest extends ServiceTestCase
{
    public function testPaySetsPaidAndStatus(): void
    {
        $user = $this->createUser();
        $productId = $this->createProduct('Часы', 7990, 3);
        $orderId = $this->placeOrder($user, [[$productId, 1]]);

        self::assertSame(PaymentService::PAID, new PaymentService()->process($orderId, $user['id'], true));

        $order = Order::load($orderId);
        self::assertInstanceOf(Order::class, $order);
        self::assertTrue($order->isPaid());
        self::assertSame(Config::STATUS_PAID, $order->getField('STATUS_ID'));
        $payment = new OrderService()->stubPayment($order);
        self::assertNotNull($payment);
        self::assertTrue($payment->isPaid());
        self::assertStringStartsWith('STUB-'.$payment->getId().'-', (string) $payment->getField('PS_INVOICE_ID'));
        self::assertSame(['quantity' => 2, 'reserved' => 1], $this->stock($productId), 'Резерв держится до отгрузки.');
    }

    public function testDeclineKeepsOrderUnpaidAndAllowsRetry(): void
    {
        $user = $this->createUser();
        $productId = $this->createProduct('Камера', 3490, 2);
        $orderId = $this->placeOrder($user, [[$productId, 1]]);
        $service = new PaymentService();
        $orders = new OrderService();

        self::assertSame(PaymentService::DECLINED, $service->process($orderId, $user['id'], false));
        $order = Order::load($orderId);
        self::assertInstanceOf(Order::class, $order);
        self::assertFalse($order->isPaid());
        self::assertSame(Config::STATUS_ACCEPTED, $order->getField('STATUS_ID'));
        self::assertTrue($orders->isDeclined($order), 'Отметка «Оплата отклонена».');
        self::assertSame('Y', $order->getField('MARKED'));
        self::assertSame(Config::DECLINED_MESSAGE, $order->getField('REASON_MARKED'));
        self::assertSame(['quantity' => 1, 'reserved' => 1], $this->stock($productId), 'Резерв сохраняется.');
        self::assertContains('ORDER_MARKED', $this->history($orderId), 'Отказ записан в историю заказа.');

        // Второй отказ подряд — тоже без оплаты, заказ остаётся доступным для оплаты.
        self::assertSame(PaymentService::DECLINED, $service->process($orderId, $user['id'], false));

        self::assertSame(PaymentService::PAID, $service->process($orderId, $user['id'], true));
        $order = Order::load($orderId);
        self::assertInstanceOf(Order::class, $order);
        self::assertTrue($order->isPaid());
        self::assertSame(Config::STATUS_PAID, $order->getField('STATUS_ID'));
        self::assertFalse($orders->isDeclined($order));
        self::assertSame('N', $order->getField('MARKED'), 'Отметка снята после успешной оплаты.');
        self::assertSame(['quantity' => 1, 'reserved' => 1], $this->stock($productId));
        self::assertContains('PAYMENT_PAID', $this->history($orderId));
    }

    public function testRejectsForeignPaidAndCanceledOrders(): void
    {
        $user = $this->createUser();
        $service = new PaymentService();

        $foreign = $this->placeOrder($this->createUser(), [[$this->createProduct(), 1]]);
        $this->assertPaymentError(fn () => $service->process($foreign, $user['id'], true), 'Заказ не найден.');
        self::assertFalse(Order::load($foreign)?->isPaid());

        $paid = $this->placeOrder($user, [[$this->createProduct(), 1]]);
        $service->process($paid, $user['id'], true);
        $this->assertPaymentError(fn () => $service->process($paid, $user['id'], false), 'Заказ уже оплачен.');

        $canceled = $this->placeOrder($user, [[$this->createProduct(), 1]]);
        new OrderService()->cancel($canceled);
        $this->assertPaymentError(fn () => $service->process($canceled, $user['id'], true), 'Заказ отменён, оплатить его нельзя.');
    }

    public function testExternalCallbackCannotPay(): void
    {
        $user = $this->createUser();
        $orderId = $this->placeOrder($user, [[$this->createProduct(), 1]]);
        $payment = new OrderService()->stubPayment(Order::load($orderId) ?? self::fail());
        self::assertNotNull($payment);

        // Штатная точка уведомлений платёжных систем ищет обработчик по признакам запроса;
        // у «Тестовой оплаты» признаков нет, поэтому запрос извне её не находит.
        $request = new \Bitrix\Main\HttpRequest(new \Bitrix\Main\Server([]), [], ['PAYMENT_ID' => (string) $payment->getId(), 'STUB_RESULT' => 'success'], [], []);
        self::assertFalse(\Bitrix\Sale\PaySystem\Manager::searchByRequest($request));
        self::assertFalse($payment->isPaid());
        self::assertFalse(Order::load($orderId)?->isPaid());
    }

    /**
     * @return list<string>
     */
    private function history(int $orderId): array
    {
        $rows = OrderChangeTable::getList(['select' => ['TYPE'], 'filter' => ['=ORDER_ID' => $orderId], 'order' => ['ID' => 'ASC']])->fetchAll();

        return array_map(static fn (array $row): string => Row::string($row, 'TYPE'), $rows);
    }

    private function assertPaymentError(\Closure $call, string $message): void
    {
        try {
            $call();
            self::fail('Ожидалась ошибка оплаты: '.$message);
        } catch (PaymentException $e) {
            self::assertSame($message, $e->getMessage());
        }
    }
}
