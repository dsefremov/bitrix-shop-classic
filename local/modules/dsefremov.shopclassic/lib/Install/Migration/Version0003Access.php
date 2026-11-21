<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Install\Migration;

use Bitrix\Main\GroupTable;
use Bitrix\Main\Loader;
use Bitrix\Main\TaskTable;
use Bitrix\Sale\Internals\StatusGroupTaskTable;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Entity\Row;
use Dsefremov\ShopClassic\Install\InstallException;
use Dsefremov\ShopClassic\Install\Migration;

/**
 * Группа «Менеджеры магазина»: вход в штатную админку /bitrix/admin (чтение файлов раздела) и доступ только к модулям
 * iblock (инфоблок «Товары» — изменение), catalog (цены и остатки) и sale (обработка заказов,
 * все действия во всех статусах заказа). Остальные модули для группы закрыты.
 */
final class Version0003Access implements Migration
{
    /** @var array<string, string> модуль => право (буквенные права модуля) */
    public const array MODULE_RIGHTS = [
        'iblock' => 'R',
        'sale' => 'U',
    ];

    /**
     * Модули с правами-задачами (b_group_task): catalog проверяет операции (catalog_price, catalog_store…),
     * а не букву. catalog_price_edit — цены, остатки, скидки; без настроек модуля и импорта/экспорта.
     *
     * @var array<string, string> модуль => задача
     */
    public const array MODULE_TASKS = [
        'catalog' => 'catalog_price_edit',
    ];

    public function name(): string
    {
        return 'Version0003Access';
    }

    public function up(): void
    {
        Loader::requireModule('iblock');
        Loader::requireModule('sale');
        global $APPLICATION;
        /** @var \CMain $APPLICATION */
        $groupId = $this->ensureGroup();
        Config::setIntOption('manager_group_id', $groupId);

        foreach (self::MODULE_RIGHTS as $module => $right) {
            $APPLICATION->SetGroupRight($module, $groupId, $right);
        }
        foreach (self::MODULE_TASKS as $module => $task) {
            \CGroup::SetModulePermission($groupId, $module, $task);
        }

        // Вход в /bitrix/admin: доступ к файлам раздела на чтение (/bitrix/.access.php), как это
        // делает «Настройки → Пользователи → Группы → Доступ к файлам».
        // SetFileAccessPermission заменяет все права пути, поэтому остальные группы берутся из файла.
        $permissions = $this->adminFilePermissions();
        if ('R' !== ($permissions[(string) $groupId] ?? null)) {
            $permissions[(string) $groupId] = 'R';
            $APPLICATION->SetFileAccessPermission([Config::SITE_ID, '/bitrix/admin'], $permissions);
        }

        // Права на инфоблок «Товары»: W — изменение элементов и разделов в админке.
        $iblockId = Config::iblockId();
        $permissions = \CIBlock::GetGroupPermissions($iblockId);
        $permissions[$groupId] = 'W';
        \CIBlock::SetPermission($iblockId, $permissions);

        // Права на заказы по статусам (модуль sale): «все действия» в каждом статусе заказа.
        $task = TaskTable::getList(['select' => ['ID'], 'filter' => ['=MODULE_ID' => 'sale', '=NAME' => 'sale_status_all']])->fetch();
        if (!\is_array($task)) {
            throw new InstallException('В модуле sale нет задачи sale_status_all.');
        }
        $taskId = Row::int($task, 'ID');
        $statuses = \Bitrix\Sale\Internals\StatusTable::getList(['select' => ['ID'], 'filter' => ['=TYPE' => 'O']]);
        while ($status = $statuses->fetch()) {
            $statusId = Row::string($status, 'ID');
            $exists = StatusGroupTaskTable::getList(['filter' => ['=STATUS_ID' => $statusId, '=GROUP_ID' => $groupId]])->fetch();
            if (!\is_array($exists)) {
                InstallException::check(StatusGroupTaskTable::add(['STATUS_ID' => $statusId, 'GROUP_ID' => $groupId, 'TASK_ID' => $taskId]), 'Права на статус '.$statusId);
            }
        }
    }

    /**
     * Текущие права на /bitrix/admin из /bitrix/.access.php (по умолчанию ядра — «*» => D).
     *
     * @return array<string, string>
     */
    private function adminFilePermissions(): array
    {
        /** @var array<string, array<string, string>> $PERM */
        $PERM = [];
        $file = \Bitrix\Main\Application::getDocumentRoot().'/bitrix/.access.php';
        if (is_file($file)) {
            include $file;
        }
        $result = [];
        $admin = \is_array($PERM['admin'] ?? null) ? $PERM['admin'] : ['*' => 'D'];
        foreach ($admin as $group => $right) {
            if (\is_string($right)) {
                $result[(string) $group] = $right;
            }
        }

        return $result + ['*' => 'D'];
    }

    private function ensureGroup(): int
    {
        $row = GroupTable::getList(['select' => ['ID'], 'filter' => ['=STRING_ID' => Config::MANAGER_GROUP_CODE]])->fetch();
        if (\is_array($row)) {
            return Row::int($row, 'ID');
        }
        $group = new \CGroup();
        $id = $group->Add([
            'ACTIVE' => 'Y',
            'C_SORT' => 200,
            'NAME' => 'Менеджеры магазина',
            'STRING_ID' => Config::MANAGER_GROUP_CODE,
            'DESCRIPTION' => 'Товары, цены, остатки и заказы в штатной админке /bitrix/admin.',
        ]);
        if (!is_numeric($id) || (int) $id <= 0) {
            throw new InstallException('Группа «Менеджеры магазина»: '.$group->LAST_ERROR);
        }

        return (int) $id;
    }
}
