<?php

/**
 * Строки корзины (GRID.ROWS в COMPATIBLE_MODE) -> простой список для шаблона; суммы в копейках.
 *
 * @var array<string, mixed> $arResult
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$lines = [];
$total = 0;
$rows = is_array($arResult['GRID']['ROWS'] ?? null) ? $arResult['GRID']['ROWS'] : [];
foreach ($rows as $row) {
    if (!is_array($row) || 'Y' !== ($row['CAN_BUY'] ?? 'N') || 'Y' === ($row['DELAY'] ?? 'N')) {
        continue;
    }
    $price = (int) round((float) ($row['~PRICE'] ?? 0) * 100);
    $quantity = (int) round((float) ($row['~QUANTITY'] ?? 0));
    $lines[] = [
        'id' => (int) $row['ID'],
        'productId' => (int) $row['PRODUCT_ID'],
        'name' => (string) ($row['~NAME'] ?? ''),
        'url' => (string) ($row['~DETAIL_PAGE_URL'] ?? ''),
        'pictureId' => is_numeric($row['PREVIEW_PICTURE'] ?? null) ? (int) $row['PREVIEW_PICTURE'] : (is_numeric($row['DETAIL_PICTURE'] ?? null) ? (int) $row['DETAIL_PICTURE'] : null),
        'price' => $price,
        'quantity' => $quantity,
        'stock' => is_numeric($row['AVAILABLE_QUANTITY'] ?? null) ? (int) $row['AVAILABLE_QUANTITY'] : 0,
        'subtotal' => $price * $quantity,
    ];
    $total += $price * $quantity;
}
$arResult['SHOP_LINES'] = $lines;
$arResult['SHOP_TOTAL'] = $total;
