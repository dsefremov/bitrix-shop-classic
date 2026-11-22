<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Tests\Service;

use Bitrix\Sale\Order;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Service\OrderService;

/**
 * Статусы и отмена заказа через Sale\Order: отмена (CANCELED = Y) возвращает остаток ровно один раз.
 */
final class OrderServiceTest extends ServiceTestCase
{
    public function testCancelReturnsStockExactlyOnce(): void
    {
        $productId = $this->createProduct('Френч-пресс', 1890, 5);
        $orderId = $this->placeOrder($this->createUser(), [[$productId, 3]]);
        self::assertSame(['quantity' => 2, 'reserved' => 3], $this->stock($productId));

        $orders = new OrderService();
        self::assertTrue($orders->cancel($orderId, 'Покупатель передумал')->isSuccess());
        self::assertSame(['quantity' => 5, 'reserved' => 0], $this->stock($productId), 'Резерв снят, остаток вернулся.');

        self::assertTrue($orders->cancel($orderId)->isSuccess(), 'Повторная отмена — без ошибки.');
        self::assertSame(['quantity' => 5, 'reserved' => 0], $this->stock($productId), 'Повторная отмена не возвращает остаток второй раз.');

        $order = Order::load($orderId);
        self::assertInstanceOf(Order::class, $order);
        self::assertTrue($order->isCanceled());
        self::assertSame('Покупатель передумал', $order->getField('REASON_CANCELED'));
    }

    public function testCancelOfPaidOrderReturnsStockOnce(): void
    {
        $user = $this->createUser();
        $productId = $this->createProduct('Термос', 1290, 4);
        $orderId = $this->placeOrder($user, [[$productId, 1]]);
        new \Dsefremov\ShopClassic\Service\PaymentService()->process($orderId, $user['id'], true);
        self::assertSame(['quantity' => 3, 'reserved' => 1], $this->stock($productId));

        $orders = new OrderService();
        // Оплаченный заказ отменяется после возврата оплаты — как делает менеджер в админке.
        $order = Order::load($orderId);
        self::assertInstanceOf(Order::class, $order);
        foreach ($order->getPaymentCollection() as $payment) {
            self::assertTrue($payment->setPaid('N')->isSuccess());
        }
        self::assertTrue($order->save()->isSuccess());

        self::assertTrue($orders->cancel($orderId)->isSuccess());
        self::assertTrue($orders->cancel($orderId)->isSuccess());
        self::assertSame(['quantity' => 4, 'reserved' => 0], $this->stock($productId));
    }

    public function testStatusChanges(): void
    {
        $user = $this->createUser();
        $orderId = $this->placeOrder($user, [[$this->createProduct(), 1]]);
        $orders = new OrderService();

        self::assertTrue($orders->setStatus($orderId, Config::STATUS_COMPLETED)->isSuccess());
        self::assertSame(Config::STATUS_COMPLETED, Order::load($orderId)?->getField('STATUS_ID'));
        self::assertFalse($orders->setStatus($orderId, 'ZZ')->isSuccess(), 'Неизвестный статус не принимается.');
        self::assertFalse($orders->setStatus(999999, Config::STATUS_PAID)->isSuccess());
    }

    public function testLoadForUserHidesForeignOrders(): void
    {
        $owner = $this->createUser();
        $orderId = $this->placeOrder($owner, [[$this->createProduct(), 1]]);
        $orders = new OrderService();

        self::assertNotNull($orders->loadForUser($orderId, $owner['id']));
        self::assertNull($orders->loadForUser($orderId, $this->createUser()['id']));
        self::assertNull($orders->loadForUser($orderId, 0));
    }
}
