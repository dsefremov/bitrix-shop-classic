<?php

/**
 * @var array{PAID: bool, CANCELED: bool, DECLINED: bool, PAY_SYSTEM: string, SUM: int, DATE_PAID: ?string, INVOICE: string, FORM: string} $arResult
 */

use Dsefremov\ShopClassic\View\Format;
use Dsefremov\ShopClassic\View\Html;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
?>
<aside class="card space-y-3 p-4" data-testid="payment">
    <h2 class="font-semibold">Оплата</h2>
    <p class="text-sm text-zinc-500">Способ оплаты: <span class="font-medium text-zinc-900"><?php echo Html::e($arResult['PAY_SYSTEM']); ?></span></p>
    <?php if ($arResult['PAID']) { ?>
        <p class="text-sm" data-testid="payment-paid">Оплачено <?php echo Html::e(Format::rub($arResult['SUM'])); ?><?php echo null !== $arResult['DATE_PAID'] ? ' '.Html::e($arResult['DATE_PAID']) : ''; ?>.</p>
        <?php if ('' !== $arResult['INVOICE']) { ?>
            <p class="text-xs text-zinc-500">Номер платежа: <?php echo Html::e($arResult['INVOICE']); ?></p>
        <?php } ?>
    <?php } elseif ($arResult['CANCELED']) { ?>
        <p class="text-sm">Заказ отменён, оплата недоступна.</p>
    <?php } else { ?>
        <?php if ($arResult['DECLINED']) { ?>
            <p class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" data-testid="payment-declined">Оплата отклонена. Товар зарезервирован — можно оплатить ещё раз.</p>
        <?php } ?>
        <p class="text-sm text-zinc-500">Тестовая оплата: деньги не списываются. «Оплатить» — оплата пройдёт, «Отказ» — смоделировать отклонённый платёж.</p>
        <?php echo $arResult['FORM']; ?>
    <?php } ?>
</aside>
