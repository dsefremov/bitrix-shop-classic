<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Tests\Service;

use Bitrix\Catalog\CatalogIblockTable;
use Bitrix\Catalog\GroupTable as PriceTypeTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Config\Option;
use Bitrix\Sale\Delivery\Services\Table as DeliveryTable;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Bitrix\Sale\Internals\PersonTypeTable;
use Bitrix\Sale\Internals\StatusGroupTaskTable;
use Bitrix\Sale\Internals\StatusTable;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Entity\Row;
use Dsefremov\ShopClassic\Install\Installer;
use Dsefremov\ShopClassic\Install\Migration\Version0003Access;

/**
 * Установщик модуля: миграции идемпотентны, справочники iblock/catalog/sale и права менеджеров
 * созданы штатными API.
 */
final class InstallerTest extends ServiceTestCase
{
    public function testMigrationsAreIdempotent(): void
    {
        $installer = new Installer();
        $names = array_map(static fn ($m): string => $m->name(), Installer::migrations());
        self::assertSame($names, array_values(array_intersect($names, $installer->applied())), 'Все миграции записаны как применённые.');

        $counts = $this->counts();
        self::assertSame([], $installer->installDb(), 'Повторный запуск не применяет новых миграций.');
        self::assertSame([], $installer->installDb());
        self::assertSame($counts, $this->counts(), 'Повторный запуск не создаёт дубликатов.');
    }

    public function testCatalogIblockAndPrice(): void
    {
        $iblock = IblockTable::getList(['select' => ['ID', 'NAME', 'IBLOCK_TYPE_ID', 'CODE', 'API_CODE'], 'filter' => ['=ID' => Config::iblockId()]])->fetch();
        self::assertIsArray($iblock);
        self::assertSame('Товары', $iblock['NAME']);
        self::assertSame(Config::IBLOCK_TYPE, $iblock['IBLOCK_TYPE_ID']);
        self::assertSame(Config::IBLOCK_CODE, $iblock['CODE']);
        self::assertIsArray(CatalogIblockTable::getList(['filter' => ['=IBLOCK_ID' => Config::iblockId()]])->fetch(), 'Инфоблок — торговый каталог.');

        $fields = \CIBlock::GetFields(Config::iblockId());
        $code = $fields['CODE']['DEFAULT_VALUE'] ?? null;
        self::assertIsArray($code);
        self::assertSame('Y', $code['TRANSLITERATION'] ?? null, 'Символьный код — штатная транслитерация.');
        self::assertSame('Y', $code['UNIQUE'] ?? null);

        $price = PriceTypeTable::getList(['filter' => ['=ID' => Config::priceTypeId()]])->fetch();
        self::assertIsArray($price);
        self::assertSame(Config::PRICE_TYPE, $price['NAME']);
        self::assertSame('Y', $price['BASE']);
        self::assertSame('Y', Option::get('catalog', 'enable_reservation'));
        self::assertSame('O', Option::get('sale', 'product_reserve_condition'), 'Резерв — при оформлении заказа.');
    }

