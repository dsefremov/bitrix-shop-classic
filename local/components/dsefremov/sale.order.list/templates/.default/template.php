<?php

/**
 * @var array{state: array{q: ?string, status: ?string, date_from: ?string, date_to: ?string, sort: string, dir: string}, page: int, pages: int, total: int, items: list<array{id: int, date: DateTimeImmutable, total: int, quantity: int, view: Dsefremov\ShopClassic\Service\OrderView}>, HAS_ANY: bool} $arResult
 */

use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Service\OrderListService;
use Dsefremov\ShopClassic\View\Format;
use Dsefremov\ShopClassic\View\Html;
use Dsefremov\ShopClassic\View\Url;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$state = $arResult['state'];
$tabs = [['value' => null, 'label' => 'Все']];
foreach (Config::STATUSES as $code => $name) {
    $tabs[] = ['value' => $code, 'label' => $name];
}
$tabs[] = ['value' => OrderListService::CANCELED, 'label' => 'Отменён'];
?>
<div class="mb-6 flex flex-wrap items-end justify-between gap-2">
    <div>
        <h1 class="text-2xl font-semibold">Мои заказы</h1>
        <p class="text-sm text-zinc-500">История заказов личного кабинета.</p>
    </div>
    <?php if ($arResult['HAS_ANY']) { ?>
        <p class="text-sm text-zinc-500" data-testid="orders-total">Найдено: <?php echo $arResult['total']; ?> <?php echo Format::plural($arResult['total'], 'заказ', 'заказа', 'заказов'); ?></p>
    <?php } ?>
</div>

<?php if (!$arResult['HAS_ANY']) { ?>
    <div class="card p-8 text-center">
        <p class="mb-4 text-zinc-500">Заказов пока нет.</p>
        <a href="/" class="btn btn-primary">Перейти в каталог</a>
    </div>
<?php } else { ?>
    <div class="mb-4 space-y-3">
        <?php echo Html::statusTabs($tabs, $state['status']); ?>
        <div class="card flex flex-col gap-3 p-3 lg:flex-row lg:items-end">
            <div class="min-w-0 flex-1">
                <span class="mb-1 block text-xs text-zinc-500">Поиск</span>
                <div class="min-h-9">
                    <?php echo Html::react('SearchBox', [
                        'value' => $state['q'], 'label' => 'Поиск по номеру заказа или товару',
                        'placeholder' => 'Номер заказа или товар', 'testId' => 'orders-search',
                    ]); ?>
                </div>
            </div>
            <div class="min-h-9">
                <?php echo Html::react('FilterPanel', [
                    'fields' => [
                        ['name' => 'date_from', 'label' => 'Дата с', 'type' => 'date'],
                        ['name' => 'date_to', 'label' => 'Дата по', 'type' => 'date'],
                    ],
                    'values' => ['date_from' => $state['date_from'], 'date_to' => $state['date_to']],
                ]); ?>
            </div>
            <div class="lg:w-52">
                <span class="mb-1 block text-xs text-zinc-500">Сортировка</span>
                <div class="min-h-9">
                    <?php echo Html::react('SortSelect', [
                        'options' => [
                            ['value' => 'date:desc', 'label' => 'Сначала новые'],
                            ['value' => 'date:asc', 'label' => 'Сначала старые'],
                            ['value' => 'total:asc', 'label' => 'Сумма по возрастанию'],
                            ['value' => 'total:desc', 'label' => 'Сумма по убыванию'],
                        ],
                        'sort' => $state['sort'], 'dir' => $state['dir'], 'defaultSort' => 'date', 'defaultDir' => 'desc',
                        'label' => 'Сортировка', 'testId' => 'orders-sort',
                    ]); ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ([] === $arResult['items']) { ?>
        <?php echo Html::noResults('/personal/orders/'); ?>
    <?php } else { ?>
        <ul class="space-y-3">
            <?php foreach ($arResult['items'] as $order) { ?>
                <li>
                    <a href="<?php echo Html::e(Url::order($order['id'])); ?>" class="card flex flex-wrap items-center gap-x-4 gap-y-1 p-4 hover:border-zinc-300" data-testid="order-row">
                        <span class="font-semibold">Заказ №<?php echo $order['id']; ?></span>
                        <?php echo Html::statusBadge($order['view']->label(), $order['view']->tone()); ?>
                        <?php if ($order['view']->declined) { ?>
                            <span class="badge bg-red-100 text-red-800" data-testid="payment-declined">Оплата отклонена</span>
                        <?php } ?>
                        <span class="text-sm text-zinc-500"><?php echo Html::e(Format::dateTime($order['date'])); ?></span>
                        <span class="text-sm text-zinc-500"><?php echo $order['quantity']; ?> <?php echo Format::plural($order['quantity'], 'товар', 'товара', 'товаров'); ?></span>
                        <span class="ml-auto font-semibold"><?php echo Html::e(Format::rub($order['total'])); ?></span>
                    </a>
                </li>
            <?php } ?>
        </ul>
        <?php echo Html::pagination($arResult['page'], $arResult['pages']); ?>
    <?php } ?>
<?php } ?>
