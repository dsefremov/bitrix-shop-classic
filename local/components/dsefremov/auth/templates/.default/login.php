<?php

/**
 * @var array{BACK: string, ERRORS: array<string, string>, VALUES: array<string, string>} $arResult
 */

use Dsefremov\ShopClassic\View\Html;
use Dsefremov\ShopClassic\View\Url;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<div class="mx-auto max-w-sm">
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-semibold">Вход в аккаунт</h1>
        <p class="text-sm text-zinc-500">Введите эл. почту и пароль, чтобы войти</p>
    </div>
    <form method="post" action="<?= Html::e(Url::action('auth.login')) ?>" class="card space-y-4 p-6">
        <?php if (isset($arResult['ERRORS']['_form'])): ?>
            <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert" data-testid="login-error">
                <?= Html::e($arResult['ERRORS']['_form']) ?>
            </div>
        <?php endif; ?>
        <div>
            <label for="email" class="label">Эл. почта</label>
            <input type="email" id="email" name="email" value="<?= Html::e($arResult['VALUES']['email'] ?? '') ?>" class="input" autocomplete="email" required autofocus>
        </div>
        <div>
            <label for="password" class="label">Пароль</label>
            <input type="password" id="password" name="password" class="input" autocomplete="current-password" required>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="Y" class="size-4 accent-zinc-900"> Запомнить меня
        </label>
        <input type="hidden" name="back" value="<?= Html::e($arResult['BACK']) ?>">
        <?= Html::csrf() ?>
        <button type="submit" class="btn btn-primary w-full">Войти</button>
        <p class="text-center text-sm text-zinc-500">Нет аккаунта? <a href="/register/" class="link font-medium text-zinc-900">Зарегистрироваться</a></p>
    </form>
</div>
