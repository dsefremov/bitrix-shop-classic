<?php

/**
 * Раздел каталога (/catalog/{код}/).
 *
 * @var array<string, mixed> $arParams
 * @var array{VARIABLES: array<string, string>} $arResult
 * @var CMain $APPLICATION
 * @var CBitrixComponent $component
 */

use Bitrix\Iblock\Component\Tools;
use Dsefremov\ShopClassic\View\Html;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$sectionCode = (string) ($arResult['VARIABLES']['SECTION_CODE'] ?? '');
$sectionFound = '' !== $sectionCode && is_array(CIBlockSection::GetList([], [
    'IBLOCK_ID' => $arParams['IBLOCK_ID'], '=CODE' => $sectionCode, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y',
], false, ['ID'])->Fetch());
if (!$sectionFound) {
    Tools::process404();
    $APPLICATION->SetTitle('Раздел не найден');
    echo Html::notFound('Раздел не найден', 'Такого раздела каталога нет.');

    return;
}
include __DIR__.'/listing.php';
