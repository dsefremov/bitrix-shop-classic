<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Tests\Unit;

use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Service\OrderView;
use Dsefremov\ShopClassic\View\Format;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Форматирование цен, склонения и подписи статусов заказа.
 */
final class FormattingTest extends TestCase
{
    public function testRubles(): void
    {
        self::assertSame("5\u{a0}990\u{a0}₽", Format::rub(599000));
        self::assertSame("1\u{a0}299,9\u{a0}₽", Format::rub(129990));
        self::assertSame("0\u{a0}₽", Format::rub(0));
        self::assertSame('1299.9', Format::rubInput(129990));
        self::assertSame('5990', Format::rubInput(599000));
    }

    /**
     * @return iterable<array{int, string}>
     */
    public static function pluralCases(): iterable
    {
        yield [1, 'товар'];
        yield [2, 'товара'];
        yield [5, 'товаров'];
        yield [11, 'товаров'];
        yield [14, 'товаров'];
        yield [21, 'товар'];
        yield [24, 'товара'];
        yield [111, 'товаров'];
        yield [0, 'товаров'];
    }

    #[DataProvider('pluralCases')]
    public function testPlural(int $n, string $expected): void
    {
        self::assertSame($expected, Format::plural($n, 'товар', 'товара', 'товаров'));
    }

    public function testOrderView(): void
    {
        $accepted = new OrderView(1, Config::STATUS_ACCEPTED, false, false, true);
        self::assertSame('Принят', $accepted->label());
        self::assertSame('warning', $accepted->tone());
        self::assertTrue($accepted->isPayable(), 'После отказа заказ можно оплатить повторно.');

        $paid = new OrderView(2, Config::STATUS_PAID, false, true, false);
        self::assertSame(['Оплачен', 'info'], [$paid->label(), $paid->tone()]);
        self::assertFalse($paid->isPayable());

        self::assertSame(['Выполнен', 'success'], [new OrderView(3, Config::STATUS_COMPLETED, false, true, false)->label(), new OrderView(3, Config::STATUS_COMPLETED, false, true, false)->tone()]);
        $canceled = new OrderView(4, Config::STATUS_ACCEPTED, true, false, false);
        self::assertSame(['Отменён', 'muted'], [$canceled->label(), $canceled->tone()]);
        self::assertFalse($canceled->isPayable());
    }
}
