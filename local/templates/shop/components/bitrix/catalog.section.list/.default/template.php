<?php

/**
 * Разделы инфоблока «Товары» — категории каталога (с числом активных товаров).
 *
 * @var array{SECTIONS: list<array<string, mixed>>} $arResult
 * @var array<string, mixed> $arParams
 */

use Dsefremov\ShopClassic\View\Html;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$current = is_string($arParams['CURRENT_SECTION_CODE'] ?? null) ? $arParams['CURRENT_SECTION_CODE'] : '';
$link = static fn (bool $active): string => 'flex items-center justify-between gap-3 rounded-md px-2 py-1.5 text-sm '.($active ? 'bg-zinc-200/70 font-medium' : 'hover:bg-zinc-100');
?>
<nav aria-label="Категории" data-testid="categories">
    <h2 class="mb-2 text-sm font-semibold">Категории</h2>
    <ul class="flex gap-1 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible">
        <li class="shrink-0">
            <a href="/" class="<?php echo $link('' === $current); ?>"<?php echo '' === $current ? ' aria-current="page"' : ''; ?>>Все товары</a>
        </li>
        <?php foreach ($arResult['SECTIONS'] as $section) { ?>
            <?php $active = $current === (string) $section['CODE']; ?>
            <li class="shrink-0">
                <a href="<?php echo Html::e((string) $section['SECTION_PAGE_URL']); ?>" class="<?php echo $link($active); ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>>
                    <?php echo Html::e((string) $section['NAME']); ?><span class="text-xs text-zinc-400"><?php echo (int) ($section['ELEMENT_CNT'] ?? 0); ?></span>
                </a>
            </li>
        <?php } ?>
    </ul>
</nav>
