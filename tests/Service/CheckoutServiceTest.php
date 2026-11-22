<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Tests\Service;

use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Bitrix\Sale\Shipment;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Service\CheckoutException;
use Dsefremov\ShopClassic\Service\CheckoutService;

/**
 * Оформление заказа на Sale API: статус «Принят», свойства, «Самовывоз», «Тестовая оплата»,
 * резерв при создании заказа, проверка остатков в одной транзакции.
 */
final class CheckoutServiceTest extends ServiceTestCase
{
    public function testPlacesOrderAndReservesStock(): void
    {
        $user = $this->createUser('Иван Петров');
        $first = $this->createProduct('Наушники', 5990, 5);
        $second = $this->createProduct('Колонка', 2790, 2);

        $orderId = $this->placeOrder($user, [[$first, 2], [$second, 1]]);
        $order = Order::load($orderId);
        self::assertInstanceOf(Order::class, $order);

        self::assertSame($user['id'], (int) $order->getUserId());
        self::assertSame(Config::STATUS_ACCEPTED, $order->getField('STATUS_ID'));
        self::assertSame(Config::personTypeId(), (int) $order->getPersonTypeId());
        self::assertEqualsWithDelta(14770.0, (float) $order->getPrice(), 0.001);
        self::assertFalse($order->isPaid());
        self::assertFalse($order->isCanceled());

        $props = [];
        foreach ($order->getPropertyCollection() as $property) {
            $props[(string) $property->getField('CODE')] = $property->getValue();
        }
        self::assertSame(['FIO' => 'Иван Петров', 'EMAIL' => $user['email'], 'PHONE' => '+7 900 123-45-67', 'ADDRESS' => 'г. Москва, ул. Тверская, д. 1'], $props);

        $shipments = array_values(array_filter(iterator_to_array($order->getShipmentCollection()), static fn (Shipment $s): bool => !$s->isSystem()));
        self::assertCount(1, $shipments);
        self::assertSame(Config::deliveryId(), (int) $shipments[0]->getDeliveryId());
        self::assertTrue($shipments[0]->isReserved(), 'Отгрузка зарезервирована при создании заказа.');

        $payments = iterator_to_array($order->getPaymentCollection());
        self::assertCount(1, $payments);
        self::assertInstanceOf(Payment::class, $payments[0]);
        self::assertSame(Config::paySystemId(), (int) $payments[0]->getPaymentSystemId());
        self::assertEqualsWithDelta(14770.0, (float) $payments[0]->getSum(), 0.001);

        self::assertSame(['quantity' => 3, 'reserved' => 2], $this->stock($first));
        self::assertSame(['quantity' => 1, 'reserved' => 1], $this->stock($second));
        self::assertSame(0, $this->basketOf($user['id'])->count(), 'Корзина стала позициями заказа.');
    }

    public function testStockTakenByAnotherBuyerFailsWithoutOrder(): void
    {
        $productId = $this->createProduct('Последний', 1000, 2);
        $late = $this->createUser();
        $lateBasket = $this->basketOf($late['id']);
        $lateBasket->add($productId, 2);

        $this->placeOrder($this->createUser(), [[$productId, 1]]);
        $ordersBefore = \Bitrix\Sale\Internals\OrderTable::getCount();

        try {
            new CheckoutService()->place($late['id'], $this->checkoutData($late), $lateBasket);
            self::fail('Ожидалась ошибка остатка.');
        } catch (CheckoutException $e) {
            self::assertSame('Товара «Последний» осталось 1 шт. Уменьшите количество в корзине.', $e->getMessage());
        }
        self::assertSame($ordersBefore, \Bitrix\Sale\Internals\OrderTable::getCount(), 'Заказ не создан.');
        self::assertSame(['quantity' => 1, 'reserved' => 1], $this->stock($productId));
        self::assertSame(2, $lateBasket->count(), 'Корзина не тронута.');
    }

    public function testEmptyBasketAndGuest(): void
    {
        $user = $this->createUser();
        try {
            new CheckoutService()->place($user['id'], $this->checkoutData($user), $this->basketOf($user['id']));
            self::fail('Ожидалась ошибка пустой корзины.');
        } catch (CheckoutException $e) {
            self::assertSame('Корзина пуста.', $e->getMessage());
        }

        $this->expectException(CheckoutException::class);
        $this->expectExceptionMessage('Оформить заказ может только вошедший покупатель.');
        new CheckoutService()->place(0, $this->checkoutData($user), $this->basketOf($user['id']));
    }

    public function testCheckoutDataValidation(): void
    {
        $errors = \Dsefremov\ShopClassic\Service\CheckoutData::fromInput(['customer_name' => '', 'customer_email' => 'nope', 'customer_phone' => 'abc', 'address' => '']);
        self::assertIsArray($errors);
        self::assertSame(['customer_name', 'customer_email', 'customer_phone', 'address'], array_keys($errors));
    }
}
