<?php

/**
 * Оформление заказа: свой компонент на Sale API (вход обязателен, одна транзакция, проверка остатков).
 */

/** @var string $documentRoot */
$documentRoot = $_SERVER['DOCUMENT_ROOT'];
require $documentRoot.'/bitrix/header.php';
/** @var CMain $APPLICATION */
$APPLICATION->SetTitle('Оформление заказа');
$APPLICATION->IncludeComponent('dsefremov:sale.checkout', '.default', [], false, ['HIDE_ICONS' => 'Y']);
require $documentRoot.'/bitrix/footer.php';
