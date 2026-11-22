<?php

/**
 * Шаблон «Тестовой оплаты»: обычная форма с кнопками «Оплатить» / «Отказ» (POST на страницу заказа,
 * как в bitrix-shop — без React). Адрес действия, ID оплаты и признак отказа передаёт компонент
 * dsefremov:sale.order.payment через PaySystem\Service::setTemplateParams().
 *
 * @var array<string, mixed> $params
 */

use Dsefremov\ShopClassic\View\Html;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$action = is_string($params['ACTION'] ?? null) ? $params['ACTION'] : '';
$paymentId = is_numeric($params['PAYMENT_ID'] ?? null) ? (int) $params['PAYMENT_ID'] : 0;
$declined = true === ($params['DECLINED'] ?? false);
?>
<form method="post" action="<?php echo Html::e($action); ?>" class="flex gap-2" data-testid="payment-form">
    <?php echo bitrix_sessid_post(); ?>
    <input type="hidden" name="PAYMENT_ID" value="<?php echo $paymentId; ?>">
    <button type="submit" name="STUB_RESULT" value="success" class="btn btn-primary flex-1" data-testid="pay-button"><?php echo $declined ? 'Оплатить ещё раз' : 'Оплатить'; ?></button>
    <button type="submit" name="STUB_RESULT" value="fail" class="btn btn-outline" data-testid="decline-button">Отказ</button>
</form>
