<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Install\Migration;

use Bitrix\Catalog\CatalogIblockTable;
use Bitrix\Catalog\GroupTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\TypeTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Entity\Row;
use Dsefremov\ShopClassic\Install\InstallException;
use Dsefremov\ShopClassic\Install\Migration;

/**
 * Каталог на штатных модулях: тип инфоблоков «Каталог», инфоблок «Товары» (разделы = категории,
 * символьный код — транслитерацией ядра), торговый каталог на нём, базовый тип цен в рублях,
 * учёт остатков и резервирование (резерв при создании заказа, без автоснятия по времени).
 */
final class Version0001Catalog implements Migration
{
    /** Настройки символьного кода: обязателен, уникален, транслитерация из названия (как в админке ядра). */
    private const array CODE_SETTINGS = [
        'IS_REQUIRED' => 'Y',
        'DEFAULT_VALUE' => [
            'UNIQUE' => 'Y',
            'TRANSLITERATION' => 'Y',
            'TRANS_LEN' => 100,
            'TRANS_CASE' => 'L',
            'TRANS_SPACE' => '-',
            'TRANS_OTHER' => '-',
            'TRANS_EAT' => 'Y',
            'USE_GOOGLE' => 'N',
        ],
    ];

    public function name(): string
    {
        return 'Version0001Catalog';
    }

    public function up(): void
    {
        Loader::requireModule('iblock');
        Loader::requireModule('catalog');
        Loader::requireModule('currency');

        // Остатки: количественный учёт включён, покупка при нулевом остатке запрещена.
        Option::set('catalog', 'default_quantity_trace', 'Y');
        Option::set('catalog', 'default_can_buy_zero', 'N');
        Option::set('catalog', 'default_subscribe', 'N');
        Option::set('catalog', 'enable_reservation', 'Y');
        // Резерв товара — при создании заказа, автоматическое снятие резерва по времени выключено.
        Option::set('sale', 'product_reserve_condition', 'O');
        Option::set('sale', 'product_reserve_clear_period', '0');

        $this->ensureIblockType();
        $iblockId = $this->ensureIblock();
        Config::setIntOption('iblock_id', $iblockId);

        if (!\is_array(CatalogIblockTable::getById($iblockId)->fetch())) {
            if (!\CCatalog::Add(['IBLOCK_ID' => $iblockId, 'YANDEX_EXPORT' => 'N', 'SUBSCRIPTION' => 'N', 'VAT_ID' => 0])) {
                throw new InstallException('Не удалось сделать инфоблок торговым каталогом.');
            }
        }

        Config::setIntOption('price_type_id', $this->ensurePriceType());
    }

    private function ensureIblockType(): void
    {
        if (\is_array(TypeTable::getById(Config::IBLOCK_TYPE)->fetch())) {
            return;
        }
        $type = new \CIBlockType();
        $ok = $type->Add([
            'ID' => Config::IBLOCK_TYPE,
            'SECTIONS' => 'Y',
            'IN_RSS' => 'N',
            'SORT' => 100,
            'LANG' => [
                'ru' => ['NAME' => 'Каталог', 'SECTION_NAME' => 'Категории', 'ELEMENT_NAME' => 'Товары'],
                'en' => ['NAME' => 'Catalog', 'SECTION_NAME' => 'Categories', 'ELEMENT_NAME' => 'Products'],
            ],
        ]);
        if (!$ok) {
            throw new InstallException('Тип инфоблоков: '.$type->LAST_ERROR);
        }
    }

    private function ensureIblock(): int
    {
        $row = IblockTable::getList([
            'select' => ['ID'],
            'filter' => ['=IBLOCK_TYPE_ID' => Config::IBLOCK_TYPE, '=CODE' => Config::IBLOCK_CODE],
        ])->fetch();
        if (\is_array($row)) {
            return Row::int($row, 'ID');
        }
        $iblock = new \CIBlock();
        $id = $iblock->Add([
            'ACTIVE' => 'Y',
            'IBLOCK_TYPE_ID' => Config::IBLOCK_TYPE,
            'LID' => [Config::SITE_ID],
            'CODE' => Config::IBLOCK_CODE,
            'API_CODE' => Config::IBLOCK_API_CODE,
            'XML_ID' => Config::IBLOCK_CODE,
            'NAME' => 'Товары',
            'SORT' => 100,
            'LIST_PAGE_URL' => '#SITE_DIR#/',
            'SECTION_PAGE_URL' => '#SITE_DIR#/catalog/#SECTION_CODE#/',
            'DETAIL_PAGE_URL' => '#SITE_DIR#/products/#ELEMENT_CODE#/',
            'INDEX_ELEMENT' => 'N',
            'INDEX_SECTION' => 'N',
            'WORKFLOW' => 'N',
            'BIZPROC' => 'N',
            'VERSION' => 2,
            'GROUP_ID' => [1 => 'X', 2 => 'R'],
            'FIELDS' => [
                'CODE' => self::CODE_SETTINGS,
                'SECTION_CODE' => self::CODE_SETTINGS,
            ],
        ]);
        if (!is_numeric($id) || (int) $id <= 0) {
            throw new InstallException('Инфоблок «Товары»: '.$iblock->LAST_ERROR);
        }

        return (int) $id;
    }

    private function ensurePriceType(): int
    {
        $row = GroupTable::getList(['select' => ['ID'], 'filter' => ['=BASE' => 'Y']])->fetch();
        if (\is_array($row)) {
            return Row::int($row, 'ID');
        }
        // Цены видят и покупают все, включая гостей (группа 2 «Все пользователи»).
        $id = \CCatalogGroup::Add([
            'NAME' => Config::PRICE_TYPE,
            'XML_ID' => Config::PRICE_TYPE,
            'BASE' => 'Y',
            'SORT' => 100,
            'USER_GROUP' => [1, 2],
            'USER_GROUP_BUY' => [1, 2],
            'USER_LANG' => ['ru' => 'Розничная цена', 'en' => 'Retail price'],
        ]);
        if (!is_numeric($id) || (int) $id <= 0) {
            throw new InstallException('Не удалось создать базовый тип цен.');
        }

        return (int) $id;
    }
}
