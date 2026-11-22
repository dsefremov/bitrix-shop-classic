<?php

/**
 * Заказ покупателя: состав, получатель и доставка, блок оплаты (dsefremov:sale.order.payment).
 * Чужой или несуществующий заказ компонент ядра не показывает: редирект на список заказов.
 *
 * @var array{ID?: int|string, ERROR_MESSAGE?: string, DATE_INSERT_FORMATED?: string, SHOP_VIEW?: ?Dsefremov\ShopClassic\Service\OrderView, SHOP_PROPS?: array<string, string>, SHOP_ITEMS?: list<array{name: string, url: string, price: int, quantity: int, subtotal: int}>, SHOP_TOTAL?: int, SHOP_DELIVERY?: string, SHOP_COMMENT?: string} $arResult
 * @var CMain $APPLICATION
 * @var CBitrixComponent $component
 */

use Bitrix\Iblock\Component\Tools;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\View\Format;
use Dsefremov\ShopClassic\View\Html;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$view = $arResult['SHOP_VIEW'] ?? null;
if ('' !== (string) ($arResult['ERROR_MESSAGE'] ?? '') || null === $view) {
    Bitrix\Main\Loader::includeModule('iblock');
    Tools::process404();
    $APPLICATION->SetTitle('Заказ не найден');
    echo Html::notFound('Заказ не найден', 'Такого заказа нет в вашем личном кабинете.');

    return;
}
$APPLICATION->SetTitle('Заказ №'.$view->id);
$props = $arResult['SHOP_PROPS'] ?? [];
?>
<nav class="mb-4 text-sm text-zinc-500" aria-label="Хлебные крошки">
    <a href="/personal/orders/" class="link">Мои заказы</a>
</nav>
<div class="mb-6 flex flex-wrap items-center gap-3">
    <h1 class="text-2xl font-semibold">Заказ №<?php echo $view->id; ?></h1>
    <?php echo Html::statusBadge($view->label(), $view->tone()); ?>
    <?php if ($view->declined) { ?>
        <span class="badge bg-red-100 text-red-800">Оплата отклонена</span>
    <?php } ?>
</div>

<div class="grid items-start gap-6 lg:grid-cols-[1fr_22rem]">
    <div class="space-y-6">
        <section class="card">
            <h2 class="border-b p-4 font-semibold">Товары</h2>
            <ul class="divide-y">
                <?php foreach ($arResult['SHOP_ITEMS'] ?? [] as $item) { ?>
                    <li class="flex justify-between gap-3 p-4 text-sm" data-testid="order-item">
                        <div class="min-w-0">
                            <p class="font-medium"><?php echo Html::e($item['name']); ?></p>
                            <p class="text-zinc-500"><?php echo Html::e(Format::rub($item['price'])); ?> × <?php echo $item['quantity']; ?></p>
                        </div>
                        <span class="shrink-0 font-medium"><?php echo Html::e(Format::rub($item['subtotal'])); ?></span>
                    </li>
                <?php } ?>
            </ul>
            <div class="flex justify-between border-t p-4 font-semibold">
                <span>Итого</span><span data-testid="order-total"><?php echo Html::e(Format::rub($arResult['SHOP_TOTAL'] ?? 0)); ?></span>
            </div>
        </section>
        <section class="card space-y-1 p-4 text-sm">
            <h2 class="mb-2 font-semibold">Получатель и доставка</h2>
            <p><?php echo Html::e($props[Config::PROP_FIO] ?? ''); ?></p>
            <p class="text-zinc-500"><?php echo Html::e($props[Config::PROP_PHONE] ?? ''); ?> · <?php echo Html::e($props[Config::PROP_EMAIL] ?? ''); ?></p>
            <p class="text-zinc-500"><?php echo Html::e($arResult['SHOP_DELIVERY'] ?? ''); ?>: <?php echo Html::e($props[Config::PROP_ADDRESS] ?? ''); ?></p>
            <?php if ('' !== ($arResult['SHOP_COMMENT'] ?? '')) { ?><p class="text-zinc-500">Комментарий: <?php echo Html::e($arResult['SHOP_COMMENT'] ?? ''); ?></p><?php } ?>
            <p class="pt-2 text-xs text-zinc-400">Оформлен: <?php echo Html::e((string) ($arResult['DATE_INSERT_FORMATED'] ?? '')); ?></p>
        </section>
    </div>

    <?php $APPLICATION->IncludeComponent('dsefremov:sale.order.payment', '.default', ['ORDER_ID' => $view->id], $component, ['HIDE_ICONS' => 'Y']); ?>
</div>
