<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Service;

use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Internals\BasketTable;
use Bitrix\Sale\Internals\OrderTable;
use Bitrix\Sale\Internals\PaymentTable;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Entity\Row;

/**
 * «Мои заказы»: заказы покупателя из sale с поиском (номер заказа или название товара в позициях),
 * фильтром статуса и дат, сортировкой и постраничностью. Штатный sale.personal.order.list не умеет
 * искать по товару и сортировать по сумме, поэтому список — свой.
 * Значения из GET проходят белые списки; неизвестные заменяются значениями по умолчанию,
 * в поиске символы % _ \ ищутся буквально.
 */
final class OrderListService
{
    public const int PER_PAGE = 10;
    public const string CANCELED = 'canceled';

    /** @var array<string, string> sort => поле */
    public const array SORTS = ['date' => 'DATE_INSERT', 'total' => 'PRICE', 'id' => 'ID'];

    /**
     * @param array<array-key, mixed> $query GET-параметры
     *
     * @return array{state: array{q: ?string, status: ?string, date_from: ?string, date_to: ?string, sort: string, dir: string}, page: int, pages: int, total: int, items: list<array{id: int, date: \DateTimeImmutable, total: int, quantity: int, view: OrderView}>}
     */
    public function find(int $userId, array $query): array
    {
        $state = self::state($query);
        $filter = Query::filter()->where('USER_ID', $userId)->where('LID', Config::SITE_ID);
        if (null !== $state['q']) {
            $byProduct = BasketTable::query()
                ->setSelect(['ORDER_ID'])
                ->whereLike('NAME', '%'.self::escapeLike($state['q']).'%')
                ->where('ORDER.USER_ID', $userId)
                ->whereNotNull('ORDER_ID');
            $search = Query::filter()->logic('or')->whereIn('ID', $byProduct);
            if (ctype_digit($state['q'])) {
                $search->where('ID', (int) $state['q']);
            }
            $filter->where($search);
        }
        if (self::CANCELED === $state['status']) {
            $filter->where('CANCELED', 'Y');
        } elseif (null !== $state['status']) {
            $filter->where('STATUS_ID', $state['status'])->where('CANCELED', 'N');
        }
        if (null !== $state['date_from']) {
            $filter->where('DATE_INSERT', '>=', new DateTime($state['date_from'].' 00:00:00', 'Y-m-d H:i:s'));
        }
        if (null !== $state['date_to']) {
            $filter->where('DATE_INSERT', '<=', new DateTime($state['date_to'].' 23:59:59', 'Y-m-d H:i:s'));
        }

        $total = (int) OrderTable::query()->setSelect(['ID'])->where($filter)->queryCountTotal();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($pages, max(1, \is_string($query['page'] ?? null) && ctype_digit($query['page']) ? (int) $query['page'] : 1));
        $rows = OrderTable::query()
            ->setSelect(['ID', 'DATE_INSERT', 'PRICE', 'STATUS_ID', 'CANCELED', 'PAYED'])
            ->where($filter)
            ->setOrder([self::SORTS[$state['sort']] => strtoupper($state['dir']), 'ID' => 'DESC'])
            ->setLimit(self::PER_PAGE)
            ->setOffset(($page - 1) * self::PER_PAGE)
            ->fetchAll();

        $ids = array_map(static fn (array $row): int => Row::int($row, 'ID'), $rows);
        $quantities = $this->quantities($ids);
        $declined = $this->declined($ids);
        $items = [];
        foreach ($rows as $row) {
            $id = Row::int($row, 'ID');
            $items[] = [
                'id' => $id,
                'date' => Row::date($row, 'DATE_INSERT') ?? new \DateTimeImmutable(),
                'total' => (int) round((float) Row::string($row, 'PRICE') * 100),
                'quantity' => $quantities[$id] ?? 0,
                'view' => new OrderView($id, Row::string($row, 'STATUS_ID'), 'Y' === Row::string($row, 'CANCELED'), 'Y' === Row::string($row, 'PAYED'), isset($declined[$id]) && 'Y' !== Row::string($row, 'PAYED')),
            ];
        }

        return ['state' => $state, 'page' => $page, 'pages' => $pages, 'total' => $total, 'items' => $items];
    }

    public function hasAny(int $userId): bool
    {
        return 0 < OrderTable::query()->setSelect(['ID'])->where('USER_ID', $userId)->setLimit(1)->queryCountTotal();
    }

    /**
     * @param array<array-key, mixed> $query
     *
     * @return array{q: ?string, status: ?string, date_from: ?string, date_to: ?string, sort: string, dir: string}
     */
    public static function state(array $query): array
    {
        $string = static fn (string $key): ?string => \is_string($query[$key] ?? null) && '' !== trim($query[$key]) ? trim($query[$key]) : null;
        $date = static function (string $key) use ($string): ?string {
            $value = $string($key);

            if (null === $value || 1 !== preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
                return null;
            }

            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $value : null;
        };
        $status = $string('status');
        $sort = $string('sort');
        $dir = $string('dir');

        return [
            'q' => null !== $string('q') ? mb_substr((string) $string('q'), 0, 100) : null,
            'status' => null !== $status && (isset(Config::STATUSES[$status]) || self::CANCELED === $status) ? $status : null,
            'date_from' => $date('date_from'),
            'date_to' => $date('date_to'),
            'sort' => null !== $sort && isset(self::SORTS[$sort]) ? $sort : 'date',
            'dir' => \in_array($dir, ['asc', 'desc'], true) ? (string) $dir : 'desc',
        ];
    }

    public static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * @param list<int> $orderIds
     *
     * @return array<int, int>
     */
    private function quantities(array $orderIds): array
    {
        if ([] === $orderIds) {
            return [];
        }
        $result = [];
        $rows = BasketTable::query()->setSelect(['ORDER_ID', 'QUANTITY'])->whereIn('ORDER_ID', $orderIds)->fetchAll();
        foreach ($rows as $row) {
            $id = Row::int($row, 'ORDER_ID');
            $result[$id] = ($result[$id] ?? 0) + (int) round((float) Row::string($row, 'QUANTITY'));
        }

        return $result;
    }

    /**
     * Заказы, у которых оплата «Тестовой оплатой» помечена ядром после отказа (MARKED = Y).
     *
     * @param list<int> $orderIds
     *
     * @return array<int, true>
     */
    private function declined(array $orderIds): array
    {
        if ([] === $orderIds) {
            return [];
        }
        $result = [];
        $rows = PaymentTable::query()->setSelect(['ORDER_ID'])->whereIn('ORDER_ID', $orderIds)
            ->where('MARKED', 'Y')->where('PAID', 'N')->where('PAY_SYSTEM_ID', Config::paySystemId())->fetchAll();
        foreach ($rows as $row) {
            $result[Row::int($row, 'ORDER_ID')] = true;
        }

        return $result;
    }
}
