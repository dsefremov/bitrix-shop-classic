<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Tests\Service;

use Bitrix\Sale\Fuser;
use Dsefremov\ShopClassic\Service\BasketService;

/**
 * Корзина покупателя (Sale\Basket по FUSER): количество ограничено остатком торгового каталога.
 */
final class BasketServiceTest extends ServiceTestCase
{
    public function testGuestBasketByFuser(): void
    {
        $productId = $this->createProduct('Чайник', 2490, 5);
        $fuserId = (int) Fuser::getIdByUserId(0);
        self::assertGreaterThan(0, $fuserId);
        $basket = new BasketService(fuserId: $fuserId);

        self::assertTrue($basket->add($productId, 2)->isSuccess());
        self::assertSame(2, $basket->count());
        $lines = $basket->lines();
        self::assertCount(1, $lines);
        self::assertSame(249000, $lines[0]['price']);
        self::assertSame(498000, BasketService::total($lines));
    }

    public function testAddIsLimitedByStock(): void
    {
        $productId = $this->createProduct('Гантели', 3990, 3);
        $basket = $this->basketOf($this->createUser()['id']);

        $result = $basket->add($productId, 5);
        self::assertTrue($result->isSuccess());
        self::assertTrue($result->getData()['limited'] ?? false);
        self::assertSame(3, $result->getData()['available'] ?? null);
        self::assertSame(3, $basket->count());

        $again = $basket->add($productId, 1);
        self::assertFalse($again->isSuccess());
        self::assertSame(['В корзине уже весь остаток: 3 шт.'], $again->getErrorMessages());
        self::assertSame(3, $basket->count());
    }

    public function testOutOfStockAndMissingProduct(): void
    {
        $basket = $this->basketOf($this->createUser()['id']);
        self::assertSame(['Товара нет в наличии.'], $basket->add($this->createProduct('Нет на складе', 100, 0), 1)->getErrorMessages());
        self::assertSame(['Товар не найден.'], $basket->add(999999, 1)->getErrorMessages());
        self::assertSame(0, $basket->count());
    }

    public function testSetQuantityChecksStockAndZeroRemoves(): void
    {
        $productId = $this->createProduct('Коврик', 1990, 4);
        $basket = $this->basketOf($this->createUser()['id']);
        $basket->add($productId, 1);
        $itemId = $basket->lines()[0]['id'];

        self::assertTrue($basket->setQuantity($itemId, 4)->isSuccess());
        self::assertSame(4, $basket->count());
        self::assertSame(['Товара «Коврик» в наличии только 4 шт.'], $basket->setQuantity($itemId, 5)->getErrorMessages());
        self::assertSame(4, $basket->count());

        self::assertTrue($basket->setQuantity($itemId, 0)->isSuccess());
        self::assertSame(0, $basket->count());
        self::assertSame('not_found', $basket->remove($itemId)->getErrorCollection()->current()?->getCode());
    }

    public function testBasketsOfDifferentBuyersAreSeparate(): void
    {
        $productId = $this->createProduct('Книга', 590, 10);
        $first = $this->basketOf($this->createUser()['id']);
        $second = $this->basketOf($this->createUser()['id']);
        $first->add($productId, 2);
        $second->add($productId, 1);

        self::assertSame(2, $first->count());
        self::assertSame(1, $second->count());
    }
}
