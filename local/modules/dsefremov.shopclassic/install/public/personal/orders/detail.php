<?php

/**
 * Заказ покупателя: штатный sale.personal.order.detail (свой шаблон) + блок оплаты dsefremov:sale.order.payment.
 * Адрес /personal/orders/{ID}/ (urlrewrite.php).
 */

use Bitrix\Main\Loader;
use Dsefremov\ShopClassic\Security\Access;
use Dsefremov\ShopClassic\View\Url;

/** @var string $documentRoot */
$documentRoot = $_SERVER['DOCUMENT_ROOT'];
require $documentRoot.'/bitrix/header.php';
/** @var CMain $APPLICATION */
$APPLICATION->SetTitle('Заказ');
Loader::includeModule('dsefremov.shopclassic');
if (!Access::isAuthorized()) {
    LocalRedirect(Access::loginUrl(Url::current()));
}
$orderId = isset($_GET['ID']) && is_string($_GET['ID']) && ctype_digit($_GET['ID']) ? (int) $_GET['ID'] : 0;
$APPLICATION->IncludeComponent('bitrix:sale.personal.order.detail', '.default', [
    'ID' => $orderId,
    'PATH_TO_LIST' => '/personal/orders/',
    'PATH_TO_CANCEL' => '',
    'PATH_TO_PAYMENT' => '/personal/orders/'.$orderId.'/',
    'SET_TITLE' => 'N',
    'CACHE_TYPE' => 'N',
    'ALLOW_INNER' => 'N',
    'ONLY_INNER_FULL' => 'N',
    'HIDE_USER_INFO' => [],
    'PROP_1' => [],
    'ACTIVE_DATE_FORMAT' => 'd.m.Y H:i',
    'DISALLOW_CANCEL' => 'Y',
    'RESTRICT_CHANGE_PAYSYSTEM' => ['AC', 'PD', 'CM'],
    'REFRESH_PRICES' => 'N',
    'CUSTOM_SELECT_PROPS' => [],
    'AUTH_FORM_IN_TEMPLATE' => 'N',
], false, ['HIDE_ICONS' => 'Y']);
require $documentRoot.'/bitrix/footer.php';
