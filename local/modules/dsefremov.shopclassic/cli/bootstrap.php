<?php

// Подключение ядра для консольных скриптов модуля (php local/modules/dsefremov.shopclassic/cli/<script>.php).
if (\PHP_SAPI !== 'cli') {
    exit(1);
}
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 4);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
define('BX_CRONTAB', true);
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('BX_WITH_ON_AFTER_EPILOG', false);
@set_time_limit(0);

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
