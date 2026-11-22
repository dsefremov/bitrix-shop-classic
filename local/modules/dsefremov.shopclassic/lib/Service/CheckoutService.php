<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Service;

use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Delivery\Services\Manager as DeliveryManager;
use Bitrix\Sale\Order;
use Bitrix\Sale\PaySystem\Manager as PaySystemManager;
use Dsefremov\ShopClassic\Config;

/**
 * Оформление заказа на Sale API. Одна транзакция БД: строки остатков товаров корзины блокируются
 * (SELECT ... FOR UPDATE), остаток проверяется, затем создаётся Sale\Order: тип плательщика
 * «Физ. лицо», позиции из корзины покупателя, свойства ФИО/почта/телефон/адрес, отгрузка
 * «Самовывоз», оплата «Тестовая оплата», статус «Принят». При сохранении заказа ядро резервирует
 * товар (условие резервирования — создание заказа), корзина становится позициями заказа.
 */
final class CheckoutService
{
    public function __construct(
        private readonly StockService $stock = new StockService(),
    ) {
    }

    /**
     * @return int ID созданного заказа
     */
    public function place(int $userId, CheckoutData $data, BasketService $basketService): int
    {
        if ($userId <= 0) {
            throw new CheckoutException('Оформить заказ может только вошедший покупатель.');
        }

        return Transaction::run(function () use ($userId, $data, $basketService): int {
            $basket = $basketService->basket();
            $items = $basketService->items($basket);
            if ([] === $items) {
                throw new CheckoutException('Корзина пуста.');
            }
            $locked = $this->stock->lock(array_map(static fn (BasketItem $item): int => (int) $item->getProductId(), $items));
            $wanted = [];
            foreach ($items as $item) {
                $productId = (int) $item->getProductId();
                $wanted[$productId] = ($wanted[$productId] ?? 0) + (int) $item->getQuantity();
            }
            foreach ($items as $item) {
                $productId = (int) $item->getProductId();
                if (!isset($locked[$productId])) {
                    throw new CheckoutException('Один из товаров больше не продаётся. Обновите корзину.');
                }
                if ($wanted[$productId] > $locked[$productId]) {
                    throw new CheckoutException(\sprintf('Товара «%s» осталось %d шт. Уменьшите количество в корзине.', (string) $item->getField('NAME'), $locked[$productId]));
                }
            }

            $order = $this->build($userId, $data, $basket);
            $saved = $order->save();
            if (!$saved->isSuccess()) {
                throw new CheckoutException('Не удалось оформить заказ: '.implode('; ', $saved->getErrorMessages()));
            }

            return (int) $order->getId();
        });
    }

    private function build(int $userId, CheckoutData $data, Basket $basket): Order
    {
        $order = Order::create(Config::SITE_ID, $userId, Config::CURRENCY);
        $order->setPersonTypeId(Config::personTypeId());
        self::check($order->setBasket($basket->getOrderableItems()));
        self::check($order->setField('STATUS_ID', Config::STATUS_ACCEPTED));
        if (null !== $data->comment) {
            self::check($order->setField('USER_DESCRIPTION', $data->comment));
        }

        $values = [
            Config::PROP_FIO => $data->customerName,
            Config::PROP_EMAIL => $data->customerEmail,
            Config::PROP_PHONE => $data->customerPhone,
            Config::PROP_ADDRESS => $data->address,
        ];
        foreach ($order->getPropertyCollection() as $property) {
            $code = (string) $property->getField('CODE');
            if (isset($values[$code])) {
                self::check($property->setValue($values[$code]));
            }
        }

        $delivery = DeliveryManager::getObjectById(Config::deliveryId());
        if (null === $delivery) {
            throw new CheckoutException('Служба доставки «Самовывоз» не настроена.');
        }
        $shipment = $order->getShipmentCollection()->createItem($delivery);
        $shipmentItems = $shipment->getShipmentItemCollection();
        foreach ($order->getBasket() as $basketItem) {
            $shipmentItem = $shipmentItems->createItem($basketItem);
            if (null === $shipmentItem) {
                throw new CheckoutException('Не удалось добавить товар в отгрузку.');
            }
            self::check($shipmentItem->setQuantity($basketItem->getQuantity()));
        }

        $paySystem = PaySystemManager::getObjectById(Config::paySystemId());
        if (null === $paySystem) {
            throw new CheckoutException('Платёжная система «Тестовая оплата» не настроена.');
        }
        $payment = $order->getPaymentCollection()->createItem($paySystem);
        self::check($payment->setField('SUM', $order->getPrice()));
        self::check($payment->setField('CURRENCY', $order->getCurrency()));

        return $order;
    }

    public static function check(\Bitrix\Main\Result $result): void
    {
        if (!$result->isSuccess()) {
            throw new CheckoutException(implode('; ', $result->getErrorMessages()));
        }
    }
}
