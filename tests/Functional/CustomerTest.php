<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Tests\Functional;

use Bitrix\Main\UserTable;
use Bitrix\Sale\Order;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Entity\Row;
use Dsefremov\ShopClassic\Service\OrderService;
use Dsefremov\ShopClassic\Service\PaymentService;

/**
 * Покупатель: регистрация и вход, корзина гостя сохраняется после входа, оформление заказа,
 * оплата «Тестовой оплатой» с отказом и повтором, «Мои заказы», профиль, нет доступа в /bitrix/admin.
 */
final class CustomerTest extends WebTestCase
{
    public function testRegisterLogoutAndLogin(): void
    {
        $email = 'new-'.bin2hex(random_bytes(3)).'@example.test';
        $password = bin2hex(random_bytes(10));
        $this->get('/register/');
        $this->client->submitForm('Зарегистрироваться', ['name' => 'Новый Покупатель', 'email' => $email, 'password' => $password, 'password_confirmation' => $password]);
        $this->assertRedirect('/personal/orders/');
        $this->followRedirect();
        self::assertStringContainsString('Заказов пока нет.', $this->text('main'));
        $groups = $this->groupIds($email);
        self::assertNotContains(1, $groups, 'Новый пользователь — не администратор.');
        self::assertNotContains(Config::managerGroupId(), $groups, 'Новый пользователь — не менеджер.');

        $this->client->submitForm('Выйти');
        $this->assertRedirect('/');
        $this->get('/personal/orders/');
        $this->assertRedirect('/login/?back=%2Fpersonal%2Forders%2F');

        $this->get('/login/');
        $this->client->submitForm('Войти', ['email' => $email, 'password' => strrev($password)]);
        $this->assertRedirect('/login/');
        $this->followRedirect();
        self::assertStringContainsString('Неверная эл. почта или пароль.', $this->text('form'));

        $this->login(['email' => $email, 'password' => $password]);
        self::assertSame('/', $this->path(), 'Без back — в каталог.');

        // Вход со страницы, куда гость не попал, возвращает на неё.
        $this->client = $this->newClient();
        $this->get('/personal/profile/');
        $this->followRedirect();
        $this->client->submitForm('Войти', ['email' => $email, 'password' => $password]);
        $this->assertRedirect('/personal/profile/');
    }

