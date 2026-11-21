<?php

/**
 * Умный фильтр: цена «от/до» штатного catalog.smart.filter (GET arrFilter_P{id}_MIN/MAX + set_filter=y)
 * и флаг «Только в наличии» (GET available → HIDE_NOT_AVAILABLE). Форма — React-остров FilterPanel.
 *
 * @var array{ITEMS: array<array-key, array<string, mixed>>} $arResult
 * @var array<string, mixed> $arParams
 */

use Dsefremov\ShopClassic\View\Html;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$fields = [];
$values = [];
foreach ($arResult['ITEMS'] as $item) {
    if (true !== ($item['PRICE'] ?? false) || !is_array($item['VALUES'] ?? null)) {
        continue;
    }
    foreach (['MIN' => 'Цена от, ₽', 'MAX' => 'до, ₽'] as $key => $label) {
        $value = $item['VALUES'][$key] ?? null;
        if (!is_array($value)) {
            continue;
        }
        $name = (string) $value['CONTROL_NAME'];
        $bound = is_numeric($value['VALUE'] ?? null) ? (string) (int) floor((float) $value['VALUE']) : '';
        $fields[] = ['name' => $name, 'label' => $label, 'type' => 'number', 'placeholder' => 'MIN' === $key ? $bound : (string) (int) ceil((float) ($value['VALUE'] ?? 0))];
        $values[$name] = is_scalar($value['HTML_VALUE'] ?? null) && '' !== (string) $value['HTML_VALUE'] ? (string) $value['HTML_VALUE'] : null;
    }
}
$fields[] = ['name' => 'available', 'label' => 'Только в наличии', 'type' => 'checkbox'];
$values['available'] = true === ($arParams['AVAILABLE'] ?? false) ? '1' : null;
?>
<div data-testid="smart-filter">
    <?php echo Html::react('FilterPanel', [
        'title' => 'Фильтр',
        'layout' => 'card',
        'fields' => $fields,
        'values' => $values,
        'extra' => ['set_filter' => 'y'],
    ]); ?>
</div>
