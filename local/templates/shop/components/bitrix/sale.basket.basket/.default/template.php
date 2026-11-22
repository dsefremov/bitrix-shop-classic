<?php

/**
 * Корзина покупателя (штатный sale.basket.basket): количество — остров CartQuantity
 * (верхняя граница — остаток), изменение и удаление — контроллер Cart модуля (с проверкой остатка).
 *
 * @var array{SHOP_LINES: list<array{id: int, productId: int, name: string, url: string, pictureId: ?int, price: int, quantity: int, stock: int, subtotal: int}>, SHOP_TOTAL: int} $arResult
 */

use Dsefremov\ShopClassic\Security\Access;
use Dsefremov\ShopClassic\View\Format;
use Dsefremov\ShopClassic\View\Html;
use Dsefremov\ShopClassic\View\Url;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
?>
<h1 class="mb-6 text-2xl font-semibold">Корзина</h1>

<?php if ([] === $arResult['SHOP_LINES']) { ?>
    <div class="card p-8 text-center">
        <p class="mb-4 text-zinc-500">Корзина пуста.</p>
        <a href="/" class="btn btn-primary">Перейти в каталог</a>
    </div>
<?php } else { ?>
    <div class="grid items-start gap-6 lg:grid-cols-[1fr_20rem]">
        <ul class="card divide-y">
            <?php foreach ($arResult['SHOP_LINES'] as $line) { ?>
                <li class="flex gap-3 p-4" data-testid="cart-line">
                    <a href="<?php echo Html::e($line['url']); ?>" class="shrink-0" tabindex="-1" aria-hidden="true">
                        <?php echo Html::image($line['pictureId'], $line['name'], 'size-16 rounded-md sm:size-20'); ?>
                    </a>
                    <div class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="min-w-0 flex-1">
                            <a href="<?php echo Html::e($line['url']); ?>" class="font-medium hover:underline"><?php echo Html::e($line['name']); ?></a>
                            <p class="text-xs text-zinc-500"><?php echo Html::e(Format::rub($line['price'])); ?> за шт. · в наличии <?php echo $line['stock']; ?> шт.</p>
                        </div>
                        <div class="flex items-center justify-between gap-3 sm:justify-end">
                            <div class="min-h-8 min-w-[6.5rem]">
                                <?php echo Html::react('CartQuantity', [
                                    'action' => Url::action('cart.update', ['itemId' => $line['id']]), 'csrf' => bitrix_sessid(),
                                    'quantity' => $line['quantity'], 'stock' => $line['stock'], 'productName' => $line['name'],
                                ]); ?>
                            </div>
                            <p class="w-24 text-right font-semibold" data-testid="cart-line-subtotal"><?php echo Html::e(Format::rub($line['subtotal'])); ?></p>
                            <form method="post" action="<?php echo Html::e(Url::action('cart.remove', ['itemId' => $line['id']])); ?>">
                                <?php echo Html::csrf(); ?>
                                <button type="submit" class="btn btn-ghost btn-sm text-zinc-500" aria-label="Удалить: <?php echo Html::e($line['name']); ?>">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </li>
            <?php } ?>
        </ul>

        <aside class="card space-y-4 p-4">
            <div class="flex items-center justify-between text-lg font-semibold">
                <span>Итого</span>
                <span data-testid="cart-total"><?php echo Html::e(Format::rub($arResult['SHOP_TOTAL'])); ?></span>
            </div>
            <a href="/checkout/" class="btn btn-primary w-full">Оформить заказ</a>
            <?php if (!Access::isAuthorized()) { ?>
                <p class="text-xs text-zinc-500">Для оформления заказа нужно <a href="/login/?back=%2Fcheckout%2F" class="link font-medium text-zinc-900">войти</a> — корзина сохранится.</p>
            <?php } ?>
        </aside>
    </div>
<?php } ?>
