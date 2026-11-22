import { defineConfig, devices } from '@playwright/test';

/**
 * E2E и скриншоты запускаются против локального стенда (docker compose, nginx на :8096)
 * на свежих фикстурах (cli/fixtures.php модуля dsefremov.shopclassic). Без ретраев, 1 воркер.
 */
export default defineConfig({
    testDir: './e2e',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    reporter: [['list']],
    use: {
        baseURL: process.env.BASE_URL ?? 'http://127.0.0.1:8096',
        locale: 'ru-RU',
        timezoneId: 'Europe/Moscow',
        trace: 'retain-on-failure',
    },
    projects: [
        {
            name: 'e2e',
            testMatch: /purchase\.spec\.ts/,
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'screenshots',
            testMatch: /screenshots\.spec\.ts/,
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
