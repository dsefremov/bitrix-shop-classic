import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';
import { login } from './support/credentials';

/**
 * Скриншоты главных экранов на свежих фикстурах (php local/modules/dsefremov.shopclassic/cli/fixtures.php)
 * в 1440 и 390 — тот же набор 01–13, что в bitrix-shop. Экраны 07–09 и 12–13 — штатная
 * административная часть /bitrix/admin под демо-менеджером (группа «Менеджеры магазина»).
 * Запуск: npx playwright test --project=screenshots
 * Для публичной части проверяется отсутствие горизонтального переполнения; штатная админка
 * Битрикса рассчитана на десктоп и на 390 прокручивается по горизонтали — для неё проверки нет.
 */
const OUT = 'docs/screenshots';

async function shot(page: Page, name: string, width: number, checkOverflow = true) {
    if (checkOverflow) {
        const overflow = await page.evaluate(
            () => document.documentElement.scrollWidth - window.innerWidth,
        );
        expect(overflow, `горизонтальное переполнение на «${name}» @${width}`).toBeLessThanOrEqual(
            0,
        );
    }
    await page.screenshot({ path: `${OUT}/${name}-${width}.png`, fullPage: true });
}

/** Дата по Москве со сдвигом в днях, формат YYYY-MM-DD. */
function moscowDate(offsetDays: number): string {
    const d = new Date(Date.now() + offsetDays * 86_400_000);

    return d.toLocaleDateString('sv-SE', { timeZone: 'Europe/Moscow' });
}

/** Корзина покупателя хранится в базе (FUSER), поэтому перед сценарием она очищается. */
async function emptyCart(page: Page) {
    await page.goto('/cart/');
    const remove = page.getByRole('button', { name: /^Удалить:/ });
    while ((await remove.count()) > 0) {
        const before = await remove.count();
        await remove.first().click();
        await expect(remove).toHaveCount(before - 1);
    }
}

const ADMIN = '/bitrix/admin';
const PRODUCTS = `${ADMIN}/iblock_list_admin.php?IBLOCK_ID=1&type=catalog&lang=ru`;
const ORDERS = `${ADMIN}/sale_order.php?lang=ru`;

for (const width of [1440, 390]) {
    test.describe(`экраны @${width}`, () => {
        test.use({ viewport: { width, height: width === 390 ? 844 : 900 } });

        test(`покупатель @${width}`, async ({ page }) => {
            await login(page, 'customer');
            await emptyCart(page);

            await page.goto('/');
            await expect(page.getByTestId('product-card')).toHaveCount(12);
            await expect(page.getByTestId('catalog-search')).toBeVisible();
            await shot(page, '01-catalog', width);

            await page.goto('/products/besprovodnye-naushniki-aero/');
            await expect(page.getByRole('heading', { level: 1 })).toHaveText(
                'Беспроводные наушники Aero',
            );
            await expect(page.getByTestId('add-to-cart')).toBeVisible();
            await shot(page, '02-product', width);

            await page.getByTestId('add-to-cart').click();
            await expect(page.getByTestId('cart-count')).toHaveText('1');
            await page.goto('/products/french-press-1-l/');
            await page.getByLabel('Количество').fill('2');
            await page.getByTestId('add-to-cart').click();
            await expect(page.getByTestId('cart-count')).toHaveText('3');

            await page.getByTestId('cart-link').click();
            await expect(page.getByTestId('cart-line')).toHaveCount(2);
            await expect(page.getByTestId('cart-qty')).toHaveText(['1', '2']);
            await shot(page, '03-cart', width);

            await page.getByRole('link', { name: 'Оформить заказ' }).click();
            await expect(
                page.getByRole('heading', { name: 'Оформление заказа', level: 1 }),
            ).toBeVisible();
            await page.getByLabel('Телефон').fill('+7 900 123-45-67');
            await page.getByLabel('Адрес доставки').fill('г. Москва, ул. Тверская, д. 1, кв. 10');
            await shot(page, '04-checkout', width);

            await page.goto('/personal/orders/');
            await expect(page.getByTestId('order-row')).toHaveCount(3);
            await expect(page.getByTestId('orders-search')).toBeVisible();
            await shot(page, '06-account-orders', width);

            await page.getByTestId('order-row').filter({ hasText: 'Принят' }).click();
            await expect(page.getByTestId('pay-button')).toBeVisible();
            await expect(page.getByTestId('decline-button')).toBeVisible();
            await shot(page, '05-payment', width);

            // Экраны с активными фильтрами (раздел, наличие, сортировка из URL).
            await page.goto('/catalog/sport/?available=1&sort=price&dir=desc');
            await expect(page.getByRole('heading', { name: 'Спорт', level: 1 })).toBeVisible();
            await expect(page.getByTestId('product-card')).toHaveCount(5);
            await expect(page.getByTestId('catalog-sort')).toHaveValue('price:desc');
            await shot(page, '10-catalog-filtered', width);

            await page.goto(`/personal/orders/?date_from=${moscowDate(-5)}&sort=total&dir=desc`);
            await expect(page.getByTestId('order-row')).toHaveCount(2);
            await expect(page.getByTestId('orders-sort')).toHaveValue('total:desc');
            await shot(page, '11-account-orders-filtered', width);
        });

        test(`менеджер в /bitrix/admin @${width}`, async ({ page }) => {
            await login(page, 'manager');
            const rows = page.locator('.main-grid-row-body:not(.main-grid-not-count)');

            // Фильтры списков штатной админки сохраняются в настройках пользователя — сбрасываем явно.
            await page.goto(`${PRODUCTS}&apply_filter=Y&clear_filter=Y`);
            await expect(page.getByRole('heading', { name: 'Товары', level: 1 })).toBeVisible();
            await expect(rows).toHaveCount(20);
            await shot(page, '07-admin-products', width, false);

            await page.goto(`${PRODUCTS}&apply_filter=Y&NAME=Aero`);
            await expect(rows).toHaveCount(1);
            await page.getByRole('link', { name: 'Беспроводные наушники Aero' }).click();
            await expect(page.locator('#tr_NAME input')).toHaveValue('Беспроводные наушники Aero');
            await shot(page, '08-admin-product-form', width, false);

            await page.goto(`${ORDERS}&del_filter=Y`);
            await expect(page.getByRole('heading', { name: 'Заказы', level: 1 })).toBeVisible();
            await expect(page.locator('tr.adm-list-table-row')).toHaveCount(5);
            await shot(page, '09-admin-orders', width, false);

            await page.goto(`${PRODUCTS}&apply_filter=Y&NAME=${encodeURIComponent('Набор')}`);
            await expect(rows).toHaveCount(2);
            await expect(page.getByText('Название: Набор')).toBeVisible();
            await shot(page, '12-admin-products-filtered', width, false);

            await page.goto(`${ORDERS}&set_filter=Y&filter_status%5B%5D=PD&by=PRICE&order=desc`);
            await expect(page.locator('tr.adm-list-table-row')).toHaveCount(2);
            await shot(page, '13-admin-orders-filtered', width, false);

            await page.goto(`${PRODUCTS}&apply_filter=Y&clear_filter=Y`);
            await page.goto(`${ORDERS}&del_filter=Y`);
        });
    });
}
