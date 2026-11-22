<?php

/**
 * «Мои заказы»: свой список (поиск по номеру и товару, статус, даты, сортировка).
 */

/** @var string $documentRoot */
$documentRoot = $_SERVER['DOCUMENT_ROOT'];
require $documentRoot.'/bitrix/header.php';
/** @var CMain $APPLICATION */
$APPLICATION->SetTitle('Мои заказы');
$APPLICATION->IncludeComponent('dsefremov:sale.order.list', '.default', [], false, ['HIDE_ICONS' => 'Y']);
require $documentRoot.'/bitrix/footer.php';
