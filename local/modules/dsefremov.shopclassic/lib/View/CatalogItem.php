<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\View;

/**
 * Товар для шаблонов catalog.section / catalog.element: поля элемента инфоблока, базовая цена
 * (ITEM_PRICES) и остаток торгового каталога (PRODUCT.QUANTITY).
 */
final readonly class CatalogItem
{
    public function __construct(
        public int $id,
        public string $name,
        public string $url,
        public ?int $pictureId,
        public int $price,
        public int $stock,
        public string $sectionName,
        public string $sectionUrl,
        public string $description,
    ) {
    }

    /**
     * @param array<array-key, mixed> $item элемент из $arResult компонента
     */
    public static function fromComponent(array $item, string $sectionName = '', string $sectionUrl = ''): self
    {
        $picture = $item['PREVIEW_PICTURE'] ?? $item['DETAIL_PICTURE'] ?? null;
        $pictureId = \is_array($picture) && is_numeric($picture['ID'] ?? null) ? (int) $picture['ID'] : null;
        $prices = \is_array($item['ITEM_PRICES'] ?? null) ? $item['ITEM_PRICES'] : [];
        $selected = is_numeric($item['ITEM_PRICE_SELECTED'] ?? null) ? (int) $item['ITEM_PRICE_SELECTED'] : 0;
        $price = \is_array($prices[$selected] ?? null) && is_numeric($prices[$selected]['PRICE'] ?? null) ? (float) $prices[$selected]['PRICE'] : 0.0;
        $product = \is_array($item['PRODUCT'] ?? null) ? $item['PRODUCT'] : [];
        $stock = is_numeric($product['QUANTITY'] ?? null) ? (int) $product['QUANTITY'] : 0;
        // Ключи с ~ — исходные значения (без htmlspecialchars из GetNext): экранирует шаблон.
        $raw = static fn (string $key): string => \is_string($item['~'.$key] ?? null) ? $item['~'.$key] : (\is_string($item[$key] ?? null) ? $item[$key] : '');
        $text = '' !== $raw('DETAIL_TEXT') ? $raw('DETAIL_TEXT') : $raw('PREVIEW_TEXT');

        return new self(
            is_numeric($item['ID'] ?? null) ? (int) $item['ID'] : 0,
            $raw('NAME'),
            $raw('DETAIL_PAGE_URL'),
            $pictureId,
            (int) round($price * 100),
            max(0, $stock),
            $sectionName,
            $sectionUrl,
            $text,
        );
    }
}
