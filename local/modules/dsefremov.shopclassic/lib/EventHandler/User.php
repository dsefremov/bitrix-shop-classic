<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\EventHandler;

use Bitrix\Main\UserTable;
use Dsefremov\ShopClassic\Entity\Row;

/**
 * Обработчики событий main для пользователей магазина.
 */
final class User
{
    /**
     * OnBeforeUserUpdate: у покупателей логин = эл. почта. Если почту меняют (например, в профиле
     * через штатный main.profile), а логин совпадал со старой почтой, логин меняется вместе с ней.
     *
     * @param array<array-key, mixed> $fields
     */
    public static function syncLogin(array &$fields): bool
    {
        $id = is_numeric($fields['ID'] ?? null) ? (int) $fields['ID'] : 0;
        $email = \is_string($fields['EMAIL'] ?? null) ? mb_strtolower(trim($fields['EMAIL'])) : '';
        if ($id <= 0 || '' === $email) {
            return true;
        }
        $row = UserTable::getList(['select' => ['LOGIN', 'EMAIL'], 'filter' => ['=ID' => $id]])->fetch();
        if (!\is_array($row) || mb_strtolower(Row::string($row, 'LOGIN')) !== mb_strtolower(Row::string($row, 'EMAIL'))) {
            return true;
        }
        $fields['EMAIL'] = $email;
        $fields['LOGIN'] = $email;

        return true;
    }
}
