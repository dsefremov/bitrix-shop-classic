<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Fixtures;

use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use Bitrix\Sale\Fuser;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Entity\Row;
use Dsefremov\ShopClassic\Service\BasketService;
use Dsefremov\ShopClassic\Service\CheckoutData;
use Dsefremov\ShopClassic\Service\CheckoutService;
use Dsefremov\ShopClassic\Service\OrderService;
use Dsefremov\ShopClassic\Service\PaymentService;
use Dsefremov\ShopClassic\Service\Slug;

/**
 * Демо-данные на штатных модулях: 4 раздела инфоблока «Товары» × 6 товаров (цена базового типа,
 * остаток торгового каталога, картинки), менеджер магазина и два покупателя, 5 заказов sale
 * (оформлены через CheckoutService, оплачены/отклонены через «Тестовую оплату»).
 * Пароли демо-учёток генерируются и хранятся в var/demo-users.json (Credentials).
 */
final class DemoData
{
    public const string EMAIL_DOMAIN = 'bitrix-shop-classic.test';

    public function __construct(
        private readonly CatalogWriter $catalog = new CatalogWriter(),
    ) {
    }

    public const array CATALOG = [
        'Электроника' => [
            'color' => [37, 99, 235],
            'description' => 'Гаджеты и аксессуары для дома и работы.',
            'products' => [
                ['Беспроводные наушники Aero', 5990, 15],
                ['Смарт-часы Pulse 2', 12490, 8],
                ['Портативная колонка Wave', 4290, 20],
                ['Внешний аккумулятор 20 000 мА·ч', 2790, 30],
                ['Механическая клавиатура K87', 6990, 6],
                ['Веб-камера Full HD', 3490, 0],
            ],
        ],
        'Книги' => [
            'color' => [180, 83, 9],
            'description' => 'Художественная и профессиональная литература.',
            'products' => [
                ['«Чистый код», Роберт Мартин', 1290, 12],
                ['«Мастер и Маргарита», М. Булгаков', 690, 25],
                ['«1С-Битрикс: быстрый старт»', 1590, 10],
                ['«Война и мир», Л. Толстой (2 тома)', 1890, 7],
                ['«Алгоритмы. Построение и анализ»', 3490, 4],
                ['«Сто лет одиночества», Г. Маркес', 790, 0],
            ],
        ],
        'Дом и кухня' => [
            'color' => [5, 150, 105],
            'description' => 'Посуда, текстиль и полезные мелочи.',
            'products' => [
                ['Френч-пресс 1 л', 1490, 18],
                ['Набор ножей «Шеф»', 4990, 5],
                ['Чугунная сковорода 26 см', 3290, 9],
                ['Электрочайник Glass', 2490, 14],
                ['Плед флисовый 150×200', 1790, 22],
                ['Набор контейнеров, 6 шт.', 990, 40],
            ],
        ],
        'Спорт' => [
            'color' => [190, 24, 93],
            'description' => 'Инвентарь для тренировок дома и на улице.',
            'products' => [
                ['Коврик для йоги 6 мм', 1990, 16],
                ['Гантели 2 × 5 кг', 3990, 6],
                ['Скакалка скоростная', 590, 35],
                ['Фитнес-резинки, 5 шт.', 890, 28],
                ['Бутылка для воды 0,7 л', 690, 50],
                ['Рюкзак спортивный 25 л', 2990, 0],
            ],
        ],
    ];

    /** @var list<array{key: string, name: string, email: string, manager: bool}> */
    public const array USERS = [
        ['key' => 'manager', 'name' => 'Менеджер магазина', 'email' => 'manager@bitrix-shop-classic.test', 'manager' => true],
        ['key' => 'customer', 'name' => 'Иван Петров', 'email' => 'customer@bitrix-shop-classic.test', 'manager' => false],
        ['key' => 'maria', 'name' => 'Мария Соколова', 'email' => 'maria@bitrix-shop-classic.test', 'manager' => false],
    ];

