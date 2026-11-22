<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Service;

use Bitrix\Main\Application;
use Bitrix\Main\DB\TransactionException;

/**
 * Обёртка над транзакцией соединения ядра: commit при успехе, rollback при исключении.
 * Вложенные вызовы ядро делает через SAVEPOINT.
 */
final class Transaction
{
    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public static function run(callable $callback): mixed
    {
        $connection = Application::getConnection();
        $connection->startTransaction();
        try {
            $result = $callback();
            $connection->commitTransaction();

            return $result;
        } catch (\Throwable $e) {
            try {
                $connection->rollbackTransaction();
            } catch (TransactionException) {
                // Вложенный вызов: ядро откатило до SAVEPOINT и сообщает «Nested rollbacks are unsupported» —
                // наружу пробрасывается исходная ошибка, внешняя транзакция решает сама.
            }

            throw $e;
        }
    }
}
