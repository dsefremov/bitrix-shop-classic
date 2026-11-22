<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Entity;

use Bitrix\Main\Type\DateTime;

/**
 * Типобезопасное чтение значений из строк ORM / SQL.
 */
final class Row
{
    /**
     * @param array<string, mixed> $row
     */
    public static function int(array $row, string $key): int
    {
        $value = $row[$key] ?? 0;

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function nullableInt(array $row, string $key): ?int
    {
        $value = $row[$key] ?? null;

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function string(array $row, string $key): string
    {
        $value = $row[$key] ?? '';

        return \is_scalar($value) ? (string) $value : '';
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function nullableString(array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;

        return \is_scalar($value) && '' !== (string) $value ? (string) $value : null;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function date(array $row, string $key): ?\DateTimeImmutable
    {
        $value = $row[$key] ?? null;
        if ($value instanceof DateTime) {
            return new \DateTimeImmutable('@'.$value->getTimestamp())
                ->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }
        if (\is_string($value) && '' !== $value) {
            return new \DateTimeImmutable($value);
        }

        return null;
    }
}
