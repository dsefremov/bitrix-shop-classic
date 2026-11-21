<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Tests\Functional;

use Bitrix\Iblock\ElementTable;
use Dsefremov\ShopClassic\Entity\Row;

/**
 * Гость: каталог (bitrix:catalog — список, раздел, карточка, умный фильтр, поиск, сортировка,
 * 12 товаров на страницу), корзина без входа, закрытые страницы ведут на вход, /bitrix/admin закрыт.
 */
final class GuestTest extends WebTestCase
{
    public function testCatalogListPaginationAndSorting(): void
    {
        $section = $this->createSection('Электроника');
        for ($i = 1; $i <= 13; ++$i) {
            $this->createProduct(\sprintf('Товар %02d', $i), $i * 100, 5, $section);
        }

        $crawler = $this->get('/');
        $this->assertStatus(200);
        self::assertSame('Найдено: 13 товаров', $this->text('[data-testid="catalog-total"]'));
        self::assertCount(12, $crawler->filter('[data-testid="product-card"]'), '12 товаров на страницу.');
        self::assertSame('Товар 01', $this->text('[data-testid="product-card"] h3'), 'По умолчанию — по названию.');

        $crawler = $this->get('/?PAGEN_1=2');
        self::assertSame(['Товар 13'], $this->texts($crawler, '[data-testid="product-card"] h3'));

        $crawler = $this->get('/?sort=price&dir=desc');
        self::assertSame(['Товар 13', 'Товар 12'], \array_slice($this->texts($crawler, '[data-testid="product-card"] h3'), 0, 2));
        $sort = $this->reactProps('SortSelect');
        self::assertSame(['price', 'desc'], [$sort['sort'], $sort['dir']]);

        $crawler = $this->get('/?sort=name&dir=desc');
        self::assertSame('Товар 13', $this->texts($crawler, '[data-testid="product-card"] h3')[0]);
    }

    public function testSortByNewness(): void
    {
        $section = $this->createSection();
        $this->createProduct('Старый', 100, 1, $section, '', ['DATE_CREATE' => '01.01.2026 10:00:00']);
        $this->createProduct('Новый', 100, 1, $section, '', ['DATE_CREATE' => '01.03.2026 10:00:00']);
        $this->createProduct('Средний', 100, 1, $section, '', ['DATE_CREATE' => '01.02.2026 10:00:00']);

        self::assertSame(['Новый', 'Средний', 'Старый'], $this->texts($this->get('/?sort=newest&dir=desc'), '[data-testid="product-card"] h3'));
        self::assertSame(['Старый', 'Средний', 'Новый'], $this->texts($this->get('/?sort=newest&dir=asc'), '[data-testid="product-card"] h3'));
    }

    public function testSearchSmartFilterAndAvailability(): void
    {
        $section = $this->createSection();
        $this->createProduct('Чайник электрический', 2490, 3, $section);
        $this->createProduct('Термос', 1290, 0, $section, 'Сохраняет чай горячим 12 часов.');
        $this->createProduct('Кружка', 390, 10, $section);
        $this->createProduct('Кофемолка 100%', 4990, 2, $section);

        self::assertSame(['Чайник электрический'], $this->texts($this->get('/?q=чайник'), '[data-testid="product-card"] h3'), 'Поиск по названию.');
        self::assertSame(['Термос'], $this->texts($this->get('/?q=горячим'), '[data-testid="product-card"] h3'), 'Поиск по описанию.');
        self::assertSame(['Кофемолка 100%'], $this->texts($this->get('/?q=%25'), '[data-testid="product-card"] h3'), '% ищется как символ.');
        $this->get('/?q=несуществующее');
        self::assertSame('Найдено: 0 товаров', $this->text('[data-testid="catalog-total"]'));

        // Умный фильтр: имена полей цены берутся из шаблона catalog.smart.filter.
        $this->get('/');
        $panel = $this->reactProps('FilterPanel')['fields'];
        self::assertIsArray($panel);
        $fields = array_map(static fn (mixed $field): string => \is_array($field) && \is_string($field['name'] ?? null) ? $field['name'] : '', array_values($panel));
        self::assertCount(3, $fields);
        [$min, $max] = $fields;
        self::assertMatchesRegularExpression('/^arrFilter_P\d+_MIN$/', $min);
        $crawler = $this->get('/?set_filter=y&'.$min.'=1000&'.$max.'=3000');
        self::assertSame(['Термос', 'Чайник электрический'], $this->texts($crawler, '[data-testid="product-card"] h3'));

        $crawler = $this->get('/?available=1');
        self::assertSame(['Кофемолка 100%', 'Кружка', 'Чайник электрический'], $this->texts($crawler, '[data-testid="product-card"] h3'), 'Только в наличии.');
        $crawler = $this->get('/?available=1&set_filter=y&'.$min.'=1000&'.$max.'=3000');
        self::assertSame(['Чайник электрический'], $this->texts($crawler, '[data-testid="product-card"] h3'));
    }

