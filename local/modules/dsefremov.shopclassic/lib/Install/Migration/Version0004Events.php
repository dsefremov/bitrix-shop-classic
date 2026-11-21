<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Install\Migration;

use Bitrix\Main\EventManager;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\EventHandler\User;
use Dsefremov\ShopClassic\Install\Migration;

/**
 * Постоянные обработчики событий модуля (b_module_to_module): снимаются и ставятся заново,
 * поэтому повторный запуск не плодит дубликаты.
 */
final class Version0004Events implements Migration
{
    /** @var list<array{0: string, 1: string, 2: class-string, 3: string}> */
    public const array HANDLERS = [
        ['main', 'OnBeforeUserUpdate', User::class, 'syncLogin'],
    ];

    public function name(): string
    {
        return 'Version0004Events';
    }

    public function up(): void
    {
        $events = EventManager::getInstance();
        foreach (self::HANDLERS as [$module, $event, $class, $method]) {
            $events->unRegisterEventHandler($module, $event, Config::MODULE_ID, $class, $method);
            $events->registerEventHandler($module, $event, Config::MODULE_ID, $class, $method);
        }
    }
}
