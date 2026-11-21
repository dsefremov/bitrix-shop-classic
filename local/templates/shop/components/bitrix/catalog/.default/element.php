<?php

/**
 * Карточка товара (/products/{код}/): штатный catalog.element со своим шаблоном.
 *
 * @var array<string, mixed> $arParams
 * @var array{VARIABLES: array<string, string>} $arResult
 * @var CMain $APPLICATION
 * @var CBitrixComponent $component
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$APPLICATION->IncludeComponent('bitrix:catalog.element', '.default', [
    'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'],
    'IBLOCK_ID' => $arParams['IBLOCK_ID'],
    'ELEMENT_ID' => 0,
    'ELEMENT_CODE' => (string) ($arResult['VARIABLES']['ELEMENT_CODE'] ?? ''),
    'SECTION_ID' => 0,
    'SECTION_CODE' => '',
    'STRICT_SECTION_CHECK' => 'N',
    'CACHE_TYPE' => $arParams['CACHE_TYPE'],
    'CACHE_TIME' => $arParams['CACHE_TIME'],
    'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
    'PRICE_CODE' => $arParams['PRICE_CODE'],
    'USE_PRICE_COUNT' => $arParams['USE_PRICE_COUNT'],
    'SHOW_PRICE_COUNT' => $arParams['SHOW_PRICE_COUNT'],
    'PRICE_VAT_INCLUDE' => $arParams['PRICE_VAT_INCLUDE'],
    'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
    'HIDE_NOT_AVAILABLE' => 'N',
    'HIDE_NOT_AVAILABLE_OFFERS' => 'N',
    'BASKET_URL' => $arParams['BASKET_URL'],
    'ACTION_VARIABLE' => $arParams['ACTION_VARIABLE'],
    'PRODUCT_ID_VARIABLE' => $arParams['PRODUCT_ID_VARIABLE'],
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
    'SET_CANONICAL_URL' => 'N',
    'SET_LAST_MODIFIED' => 'N',
    'ADD_SECTIONS_CHAIN' => 'N',
    'ADD_ELEMENT_CHAIN' => 'N',
    'SET_STATUS_404' => $arParams['SET_STATUS_404'],
    'SHOW_404' => $arParams['SHOW_404'],
    'USE_ELEMENT_COUNTER' => 'N',
    'USE_MAIN_ELEMENT_SECTION' => 'Y',
    'COMPATIBLE_MODE' => $arParams['COMPATIBLE_MODE'],
    'USE_ENHANCED_ECOMMERCE' => 'N',
    'USE_COMMENTS' => 'N',
    'USE_VOTE_RATING' => 'N',
    'USE_GIFTS_DETAIL' => 'N',
    'USE_GIFTS_MAIN_PR_SECTION_LIST' => 'N',
    'DISPLAY_COMPARE' => 'N',
], $component, ['HIDE_ICONS' => 'Y']);
if (defined('ERROR_404')) {
    $APPLICATION->SetTitle('Товар не найден');
    echo Dsefremov\ShopClassic\View\Html::notFound('Товар не найден', 'Такого товара нет или он снят с продажи.');
}
