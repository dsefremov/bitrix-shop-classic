<?php

/** @var string $documentRoot */
$documentRoot = $_SERVER['DOCUMENT_ROOT'];
require $documentRoot.'/bitrix/header.php';
/** @var CMain $APPLICATION */
$APPLICATION->SetTitle('Вход');
$APPLICATION->IncludeComponent('dsefremov:auth', '.default', ['MODE' => 'login'], false, ['HIDE_ICONS' => 'Y']);
require $documentRoot.'/bitrix/footer.php';
