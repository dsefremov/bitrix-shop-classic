<?php

// Копируется в bitrix/.settings_extra.php скриптом docker/setup.sh (каталог /bitrix в git не попадает).
// Подключение к БД: хост/база/пользователь — из окружения (значения по умолчанию — для локального Docker),
// пароль — из файла var/secrets/db-password (генерирует docker/secrets.sh, каталог var/ вне git)
// или из переменной BX_DB_PASSWORD. Паролей в коде нет.
// BX_DB_NAME задаёт nginx (второй сервер — тестовая база для PHPUnit) или окружение PHPUnit.
// Для тестовой базы кеш ядра отключён, чтобы рабочий и тестовый сайт не делили файловый кеш.
$env = static fn (string $name, string $default): string => (string) ($_SERVER[$name] ?? getenv($name) ?: $default);
$database = $env('BX_DB_NAME', 'bitrix_shop_classic');
$passwordFile = dirname(__DIR__).'/var/secrets/db-password';
$password = $env('BX_DB_PASSWORD', '');
if ('' === $password && is_readable($passwordFile)) {
    $password = trim((string) file_get_contents($passwordFile));
}

$settings = [
    'connections' => [
        'value' => [
            'default' => [
                'host' => $env('BX_DB_HOST', '127.0.0.1:'.$env('DB_PORT', '33076')),
                'database' => $database,
                'login' => $env('BX_DB_USER', 'shop'),
                'password' => $password,
                'options' => 2,
                'className' => '\\Bitrix\\Main\\DB\\MysqliConnection',
            ],
        ],
        'readonly' => true,
    ],
];

// Подробный вывод ошибок — только для локальной разработки (BX_DEBUG=0 выключает).
$settings['exception_handling'] = [
    'value' => [
        'debug' => '1' === $env('BX_DEBUG', '1'),
        'handled_errors_types' => E_ALL & ~E_NOTICE & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED,
        'exception_errors_types' => E_ALL & ~E_NOTICE & ~E_WARNING & ~E_USER_WARNING & ~E_USER_NOTICE & ~E_COMPILE_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED,
        'ignore_silence' => false,
        'assertion_throws_exception' => true,
        'assertion_error_type' => E_USER_ERROR,
        'log' => null,
    ],
    'readonly' => false,
];

if (str_ends_with($database, '_test')) {
    $settings['cache'] = ['value' => ['type' => 'none'], 'readonly' => false];
}

return $settings;
