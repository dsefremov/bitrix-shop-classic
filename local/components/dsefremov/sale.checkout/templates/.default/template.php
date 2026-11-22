<?php

/**
 * @var array{LINES: list<array{id: int, productId: int, name: string, code: string, pictureId: ?int, price: int, quantity: int, subtotal: int, stock: int}>, TOTAL: int, ERRORS: array<string, string>, VALUES: array<string, string>} $arResult
 */

use Dsefremov\ShopClassic\View\Format;
use Dsefremov\ShopClassic\View\Html;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
$errors = $arResult['ERRORS'];
$value = static fn (string $key): string => $arResult['VALUES'][$key] ?? '';
?>
<h1 class="mb-6 text-2xl font-semibold">Оформление заказа</h1>

<div class="grid items-start gap-6 lg:grid-cols-[1fr_22rem]">
    <form method="post" action="/checkout/" class="space-y-6" novalidate>
        <?= Html::csrf() ?>
        <?php if (isset($errors['_form'])): ?>
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert" data-testid="checkout-error">
                <p><?= Html::e($errors['_form']) ?></p>
            </div>
        <?php endif; ?>
        <section class="card space-y-4 p-4">
            <h2 class="font-semibold">Контакты</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <?= Html::field('text', 'customer_name', 'Имя и фамилия', $value('customer_name'), $errors, ['autocomplete' => 'name', 'required' => 'required']) ?>
                <?= Html::field('tel', 'customer_phone', 'Телефон', $value('customer_phone'), $errors, ['autocomplete' => 'tel', 'placeholder' => '+7 900 000-00-00', 'required' => 'required']) ?>
                <div class="sm:col-span-2"><?= Html::field('email', 'customer_email', 'Эл. почта', $value('customer_email'), $errors, ['autocomplete' => 'email', 'required' => 'required']) ?></div>
            </div>
        </section>
        <section class="card space-y-4 p-4">
            <h2 class="font-semibold">Доставка и оплата</h2>
            <p class="text-sm text-zinc-500">Доставка: <span class="font-medium text-zinc-900">Самовывоз</span> (бесплатно). Оплата: <span class="font-medium text-zinc-900">Тестовая оплата</span> на странице заказа.</p>
            <?= Html::field('textarea', 'address', 'Адрес доставки', $value('address'), $errors, ['rows' => 3, 'placeholder' => 'Город, улица, дом, квартира', 'required' => 'required']) ?>
            <?= Html::field('textarea', 'comment', 'Комментарий (необязательно)', $value('comment'), $errors, ['rows' => 2]) ?>
        </section>
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="btn btn-primary" data-testid="place-order">Подтвердить заказ</button>
            <a href="/cart/" class="btn btn-ghost">Вернуться в корзину</a>
        </div>
    </form>

    <aside class="card p-4">
        <h2 class="mb-3 font-semibold">Ваш заказ</h2>
        <ul class="divide-y text-sm">
            <?php foreach ($arResult['LINES'] as $line): ?>
                <li class="flex justify-between gap-3 py-2">
                    <span class="min-w-0"><?= Html::e($line['name']) ?> <span class="text-zinc-500">× <?= $line['quantity'] ?></span></span>
                    <span class="shrink-0"><?= Html::e(Format::rub($line['subtotal'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="mt-3 flex justify-between border-t pt-3 font-semibold">
            <span>Итого</span><span data-testid="checkout-total"><?= Html::e(Format::rub($arResult['TOTAL'])) ?></span>
        </div>
    </aside>
</div>
