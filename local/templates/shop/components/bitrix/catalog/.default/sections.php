<?php

/**
 * Корень каталога (/): все товары магазина.
 *
 * @var array<string, mixed> $arParams
 * @var array<string, mixed> $arResult
 * @var CMain $APPLICATION
 * @var CBitrixComponent $component
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$sectionCode = '';
include __DIR__.'/listing.php';
