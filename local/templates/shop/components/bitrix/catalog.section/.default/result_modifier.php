<?php

/**
 * Товары -> CatalogItem; имена и адреса разделов для подписи в карточке.
 *
 * @var array<string, mixed> $arResult
 */

use Dsefremov\ShopClassic\View\CatalogItem;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$items = is_array($arResult['ITEMS'] ?? null) ? $arResult['ITEMS'] : [];
$sectionIds = [];
foreach ($items as $item) {
    if (is_array($item) && is_numeric($item['IBLOCK_SECTION_ID'] ?? null)) {
        $sectionIds[(int) $item['IBLOCK_SECTION_ID']] = true;
    }
}
$sections = [];
if ([] !== $sectionIds) {
    $rows = CIBlockSection::GetList([], ['ID' => array_keys($sectionIds)], false, ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'SECTION_PAGE_URL']);
    while (is_array($row = $rows->GetNext())) {
        $sections[(int) $row['ID']] = ['name' => (string) $row['~NAME'], 'url' => (string) $row['SECTION_PAGE_URL']];
    }
}
$arResult['SHOP_ITEMS'] = [];
foreach ($items as $item) {
    if (!is_array($item)) {
        continue;
    }
    $section = $sections[is_numeric($item['IBLOCK_SECTION_ID'] ?? null) ? (int) $item['IBLOCK_SECTION_ID'] : 0] ?? ['name' => '', 'url' => ''];
    $arResult['SHOP_ITEMS'][] = CatalogItem::fromComponent($item, $section['name'], $section['url']);
}
$nav = $arResult['NAV_RESULT'] ?? null;
$arResult['SHOP_TOTAL'] = $nav instanceof CDBResult ? (int) $nav->NavRecordCount : count($arResult['SHOP_ITEMS']);
