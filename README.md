# Bitrix Shop Classic

Учебный интернет-магазин на **1С-Битрикс** — тот же магазин, что [bitrix-shop](https://github.com/dsefremov/bitrix-shop)
(v2), но сделанный «по-битриксовому»: на штатных модулях **iblock** (товары), **catalog** (цены и остатки)
и **sale** (корзина, заказы, оплаты, отгрузки), со штатной административной частью `/bitrix/admin`.
Свой код — только там, где штатного нет: оформление заказа, страница оплаты, список заказов покупателя.
Цены в рублях, интерфейс на русском.

## Что умеет

- **Каталог** — комплексный компонент `bitrix:catalog` со своими шаблонами: разделы инфоблока «Товары» = категории,
  карточка товара, умный фильтр `catalog.smart.filter` (цена, наличие), сортировка по названию, цене и новизне,
  12 товаров на страницу, поиск по названию и описанию. Символьные коды — штатная транслитерация инфоблока.
- **Корзина** — штатная корзина sale (FUSER): работает и у гостя, после входа переходит к покупателю.
  Количество ограничено остатком (`QUANTITY` торгового каталога). Вывод — `sale.basket.basket` со своим шаблоном.
- **Оформление** — свой компонент `dsefremov:sale.checkout` на Sale API: только для вошедших, одна транзакция,
  проверка остатка под блокировкой, тип плательщика «Физ. лицо», свойства ФИО / телефон / эл. почта / адрес,
  отгрузка «Самовывоз», оплата «Тестовая оплата». При создании заказа товар резервируется (штатный резерв catalog).
- **Оплата** — платёжная система «Тестовая оплата» (обработчик `ServiceHandler` в
  `local/php_interface/include/sale_payment/dsefremov_stub`, создаётся миграцией). На странице заказа — «Оплатить»
  и «Отказ»: оплата → `setPaid('Y')` и статус «Оплачен»; отказ → запись в историю заказа и отметка
  «Оплата отклонена», заказ не оплачен, резерв сохраняется, можно оплатить ещё раз.
- **Статусы** — «Принят» (AC), «Оплачен» (PD), «Выполнен» (CM); отмена — штатная `CANCELED=Y`,
  резерв возвращается на склад ровно один раз.
- **Личный кабинет** — «Мои заказы» (поиск по номеру и товару, статус, даты, сортировка, пагинация),
  страница заказа (`sale.personal.order.detail` со своим шаблоном + оплата), профиль (`main.profile`).
- **Администрирование** — только штатная `/bitrix/admin`. Группа «Менеджеры магазина» получает права только
  на iblock (модуль — чтение, инфоблок «Товары» — изменение), catalog (задача `catalog_price_edit`: цены и остатки)
  и sale (обработка заказов, все действия во всех статусах) — без пользователей и настроек; вход в админку —
  доступ к файлам `/bitrix/admin` на чтение. Демо-менеджер создаётся фикстурами.

## Версии

| Что | Версия | Комментарий |
|---|---|---|
| Редакция | 1С-Битрикс **«Малый бизнес»**, пробная (30 дней) | ставится официальным `bitrixsetup.php` (`small_business`) без ключа |
| main | **26.150.0** | |
| iblock | **25.300.0** | |
| catalog | **25.550.0** | |
| sale | **26.0.0** | |
| PHP | **8.4.28** (FPM) | запасной вариант 8.3 не понадобился |
| MySQL | **8.4.13** (LTS) | запасной вариант 8.0 не понадобился |
| Фронтенд | Vite 7, React 19, TypeScript 5, Tailwind CSS 4 | React-острова — только те, что были в bitrix-shop |
| Тесты | PHPUnit 12.5, Symfony BrowserKit 7.4, Playwright 1.65 | |
| Статпроверки | PHPStan 2.3 (level max), php-cs-fixer 3 (`@Symfony`), tsc, ESLint, Prettier | |

**Ядро в репозиторий не входит** (лицензия, размер): `/bitrix`, `/upload`, публичные файлы установки, файлы
с доступами (`.settings*.php`), `var/`, дампы, `bitrixsetup.php`, `vendor`, `node_modules` перечислены в `.gitignore`.

## Запуск (Docker Compose)

Нужны Docker с Compose v2, Python 3 и `curl`. Порты по умолчанию: сайт **8096**, тот же сайт на тестовой базе
**8097**, MySQL **33076**, PHP-FPM **9078** (переменные `HTTP_PORT`, `TEST_HTTP_PORT`, `DB_PORT`, `FPM_PORT`).

```bash
docker/setup.sh          # или DC="sudo docker compose" docker/setup.sh
```

Скрипт с нуля: генерирует пароли в `var/` → поднимает контейнеры → ставит зависимости и собирает фронтенд →
скачивает ядро через `bitrixsetup.php` и проходит мастер установки → ставит модуль `dsefremov.shopclassic`
(`cli/install.php`: инфоблок, торговый каталог, sale, права, публичные страницы) → загружает демо-данные
(`cli/fixtures.php`) → готовит тестовую базу. Повторный запуск безопасен (миграции идемпотентны).

**Паролей в репозитории нет — ни рабочих, ни учебных.** Все они генерируются при установке и лежат вне git:

| Что | Файл |
|---|---|
| Пароли MySQL (root и пользователь `shop`) | `var/secrets/db-root-password`, `var/secrets/db-password` |
| Администратор ядра (`/bitrix/admin`, полный доступ) | `var/bx-admin-password` (логин `bxadmin`) |
| Демо-менеджер и демо-покупатели | `var/demo-users.json` |

Сайт: http://127.0.0.1:8096, администрирование: http://127.0.0.1:8096/bitrix/admin/.

## Проверки

```bash
docker/round.sh          # полный раунд: всё ниже по порядку, любой сбой — раунд красный
```

| Проверка | Команда |
|---|---|
| Ядра и паролей нет в git | `docker/check-repo.sh` |
| Стиль PHP | `docker compose exec php composer cs:check` |
| PHPStan level max (по стабам `local/stubs`) | `docker compose exec php composer stan` |
| TypeScript, ESLint, Prettier | `npm run types:check`, `npm run lint:check`, `npm run format:check` |
| Сборка Vite | `npm run build` |
| PHPUnit (unit, service, functional) | `docker/test-db.sh && docker compose exec php composer test` |
| Playwright e2e (покупка, отказ и повторная оплата) | `php …/cli/fixtures.php` и `npx playwright test --project=e2e` |
| Скриншоты 01–13 (1440 и 390) | `npx playwright test --project=screenshots` → `docs/screenshots` |

Тесты и e2e берут пароли демо-пользователей из `var/demo-users.json` (или из переменных `E2E_<РОЛЬ>_EMAIL` /
`E2E_<РОЛЬ>_PASSWORD`). Что именно запускалось и с каким результатом — [docs/testing.md](docs/testing.md).

## CI

`.github/workflows/ci.yml` — только на `pull_request`. Ядра в CI нет, поэтому там статические проверки и сборка:
`docker/check-repo.sh` (нет ядра, файлов с доступами и литералов паролей), gitleaks по всей истории,
`composer validate`, php-cs-fixer, PHPStan (level max по стабам main / iblock / catalog / sale), tsc, ESLint,
Prettier, сборка Vite. PHPUnit и Playwright выполняются локально на стенде Docker.

## Структура

| Что | Где |
|---|---|
| Модуль | `local/modules/dsefremov.shopclassic`: `install/index.php`, идемпотентные миграции `lib/Install/Migration` (iblock+catalog, sale, права, события), публичные страницы `install/public`, `cli/install.php`, `cli/fixtures.php` |
| Сервисы | `lib/Service`: корзина (поверх `Sale\Basket`), оформление, оплата, статусы, список заказов, параметры каталога |
| Действия форм | `lib/Controller` (`Engine\Controller`): корзина, вход / регистрация / выход — POST, CSRF `sessid`, PRG |
| Платёжная заглушка | `local/php_interface/include/sale_payment/dsefremov_stub` |
| Свои компоненты | `local/components/dsefremov`: `sale.checkout`, `sale.order.payment`, `sale.order.list`, `auth` |
| Шаблон сайта и шаблоны штатных компонентов | `local/templates/shop` (`components/bitrix/catalog`, `catalog.section`, `catalog.element`, `catalog.smart.filter`, `sale.basket.basket`, `sale.personal.order.detail`, `main.profile`, …) |
| React-острова | `local/js/dsefremov.shopclassic/src` → `dist/app.{js,css}` (Vite) |
| Стабы ядра для PHPStan | `local/stubs/{main,iblock,catalog,sale}.stub.php` |
| Тесты | `tests/Unit`, `tests/Service`, `tests/Functional` (PHPUnit + BrowserKit), `e2e/` (Playwright) |
| Стенд | `compose.yaml`, `docker/` (`setup.sh`, `round.sh`, `test-db.sh`, `check-repo.sh`, nginx, PHP) |

## Отличия от bitrix-shop

| | bitrix-shop | bitrix-shop-classic |
|---|---|---|
| Редакция | «Старт», только main | «Малый бизнес»: main + iblock + catalog + sale |
| Товары и категории | свои таблицы D7 ORM | инфоблок «Товары» (тип catalog), разделы = категории |
| Цены и остатки | поля своих таблиц | торговый каталог: тип цены BASE (RUB), `QUANTITY`, штатный резерв |
| Корзина | в сессии | штатная корзина sale (FUSER), доступна гостю |
| Заказ, оплата, статусы | свои таблицы, свой шлюз оплаты, попытки в своей таблице | `Sale\Order`, отгрузка «Самовывоз», платёжная система с `ServiceHandler`, история заказа sale |
| Возврат остатка | при статусе «Отменён», `STOCK_RETURNED_AT` | штатная отмена `CANCELED=Y`, снятие резерва ядром (ровно один раз) |
| Админка | своя `/admin` (товары, заказы) | штатная `/bitrix/admin`, группа «Менеджеры магазина» |
| Публичная часть | свои компоненты | штатные компоненты со своими шаблонами; свои — только оформление, оплата, список заказов, вход |
| Отказ в оплате | статус оплаты в своей таблице | отметка заказа «Оплата отклонена» (`MARKED`) + запись в истории |

**От Laravel Shop и Symphony Shop** (кратко): там модель данных, корзина, заказы и админка написаны целиком
на фреймворке; здесь почти всё это даёт платформа (инфоблоки, торговый каталог, интернет-магазин, штатная админка),
а свой код сведён к шаблонам компонентов и нескольким сервисам поверх Sale API.

## Скриншоты

`docs/screenshots/NN-*-{1440,390}.png`: 01 каталог, 02 товар, 03 корзина, 04 оформление, 05 оплата, 06 мои заказы,
07–09 товары / форма товара / заказы в штатной `/bitrix/admin`, 10 каталог с фильтрами, 11 заказы с фильтрами,
12–13 списки товаров и заказов `/bitrix/admin` с фильтрами.
