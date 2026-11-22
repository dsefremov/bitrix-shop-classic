<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Security;

use Bitrix\Main\Engine\CurrentUser;
use Dsefremov\ShopClassic\Config;

/**
 * Права публичной части: покупатель — любой вошедший пользователь. Менеджеры магазина
 * (группа «Менеджеры магазина») и администраторы ядра работают в штатной админке /bitrix/admin.
 */
final class Access
{
    public static function userId(): int
    {
        return (int) CurrentUser::get()->getId();
    }

    public static function isAuthorized(): bool
    {
        return self::userId() > 0;
    }

    /**
     * Менеджер магазина или администратор ядра: в шапке показывается ссылка в /bitrix/admin.
     */
    public static function isManager(): bool
    {
        $user = CurrentUser::get();
        if ((int) $user->getId() <= 0) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }
        $groupId = Config::managerGroupId();

        return $groupId > 0 && \in_array($groupId, array_map('intval', $user->getUserGroups()), true);
    }

    /**
     * Локальный адрес для возврата после входа (защита от открытого редиректа).
     */
    public static function safeBackUrl(mixed $url, string $fallback = '/'): string
    {
        if (!\is_string($url) || '' === $url || !str_starts_with($url, '/') || str_starts_with($url, '//') || str_contains($url, '\\')) {
            return $fallback;
        }

        return $url;
    }

    public static function loginUrl(string $back): string
    {
        return '/login/?back='.rawurlencode($back);
    }
}
