<?php

/**
 * Шапка сайта: меню (React MobileMenu на узких экранах), корзина, пользователь, флеш-сообщения.
 *
 * @var CMain $APPLICATION
 */

use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Dsefremov\ShopClassic\Security\Access;
use Dsefremov\ShopClassic\Service\BasketService;
use Dsefremov\ShopClassic\Service\UserService;
use Dsefremov\ShopClassic\View\Html;
use Dsefremov\ShopClassic\View\Url;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
Loader::includeModule('dsefremov.shopclassic');

$dist = '/local/js/dsefremov.shopclassic/dist';
Asset::getInstance()->addCss($dist.'/app.css');
Asset::getInstance()->addJs($dist.'/app.js');

$path = (string) parse_url((string) $APPLICATION->GetCurPage(true), PHP_URL_PATH);
$path = preg_replace('#/index\.php$#', '/', $path) ?? '/';
$user = Access::isAuthorized() ? new UserService()->find(Access::userId()) : null;
$navItems = [['title' => 'Каталог', 'href' => '/', 'active' => '/' === $path || str_starts_with($path, '/catalog/') || str_starts_with($path, '/products/')]];
if (null !== $user) {
    $navItems[] = ['title' => 'Мои заказы', 'href' => '/personal/orders/', 'active' => str_starts_with($path, '/personal/orders/')];
}
if (Access::isManager()) {
    // Управление магазином — штатная административная часть Битрикса.
    $navItems[] = ['title' => 'Админка', 'href' => '/bitrix/admin/', 'active' => false];
}
$count = new BasketService()->count();
$logout = Url::action('auth.logout');
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php $APPLICATION->ShowTitle(false); ?> — Bitrix Shop Classic</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 128 128%22><text y=%221.2em%22 font-size=%2296%22>🛒</text></svg>">
    <?php $APPLICATION->ShowCSS(true, false); ?>
    <?php $APPLICATION->ShowHeadStrings(); ?>
    <?php $APPLICATION->ShowHeadScripts(); ?>
</head>
<body class="flex min-h-svh flex-col">
<header class="sticky top-0 z-40 border-b bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-16 w-full max-w-7xl items-center gap-2 px-4">
        <div class="md:hidden">
            <?php echo Html::react('MobileMenu', [
                'items' => $navItems,
                'user' => null !== $user ? ['name' => $user['name'], 'email' => $user['email']] : null,
                'links' => ['profile' => '/personal/profile/', 'login' => '/login/', 'register' => '/register/'],
                'logout' => null !== $user ? ['action' => $logout, 'csrf' => bitrix_sessid()] : null,
            ]); ?>
        </div>
        <a href="/" class="flex min-w-0 shrink-0 items-center gap-2 font-semibold">
            <span class="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12a2 2 0 0 1-2-2V7"/></svg>
            </span>
            <span class="truncate">Bitrix Shop Classic</span>
        </a>

        <nav class="ml-4 hidden items-center gap-1 md:flex" aria-label="Основное меню">
            <?php foreach ($navItems as $item) { ?>
                <a href="<?php echo Html::e($item['href']); ?>" class="rounded-md px-3 py-2 text-sm <?php echo $item['active'] ? 'bg-zinc-100 font-medium text-zinc-900' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900'; ?>"<?php echo $item['active'] ? ' aria-current="page"' : ''; ?>><?php echo Html::e($item['title']); ?></a>
            <?php } ?>
        </nav>

        <div class="ml-auto flex items-center gap-2">
            <a href="/cart/" class="btn btn-ghost relative px-3" data-testid="cart-link" aria-label="Корзина<?php echo $count > 0 ? ', товаров: '.$count : ''; ?>">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                <span class="hidden sm:inline">Корзина</span>
                <?php if ($count > 0) { ?>
                    <span class="badge bg-primary px-1.5 text-primary-foreground" data-testid="cart-count"><?php echo $count; ?></span>
                <?php } ?>
            </a>
            <?php if (null !== $user) { ?>
                <details class="relative hidden md:block" data-user-menu>
                    <summary class="btn btn-outline cursor-pointer list-none [&::-webkit-details-marker]:hidden"><?php echo Html::e($user['name']); ?></summary>
                    <div class="card absolute right-0 z-50 mt-2 w-60 p-2">
                        <div class="border-b px-2 pb-2 text-sm">
                            <div class="font-medium"><?php echo Html::e($user['name']); ?></div>
                            <div class="truncate text-zinc-500"><?php echo Html::e($user['email']); ?></div>
                        </div>
                        <a href="/personal/orders/" class="mt-1 block rounded-md px-2 py-1.5 text-sm hover:bg-zinc-100">Мои заказы</a>
                        <a href="/personal/profile/" class="block rounded-md px-2 py-1.5 text-sm hover:bg-zinc-100">Профиль</a>
                        <form method="post" action="<?php echo Html::e($logout); ?>">
                            <?php echo Html::csrf(); ?>
                            <button type="submit" class="block w-full rounded-md px-2 py-1.5 text-left text-sm hover:bg-zinc-100">Выйти</button>
                        </form>
                    </div>
                </details>
            <?php } else { ?>
                <a href="/login/" class="btn btn-ghost hidden md:inline-flex">Войти</a>
                <a href="/register/" class="btn btn-outline hidden md:inline-flex">Регистрация</a>
            <?php } ?>
        </div>
    </div>
</header>

<main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 md:py-8">
    <?php
    // Флеш-сообщения выводятся отложенно (после компонента страницы): если компонент сделал редирект,
    // сообщения не теряются и показываются на следующей странице.
    $APPLICATION->AddBufferContent([Html::class, 'flashes']);
?>
