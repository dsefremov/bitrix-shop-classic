<?php

/**
 * Минимальные стабы API 1С-Битрикс для PHPStan: модуль iblock (сверены с iblock 25.300.0).
 * Только то, что вызывает код /local и tests; тел у методов нет.
 */

namespace {
    class CIBlockResult extends CDBResult
    {
        /** @return array<string, mixed>|false */
        public function GetNext(bool $bTextHtmlAuto = true, bool $use_tilda = true)
        {
        }
    }

    class CIBlock
    {
        public string $LAST_ERROR = '';

        /**
         * @param array<string, mixed> $arFields
         *
         * @return int|false
         */
        public function Add(array $arFields)
        {
        }

        /** @return array<string, array<string, mixed>> */
        public static function GetFields(int $ID): array
        {
        }

        /** @return array<int, string> */
        public static function GetGroupPermissions(int $ID): array
        {
        }

        /** @param array<int|string, string> $arGROUP_ID */
        public static function SetPermission(int $IBLOCK_ID, array $arGROUP_ID): void
        {
        }
    }

    class CIBlockType
    {
        public string $LAST_ERROR = '';

        /**
         * @param array<string, mixed> $arFields
         *
         * @return string|false
         */
        public function Add(array $arFields)
        {
        }
    }

    class CIBlockSection
    {
        public string $LAST_ERROR = '';

        /**
         * @param array<string, mixed> $arFields
         *
         * @return int|false
         */
        public function Add(array $arFields, bool $bResort = true, bool $bUpdateSearch = true, bool $bResizePictures = false)
        {
        }

        public static function GetByID(int $ID): CIBlockResult
        {
        }

        public static function Delete(int $ID, bool $bCheckPermissions = true): bool
        {
        }
    }

    class CIBlockElement
    {
        public string $LAST_ERROR = '';

        /**
         * @param array<string, mixed> $arFields
         *
         * @return int|false
         */
        public function Add(array $arFields, bool $bWorkFlow = false, bool $bUpdateSearch = true, bool $bResizePictures = false)
        {
        }

        /**
         * @param array<string, string>      $arOrder
         * @param array<array-key, mixed>    $arFilter
         * @param array<string, mixed>|false $arGroupBy
         * @param array<string, mixed>|false $arNavStartParams
         * @param list<string>               $arSelectFields
         */
        public static function GetList(array $arOrder = ['SORT' => 'ASC'], array $arFilter = [], array|false $arGroupBy = false, array|false $arNavStartParams = false, array $arSelectFields = []): CIBlockResult
        {
        }

        public static function Delete(int $ID): bool
        {
        }
    }
}

namespace Bitrix\Iblock {
    class ElementTable extends \Bitrix\Main\ORM\Data\DataManager
    {
    }

    class SectionTable extends \Bitrix\Main\ORM\Data\DataManager
    {
    }

    class IblockTable extends \Bitrix\Main\ORM\Data\DataManager
    {
    }

    class TypeTable extends \Bitrix\Main\ORM\Data\DataManager
    {
    }
}
