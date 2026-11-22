<?php

/**
 * Заказ из sale.personal.order.detail -> поля для шаблона; состояние (статус, отмена, «Оплата отклонена»)
 * — через Sale\Order (OrderService).
 *
 * @var array<string, mixed> $arResult
 */

use Dsefremov\ShopClassic\Service\OrderService;
use Dsefremov\ShopClassic\Service\OrderView;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$orderId = is_numeric($arResult['ID'] ?? null) ? (int) $arResult['ID'] : 0;
$orders = new OrderService();
$order = $orderId > 0 ? $orders->load($orderId) : null;
$arResult['SHOP_VIEW'] = null === $order ? null : new OrderView(
    $orderId,
    (string) $order->getField('STATUS_ID'),
    $order->isCanceled(),
    $order->isPaid(),
    $orders->isDeclined($order),
);
$props = [];
foreach (is_array($arResult['ORDER_PROPS'] ?? null) ? $arResult['ORDER_PROPS'] : [] as $prop) {
    if (is_array($prop) && is_string($prop['CODE'] ?? null)) {
        $props[$prop['CODE']] = is_scalar($prop['VALUE'] ?? null) ? htmlspecialchars_decode((string) $prop['VALUE']) : '';
    }
}
$arResult['SHOP_PROPS'] = $props;
$items = [];
foreach (is_array($arResult['BASKET'] ?? null) ? $arResult['BASKET'] : [] as $row) {
    if (!is_array($row)) {
        continue;
    }
    $price = (int) round((float) ($row['PRICE'] ?? 0) * 100);
    $quantity = (int) round((float) ($row['QUANTITY'] ?? 0));
    $items[] = [
        'name' => htmlspecialchars_decode((string) ($row['NAME'] ?? '')),
        'url' => (string) ($row['DETAIL_PAGE_URL'] ?? ''),
        'price' => $price,
        'quantity' => $quantity,
        'subtotal' => $price * $quantity,
    ];
}
$arResult['SHOP_ITEMS'] = $items;
$arResult['SHOP_TOTAL'] = (int) round((float) ($arResult['PRICE'] ?? 0) * 100);
$arResult['SHOP_DELIVERY'] = is_array($arResult['DELIVERY'] ?? null) ? htmlspecialchars_decode((string) ($arResult['DELIVERY']['NAME'] ?? '')) : '';
$arResult['SHOP_COMMENT'] = htmlspecialchars_decode((string) ($arResult['USER_DESCRIPTION'] ?? ''));
