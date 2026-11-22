<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Tests\Service;

use Bitrix\Main\Application;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Service\OrderListService;
use Dsefremov\ShopClassic\Service\OrderService;
use Dsefremov\ShopClassic\Service\PaymentService;

/**
 * «Мои заказы»: поиск по номеру и товару, фильтр статуса и дат, сортировка, постраничность.
 */
final class OrderListServiceTest extends ServiceTestCase
{
    /** @var array{id: int, name: string, email: string, password: string} */
    private array $user;

    /** @var array<string, int> */
    private array $orders = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
        $aero = $this->createProduct('Наушники Aero', 5990, 10);
        $book = $this->createProduct('Книга 100%_скидка', 590, 10);
        $mat = $this->createProduct('Коврик', 1990, 10);

        $this->orders['accepted'] = $this->placeOrder($this->user, [[$aero, 1]]);
        $this->orders['paid'] = $this->placeOrder($this->user, [[$book, 2]]);
        $this->orders['completed'] = $this->placeOrder($this->user, [[$mat, 1], [$book, 1]]);
        $this->orders['canceled'] = $this->placeOrder($this->user, [[$mat, 3]]);
        $payments = new PaymentService();
        $payments->process($this->orders['paid'], $this->user['id'], true);
        $payments->process($this->orders['completed'], $this->user['id'], true);
        new OrderService()->setStatus($this->orders['completed'], Config::STATUS_COMPLETED);
        new OrderService()->cancel($this->orders['canceled']);
        $this->placeOrder($this->createUser(), [[$aero, 1]]);

        $this->setDate($this->orders['accepted'], '2026-03-10 12:00:00');
        $this->setDate($this->orders['paid'], '2026-03-05 12:00:00');
        $this->setDate($this->orders['completed'], '2026-02-20 12:00:00');
        $this->setDate($this->orders['canceled'], '2026-01-15 12:00:00');
    }

    public function testDefaultListIsOwnOrdersNewestFirst(): void
    {
        $result = $this->find([]);
        self::assertSame(4, $result['total']);
        self::assertSame([$this->orders['accepted'], $this->orders['paid'], $this->orders['completed'], $this->orders['canceled']], $this->ids($result));
        self::assertSame(['Принят', 'Оплачен', 'Выполнен', 'Отменён'], array_map(static fn (array $i): string => $i['view']->label(), $result['items']));
        self::assertSame([1, 2, 2, 3], array_column($result['items'], 'quantity'));
    }

    public function testSearchByIdAndProductLiterally(): void
    {
        self::assertSame([$this->orders['paid']], $this->ids($this->find(['q' => (string) $this->orders['paid']])));
        self::assertSame([$this->orders['accepted']], $this->ids($this->find(['q' => 'aero'])));
        self::assertSame([$this->orders['paid'], $this->orders['completed']], $this->ids($this->find(['q' => '100%_'])));
        self::assertSame([$this->orders['paid'], $this->orders['completed']], $this->ids($this->find(['q' => '%'])), '% — обычный символ, а не «любая строка».');
        self::assertSame([], $this->ids($this->find(['q' => 'Ковр_к'])), '_ ищется буквально.');
    }

    public function testStatusAndDateFilters(): void
    {
        self::assertSame([$this->orders['paid']], $this->ids($this->find(['status' => 'PD'])));
        self::assertSame([$this->orders['completed']], $this->ids($this->find(['status' => 'CM'])));
        self::assertSame([$this->orders['canceled']], $this->ids($this->find(['status' => 'canceled'])));
        self::assertSame([$this->orders['accepted']], $this->ids($this->find(['status' => 'AC'])), 'Отменённый заказ в «Принят» не попадает.');
        self::assertSame([$this->orders['accepted'], $this->orders['paid']], $this->ids($this->find(['date_from' => '2026-03-01'])));
        self::assertSame([$this->orders['completed'], $this->orders['canceled']], $this->ids($this->find(['date_to' => '2026-02-28'])));
        self::assertSame([$this->orders['paid']], $this->ids($this->find(['date_from' => '2026-03-05', 'date_to' => '2026-03-05'])));
    }

    public function testSortingAndInvalidParams(): void
    {
        // Суммы: оплаченный 1180 ₽, выполненный 2580 ₽, отменённый 5970 ₽, принятый 5990 ₽.
        self::assertSame([$this->orders['paid'], $this->orders['completed'], $this->orders['canceled'], $this->orders['accepted']], $this->ids($this->find(['sort' => 'total', 'dir' => 'asc'])));
        self::assertSame([$this->orders['accepted'], $this->orders['canceled'], $this->orders['completed'], $this->orders['paid']], $this->ids($this->find(['sort' => 'total', 'dir' => 'desc'])));
        self::assertSame([$this->orders['canceled'], $this->orders['completed'], $this->orders['paid'], $this->orders['accepted']], $this->ids($this->find(['sort' => 'date', 'dir' => 'asc'])));

        $result = $this->find(['status' => 'XX', 'sort' => 'PRICE; DROP', 'dir' => 'up', 'date_from' => '2026-13-45', 'page' => 'abc']);
        self::assertSame(['q' => null, 'status' => null, 'date_from' => null, 'date_to' => null, 'sort' => 'date', 'dir' => 'desc'], $result['state']);
        self::assertSame(4, $result['total']);
    }

    public function testPagination(): void
    {
        for ($i = 0; $i < 8; ++$i) {
            $this->placeOrder($this->user, [[$this->createProduct('Товар '.$i, 100, 5), 1]]);
        }
        $first = $this->find([]);
        self::assertSame(12, $first['total']);
        self::assertSame(2, $first['pages']);
        self::assertCount(OrderListService::PER_PAGE, $first['items']);
        $second = $this->find(['page' => '2']);
        self::assertSame(2, $second['page']);
        self::assertCount(2, $second['items']);
        self::assertSame(2, $this->find(['page' => '99'])['page'], 'Номер страницы ограничен последней.');
    }

    /**
     * @param array<string, string> $query
     *
     * @return array{state: array<string, ?string>, page: int, pages: int, total: int, items: list<array{id: int, date: \DateTimeImmutable, total: int, quantity: int, view: \Dsefremov\ShopClassic\Service\OrderView}>}
     */
    private function find(array $query): array
    {
        return new OrderListService()->find($this->user['id'], $query);
    }

    /**
     * @param array{items: list<array{id: int}>} $result
     *
     * @return list<int>
     */
    private function ids(array $result): array
    {
        return array_column($result['items'], 'id');
    }

    private function setDate(int $orderId, string $date): void
    {
        Application::getConnection()->queryExecute(\sprintf("UPDATE b_sale_order SET DATE_INSERT = '%s' WHERE ID = %d", $date, $orderId));
    }
}
