<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Install;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\SiteTemplateTable;
use Bitrix\Main\UrlRewriter;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Install\Migration\Version0001Catalog;
use Dsefremov\ShopClassic\Install\Migration\Version0002Sale;
use Dsefremov\ShopClassic\Install\Migration\Version0003Access;
use Dsefremov\ShopClassic\Install\Migration\Version0004Events;

/**
 * Шаги установки модуля (install/index.php и cli/install.php): миграции сущностей штатных модулей,
 * публичные страницы, шаблон сайта и правила ЧПУ. Все шаги можно запускать повторно.
 */
final class Installer
{
    public const string SITE_TEMPLATE = 'shop';
    public const string OPTION_APPLIED = 'applied_migrations';

    /**
     * Правила ЧПУ: адреса разбирает комплексный компонент bitrix:catalog (SEF_FOLDER = /).
     *
     * @var list<array{CONDITION: string, ID: string, PATH: string, RULE?: string}>
     */
    public const array URL_RULES = [
        ['CONDITION' => '#^/catalog/#', 'ID' => 'bitrix:catalog', 'PATH' => '/index.php'],
        ['CONDITION' => '#^/products/#', 'ID' => 'bitrix:catalog', 'PATH' => '/index.php'],
        ['CONDITION' => '#^/personal/orders/([0-9]+)/?(\\?.*)?$#', 'ID' => 'bitrix:sale.personal.order.detail', 'PATH' => '/personal/orders/detail.php', 'RULE' => 'ID=$1'],
    ];

    /**
     * @return list<Migration>
     */
    public static function migrations(): array
    {
        return [new Version0001Catalog(), new Version0002Sale(), new Version0003Access(), new Version0004Events()];
    }

    /**
     * Применяет миграции. Каждая миграция идемпотентна, поэтому запускаются все; список
     * впервые применённых возвращается для вывода.
     *
     * @return list<string>
     */
    public function installDb(): array
    {
        $applied = $this->applied();
        $new = [];
        foreach (self::migrations() as $migration) {
            $migration->up();
            if (!\in_array($migration->name(), $applied, true)) {
                $applied[] = $migration->name();
                $new[] = $migration->name();
            }
        }
        Option::set(Config::MODULE_ID, self::OPTION_APPLIED, implode(',', $applied));

        return $new;
    }

    /**
     * @return list<string>
     */
    public function applied(): array
    {
        $value = Option::get(Config::MODULE_ID, self::OPTION_APPLIED, '');

        return '' === $value ? [] : explode(',', $value);
    }

    /**
     * Копирует публичные страницы (install/public) в корень сайта и назначает шаблон сайта.
     */
    public function installFiles(string $publicDir): void
    {
        $root = Application::getDocumentRoot();
        CopyDirFiles($publicDir, $root, true, true);

        $connection = Application::getConnection();
        $connection->queryExecute(\sprintf(
            "DELETE FROM b_site_template WHERE SITE_ID = '%s'",
            $connection->getSqlHelper()->forSql(Config::SITE_ID),
        ));
        SiteTemplateTable::add([
            'SITE_ID' => Config::SITE_ID,
            'CONDITION' => '',
            'SORT' => 1,
            'TEMPLATE' => self::SITE_TEMPLATE,
        ]);
        new \CSite()->Update(Config::SITE_ID, ['NAME' => 'Bitrix Shop Classic', 'SITE_NAME' => 'Bitrix Shop Classic']);
        // Название сайта в заголовках административного раздела.
        Option::set('main', 'site_name', 'Bitrix Shop Classic');
    }

    public function uninstallFiles(string $publicDir): void
    {
        DeleteDirFiles($publicDir, Application::getDocumentRoot());
    }

    public function installUrlRewrite(): void
    {
        $this->uninstallUrlRewrite();
        foreach (self::URL_RULES as $i => $rule) {
            UrlRewriter::add(Config::SITE_ID, $rule + ['RULE' => '', 'SORT' => 100 + $i]);
        }
    }

    public function uninstallEvents(): void
    {
        $events = \Bitrix\Main\EventManager::getInstance();
        foreach (Version0004Events::HANDLERS as [$module, $event, $class, $method]) {
            $events->unRegisterEventHandler($module, $event, Config::MODULE_ID, $class, $method);
        }
    }

    public function uninstallUrlRewrite(): void
    {
        foreach (self::URL_RULES as $rule) {
            UrlRewriter::delete(Config::SITE_ID, ['CONDITION' => $rule['CONDITION']]);
        }
    }
}
