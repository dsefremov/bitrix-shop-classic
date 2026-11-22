<?php

/**
 * Список товаров раздела (или всего каталога): поиск, сортировка, карточки, постраничка.
 *
 * @var array{SHOP_ITEMS: list<Dsefremov\ShopClassic\View\CatalogItem>, SHOP_TOTAL: int, NAV_STRING?: string, NAME?: string, ~DESCRIPTION?: string, ID?: int} $arResult
 * @var array<string, mixed> $arParams
 */

use Dsefremov\ShopClassic\Service\CatalogQuery;
use Dsefremov\ShopClassic\View\Format;
use Dsefremov\ShopClassic\View\Html;
use Dsefremov\ShopClassic\View\Url;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$query = CatalogQuery::fromQuery($_GET);
$inSection = '' !== (string) ($arParams['SECTION_CODE'] ?? '') && is_string($arResult['~NAME'] ?? null);
$title = $inSection ? (string) $arResult['~NAME'] : 'Каталог';
$description = $inSection ? trim(strip_tags((string) ($arResult['~DESCRIPTION'] ?? ''))) : 'Все товары магазина. Цены указаны в рублях.';
$cartAction = Url::action('cart.add');
$total = $arResult['SHOP_TOTAL'];
$resetUrl = (string) parse_url((string) Bitrix\Main\Context::getCurrent()->getRequest()->getRequestUri(), PHP_URL_PATH);
?>
<div class="mb-4 flex flex-wrap items-end justify-between gap-2">
    <div>
        <h1 class="text-2xl font-semibold"><?php echo Html::e($title); ?></h1>
        <p class="text-sm text-zinc-500"><?php echo Html::e('' !== $description ? $description : 'Товары раздела.'); ?></p>
    </div>
    <p class="text-sm text-zinc-500" data-testid="catalog-total">Найдено: <?php echo $total; ?> <?php echo Format::plural($total, 'товар', 'товара', 'товаров'); ?></p>
</div>

<div class="mb-4 flex flex-col gap-2 sm:flex-row">
    <div class="min-h-9 flex-1">
        <?php echo Html::react('SearchBox', [
            'value' => $query->q,
            'label' => 'Поиск по названию и описанию',
            'placeholder' => 'Поиск по названию и описанию',
            'testId' => 'catalog-search',
        ]); ?>
    </div>
    <div class="min-h-9 sm:w-56">
        <?php echo Html::react('SortSelect', [
            'options' => [
                ['value' => 'name:asc', 'label' => 'По названию'],
                ['value' => 'name:desc', 'label' => 'По названию (Я–А)'],
                ['value' => 'price:asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price:desc', 'label' => 'Сначала дороже'],
                ['value' => 'newest:desc', 'label' => 'Сначала новые'],
                ['value' => 'newest:asc', 'label' => 'Сначала старые'],
            ],
            'sort' => $query->sort, 'dir' => $query->dir,
            'defaultSort' => CatalogQuery::DEFAULT_SORT, 'defaultDir' => CatalogQuery::DEFAULT_DIR,
            'label' => 'Сортировка', 'testId' => 'catalog-sort',
        ]); ?>
    </div>
</div>

<?php if ([] === $arResult['SHOP_ITEMS']) { ?>
    <?php echo Html::noResults($resetUrl); ?>
<?php } else { ?>
    <ul class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <?php foreach ($arResult['SHOP_ITEMS'] as $product) { ?>
            <li class="card flex flex-col overflow-hidden" data-testid="product-card">
                <a href="<?php echo Html::e($product->url); ?>" tabindex="-1" aria-hidden="true">
                    <?php echo Html::image($product->pictureId, $product->name, 'aspect-square w-full'); ?>
                </a>
                <div class="flex flex-1 flex-col gap-1 p-3">
                    <p class="text-xs text-zinc-500"><?php echo Html::e($product->sectionName); ?></p>
                    <h3 class="text-sm font-medium leading-snug"><a href="<?php echo Html::e($product->url); ?>" class="hover:underline"><?php echo Html::e($product->name); ?></a></h3>
                    <div class="mt-auto flex flex-col gap-2 pt-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="font-semibold"><?php echo Html::e(Format::rub($product->price)); ?></p>
                            <?php if ($product->stock > 0) { ?>
                                <p class="text-xs text-emerald-700">В наличии: <?php echo $product->stock; ?> шт.</p>
                            <?php } else { ?>
                                <p class="text-xs text-red-600">Нет в наличии</p>
                            <?php } ?>
                        </div>
                        <div class="min-h-8 shrink-0">
                            <?php echo Html::react('AddToCart', [
                                'action' => $cartAction, 'csrf' => bitrix_sessid(),
                                'productId' => $product->id, 'stock' => $product->stock, 'inCart' => 0,
                                'compact' => true, 'productName' => $product->name,
                            ]); ?>
                        </div>
                    </div>
                </div>
            </li>
        <?php } ?>
    </ul>
    <?php echo (string) ($arResult['NAV_STRING'] ?? ''); ?>
<?php } ?>
