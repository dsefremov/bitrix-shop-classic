<?php

// Вызывается из docker/test-db.sh после копирования основной базы в bitrix_shop_classic_test.
if (\PHP_SAPI !== 'cli') {
    exit(1);
}
require __DIR__.'/bootstrap.php';

Dsefremov\ShopClassic\Tests\Support\TestDatabase::prepareClone();
echo "Тестовая база bitrix_shop_classic_test готова.\n";
