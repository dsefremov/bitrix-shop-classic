<?php

// Установка (или обновление) модуля: регистрация, миграции, публичные страницы, шаблон сайта, urlrewrite.
require __DIR__.'/bootstrap.php';

use Bitrix\Main\ModuleManager;
use Dsefremov\ShopClassic\Install\Installer;

$module = CModule::CreateModuleObject('dsefremov.shopclassic');
if (!$module instanceof CModule) {
    fwrite(STDERR, "Модуль dsefremov.shopclassic не найден в /local/modules\n");
    exit(1);
}
if (!ModuleManager::isModuleInstalled('dsefremov.shopclassic')) {
    $module->DoInstall();
    echo 'Модуль dsefremov.shopclassic установлен. Миграции: '.implode(', ', new Installer()->applied())."\n";
} else {
    Bitrix\Main\Loader::includeModule('dsefremov.shopclassic');
    $installer = new Installer();
    $new = $installer->installDb();
    $installer->installFiles(dirname(__DIR__).'/install/public');
    $installer->installUrlRewrite();
    echo 'Модуль dsefremov.shopclassic обновлён. Новые миграции: '.([] === $new ? 'нет' : implode(', ', $new))."\n";
}
