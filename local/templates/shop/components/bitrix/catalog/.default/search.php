<?php

/**
 * Комплексный bitrix:catalog открывает страницу search, когда в корне есть GET q.
 * Поиск магазина — фильтр списка (название и описание), поэтому это тот же список всех товаров.
 *
 * @var array<string, mixed> $arParams
 * @var CMain $APPLICATION
 * @var CBitrixComponent $component
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$sectionCode = '';
include __DIR__.'/listing.php';
