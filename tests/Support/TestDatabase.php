<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Tests\Support;

use Bitrix\Main\Application;
use Bitrix\Main\UserTable;
use Dsefremov\ShopClassic\Entity\Row;
use Dsefremov\ShopClassic\Fixtures\CatalogWriter;
use Dsefremov\ShopClassic\Fixtures\DemoData;

/**
 * Состояние тестовой базы bitrix_shop_classic_test.
 *
 * HTTP-тесты ходят в nginx тестового сайта (порт 8097, та же база) — это другой процесс, общую
 * откатываемую транзакцию с ним не открыть, а Sale API сам открывает транзакции и держит кеши.
 * Поэтому перед каждым тестом заказы, корзины, товары и разделы инфоблока удаляются штатными API,
 * а пользователи, созданные тестами, — через CUser::Delete.
 */
final class TestDatabase
{
    public const string DATABASE = 'bitrix_shop_classic_test';

    /** Домены почты пользователей, которых создают тесты и демо-данные. */
    public const array TEST_EMAIL_DOMAINS = ['example.test', DemoData::EMAIL_DOMAIN];

    public static function assertTestDatabase(): void
    {
        $name = Application::getConnection()->getDatabase();
        if (self::DATABASE !== $name) {
            throw new \LogicException('Очистка разрешена только для '.self::DATABASE.", а не {$name}.");
        }
    }

    /**
     * Подготовка свежей копии основной базы: убрать демо-данные магазина, не удаляя файлы картинок
     * (записи b_file копии указывают на файлы основной базы в общем /upload).
     */
    public static function prepareClone(): void
    {
        self::assertTestDatabase();
        Application::getConnection()->queryExecute("DELETE FROM b_file WHERE MODULE_ID = 'iblock'");
        self::reset();
    }

    public static function reset(): void
    {
        self::assertTestDatabase();
        new CatalogWriter()->purge();
        Application::getConnection()->queryExecute('DELETE FROM b_sale_basket');
        self::deleteTestUsers();
    }

    private static function deleteTestUsers(): void
    {
        $query = UserTable::query()->setSelect(['ID']);
        $filter = $query::filter()->logic('or');
        foreach (self::TEST_EMAIL_DOMAINS as $domain) {
            $filter->whereLike('EMAIL', '%@'.$domain);
        }
        // ID 1 — администратор ядра из мастера установки, его не трогаем.
        foreach ($query->where('ID', '>', 1)->where($filter)->fetchAll() as $row) {
            \CUser::Delete(Row::int($row, 'ID'));
        }
    }
}
