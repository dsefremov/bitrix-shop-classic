<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\View;

/**
 * Форматирование: рубли, склонения, даты.
 */
final class Format
{
    /**
     * Копейки -> «5 990 ₽».
     */
    public static function rub(int $kopecks): string
    {
        $formatter = new \NumberFormatter('ru_RU', \NumberFormatter::CURRENCY);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 2);

        return (string) $formatter->formatCurrency($kopecks / 100, 'RUB');
    }

    /**
     * Копейки -> строка рублей для поля ввода: 129990 -> «1299.9».
     */
    public static function rubInput(int $kopecks): string
    {
        return rtrim(rtrim(number_format($kopecks / 100, 2, '.', ''), '0'), '.');
    }

    /**
     * Склонение: plural(n, 'товар', 'товара', 'товаров').
     */
    public static function plural(int $n, string $one, string $few, string $many): string
    {
        $mod10 = $n % 10;
        $mod100 = $n % 100;
        if (1 === $mod10 && 11 !== $mod100) {
            return $one;
        }
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            return $few;
        }

        return $many;
    }

    public static function dateTime(\DateTimeInterface $date): string
    {
        return $date->format('d.m.Y H:i');
    }
}