    public function testPurchaseWithDeclineAndRetry(): void
    {
        $user = $this->createUser('Иван Петров');
        $productId = $this->createProduct('Наушники Aero', 5990, 3);

        // Корзина гостя переходит к покупателю после входа.
        $this->get('/');
        $this->postAction('cart.add', [], ['product_id' => $productId, 'quantity' => 2]);
        $this->login($user);
        $this->get('/cart/');
        self::assertCount(1, $this->client->getCrawler()->filter('[data-testid="cart-line"]'));
        self::assertSame("11\u{a0}980\u{a0}₽", $this->text('[data-testid="cart-total"]'));

        $this->get('/checkout/');
        $this->assertStatus(200);
        $this->client->submitForm('Подтвердить заказ', [
            'customer_name' => 'Иван Петров', 'customer_email' => $user['email'],
            'customer_phone' => '+7 900 123-45-67', 'address' => 'г. Москва, ул. Тверская, д. 1', 'comment' => 'Позвонить заранее',
        ]);
        self::assertSame(302, $this->httpStatus());
        self::assertMatchesRegularExpression('#^/personal/orders/(\d+)/$#', $this->location());
        $orderPath = $this->location();
        $orderId = (int) filter_var($orderPath, \FILTER_SANITIZE_NUMBER_INT);
        $this->followRedirect();
        self::assertSame(['Заказ №'.$orderId.' оформлен. Оплатите его.'], $this->flashes('success'));
        self::assertSame('Принят', $this->text('[data-testid="order-status"]'));
        self::assertSame("11\u{a0}980\u{a0}₽", $this->text('[data-testid="order-total"]'));
        self::assertStringContainsString('Комментарий: Позвонить заранее', $this->text('main'));
        self::assertSame(['quantity' => 1, 'reserved' => 2], $this->stock($productId), 'Товар зарезервирован.');
        $buttons = $this->reactProps('PaymentButtons');
        self::assertSame($orderPath, $buttons['action']);
        self::assertFalse($buttons['declined']);

        $this->post($orderPath, ['STUB_RESULT' => 'fail', 'PAYMENT_ID' => $buttons['paymentId']]);
        $this->assertRedirect($orderPath);
        $this->followRedirect();
        self::assertSame(['Оплата отклонена. Можно попробовать ещё раз.'], $this->flashes('error'));
        self::assertSame('Принят', $this->text('[data-testid="order-status"]'));
        self::assertStringContainsString('Оплата отклонена', $this->text('[data-testid="payment-declined"]'));
        self::assertTrue($this->reactProps('PaymentButtons')['declined']);
        self::assertSame(['quantity' => 1, 'reserved' => 2], $this->stock($productId), 'Резерв сохраняется после отказа.');

        $this->post($orderPath, ['STUB_RESULT' => 'success', 'PAYMENT_ID' => $buttons['paymentId']]);
        $this->assertRedirect($orderPath);
        $this->followRedirect();
        self::assertSame(['Оплата прошла успешно.'], $this->flashes('success'));
        self::assertSame('Оплачен', $this->text('[data-testid="order-status"]'));
        self::assertStringStartsWith("Оплачено 11\u{a0}980\u{a0}₽", $this->text('[data-testid="payment-paid"]'));
        self::assertCount(0, $this->client->getCrawler()->filter('[data-react-component="PaymentButtons"]'));
        self::assertTrue(Order::load($orderId)?->isPaid());
    }

    public function testCheckoutValidationAndStockConflict(): void
    {
        $user = $this->createUser();
        $productId = $this->createProduct('Последний', 1000, 1);
        $this->login($user);
        $this->postAction('cart.add', [], ['product_id' => $productId, 'quantity' => 1]);

        $this->get('/checkout/');
        $this->client->submitForm('Подтвердить заказ', ['customer_name' => '', 'customer_email' => 'nope', 'customer_phone' => 'x', 'address' => '']);
        $this->assertRedirect('/checkout/');
        $this->followRedirect();
        foreach (['customer_name', 'customer_email', 'customer_phone', 'address'] as $field) {
            self::assertCount(1, $this->client->getCrawler()->filter('[data-testid="error-'.$field.'"]'), $field);
        }

        // Пока покупатель заполнял форму, последний товар купили.
        $this->placeOrder($this->createUser(), [[$productId, 1]]);
        $this->client->submitForm('Подтвердить заказ', ['customer_name' => 'Иван', 'customer_email' => $user['email'], 'customer_phone' => '+7 900 000-00-00', 'address' => 'Москва']);
        $this->assertRedirect('/checkout/');
        $this->followRedirect();
        self::assertSame('Товара «Последний» осталось 0 шт. Уменьшите количество в корзине.', $this->text('[data-testid="checkout-error"]'));
    }

