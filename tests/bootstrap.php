<?php

// PHPUnit: ядро Битрикса на отдельной тестовой базе bitrix_shop_classic_test (см. docker/test-db.sh).
// Имя базы читает bitrix/.settings_extra.php из переменной BX_DB_NAME.
$_SERVER['BX_DB_NAME'] = 'bitrix_shop_classic_test';
putenv('BX_DB_NAME=bitrix_shop_classic_test');
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
$_SERVER['HTTP_HOST'] = '127.0.0.1';
$_SERVER['SERVER_NAME'] = '127.0.0.1';

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NO_AGENT_STATISTIC', true);
define('BX_WITH_ON_AFTER_EPILOG', false);
define('BX_SECURITY_SESSION_VIRTUAL', true);

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

if ('bitrix_shop_classic_test' !== Bitrix\Main\Application::getConnection()->getDatabase()) {
    fwrite(\STDERR, "PHPUnit должен работать на базе bitrix_shop_classic_test.\n");
    exit(1);
}
Bitrix\Main\Loader::includeModule('dsefremov.shopclassic');

require dirname(__DIR__).'/vendor/autoload.php';
