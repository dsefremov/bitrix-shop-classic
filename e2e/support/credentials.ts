import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import type { Page } from '@playwright/test';

/**
 * Учётные данные демо-пользователей. Пароли в репозитории не хранятся: их генерирует
 * cli/fixtures.php в var/demo-users.json (вне git). Можно переопределить через окружение:
 * E2E_<ROLE>_EMAIL / E2E_<ROLE>_PASSWORD (например, E2E_CUSTOMER_PASSWORD).
 */
export type Role = 'customer' | 'manager' | 'maria';
export interface Credentials {
    email: string;
    password: string;
}

export function credentials(role: Role): Credentials {
    const prefix = `E2E_${role.toUpperCase()}_`;
    const email = process.env[`${prefix}EMAIL`];
    const password = process.env[`${prefix}PASSWORD`];
    if (email && password) {
        return { email, password };
    }
    const file = resolve(process.env.DEMO_USERS_FILE ?? 'var/demo-users.json');
    const users = JSON.parse(readFileSync(file, 'utf8')) as Record<string, Credentials>;
    const user = users[role];
    if (!user) {
        throw new Error(`Нет пользователя «${role}» в ${file}: запустите cli/fixtures.php.`);
    }

    return user;
}

/** Вход через публичную форму /login/ (для менеджера тоже: сессия общая с /bitrix/admin). */
export async function login(page: Page, role: Role, back = '/'): Promise<void> {
    const user = credentials(role);
    await page.goto(`/login/?back=${encodeURIComponent(back)}`);
    await page.getByLabel('Эл. почта').fill(user.email);
    await page.getByLabel('Пароль', { exact: true }).fill(user.password);
    await page.getByRole('button', { name: 'Войти', exact: true }).click();
    await page.waitForURL((url) => !url.pathname.startsWith('/login'));
}