    public function testSaleDictionaries(): void
    {
        $personType = PersonTypeTable::getList(['filter' => ['=ID' => Config::personTypeId()]])->fetch();
        self::assertIsArray($personType);
        self::assertSame('Физ. лицо', $personType['NAME']);

        $props = array_column(OrderPropsTable::getList(['select' => ['CODE', 'NAME'], 'filter' => ['=PERSON_TYPE_ID' => Config::personTypeId()]])->fetchAll(), 'NAME', 'CODE');
        self::assertSame(['FIO', 'EMAIL', 'PHONE', 'ADDRESS'], array_keys($props));

        $statuses = array_column(StatusTable::getList(['select' => ['ID'], 'filter' => ['=TYPE' => 'O', '@ID' => array_keys(Config::STATUSES)], 'order' => ['SORT' => 'ASC']])->fetchAll(), 'ID');
        self::assertSame(array_keys(Config::STATUSES), $statuses);
        self::assertSame(['AC' => 'Принят', 'PD' => 'Оплачен', 'CM' => 'Выполнен'], Config::STATUSES);
        self::assertSame(Config::STATUS_PAID, Option::get('sale', 'status_on_paid'));

        $delivery = DeliveryTable::getList(['filter' => ['=ID' => Config::deliveryId()]])->fetch();
        self::assertIsArray($delivery);
        self::assertSame('Самовывоз', $delivery['NAME']);
        self::assertSame('Y', $delivery['ACTIVE']);

        $paySystem = PaySystemActionTable::getList(['filter' => ['=ID' => Config::paySystemId()]])->fetch();
        self::assertIsArray($paySystem);
        self::assertSame('Тестовая оплата', $paySystem['NAME']);
        self::assertSame(Config::PAY_SYSTEM_HANDLER, $paySystem['ACTION_FILE']);
        self::assertSame('Y', $paySystem['ACTIVE']);
    }

    public function testManagerGroupRights(): void
    {
        global $APPLICATION;
        /** @var \CMain $APPLICATION */
        $groupId = Config::managerGroupId();
        foreach (Version0003Access::MODULE_RIGHTS as $module => $right) {
            self::assertSame($right, $APPLICATION->GetGroupRight($module, [$groupId]), "Право на модуль {$module}.");
        }
        $tasks = \Bitrix\Main\Application::getConnection()->query(
            'SELECT T.NAME FROM b_group_task GT JOIN b_task T ON T.ID = GT.TASK_ID WHERE GT.GROUP_ID = '.$groupId." AND T.MODULE_ID = 'catalog'"
        )->fetchAll();
        self::assertSame(['catalog_price_edit'], array_map(static fn (array $row): string => Row::string($row, 'NAME'), $tasks), 'Торговый каталог: цены и остатки, без настроек модуля.');
        self::assertSame('D', $APPLICATION->GetGroupRight('main', [$groupId]), 'Главный модуль (пользователи, настройки) закрыт.');
        self::assertSame('W', \CIBlock::GetGroupPermissions(Config::iblockId())[$groupId] ?? null, 'Инфоблок «Товары» — изменение.');
        self::assertSame('R', $APPLICATION->GetFileAccessPermission([Config::SITE_ID, '/bitrix/admin/index.php'], [$groupId]), 'Вход в /bitrix/admin.');
        self::assertSame('D', $APPLICATION->GetFileAccessPermission([Config::SITE_ID, '/bitrix/admin/index.php'], [2]), 'Остальным группам админка закрыта.');

        $tasks = StatusGroupTaskTable::getList(['select' => ['STATUS_ID'], 'filter' => ['=GROUP_ID' => $groupId, '@STATUS_ID' => array_keys(Config::STATUSES)]])->fetchAll();
        self::assertCount(\count(Config::STATUSES), $tasks, 'Все действия с заказами в статусах магазина.');
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        return [
            'iblock' => IblockTable::getCount(['=CODE' => Config::IBLOCK_CODE]),
            'person_type' => PersonTypeTable::getCount(['=CODE' => Config::PERSON_TYPE_CODE]),
            'props' => OrderPropsTable::getCount(['=PERSON_TYPE_ID' => Config::personTypeId()]),
            'delivery' => DeliveryTable::getCount(['=XML_ID' => Config::DELIVERY_XML_ID]),
            'pay_system' => PaySystemActionTable::getCount(['=ACTION_FILE' => Config::PAY_SYSTEM_HANDLER]),
            'status_tasks' => StatusGroupTaskTable::getCount(['=GROUP_ID' => Config::managerGroupId()]),
            'handlers' => \count(\Bitrix\Main\EventManager::getInstance()->findEventHandlers('main', 'OnBeforeUserUpdate', ['dsefremov.shopclassic'])),
        ];
    }
}
