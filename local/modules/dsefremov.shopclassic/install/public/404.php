<?php

/**
 * Страница 404: адрес не нашёлся ни среди файлов, ни в urlrewrite.php.
 */

use Bitrix\Main\Loader;
use Dsefremov\ShopClassic\View\Html;

/** @var string $documentRoot */
$documentRoot = $_SERVER['DOCUMENT_ROOT'];
CHTTP::SetStatus('404 Not Found');
if (!defined('ERROR_404')) {
    define('ERROR_404', 'Y');
}
require $documentRoot.'/bitrix/header.php';
/** @var CMain $APPLICATION */
$APPLICATION->SetTitle('Страница не найдена');
Loader::includeModule('dsefremov.shopclassic');
echo Html::notFound();
require $documentRoot.'/bitrix/footer.php';
