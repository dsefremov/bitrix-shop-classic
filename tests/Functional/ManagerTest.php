<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Tests\Functional;

use Dsefremov\ShopClassic\Config;

/**
 * Менеджер магазина: работает только в штатной /bitrix/admin, группа «Менеджеры магазина»
 * имеет права на iblock/catalog/sale и не имеет доступа к пользователям и настройкам.
 */
final class ManagerTest extends WebTestCase
{
    public function testManagerSeesAdminLinkAndShopSections(): void
    {
        $productId = $this->createProduct('Наушники Aero', 5990, 5);
        $orderId = $this->placeOrder($this->createUser(), [[$productId, 1]]);
        $this->login($this->createManager());

        $this->get('/');
        self::assertSame('Админка', $this->text('a[href="/bitrix/admin/"]'));

        $iblock = Config::iblockId();
        $allowed = [
            '/bitrix/admin/?lang=ru' => 'Рабочий стол',
            '/bitrix/admin/sale_order.php?lang=ru' => 'Заказы',
            '/bitrix/admin/sale_order_view.php?ID='.$orderId.'&lang=ru' => 'Просмотр заказа ID ('.$orderId.')',
            '/bitrix/admin/iblock_list_admin.php?IBLOCK_ID='.$iblock.'&type=catalog&lang=ru' => 'Товары',
            '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID='.$iblock.'&type=catalog&ID='.$productId.'&lang=ru' => 'Наушники Aero',
        ];
        foreach ($allowed as $path => $needle) {
            $this->get($path);
            $this->assertStatus(200);
            self::assertFalse($this->isAdminAuthForm(), 'Менеджеру открыто: '.$path);
            self::assertStringContainsString($needle, (string) $this->client->getInternalResponse()->getContent(), $path);
        }
    }

    public function testManagerHasNoAccessToUsersAndSettings(): void
    {
        $this->login($this->createManager());
        foreach (['/bitrix/admin/user_admin.php?lang=ru', '/bitrix/admin/settings.php?lang=ru&mid=main'] as $path) {
            $this->get($path);
            $content = (string) $this->client->getInternalResponse()->getContent();
            self::assertTrue($this->isAdminAuthForm() || str_contains($content, 'Доступ запрещен'), 'Менеджеру закрыто: '.$path);
            self::assertStringNotContainsString('adm-list-table', $content, $path);
        }
    }
}