    public function load(): void
    {
        $this->purge();

        /** @var list<int> $products */
        $products = [];
        $tmp = (string) tempnam(sys_get_temp_dir(), 'seed');
        $sort = 100;
        foreach (self::CATALOG as $categoryName => $data) {
            $code = Slug::make($categoryName);
            file_put_contents($tmp, SeedImage::png($data['color'], crc32($code), 400));
            $sectionId = $this->catalog->section($categoryName, $code, $data['description'], $tmp, $sort);
            $sort += 100;

            foreach ($data['products'] as [$name, $priceRub, $stock]) {
                $productCode = Slug::make($name);
                file_put_contents($tmp, SeedImage::png($data['color'], crc32($productCode)));
                $products[] = $this->catalog->product(
                    $sectionId,
                    $name,
                    $productCode,
                    $priceRub,
                    $stock,
                    "{$name} — товар из раздела «{$categoryName}».",
                    "{$name} — товар из раздела «{$categoryName}». Демо-описание для локальной базы: характеристики, комплектация и условия гарантии указываются здесь.",
                    $tmp,
                );
            }
        }
        @unlink($tmp);

        $ids = [];
        foreach (self::USERS as $user) {
            $ids[$user['key']] = $this->user($user['key'], $user['name'], $user['email'], $user['manager']);
        }
        $customer = ['id' => $ids['customer'], 'name' => 'Иван Петров', 'email' => 'customer@bitrix-shop-classic.test'];
        $other = ['id' => $ids['maria'], 'name' => 'Мария Соколова', 'email' => 'maria@bitrix-shop-classic.test'];

        $this->order($customer, [[$products[0], 1], [$products[8], 2]], 'completed', '-10 days');
        $this->order($customer, [[$products[13], 1]], 'paid', '-3 days');
        $this->order($customer, [[$products[19], 1], [$products[22], 2]], 'accepted', '-1 day');
        $this->order($other, [[$products[3], 1]], 'paid', '-2 days');
        $this->order($other, [[$products[15], 1]], 'declined', '-5 hours');
    }

    public function purge(): void
    {
        $this->catalog->purge();
    }

    private function user(string $key, string $name, string $email, bool $manager): int
    {
        $groups = [2];
        if ($manager) {
            $groups[] = Config::managerGroupId();
        }
        $password = Credentials::password($key, $email);
        $fields = [
            'LOGIN' => $email, 'EMAIL' => $email, 'NAME' => $name, 'LAST_NAME' => '', 'ACTIVE' => 'Y', 'LID' => Config::SITE_ID,
            'PASSWORD' => $password, 'CONFIRM_PASSWORD' => $password, 'GROUP_ID' => $groups,
        ];
        $user = new \CUser();
        $row = UserTable::getList(['select' => ['ID'], 'filter' => ['=LOGIN' => $email]])->fetch();
        if (\is_array($row)) {
            $id = Row::int($row, 'ID');
            if (!$user->Update($id, $fields)) {
                throw new \RuntimeException('Пользователь '.$email.': '.$user->LAST_ERROR);
            }

            return $id;
        }
        $id = $user->Add($fields);
        if (!is_numeric($id) || (int) $id <= 0) {
            throw new \RuntimeException('Пользователь '.$email.': '.$user->LAST_ERROR);
        }

        return (int) $id;
    }

    /**
     * Заказ через CheckoutService из отдельной корзины (FUSER пользователя), затем оплата
     * «Тестовой оплатой» и статус; даты заказа сдвигаются в прошлое.
     *
     * @param array{id: int, name: string, email: string} $user
     * @param list<array{0: int, 1: int}>                 $lines
     * @param 'accepted'|'paid'|'completed'|'declined'    $state
     */
    private function order(array $user, array $lines, string $state, string $when): void
    {
        $fuserId = (int) Fuser::getIdByUserId($user['id']);
        $basketService = new BasketService(fuserId: $fuserId);
        foreach ($lines as [$productId, $quantity]) {
            CheckoutService::check($basketService->add($productId, $quantity));
        }
        $orderId = new CheckoutService()->place($user['id'], new CheckoutData(
            $user['name'],
            $user['email'],
            '+7 900 123-45-67',
            'г. Москва, ул. Тверская, д. 1, кв. 10',
            null,
        ), $basketService);

        $payments = new PaymentService();
        if ('declined' === $state) {
            $payments->process($orderId, $user['id'], false);
        } elseif ('accepted' !== $state) {
            $payments->process($orderId, $user['id'], true);
        }
        if ('completed' === $state) {
            CheckoutService::check(new OrderService()->setStatus($orderId, Config::STATUS_COMPLETED));
        }

        $date = DateTime::createFromTimestamp((int) strtotime($when));
        $connection = Application::getConnection();
        $sql = $connection->getSqlHelper();
        $connection->queryExecute(\sprintf(
            'UPDATE b_sale_order SET DATE_INSERT = %1$s, DATE_UPDATE = %1$s, DATE_STATUS = %1$s WHERE ID = %2$d',
            $sql->convertToDbDateTime($date),
            $orderId,
        ));
        if ('accepted' !== $state && 'declined' !== $state) {
            $connection->queryExecute(\sprintf('UPDATE b_sale_order SET DATE_PAYED = %s WHERE ID = %d', $sql->convertToDbDateTime($date), $orderId));
        }
    }
}
