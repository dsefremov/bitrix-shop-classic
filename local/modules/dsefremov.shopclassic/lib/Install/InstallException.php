<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Install;

final class InstallException extends \RuntimeException
{
    /**
     * Ошибки Result ядра (Main\Result, Sale\Result) или $LAST_ERROR старого API -> исключение.
     */
    public static function check(\Bitrix\Main\Result $result, string $what): void
    {
        if (!$result->isSuccess()) {
            throw new self($what.': '.implode('; ', $result->getErrorMessages()));
        }
    }
}
