<?php

/**
 * @var array{ERRORS: array<string, string>, VALUES: array<string, string>} $arResult
 */

use Dsefremov\ShopClassic\View\Html;
use Dsefremov\ShopClassic\View\Url;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
$errors = $arResult['ERRORS'];
$values = $arResult['VALUES'];
?>
<div class="mx-auto max-w-sm">
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-semibold">Регистрация</h1>
        <p class="text-sm text-zinc-500">Создайте аккаунт, чтобы оформлять заказы</p>
    </div>
    <form method="post" action="<?= Html::e(Url::action('auth.register')) ?>" class="card space-y-4 p-6" novalidate>
        <?= Html::csrf() ?>
        <?= Html::field('text', 'name', 'Имя', $values['name'] ?? '', $errors, ['autocomplete' => 'name', 'required' => 'required']) ?>
        <?= Html::field('email', 'email', 'Эл. почта', $values['email'] ?? '', $errors, ['autocomplete' => 'email', 'required' => 'required']) ?>
        <?= Html::field('password', 'password', 'Пароль', '', $errors, ['autocomplete' => 'new-password', 'required' => 'required']) ?>
        <?= Html::field('password', 'password_confirmation', 'Подтверждение пароля', '', $errors, ['autocomplete' => 'new-password', 'required' => 'required']) ?>
        <button type="submit" class="btn btn-primary w-full">Зарегистрироваться</button>
        <p class="text-center text-sm text-zinc-500">Уже есть аккаунт? <a href="/login/" class="link font-medium text-zinc-900">Войти</a></p>
    </form>
</div>
