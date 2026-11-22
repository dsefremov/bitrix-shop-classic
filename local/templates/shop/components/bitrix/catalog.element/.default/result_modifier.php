<?php

/**
 * Товар -> CatalogItem + раздел + сколько штук уже в корзине покупателя (FUSER).
 *
 * @var array<string, mixed> $arResult
 */

use Bitrix\Sale\Fuser;
use Dsefremov\ShopClassic\Service\BasketService;
use Dsefremov\ShopClassic\View\CatalogItem;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$section = is_array($arResult['SECTION'] ?? null) ? $arResult['SECTION'] : [];
$arResult['SHOP_ITEM'] = CatalogItem::fromComponent(
    $arResult,
    is_string($section['~NAME'] ?? null) ? $section['~NAME'] : (is_string($section['NAME'] ?? null) ? $section['NAME'] : ''),
    is_string($section['SECTION_PAGE_URL'] ?? null) ? $section['SECTION_PAGE_URL'] : '',
);
$inCart = 0;
if (0 < (int) Fuser::getId(true)) {
    $basket = new BasketService();
    foreach ($basket->items($basket->basket()) as $basketItem) {
        if ((int) $basketItem->getProductId() === $arResult['SHOP_ITEM']->id) {
            $inCart += (int) $basketItem->getQuantity();
        }
    }
}
$arResult['SHOP_IN_CART'] = $inCart;
