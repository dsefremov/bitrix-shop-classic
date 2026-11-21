<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Tests\Support;

use Bitrix\Sale\Fuser;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Fixtures\CatalogWriter;
use Dsefremov\ShopClassic\Service\BasketService;
use Dsefremov\ShopClassic\Service\CheckoutData;
use Dsefremov\ShopClassic\Service\CheckoutService;
use Dsefremov\ShopClassic\Service\StockService;

/**
 * Фабрики тестовых данных: пишут в тестовую базу штатными API (CUser, инфоблок + торговый каталог,
 * корзина и заказ sale через сервисы модуля). Пароли тестовых пользователей генерируются случайно.
 *
 * @phpstan-require-extends \PHPUnit\Framework\TestCase
 */
trait Factories
{
    private int $seq = 0;

    /**
     * @return array{id: int, name: string, email: string, password: string}
     */
    protected function createUser(string $name = 'Покупатель', ?string $email = null, bool $manager = false): array
    {
        $email ??= 'user'.(++$this->seq).'-'.bin2hex(random_bytes(3)).'@example.test';
        $password = bin2hex(random_bytes(12));
        $groups = [2];
        if ($manager) {
            $groups[] = Config::managerGroupId();
        }
        $user = new \CUser();
        $id = $user->Add([
            'LOGIN' => $email, 'EMAIL' => $email, 'NAME' => $name, 'ACTIVE' => 'Y', 'LID' => Config::SITE_ID,
            'PASSWORD' => $password, 'CONFIRM_PASSWORD' => $password, 'GROUP_ID' => $groups,
        ]);
        self::assertTrue(is_numeric($id) && (int) $id > 0, 'Пользователь не создан: '.$user->LAST_ERROR);

        return ['id' => (int) $id, 'name' => $name, 'email' => $email, 'password' => $password];
    }

    /**
     * @return array{id: int, name: string, email: string, password: string}
     */
    protected function createManager(): array
    {
        return $this->createUser('Менеджер', null, true);
    }

    protected function createSection(string $name = 'Раздел'): int
    {
        return new CatalogWriter()->section($name, 'section-'.(++$this->seq).'-'.bin2hex(random_bytes(2)));
    }

    /**
     * @param array<string, mixed> $fields
     */
    protected function createProduct(string $name = 'Товар', int $priceRub = 1000, int $stock = 10, ?int $sectionId = null, string $detail = '', array $fields = []): int
    {
        $sectionId ??= $this->createSection();

        return new CatalogWriter()->product($sectionId, $name, 'product-'.(++$this->seq).'-'.bin2hex(random_bytes(2)), $priceRub, $stock, '', $detail, null, $fields);
    }

    protected function basketOf(int $userId): BasketService
    {
        return new BasketService(fuserId: (int) Fuser::getIdByUserId($userId));
    }

    /**
     * Заказ покупателя через корзину и CheckoutService.
     *
     * @param array{id: int, name: string, email: string} $user
     * @param list<array{0: int, 1: int}>                 $lines [ID товара, количество]
     */
    protected function placeOrder(array $user, array $lines): int
    {
        $basket = $this->basketOf($user['id']);
        foreach ($lines as [$productId, $quantity]) {
            CheckoutService::check($basket->add($productId, $quantity));
        }

        return new CheckoutService()->place($user['id'], $this->checkoutData($user), $basket);
    }

    /**
     * @param array{name: string, email: string} $user
     */
    protected function checkoutData(array $user): CheckoutData
    {
        return new CheckoutData($user['name'], $user['email'], '+7 900 123-45-67', 'г. Москва, ул. Тверская, д. 1', null);
    }

    /**
     * @return array{quantity: int, reserved: int}
     */
    protected function stock(int $productId): array
    {
        return new StockService()->get([$productId])[$productId] ?? ['quantity' => 0, 'reserved' => 0];
    }
}
