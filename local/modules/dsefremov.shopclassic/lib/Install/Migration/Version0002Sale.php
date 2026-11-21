<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Install\Migration;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Sale\Delivery\Services\Configurable;
use Bitrix\Sale\Delivery\Services\Manager as DeliveryManager;
use Bitrix\Sale\Delivery\Services\Table as DeliveryTable;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Bitrix\Sale\Internals\PersonTypeTable;
use Bitrix\Sale\Internals\StatusLangTable;
use Bitrix\Sale\Internals\StatusTable;
use Bitrix\Sale\PaySystem\Manager as PaySystemManager;
use Bitrix\Sale\Registry;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Entity\Row;
use Dsefremov\ShopClassic\Install\InstallException;
use Dsefremov\ShopClassic\Install\Migration;

/**
 * Модуль sale: тип плательщика «Физ. лицо» и свойства заказа (ФИО, эл. почта, телефон, адрес),
 * свои статусы заказа «Принят» / «Оплачен» / «Выполнен», доставка «Самовывоз» (бесплатно),
 * платёжная система «Тестовая оплата» на своём обработчике dsefremov_stub
 * (/local/php_interface/include/sale_payment/dsefremov_stub) и смена статуса на «Оплачен» при оплате.
 */
final class Version0002Sale implements Migration
{
    /** @var list<array{code: string, name: string, flags: array<string, string>, sort: int}> */
    private const array PROPERTIES = [
        ['code' => Config::PROP_FIO, 'name' => 'Имя и фамилия', 'flags' => ['IS_PROFILE_NAME' => 'Y', 'IS_PAYER' => 'Y'], 'sort' => 100],
        ['code' => Config::PROP_EMAIL, 'name' => 'Эл. почта', 'flags' => ['IS_EMAIL' => 'Y', 'IS_FILTERED' => 'Y'], 'sort' => 110],
        ['code' => Config::PROP_PHONE, 'name' => 'Телефон', 'flags' => ['IS_PHONE' => 'Y', 'IS_FILTERED' => 'Y'], 'sort' => 120],
        ['code' => Config::PROP_ADDRESS, 'name' => 'Адрес доставки', 'flags' => ['IS_ADDRESS' => 'Y'], 'sort' => 130],
    ];

    public function name(): string
    {
        return 'Version0002Sale';
    }

    public function up(): void
    {
        Loader::requireModule('sale');

        $personTypeId = $this->ensurePersonType();
        Config::setIntOption('person_type_id', $personTypeId);
        $this->ensureProperties($personTypeId);
        $this->ensureStatuses();
        Config::setIntOption('delivery_id', $this->ensureDelivery());
        Option::set('sale', 'path2user_ps_files', Config::PAY_SYSTEM_DIR);
        Config::setIntOption('pay_system_id', $this->ensurePaySystem());
        // Штатная смена статуса при полной оплате заказа.
        Option::set('sale', 'status_on_paid', Config::STATUS_PAID);
    }

    private function ensurePersonType(): int
    {
        $row = PersonTypeTable::getList(['select' => ['ID'], 'filter' => ['=CODE' => Config::PERSON_TYPE_CODE]])->fetch();
        if (\is_array($row)) {
            return Row::int($row, 'ID');
        }
        $id = \CSalePersonType::Add([
            'LID' => Config::SITE_ID,
            'NAME' => 'Физ. лицо',
            'CODE' => Config::PERSON_TYPE_CODE,
            'SORT' => 100,
            'ACTIVE' => 'Y',
        ]);
        if (!is_numeric($id) || (int) $id <= 0) {
            throw new InstallException('Не удалось создать тип плательщика «Физ. лицо».');
        }

        return (int) $id;
    }

    private function ensureProperties(int $personTypeId): void
    {
        $group = \CSaleOrderPropsGroup::GetList([], ['PERSON_TYPE_ID' => $personTypeId, 'NAME' => 'Покупатель'])->Fetch();
        $groupId = \is_array($group) ? Row::int($group, 'ID') : (int) \CSaleOrderPropsGroup::Add([
            'PERSON_TYPE_ID' => $personTypeId,
            'NAME' => 'Покупатель',
            'SORT' => 100,
        ]);
        if ($groupId <= 0) {
            throw new InstallException('Не удалось создать группу свойств заказа.');
        }
        foreach (self::PROPERTIES as $property) {
            $exists = OrderPropsTable::getList([
                'select' => ['ID'],
                'filter' => ['=PERSON_TYPE_ID' => $personTypeId, '=CODE' => $property['code']],
            ])->fetch();
            if (\is_array($exists)) {
                continue;
            }
            $id = \CSaleOrderProps::Add($property['flags'] + [
                'PERSON_TYPE_ID' => $personTypeId,
                'NAME' => $property['name'],
                'TYPE' => 'TEXT',
                'REQUIRED' => 'Y',
                'USER_PROPS' => 'Y',
                'ACTIVE' => 'Y',
                'UTIL' => 'N',
                'IS_LOCATION' => 'N',
                'IS_LOCATION4TAX' => 'N',
                'IS_ZIP' => 'N',
                'PROPS_GROUP_ID' => $groupId,
                'CODE' => $property['code'],
                'SORT' => $property['sort'],
            ]);
            if (!is_numeric($id) || (int) $id <= 0) {
                throw new InstallException('Свойство заказа '.$property['code'].' не создано.');
            }
        }
    }

