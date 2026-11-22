<?php

/**
 * Профиль: штатный main.profile со своим шаблоном.
 */

use Bitrix\Main\Loader;
use Dsefremov\ShopClassic\Security\Access;
use Dsefremov\ShopClassic\View\Url;

/** @var string $documentRoot */
$documentRoot = $_SERVER['DOCUMENT_ROOT'];
require $documentRoot.'/bitrix/header.php';
/** @var CMain $APPLICATION */
$APPLICATION->SetTitle('Профиль');
Loader::includeModule('dsefremov.shopclassic');
if (!Access::isAuthorized()) {
    LocalRedirect(Access::loginUrl(Url::current()));
}
$APPLICATION->IncludeComponent('bitrix:main.profile', '.default', [
    'SET_TITLE' => 'N',
    'CHECK_RIGHTS' => 'N',
    'SEND_INFO' => 'N',
    'USER_PROPERTY' => [],
    'AJAX_MODE' => 'N',
], false, ['HIDE_ICONS' => 'Y']);
require $documentRoot.'/bitrix/footer.php';
