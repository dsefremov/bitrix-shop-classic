<?php

/**
 * Профиль покупателя (штатный main.profile): имя, эл. почта (она же логин — синхронизирует
 * обработчик OnBeforeUserUpdate модуля), смена пароля. Форма отправляется на эту же страницу.
 *
 * @var array{ID?: int|string, arUser?: array<string, mixed>, strProfileError?: string, DATA_SAVED?: string, CAN_EDIT_PASSWORD?: bool} $arResult
 */

use Dsefremov\ShopClassic\View\Html;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$user = is_array($arResult['arUser'] ?? null) ? $arResult['arUser'] : [];
$value = static fn (string $key): string => is_scalar($user['~'.$key] ?? null) ? (string) $user['~'.$key] : (is_scalar($user[$key] ?? null) ? htmlspecialchars_decode((string) $user[$key]) : '');
$error = trim(strip_tags(str_replace(['<br>', '<br />'], ' ', (string) ($arResult['strProfileError'] ?? ''))));
?>
<div class="mx-auto max-w-xl">
    <h1 class="mb-6 text-2xl font-semibold">Профиль</h1>
    <?php if ('Y' === ($arResult['DATA_SAVED'] ?? 'N')) { ?>
        <div role="status" data-testid="flash-success" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">Профиль сохранён.</div>
    <?php } ?>
    <?php if ('' !== $error) { ?>
        <div role="alert" data-testid="flash-error" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?php echo Html::e($error); ?></div>
    <?php } ?>
    <form method="post" action="/personal/profile/" class="card space-y-4 p-6" data-testid="profile-form">
        <?php echo Html::csrf(); ?>
        <input type="hidden" name="lang" value="<?php echo LANG; ?>">
        <input type="hidden" name="ID" value="<?php echo (int) ($arResult['ID'] ?? 0); ?>">
        <?php echo Html::field('text', 'NAME', 'Имя', $value('NAME'), [], ['autocomplete' => 'name', 'maxlength' => '50']); ?>
        <?php echo Html::field('text', 'LAST_NAME', 'Фамилия', $value('LAST_NAME'), [], ['autocomplete' => 'family-name', 'maxlength' => '50']); ?>
        <?php echo Html::field('email', 'EMAIL', 'Эл. почта', $value('EMAIL'), [], ['autocomplete' => 'email', 'required' => 'required', 'maxlength' => '255'], [], 'Используется для входа.'); ?>
        <?php echo Html::field('tel', 'PERSONAL_PHONE', 'Телефон', $value('PERSONAL_PHONE'), [], ['autocomplete' => 'tel', 'maxlength' => '255']); ?>
        <?php if (false !== ($arResult['CAN_EDIT_PASSWORD'] ?? true)) { ?>
            <fieldset class="space-y-4 border-t pt-4">
                <legend class="text-sm font-semibold">Смена пароля</legend>
                <?php echo Html::field('password', 'NEW_PASSWORD', 'Новый пароль', '', [], ['autocomplete' => 'new-password'], [], 'Оставьте пустым, чтобы не менять.'); ?>
                <?php echo Html::field('password', 'NEW_PASSWORD_CONFIRM', 'Повторите пароль', '', [], ['autocomplete' => 'new-password']); ?>
            </fieldset>
        <?php } ?>
        <button type="submit" name="save" value="Y" class="btn btn-primary w-full">Сохранить</button>
    </form>
</div>
