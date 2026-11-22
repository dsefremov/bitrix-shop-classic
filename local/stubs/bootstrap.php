<?php

// Автозагрузка классов модуля для PHPStan без ядра (в ядре это делает Bitrix\Main\Loader).
spl_autoload_register(static function (string $class): void {
    $prefix = 'Dsefremov\\ShopClassic\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__.'/../modules/dsefremov.shopclassic/lib/'.str_replace('\\', '/', substr($class, \strlen($prefix))).'.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});
