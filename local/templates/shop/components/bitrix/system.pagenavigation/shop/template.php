<?php

/**
 * Постраничная навигация каталога (PAGEN_N штатной навигации CDBResult).
 *
 * @var array<string, mixed> $arResult
 */

use Dsefremov\ShopClassic\Entity\Row;
use Dsefremov\ShopClassic\View\Html;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
echo Html::pagination(Row::int($arResult, 'NavPageNomer'), Row::int($arResult, 'NavPageCount'), 'PAGEN_'.Row::int($arResult, 'NavNum'));
