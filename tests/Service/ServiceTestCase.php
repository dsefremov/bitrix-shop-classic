<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Tests\Service;

use Dsefremov\ShopClassic\Tests\Support\Factories;
use Dsefremov\ShopClassic\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервисов в процессе PHPUnit на базе bitrix_shop_classic_test. Sale API сам открывает
 * транзакции, поэтому вместо отката база очищается перед каждым тестом (TestDatabase::reset).
 */
abstract class ServiceTestCase extends TestCase
{
    use Factories;

    protected function setUp(): void
    {
        TestDatabase::reset();
    }
}
