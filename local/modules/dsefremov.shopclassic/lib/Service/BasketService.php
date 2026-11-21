<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Service;

use Bitrix\Catalog\Product\Basket as CatalogBasket;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Result;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Fuser;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Entity\Row;

/**
 * Корзина — штатная Sale\Basket покупателя (FUSER): у гостя FUSER по cookie, при входе ядро
 * переносит корзину гостя пользователю. Товары добавляются через Catalog\Product\Basket
 * (провайдер каталога: цена базового типа, проверка доступности), количество ограничено
 * доступным остатком (QUANTITY торгового каталога).
 */
final class BasketService
{
    public function __construct(
        private readonly StockService $stock = new StockService(),
        private ?int $fuserId = null,
    ) {
    }

    public function fuserId(): int
    {
        return $this->fuserId ??= (int) Fuser::getId();
    }

    public function basket(): Basket
    {
        $basket = Basket::loadItemsForFUser($this->fuserId(), Config::SITE_ID);

        return $basket;
    }

    public function add(int $productId, int $quantity): Result
    {
        $result = new Result();
        if ($productId <= 0 || !$this->isProduct($productId)) {
            return $result->addError(new \Bitrix\Main\Error('Товар не найден.', 'not_found'));
        }
        if ($quantity < 1) {
            return $result->addError(new \Bitrix\Main\Error('Количество должно быть не меньше 1.'));
        }
        $basket = $this->basket();
        $inBasket = 0;
        foreach ($this->items($basket) as $item) {
            if ((int) $item->getProductId() === $productId) {
                $inBasket += (int) $item->getQuantity();
            }
        }
        $available = $this->stock->available($productId);
        if ($available < 1) {
            return $result->addError(new \Bitrix\Main\Error('Товара нет в наличии.'));
        }
        if ($inBasket + $quantity > $available) {
            $quantity = $available - $inBasket;
            if ($quantity < 1) {
                return $result->addError(new \Bitrix\Main\Error(\sprintf('В корзине уже весь остаток: %d шт.', $available)));
            }
            $result->setData(['limited' => true, 'available' => $available]);
        }
        $added = CatalogBasket::addProductToBasket(
            $basket,
            ['PRODUCT_ID' => $productId, 'QUANTITY' => $quantity],
            ['SITE_ID' => Config::SITE_ID],
        );
        if (!$added->isSuccess()) {
            return $result->addErrors($added->getErrors());
        }
        $saved = $basket->save();
        if (!$saved->isSuccess()) {
            return $result->addErrors($saved->getErrors());
        }

        return $result;
    }

    /**
     * Новое количество строки корзины; 0 — удалить строку. Больше остатка — ошибка.
     */
    public function setQuantity(int $basketItemId, int $quantity): Result
    {
        $result = new Result();
        $basket = $this->basket();
        $item = $basket->getItemById($basketItemId);
        if (!$item instanceof BasketItem) {
            return $result->addError(new \Bitrix\Main\Error('Товара нет в корзине.', 'not_found'));
        }
        if ($quantity <= 0) {
            $deleted = $item->delete();
            if (!$deleted->isSuccess()) {
                return $result->addErrors($deleted->getErrors());
            }
        } else {
            $available = $this->stock->available((int) $item->getProductId());
            if ($quantity > $available) {
                return $result->addError(new \Bitrix\Main\Error(\sprintf('Товара «%s» в наличии только %d шт.', (string) $item->getField('NAME'), $available)));
            }
            $set = $item->setField('QUANTITY', $quantity);
            if (!$set->isSuccess()) {
                return $result->addErrors($set->getErrors());
            }
        }
        $saved = $basket->save();
        if (!$saved->isSuccess()) {
            return $result->addErrors($saved->getErrors());
        }

        return $result;
    }

    public function remove(int $basketItemId): Result
    {
        return $this->setQuantity($basketItemId, 0);
    }

    /**
     * Сколько штук в корзине (для значка в шапке). Гостю без корзины FUSER не создаётся.
     */
    public function count(): int
    {
        if (null === $this->fuserId && 0 >= (int) Fuser::getId(true)) {
            return 0;
        }
        $count = 0;
        foreach ($this->items($this->basket()) as $item) {
            $count += (int) $item->getQuantity();
        }

        return $count;
    }

    /**
     * @return list<BasketItem>
     */
    public function items(Basket $basket): array
    {
        $items = [];
        foreach ($basket->getBasketItems() as $item) {
            if ($item instanceof BasketItem && $item->canBuy() && !$item->isDelay()) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Строки корзины для шаблонов: цена и сумма — в копейках (цены sale — рубли с дробной частью).
     *
     * @return list<array{id: int, productId: int, name: string, code: string, pictureId: ?int, price: int, quantity: int, subtotal: int, stock: int}>
     */
    public function lines(): array
    {
        $items = $this->items($this->basket());
        $productIds = array_map(static fn (BasketItem $item): int => (int) $item->getProductId(), $items);
        $stock = $this->stock->get($productIds);
        $elements = [];
        if ([] !== $productIds) {
            $rows = ElementTable::getList(['select' => ['ID', 'CODE', 'PREVIEW_PICTURE'], 'filter' => ['@ID' => $productIds]]);
            while ($row = $rows->fetch()) {
                $elements[Row::int($row, 'ID')] = $row;
            }
        }
        $lines = [];
        foreach ($items as $item) {
            $productId = (int) $item->getProductId();
            $element = $elements[$productId] ?? [];
            $price = (int) round((float) $item->getPrice() * 100);
            $quantity = (int) $item->getQuantity();
            $lines[] = [
                'id' => (int) $item->getId(),
                'productId' => $productId,
                'name' => (string) $item->getField('NAME'),
                'code' => Row::string($element, 'CODE'),
                'pictureId' => Row::nullableInt($element, 'PREVIEW_PICTURE'),
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $price * $quantity,
                'stock' => $stock[$productId]['quantity'] ?? 0,
            ];
        }

        return $lines;
    }

    /**
     * @param list<array{subtotal: int}> $lines
     */
    public static function total(array $lines): int
    {
        return array_sum(array_column($lines, 'subtotal'));
    }

    private function isProduct(int $productId): bool
    {
        $row = ElementTable::getList([
            'select' => ['ID'],
            'filter' => ['=ID' => $productId, '=IBLOCK_ID' => Config::iblockId(), '=ACTIVE' => 'Y'],
        ])->fetch();

        return \is_array($row) && Row::int($row, 'ID') === $productId;
    }
}
