<?php

/** @var string $documentRoot */
$documentRoot = $_SERVER['DOCUMENT_ROOT'];
require $documentRoot.'/bitrix/header.php';
/** @var CMain $APPLICATION */
$APPLICATION->SetTitle('Регистрация');
$APPLICATION->IncludeComponent('dsefremov:auth', '.default', ['MODE' => 'register'], false, ['HIDE_ICONS' => 'Y']);
require $documentRoot.'/bitrix/footer.php';
