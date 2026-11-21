<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Tests\Unit;

use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Service\CatalogQuery;
use Dsefremov\ShopClassic\Service\OrderListService;
use PHPUnit\Framework\TestCase;

/**
 * GET-параметры каталога и «Моих заказов» проходят белые списки.
 */
final class CatalogQueryTest extends TestCase
{
    public function testDefaults(): void
    {
        $query = CatalogQuery::fromQuery([]);
        self::assertSame(['q' => null, 'sort' => 'name', 'dir' => 'asc', 'available' => null], $query->state());
        self::assertSame('name', $query->sortField());
        self::assertNull($query->searchFilter());
    }

    public function testWhitelist(): void
    {
        $query = CatalogQuery::fromQuery(['q' => '  чайник ', 'sort' => 'price', 'dir' => 'desc', 'available' => '1']);
        self::assertSame('CATALOG_PRICE_'.Config::priceTypeId(), $query->sortField());
        self::assertSame('desc', $query->sortOrder());
        self::assertTrue($query->available);
        self::assertSame(['LOGIC' => 'OR', '%NAME' => 'чайник', '%PREVIEW_TEXT' => 'чайник', '%DETAIL_TEXT' => 'чайник'], $query->searchFilter());

        self::assertSame('created', CatalogQuery::fromQuery(['sort' => 'newest'])->sortField());
        $bad = CatalogQuery::fromQuery(['sort' => 'ID; DROP', 'dir' => 'sideways', 'available' => 'maybe', 'q' => ['x']]);
        self::assertSame(['q' => null, 'sort' => 'name', 'dir' => 'asc', 'available' => null], $bad->state());
        self::assertSame(100, mb_strlen((string) CatalogQuery::fromQuery(['q' => str_repeat('я', 300)])->q));
    }

    public function testOrderListEscapesLike(): void
    {
        self::assertSame('100\%\_\\\\', OrderListService::escapeLike('100%_\\'));
    }
}
