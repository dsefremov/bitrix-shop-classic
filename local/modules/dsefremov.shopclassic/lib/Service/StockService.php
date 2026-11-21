<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Service;

use Bitrix\Catalog\ProductTable;
use Bitrix\Main\Application;
use Dsefremov\ShopClassic\Entity\Row;

/**
 * Остатки торгового каталога (b_catalog_product). QUANTITY — доступный остаток: при резервировании
 * ядро уменьшает QUANTITY и увеличивает QUANTITY_RESERVED, при снятии резерва — наоборот.
 */
final class StockService
{
    /**
     * @param list<int> $productIds
     *
     * @return array<int, array{quantity: int, reserved: int}>
     */
    public function get(array $productIds): array
    {
        if ([] === $productIds) {
            return [];
        }
        $stock = [];
        $rows = ProductTable::getList([
            'select' => ['ID', 'QUANTITY', 'QUANTITY_RESERVED'],
            'filter' => ['@ID' => $productIds],
        ]);
        while ($row = $rows->fetch()) {
            $stock[Row::int($row, 'ID')] = ['quantity' => Row::int($row, 'QUANTITY'), 'reserved' => Row::int($row, 'QUANTITY_RESERVED')];
        }

        return $stock;
    }

    public function available(int $productId): int
    {
        return $this->get([$productId])[$productId]['quantity'] ?? 0;
    }

    /**
     * Блокирует строки остатков до конца транзакции (SELECT ... FOR UPDATE) и возвращает доступный остаток.
     *
     * @param list<int> $productIds
     *
     * @return array<int, int>
     */
    public function lock(array $productIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $productIds), static fn (int $id): bool => $id > 0)));
        if ([] === $ids) {
            return [];
        }
        $connection = Application::getConnection();
        $result = $connection->query('SELECT ID, QUANTITY FROM '.ProductTable::getTableName().' WHERE ID IN ('.implode(',', $ids).') ORDER BY ID FOR UPDATE');
        $stock = [];
        while ($row = $result->fetch()) {
            $stock[Row::int($row, 'ID')] = Row::int($row, 'QUANTITY');
        }

        return $stock;
    }
}