    public function testOrdersListFiltersAndForeignOrder(): void
    {
        $user = $this->createUser();
        $aero = $this->createProduct('Наушники Aero', 5990, 5);
        $book = $this->createProduct('Книга', 590, 5);
        $first = $this->placeOrder($user, [[$aero, 1]]);
        $second = $this->placeOrder($user, [[$book, 2]]);
        new PaymentService()->process($second, $user['id'], true);
        $declined = $this->placeOrder($user, [[$book, 1]]);
        new PaymentService()->process($declined, $user['id'], false);
        $canceled = $this->placeOrder($user, [[$book, 1]]);
        new OrderService()->cancel($canceled);
        $foreign = $this->placeOrder($this->createUser(), [[$aero, 1]]);

        $this->login($user);
        $crawler = $this->get('/personal/orders/');
        self::assertSame('Найдено: 4 заказа', $this->text('[data-testid="orders-total"]'));
        self::assertSame(['Отменён', 'Принят', 'Оплачен', 'Принят'], $this->texts($crawler, '[data-testid="order-status"]'));
        self::assertCount(1, $crawler->filter('[data-testid="payment-declined"]'));

        self::assertSame(['Заказ №'.$first], $this->rows($this->get('/personal/orders/?q=aero')));
        self::assertSame(['Заказ №'.$second], $this->rows($this->get('/personal/orders/?q='.$second)));
        self::assertSame(['Заказ №'.$second], $this->rows($this->get('/personal/orders/?status=PD')));
        self::assertSame(['Заказ №'.$canceled], $this->rows($this->get('/personal/orders/?status=canceled')));
        self::assertSame(['Заказ №'.$first, 'Заказ №'.$second, 'Заказ №'.$canceled, 'Заказ №'.$declined], $this->rows($this->get('/personal/orders/?sort=total&dir=desc')), 'По сумме, при равной сумме — новее выше.');
        self::assertSame([], $this->rows($this->get('/personal/orders/?date_from=2099-01-01')));
        self::assertSame(['Заказ №'.$canceled, 'Заказ №'.$declined, 'Заказ №'.$second, 'Заказ №'.$first], $this->rows($this->get('/personal/orders/?date_to=2099-01-01')));

        $this->get('/personal/orders/'.$foreign.'/');
        $this->assertRedirect('/personal/orders/');
        $this->post('/personal/orders/'.$foreign.'/', ['STUB_RESULT' => 'success']);
        self::assertFalse(Order::load($foreign)?->isPaid(), 'Чужой заказ не оплачивается.');
    }

    public function testProfileUpdateKeepsLoginInSyncWithEmail(): void
    {
        $user = $this->createUser('Иван');
        $this->login($user);
        $this->get('/personal/profile/');
        $newEmail = 'renamed-'.bin2hex(random_bytes(3)).'@example.test';
        $this->client->submitForm('Сохранить', ['NAME' => 'Иван', 'LAST_NAME' => 'Петров', 'EMAIL' => $newEmail, 'PERSONAL_PHONE' => '+7 900 111-22-33']);
        $this->assertStatus(200);
        self::assertSame(['Профиль сохранён.'], $this->flashes('success'));

        $row = UserTable::getList(['select' => ['LOGIN', 'EMAIL', 'LAST_NAME'], 'filter' => ['=ID' => $user['id']]])->fetch();
        self::assertSame(['LOGIN' => $newEmail, 'EMAIL' => $newEmail, 'LAST_NAME' => 'Петров'], $row);

        $this->client = $this->newClient();
        $this->login(['email' => $newEmail, 'password' => $user['password']]);
    }

    public function testCustomerHasNoAdminAccess(): void
    {
        $this->login($this->createUser());
        self::assertCount(0, $this->client->getCrawler()->filter('a[href="/bitrix/admin/"]'), 'В шапке нет ссылки на админку.');
        foreach (['/bitrix/admin/', '/bitrix/admin/sale_order.php?lang=ru', '/bitrix/admin/iblock_list_admin.php?IBLOCK_ID='.Config::iblockId().'&type=catalog&lang=ru'] as $path) {
            $this->get($path);
            self::assertTrue($this->isAdminAuthForm(), 'Покупателю закрыто: '.$path);
        }
    }

    /**
     * @return list<string>
     */
    private function rows(\Symfony\Component\DomCrawler\Crawler $crawler): array
    {
        return $this->texts($crawler, '[data-testid="order-row"] > span:first-child');
    }

    /**
     * @return list<int>
     */
    private function groupIds(string $email): array
    {
        $user = UserTable::getList(['select' => ['ID'], 'filter' => ['=EMAIL' => $email]])->fetch();
        self::assertIsArray($user);

        return array_values(array_map('intval', \CUser::GetUserGroup(Row::int($user, 'ID'))));
    }
}
