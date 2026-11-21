<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Fixtures;

use Bitrix\Catalog\Model\Price;
use Bitrix\Catalog\Model\Product;
use Bitrix\Catalog\ProductTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Application;
use Bitrix\Sale\Internals\OrderTable;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Entity\Row;
use Dsefremov\ShopClassic\Service\CheckoutService;

/**
 * Запись каталога штатными API: раздел инфоблока «Товары», элемент + товар торгового каталога
 * (остаток QUANTITY) + базовая цена. Используется демо-данными и тестами.
 */
final class CatalogWriter
{
    public function section(string $name, string $code, string $description = '', ?string $picture = null, int $sort = 500): int
    {
        $section = new \CIBlockSection();
        $id = $section->Add([
            'IBLOCK_ID' => Config::iblockId(),
            'NAME' => $name,
            'CODE' => $code,
            'SORT' => $sort,
            'ACTIVE' => 'Y',
            'DESCRIPTION' => $description,
            'DESCRIPTION_TYPE' => 'text',
        ] + (null !== $picture ? ['PICTURE' => self::named($picture, "{$code}.png")] : []));
        if (!is_numeric($id) || (int) $id <= 0) {
            throw new \RuntimeException('Раздел '.$name.': '.$section->LAST_ERROR);
        }

        return (int) $id;
    }

    /**
     * @param array<string, mixed> $fields дополнительные поля элемента (ACTIVE, DATE_CREATE и т. п.)
     */
    public function product(int $sectionId, string $name, string $code, int $priceRub, int $stock, string $preview = '', string $detail = '', ?string $picture = null, array $fields = []): int
    {
        $element = new \CIBlockElement();
        $id = $element->Add($fields + [
            'IBLOCK_ID' => Config::iblockId(),
            'IBLOCK_SECTION_ID' => $sectionId,
            'NAME' => $name,
            'CODE' => $code,
            'ACTIVE' => 'Y',
            'PREVIEW_TEXT' => $preview,
            'PREVIEW_TEXT_TYPE' => 'text',
            'DETAIL_TEXT' => $detail,
            'DETAIL_TEXT_TYPE' => 'text',
        ] + (null !== $picture ? [
            'PREVIEW_PICTURE' => self::named($picture, "{$code}.png"),
            'DETAIL_PICTURE' => self::named($picture, "{$code}.png"),
        ] : []));
        if (!is_numeric($id) || (int) $id <= 0) {
            throw new \RuntimeException('Товар '.$name.': '.$element->LAST_ERROR);
        }
        $id = (int) $id;
        CheckoutService::check(Product::add([
            'ID' => $id,
            'TYPE' => ProductTable::TYPE_PRODUCT,
            'QUANTITY' => $stock,
            'QUANTITY_TRACE' => ProductTable::STATUS_DEFAULT,
            'CAN_BUY_ZERO' => ProductTable::STATUS_DEFAULT,
            'MEASURE' => 5,
        ]));
        CheckoutService::check(Price::add([
            'PRODUCT_ID' => $id,
            'CATALOG_GROUP_ID' => Config::priceTypeId(),
            'PRICE' => $priceRub,
            'CURRENCY' => Config::CURRENCY,
        ]));

        return $id;
    }

    /**
     * Удаляет заказы, незаказанные корзины, товары и разделы инфоблока «Товары» (с картинками).
     */
    public function purge(): void
    {
        $orders = OrderTable::getList(['select' => ['ID']]);
        while ($row = $orders->fetch()) {
            \Bitrix\Sale\Order::deleteNoDemand(Row::int($row, 'ID'));
        }
        Application::getConnection()->queryExecute('DELETE FROM b_sale_basket WHERE ORDER_ID IS NULL');
        $elements = ElementTable::getList(['select' => ['ID'], 'filter' => ['=IBLOCK_ID' => Config::iblockId()]]);
        while ($row = $elements->fetch()) {
            \CIBlockElement::Delete(Row::int($row, 'ID'));
        }
        $sections = SectionTable::getList(['select' => ['ID'], 'filter' => ['=IBLOCK_ID' => Config::iblockId(), '=DEPTH_LEVEL' => 1]]);
        while ($row = $sections->fetch()) {
            \CIBlockSection::Delete(Row::int($row, 'ID'));
        }
    }

    /**
     * Временный файл с нужным именем (CFile берёт расширение из имени).
     *
     * @return array<string, mixed>
     */
    private static function named(string $path, string $name): array
    {
        $copy = sys_get_temp_dir().'/'.$name;
        copy($path, $copy);
        $file = \CFile::MakeFileArray($copy, 'image/png');
        if (!\is_array($file)) {
            throw new \RuntimeException('Не удалось подготовить файл.');
        }
        $file['name'] = $name;

        return $file;
    }
}
