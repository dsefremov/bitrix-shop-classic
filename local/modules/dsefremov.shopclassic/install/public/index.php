<?php

/**
 * Каталог: штатный комплексный bitrix:catalog (инфоблок «Товары», торговый каталог, умный фильтр).
 * ЧПУ: / — все товары, /catalog/{код раздела}/ — раздел, /products/{код товара}/ — карточка.
 * Поиск, сортировка и «только в наличии» — GET-параметры (Dsefremov\ShopClassic\Service\CatalogQuery).
 */

use Bitrix\Main\Loader;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Service\CatalogQuery;

/** @var string $documentRoot */
$documentRoot = $_SERVER['DOCUMENT_ROOT'];
require $documentRoot.'/bitrix/header.php';
/** @var CMain $APPLICATION */
$APPLICATION->SetTitle('Каталог');
Loader::includeModule('dsefremov.shopclassic');
$catalogQuery = CatalogQuery::fromQuery($_GET);

$APPLICATION->IncludeComponent('bitrix:catalog', '.default', [
    'IBLOCK_TYPE' => Config::IBLOCK_TYPE,
    'IBLOCK_ID' => Config::iblockId(),
    'SEF_MODE' => 'Y',
    'SEF_FOLDER' => '/',
    'SEF_URL_TEMPLATES' => [
        'sections' => '',
        'section' => 'catalog/#SECTION_CODE#/',
        'element' => 'products/#ELEMENT_CODE#/',
        'compare' => 'compare/',
        'smart_filter' => 'catalog/#SECTION_CODE#/filter/#SMART_FILTER_PATH#/apply/',
    ],
    'DETAIL_STRICT_SECTION_CHECK' => 'N',
    // Остаток и цена всегда актуальны: резерв меняет QUANTITY при каждом заказе.
    'CACHE_TYPE' => 'N',
    'CACHE_TIME' => 0,
    'CACHE_FILTER' => 'N',
    'CACHE_GROUPS' => 'Y',
    'SET_TITLE' => 'Y',
    'ADD_SECTIONS_CHAIN' => 'N',
    'ADD_ELEMENT_CHAIN' => 'N',
    'SET_STATUS_404' => 'Y',
    'SHOW_404' => 'N',
    'USE_FILTER' => 'Y',
    'FILTER_NAME' => 'arrFilter',
    'FILTER_PRICE_CODE' => [Config::PRICE_TYPE],
    'FILTER_FIELD_CODE' => [],
    'FILTER_PROPERTY_CODE' => [],
    'FILTER_VIEW_MODE' => 'VERTICAL',
    'USE_COMPARE' => 'N',
    'USE_REVIEW' => 'N',
    'USE_STORE' => 'N',
    'USE_ELEMENT_COUNTER' => 'N',
    'USE_MAIN_ELEMENT_SECTION' => 'Y',
    'PRICE_CODE' => [Config::PRICE_TYPE],
    'USE_PRICE_COUNT' => 'N',
    'SHOW_PRICE_COUNT' => 1,
    'PRICE_VAT_INCLUDE' => 'Y',
    'CONVERT_CURRENCY' => 'N',
    'HIDE_NOT_AVAILABLE' => $catalogQuery->available ? 'Y' : 'N',
    'HIDE_NOT_AVAILABLE_OFFERS' => 'N',
    'BASKET_URL' => '/cart/',
    'ACTION_VARIABLE' => 'action',
    'PRODUCT_ID_VARIABLE' => 'id',
    'SECTION_ID_VARIABLE' => 'SECTION_ID',
    'USE_PRODUCT_QUANTITY' => 'Y',
    'PRODUCT_QUANTITY_VARIABLE' => 'quantity',
    'ADD_PROPERTIES_TO_BASKET' => 'N',
    'PRODUCT_PROPERTIES' => [],
    'SHOW_TOP_ELEMENTS' => 'N',
    'SECTION_COUNT_ELEMENTS' => 'Y',
    'SECTION_TOP_DEPTH' => 1,
    'SECTIONS_VIEW_MODE' => 'LIST',
    'SHOW_ALL_WO_SECTION' => 'Y',
    'INCLUDE_SUBSECTIONS' => 'Y',
    'PAGE_ELEMENT_COUNT' => 12,
    'LINE_ELEMENT_COUNT' => 4,
    'ELEMENT_SORT_FIELD' => $catalogQuery->sortField(),
    'ELEMENT_SORT_ORDER' => $catalogQuery->sortOrder(),
    'ELEMENT_SORT_FIELD2' => 'id',
    'ELEMENT_SORT_ORDER2' => $catalogQuery->sortOrder(),
    'LIST_PROPERTY_CODE' => [],
    'DETAIL_PROPERTY_CODE' => [],
    'LIST_META_KEYWORDS' => '-',
    'LIST_META_DESCRIPTION' => '-',
    'LIST_BROWSER_TITLE' => '-',
    'DETAIL_META_KEYWORDS' => '-',
    'DETAIL_META_DESCRIPTION' => '-',
    'DETAIL_BROWSER_TITLE' => '-',
    'DETAIL_SET_CANONICAL_URL' => 'N',
    'SECTION_BACKGROUND_IMAGE' => '-',
    'DETAIL_BACKGROUND_IMAGE' => '-',
    'PAGER_TEMPLATE' => 'shop',
    'DISPLAY_TOP_PAGER' => 'N',
    'DISPLAY_BOTTOM_PAGER' => 'Y',
    'PAGER_SHOW_ALWAYS' => 'N',
    'PAGER_SHOW_ALL' => 'N',
    'PAGER_TITLE' => 'Товары',
    'COMPATIBLE_MODE' => 'N',
    'USE_ENHANCED_ECOMMERCE' => 'N',
], false, ['HIDE_ICONS' => 'Y']);

require $documentRoot.'/bitrix/footer.php';
