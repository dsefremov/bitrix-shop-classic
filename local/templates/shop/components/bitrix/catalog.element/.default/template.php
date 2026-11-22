<?php

/**
 * Карточка товара: фото, раздел, цена, остаток, «В корзину» (остров AddToCart), описание.
 *
 * @var array{SHOP_ITEM: Dsefremov\ShopClassic\View\CatalogItem, SHOP_IN_CART: int} $arResult
 */

use Dsefremov\ShopClassic\View\Format;
use Dsefremov\ShopClassic\View\Html;
use Dsefremov\ShopClassic\View\Url;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$product = $arResult['SHOP_ITEM'];
$inCart = $arResult['SHOP_IN_CART'];
?>
<nav class="mb-4 text-sm text-zinc-500" aria-label="Хлебные крошки">
    <a href="/" class="link">Каталог</a>
    <?php if ('' !== $product->sectionUrl) { ?>
        <span aria-hidden="true"> / </span>
        <a href="<?php echo Html::e($product->sectionUrl); ?>" class="link"><?php echo Html::e($product->sectionName); ?></a>
    <?php } ?>
</nav>

<div class="grid gap-8 md:grid-cols-2">
    <div class="card overflow-hidden">
        <?php echo Html::image($product->pictureId, $product->name, 'aspect-square w-full'); ?>
    </div>
    <div class="space-y-5">
        <div>
            <p class="mb-1 text-sm text-zinc-500"><?php echo Html::e($product->sectionName); ?></p>
            <h1 class="text-2xl font-semibold"><?php echo Html::e($product->name); ?></h1>
        </div>
        <p class="text-3xl font-semibold" data-testid="product-price"><?php echo Html::e(Format::rub($product->price)); ?></p>
        <p class="text-sm <?php echo $product->stock > 0 ? 'text-emerald-700' : 'text-red-600'; ?>" data-testid="product-stock">
            <?php echo $product->stock > 0 ? 'В наличии: '.$product->stock.' шт.' : 'Нет в наличии'; ?>
        </p>
        <div class="min-h-[4.5rem]">
            <?php echo Html::react('AddToCart', [
                'action' => Url::action('cart.add'), 'csrf' => bitrix_sessid(),
                'productId' => $product->id, 'stock' => $product->stock, 'inCart' => $inCart,
                'compact' => false, 'productName' => $product->name,
            ]); ?>
        </div>
        <?php if ($inCart > 0) { ?>
            <a href="/cart/" class="btn btn-outline">Перейти в корзину</a>
        <?php } ?>
        <?php if ('' !== $product->description) { ?>
            <div class="card p-4">
                <h2 class="mb-2 font-semibold">Описание</h2>
                <p class="text-sm leading-relaxed whitespace-pre-line text-zinc-700"><?php echo Html::e($product->description); ?></p>
            </div>
        <?php } ?>
    </div>
</div>
