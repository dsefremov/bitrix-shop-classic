<?php

// Классы модуля (lib/, PSR-4: Dsefremov\ShopClassic\...) подключает автозагрузчик ядра.
// Модулю нужны штатные iblock, catalog и sale.
Bitrix\Main\Loader::requireModule('iblock');
Bitrix\Main\Loader::requireModule('catalog');
Bitrix\Main\Loader::requireModule('sale');
