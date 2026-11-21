<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic;

use Bitrix\Main\Config\Option;

/**
 * Коды сущностей магазина, которые создают миграции модуля, и их ID (хранятся в настройках модуля).
 * ID разные на разных стендах, поэтому код ищет сущности по символьным кодам / XML_ID.
 */
final class Config
{
    public const string MODULE_ID = 'dsefremov.shopclassic';
    public const string SITE_ID = 's1';
    public const string CURRENCY = 'RUB';

    public const string IBLOCK_TYPE = 'catalog';
    public const string IBLOCK_CODE = 'products';
    public const string IBLOCK_API_CODE = 'ShopProducts';
    public const string PRICE_TYPE = 'BASE';

    public const string PERSON_TYPE_CODE = 'shop_individual';
    public const string PROP_FIO = 'FIO';
    public const string PROP_EMAIL = 'EMAIL';
    public const string PROP_PHONE = 'PHONE';
    public const string PROP_ADDRESS = 'ADDRESS';

    /** Свои статусы заказа в b_sale_status (TYPE = O). */
    public const string STATUS_ACCEPTED = 'AC';
    public const string STATUS_PAID = 'PD';
    public const string STATUS_COMPLETED = 'CM';

    /** @var array<string, string> код => название */
    public const array STATUSES = [
        self::STATUS_ACCEPTED => 'Принят',
        self::STATUS_PAID => 'Оплачен',
        self::STATUS_COMPLETED => 'Выполнен',
    ];

    public const string DELIVERY_XML_ID = 'dsefremov_pickup';
    public const string PAY_SYSTEM_XML_ID = 'dsefremov_stub';
    public const string PAY_SYSTEM_HANDLER = 'dsefremov_stub';
    public const string PAY_SYSTEM_DIR = '/local/php_interface/include/sale_payment/';

    public const string MANAGER_GROUP_CODE = 'shop_managers';

    public const string DECLINED_MESSAGE = 'Оплата отклонена';

    public static function iblockId(): int
    {
        return self::intOption('iblock_id');
    }

    public static function priceTypeId(): int
    {
        return self::intOption('price_type_id');
    }

    public static function personTypeId(): int
    {
        return self::intOption('person_type_id');
    }

    public static function deliveryId(): int
    {
        return self::intOption('delivery_id');
    }

    public static function paySystemId(): int
    {
        return self::intOption('pay_system_id');
    }

    public static function managerGroupId(): int
    {
        return self::intOption('manager_group_id');
    }

    public static function intOption(string $name): int
    {
        return (int) Option::get(self::MODULE_ID, $name, '0');
    }

    public static function setIntOption(string $name, int $value): void
    {
        Option::set(self::MODULE_ID, $name, (string) $value);
    }
}
