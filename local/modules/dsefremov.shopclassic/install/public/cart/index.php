<?php

/**
 * Корзина: штатный sale.basket.basket (корзина FUSER — работает и у гостя) со своим шаблоном.
 */

/** @var string $documentRoot */
$documentRoot = $_SERVER['DOCUMENT_ROOT'];
require $documentRoot.'/bitrix/header.php';
/** @var CMain $APPLICATION */
$APPLICATION->SetTitle('Корзина');
$APPLICATION->IncludeComponent('bitrix:sale.basket.basket', '.default', [
    'PATH_TO_ORDER' => '/checkout/',
    'COLUMNS_LIST_EXT' => ['PREVIEW_PICTURE', 'DELETE', 'SUM'],
    'HIDE_COUPON' => 'Y',
    'PRICE_VAT_SHOW_VALUE' => 'N',
    'USE_PREPAYMENT' => 'N',
    'QUANTITY_FLOAT' => 'N',
    'CORRECT_RATIO' => 'Y',
    'AUTO_CALCULATION' => 'Y',
    'SET_TITLE' => 'N',
    'ACTION_VARIABLE' => 'basketAction',
    'DEFERRED_REFRESH' => 'N',
    'USE_GIFTS' => 'N',
    'USE_ENHANCED_ECOMMERCE' => 'N',
    'TEMPLATE_THEME' => '',
    // Строки корзины в $arResult['GRID']['ROWS'] (шаблон рисует их сам, без JS-шаблонов ядра).
    'COMPATIBLE_MODE' => 'Y',
], false, ['HIDE_ICONS' => 'Y']);
require $documentRoot.'/bitrix/footer.php';