    private function ensureStatuses(): void
    {
        $sort = 110;
        foreach (Config::STATUSES as $code => $name) {
            if (!\is_array(StatusTable::getById($code)->fetch())) {
                InstallException::check(StatusTable::add([
                    'ID' => $code,
                    'TYPE' => StatusTable::TYPE_ORDER,
                    'SORT' => $sort,
                    'NOTIFY' => 'N',
                    'XML_ID' => 'dsefremov_'.strtolower($code),
                ]), 'Статус '.$code);
            }
            foreach (['ru' => $name, 'en' => $name] as $lid => $title) {
                $lang = StatusLangTable::getList(['filter' => ['=STATUS_ID' => $code, '=LID' => $lid]])->fetch();
                if (!\is_array($lang)) {
                    InstallException::check(StatusLangTable::add([
                        'STATUS_ID' => $code,
                        'LID' => $lid,
                        'NAME' => $title,
                        'DESCRIPTION' => 'Статус магазина dsefremov.shopclassic',
                    ]), 'Название статуса '.$code);
                }
            }
            $sort += 10;
        }
    }

    private function ensureDelivery(): int
    {
        $row = DeliveryTable::getList(['select' => ['ID'], 'filter' => ['=XML_ID' => Config::DELIVERY_XML_ID]])->fetch();
        if (\is_array($row)) {
            return Row::int($row, 'ID');
        }
        $result = DeliveryManager::add([
            'NAME' => 'Самовывоз',
            'DESCRIPTION' => 'Из пункта выдачи магазина, бесплатно.',
            'CLASS_NAME' => '\\'.Configurable::class,
            'PARENT_ID' => 0,
            'CURRENCY' => Config::CURRENCY,
            'SORT' => 100,
            'ACTIVE' => 'Y',
            'XML_ID' => Config::DELIVERY_XML_ID,
            'CONFIG' => [
                'MAIN' => [
                    'PRICE' => '0',
                    'CURRENCY' => Config::CURRENCY,
                    'PERIOD' => ['FROM' => '0', 'TO' => '0', 'TYPE' => 'D'],
                ],
            ],
        ]);
        InstallException::check($result, 'Доставка «Самовывоз»');

        return (int) $result->getId();
    }

    private function ensurePaySystem(): int
    {
        $row = PaySystemActionTable::getList(['select' => ['ID'], 'filter' => ['=XML_ID' => Config::PAY_SYSTEM_XML_ID]])->fetch();
        if (\is_array($row)) {
            return Row::int($row, 'ID');
        }
        $result = PaySystemManager::add([
            'NAME' => 'Тестовая оплата',
            'PSA_NAME' => 'Тестовая оплата',
            'DESCRIPTION' => 'Заглушка платёжного шлюза: «Оплатить» проводит оплату, «Отказ» — отклоняет. Деньги не списываются.',
            'ACTION_FILE' => Config::PAY_SYSTEM_HANDLER,
            'XML_ID' => Config::PAY_SYSTEM_XML_ID,
            'ENTITY_REGISTRY_TYPE' => Registry::REGISTRY_TYPE_ORDER,
            'ACTIVE' => 'Y',
            'NEW_WINDOW' => 'N',
            'HAVE_PAYMENT' => 'Y',
            'HAVE_ACTION' => 'N',
            'HAVE_RESULT' => 'N',
            'HAVE_PREPAY' => 'N',
            'HAVE_RESULT_RECEIVE' => 'Y',
            'ALLOW_EDIT_PAYMENT' => 'Y',
            'IS_CASH' => 'N',
            'SORT' => 100,
        ]);
        InstallException::check($result, 'Платёжная система «Тестовая оплата»');
        $id = (int) $result->getId();
        InstallException::check(PaySystemManager::update($id, ['PAY_SYSTEM_ID' => $id]), 'Платёжная система «Тестовая оплата»');

        return $id;
    }
}
