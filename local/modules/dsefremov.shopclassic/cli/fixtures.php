<?php

// Демо-данные магазина: 4 категории, 24 товара с фото, 3 пользователя, 5 заказов.
require __DIR__.'/bootstrap.php';

Bitrix\Main\Loader::includeModule('dsefremov.shopclassic');
(new Dsefremov\ShopClassic\Fixtures\DemoData())->load();
echo "Демо-данные загружены.\n";
