<?php

/**
 * Минимальные стабы API 1С-Битрикс для PHPStan: модуль catalog (сверены с catalog 25.550.0).
 * Только то, что вызывает код /local и tests; тел у методов нет.
 */

namespace {
    class CCatalog
    {
        /** @param array<string, mixed> $arFields */
        public static function Add(array $arFields): bool
        {
        }
    }

    class CCatalogGroup
    {
        /**
         * @param array<string, mixed> $arFields
         *
         * @return int|false
         */
        public static function Add(array $arFields)
        {
        }
    }
}

namespace Bitrix\Catalog {
    class ProductTable extends \Bitrix\Main\ORM\Data\DataManager
    {
        public const STATUS_DEFAULT = 'D';
        public const TYPE_PRODUCT = 1;
    }

    class GroupTable extends \Bitrix\Main\ORM\Data\DataManager
    {
    }

    class CatalogIblockTable extends \Bitrix\Main\ORM\Data\DataManager
    {
    }
}

namespace Bitrix\Catalog\Model {
    class Product
    {
        /** @param array<string, mixed> $data */
        public static function add(array $data): \Bitrix\Main\ORM\Data\AddResult
        {
        }
    }

    class Price
    {
        /** @param array<string, mixed> $data */
        public static function add(array $data): \Bitrix\Main\ORM\Data\AddResult
        {
        }
    }
}

namespace Bitrix\Catalog\Product {
    class Basket
    {
        /**
         * @param array<string, mixed> $context
         * @param array<string, mixed> $options
         */
        public static function addProductToBasket(\Bitrix\Sale\BasketBase $basket, array $product, array $context, array $options = []): \Bitrix\Main\Result
        {
        }
    }
}
