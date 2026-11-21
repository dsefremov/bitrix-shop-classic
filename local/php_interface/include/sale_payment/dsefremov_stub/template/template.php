<?php

/**
 * Шаблон «Тестовой оплаты»: React-кнопки «Оплатить» / «Отказ» (остров PaymentButtons).
 * Адрес действия, sessid и ID оплаты передаёт код магазина через setExtraParams().
 *
 * @var array<string, mixed> $params
 */

use Dsefremov\ShopClassic\View\Html;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

echo Html::react('PaymentButtons', [
    'action' => is_string($params['ACTION'] ?? null) ? $params['ACTION'] : '',
    'csrf' => bitrix_sessid(),
    'paymentId' => is_numeric($params['PAYMENT_ID'] ?? null) ? (int) $params['PAYMENT_ID'] : 0,
    'declined' => true === ($params['DECLINED'] ?? false),
]);
