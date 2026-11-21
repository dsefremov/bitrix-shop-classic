<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Dsefremov\ShopClassic\Install\Installer;

Loc::loadMessages(__FILE__);

/**
 * Модуль интернет-магазина на штатных модулях iblock/catalog/sale. Установка: миграции
 * (инфоблок «Товары» + торговый каталог, тип плательщика, свойства заказа, статусы, доставка,
 * платёжная система «Тестовая оплата», группа «Менеджеры магазина»), публичные страницы
 * (install/public), шаблон сайта и правила ЧПУ.
 */
class dsefremov_shopclassic extends CModule
{
    public $MODULE_ID = 'dsefremov.shopclassic';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI = 'https://github.com/dsefremov/bitrix-shop-classic';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__.'/version.php';
        /** @var array{VERSION: string, VERSION_DATE: string} $arModuleVersion */
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('DSEFREMOV_SHOPCLASSIC_MODULE_NAME') ?? '';
        $this->MODULE_DESCRIPTION = Loc::getMessage('DSEFREMOV_SHOPCLASSIC_MODULE_DESCRIPTION') ?? '';
        $this->PARTNER_NAME = Loc::getMessage('DSEFREMOV_SHOPCLASSIC_PARTNER_NAME') ?? '';
    }

    public function DoInstall(): bool
    {
        ModuleManager::registerModule($this->MODULE_ID);
        Bitrix\Main\Loader::includeModule($this->MODULE_ID);
        $installer = new Installer();
        $installer->installDb();
        $installer->installFiles(__DIR__.'/public');
        $installer->installUrlRewrite();

        return true;
    }

    public function DoUninstall(): bool
    {
        // Данные магазина (инфоблок, заказы, справочники sale) при удалении модуля сохраняются.
        Bitrix\Main\Loader::includeModule($this->MODULE_ID);
        $installer = new Installer();
        $installer->uninstallUrlRewrite();
        $installer->uninstallEvents();
        $installer->uninstallFiles(__DIR__.'/public');
        ModuleManager::unRegisterModule($this->MODULE_ID);

        return true;
    }
}
