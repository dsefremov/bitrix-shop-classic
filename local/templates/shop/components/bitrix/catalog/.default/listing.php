<?php

/**
 * Общая разметка списка товаров для sections.php и section.php: разделы (catalog.section.list),
 * умный фильтр по цене (catalog.smart.filter) и сам список (catalog.section, 12 на страницу).
 * Поиск по названию и описанию дополняет фильтр arrFilter после умного фильтра.
 *
 * @var array<string, mixed> $arParams
 * @var string $sectionCode
 * @var CMain $APPLICATION
 * @var CBitrixComponent $component
 */

use Dsefremov\ShopClassic\Service\CatalogQuery;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$catalogQuery = CatalogQuery::fromQuery($_GET);
$filterName = (string) $arParams['FILTER_NAME'];
$common = [
    'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'],
    'IBLOCK_ID' => $arParams['IBLOCK_ID'],
    'CACHE_TYPE' => $arParams['CACHE_TYPE'],
    'CACHE_TIME' => $arParams['CACHE_TIME'],
    'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
];
?>
<div class="grid gap-6 lg:grid-cols-[13rem_1fr]">
    <aside class="min-w-0 space-y-4">
        <?php
        $APPLICATION->IncludeComponent('bitrix:catalog.section.list', '.default', $common + [
            'SECTION_ID' => 0,
            'SECTION_CODE' => '',
            'TOP_DEPTH' => 1,
            'COUNT_ELEMENTS' => 'Y',
            'COUNT_ELEMENTS_FILTER' => 'CNT_ACTIVE',
            'HIDE_SECTIONS_WITH_ZERO_COUNT_ELEMENTS' => 'N',
            'SECTION_URL' => $arParams['SEF_FOLDER'].$arParams['SEF_URL_TEMPLATES']['section'],
            'ADD_SECTIONS_CHAIN' => 'N',
            'SECTION_FIELDS' => [],
            'SECTION_USER_FIELDS' => [],
            'CURRENT_SECTION_CODE' => $sectionCode,
        ], $component, ['HIDE_ICONS' => 'Y']);
        ?>
        <div class="min-h-[11rem]">
            <?php
            $APPLICATION->IncludeComponent('bitrix:catalog.smart.filter', '.default', $common + [
                'SECTION_ID' => 0,
                'SECTION_CODE' => $sectionCode,
                'FILTER_NAME' => $filterName,
                'PRICE_CODE' => $arParams['FILTER_PRICE_CODE'],
                'HIDE_NOT_AVAILABLE' => $arParams['HIDE_NOT_AVAILABLE'],
                'SAVE_IN_SESSION' => 'N',
                'SEF_MODE' => 'N',
                'XML_EXPORT' => 'N',
                'INSTANT_RELOAD' => 'N',
                'CONVERT_CURRENCY' => 'N',
                'DISPLAY_ELEMENT_COUNT' => 'N',
                'PAGER_PARAMS_NAME' => 'arrPager',
                'AVAILABLE' => $catalogQuery->available,
            ], $component, ['HIDE_ICONS' => 'Y']);
            ?>
        </div>
    </aside>

    <section class="min-w-0">
        <?php
        if (!isset($GLOBALS[$filterName]) || !is_array($GLOBALS[$filterName])) {
            $GLOBALS[$filterName] = [];
        }
        $search = $catalogQuery->searchFilter();
        if (null !== $search) {
            $GLOBALS[$filterName][] = $search;
        }
        $APPLICATION->IncludeComponent('bitrix:catalog.section', '.default', $common + [
            'SECTION_ID' => 0,
            'SECTION_CODE' => $sectionCode,
            'SHOW_ALL_WO_SECTION' => $arParams['SHOW_ALL_WO_SECTION'],
            'INCLUDE_SUBSECTIONS' => $arParams['INCLUDE_SUBSECTIONS'],
            'FILTER_NAME' => $filterName,
            'CACHE_FILTER' => $arParams['CACHE_FILTER'],
            'ELEMENT_SORT_FIELD' => $arParams['ELEMENT_SORT_FIELD'],
            'ELEMENT_SORT_ORDER' => $arParams['ELEMENT_SORT_ORDER'],
            'ELEMENT_SORT_FIELD2' => $arParams['ELEMENT_SORT_FIELD2'],
            'ELEMENT_SORT_ORDER2' => $arParams['ELEMENT_SORT_ORDER2'],
            'PAGE_ELEMENT_COUNT' => $arParams['PAGE_ELEMENT_COUNT'],
            'LINE_ELEMENT_COUNT' => $arParams['LINE_ELEMENT_COUNT'],
            'PRICE_CODE' => $arParams['PRICE_CODE'],
            'USE_PRICE_COUNT' => $arParams['USE_PRICE_COUNT'],
            'SHOW_PRICE_COUNT' => $arParams['SHOW_PRICE_COUNT'],
            'PRICE_VAT_INCLUDE' => $arParams['PRICE_VAT_INCLUDE'],
            'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
            'HIDE_NOT_AVAILABLE' => $arParams['HIDE_NOT_AVAILABLE'],
            'HIDE_NOT_AVAILABLE_OFFERS' => $arParams['HIDE_NOT_AVAILABLE_OFFERS'],
            'BASKET_URL' => $arParams['BASKET_URL'],
            'ACTION_VARIABLE' => $arParams['ACTION_VARIABLE'],
            'PRODUCT_ID_VARIABLE' => $arParams['PRODUCT_ID_VARIABLE'],
            'SECTION_ID_VARIABLE' => $arParams['SECTION_ID_VARIABLE'],
            'USE_PRODUCT_QUANTITY' => $arParams['USE_PRODUCT_QUANTITY'],
            'PRODUCT_QUANTITY_VARIABLE' => $arParams['PRODUCT_QUANTITY_VARIABLE'],
            'ADD_PROPERTIES_TO_BASKET' => 'N',
            'PROPERTY_CODE' => [],
            'SECTION_URL' => $arParams['SEF_FOLDER'].$arParams['SEF_URL_TEMPLATES']['section'],
            'DETAIL_URL' => $arParams['SEF_FOLDER'].$arParams['SEF_URL_TEMPLATES']['element'],
            'SET_TITLE' => $arParams['SET_TITLE'],
            'SET_BROWSER_TITLE' => 'N',
            'SET_META_KEYWORDS' => 'N',
            'SET_META_DESCRIPTION' => 'N',
            'SET_LAST_MODIFIED' => 'N',
            'ADD_SECTIONS_CHAIN' => 'N',
            'SET_STATUS_404' => $arParams['SET_STATUS_404'],
            'SHOW_404' => $arParams['SHOW_404'],
            'PAGER_TEMPLATE' => $arParams['PAGER_TEMPLATE'],
            'DISPLAY_TOP_PAGER' => $arParams['DISPLAY_TOP_PAGER'],
            'DISPLAY_BOTTOM_PAGER' => $arParams['DISPLAY_BOTTOM_PAGER'],
            'PAGER_SHOW_ALWAYS' => $arParams['PAGER_SHOW_ALWAYS'],
            'PAGER_SHOW_ALL' => $arParams['PAGER_SHOW_ALL'],
            'PAGER_TITLE' => $arParams['PAGER_TITLE'],
            'COMPATIBLE_MODE' => $arParams['COMPATIBLE_MODE'],
            'USE_ENHANCED_ECOMMERCE' => 'N',
            'COMPARE_PATH' => '',
            'USE_COMPARE_LIST' => 'N',
        ], $component, ['HIDE_ICONS' => 'Y']);
        ?>
    </section>
</div>
