<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Service;

/**
 * Символьный код транслитерацией ядра (CUtil::translit) с теми же параметрами, что заданы
 * в настройках полей CODE/SECTION_CODE инфоблока «Товары»: «Беспроводные наушники» → besprovodnye-naushniki.
 */
final class Slug
{
    public static function make(string $name): string
    {
        return (string) \CUtil::translit($name, 'ru', [
            'max_len' => 100,
            'change_case' => 'L',
            'replace_space' => '-',
            'replace_other' => '-',
            'delete_repeat_replace' => true,
            'use_google' => false,
        ]);
    }
}