    public function testSectionAndProductPages(): void
    {
        $kitchen = $this->createSection('Кухня');
        $sport = $this->createSection('Спорт');
        $productId = $this->createProduct('Френч-пресс', 1890, 7, $kitchen, 'Объём 1 л.');
        $this->createProduct('Мяч', 990, 3, $sport);
        $element = ElementTable::getById($productId)->fetch();
        $section = \CIBlockSection::GetByID($kitchen)->Fetch();
        self::assertIsArray($element);
        self::assertIsArray($section);
        $code = Row::string($element, 'CODE');
        $sectionCode = Row::string($section, 'CODE');

        $crawler = $this->get('/catalog/'.$sectionCode.'/');
        $this->assertStatus(200);
        self::assertSame('Кухня', $this->text('h1'));
        self::assertSame(['Френч-пресс'], $this->texts($crawler, '[data-testid="product-card"] h3'));
        self::assertSame('/products/'.$code.'/', $crawler->filter('[data-testid="product-card"] h3 a')->attr('href'));

        $this->get('/products/'.$code.'/');
        $this->assertStatus(200);
        self::assertSame('Френч-пресс', $this->text('h1'));
        self::assertSame("1\u{a0}890\u{a0}₽", $this->text('[data-testid="product-price"]'));
        self::assertSame('В наличии: 7 шт.', $this->text('[data-testid="product-stock"]'));
        $props = $this->reactProps('AddToCart');
        self::assertSame([$productId, 7, 0], [$props['productId'], $props['stock'], $props['inCart']]);

        $this->get('/products/net-takogo/');
        $this->assertStatus(404);
        self::assertSame('Товар не найден', $this->text('[data-testid="error-page"] h1'));
        $this->get('/catalog/net-takogo/');
        $this->assertStatus(404);
        $this->get('/net-takoy-stranicy/');
        $this->assertStatus(404);
    }

    public function testGuestCartIsLimitedByStockAndCheckoutNeedsLogin(): void
    {
        $productId = $this->createProduct('Гантели', 3990, 2);

        $this->get('/');
        $this->postAction('cart.add', [], ['product_id' => $productId, 'quantity' => 5]);
        self::assertSame(302, $this->httpStatus());
        $this->get('/cart/');
        self::assertSame(['Доступно только 2 шт. товара «Гантели», в корзину добавлен весь остаток.'], $this->flashes('error'));
        self::assertCount(1, $this->client->getCrawler()->filter('[data-testid="cart-line"]'));
        self::assertSame("7\u{a0}980\u{a0}₽", $this->text('[data-testid="cart-total"]'));
        self::assertSame('2', $this->text('[data-testid="cart-count"]'));
        $quantity = $this->reactProps('CartQuantity');
        self::assertSame([2, 2], [$quantity['quantity'], $quantity['stock']]);

        $this->get('/checkout/');
        $this->assertRedirect('/login/?back=%2Fcheckout%2F');
    }

    public function testPrivatePagesRedirectToLoginAndAdminIsClosed(): void
    {
        foreach (['/personal/orders/' => '%2Fpersonal%2Forders%2F', '/personal/orders/5/' => '%2Fpersonal%2Forders%2F5%2F', '/personal/profile/' => '%2Fpersonal%2Fprofile%2F'] as $path => $back) {
            $this->get($path);
            $this->assertRedirect('/login/?back='.$back);
        }
        $this->get('/bitrix/admin/');
        self::assertTrue($this->isAdminAuthForm(), 'Гостю /bitrix/admin показывает форму входа.');
        $this->get('/bitrix/admin/sale_order.php?lang=ru');
        self::assertTrue($this->isAdminAuthForm());
    }

    public function testCartActionsRequireSessid(): void
    {
        $productId = $this->createProduct();
        $this->get('/');
        $this->postAction('cart.add', [], ['product_id' => $productId], 'wrong');
        self::assertNotSame(302, $this->httpStatus(), 'Без верного sessid действие не выполняется.');
        $this->get('/cart/');
        self::assertCount(0, $this->client->getCrawler()->filter('[data-testid="cart-line"]'));
    }
}
