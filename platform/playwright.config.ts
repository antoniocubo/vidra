import { defineConfig, devices } from '@playwright/test';

const porta = 8124;

export default defineConfig({
    testDir: 'tests/e2e',
    timeout: 180_000,
    expect: { timeout: 15_000 },
    workers: 1,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL: `http://localhost:${porta}`,
        locale: 'pt-BR',
        timezoneId: 'America/Sao_Paulo',
        trace: 'retain-on-failure',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    webServer: {
        // APP_ENV=e2e faz o Laravel ler o .env.e2e em vez do .env.
        command: `php artisan serve --port=${porta}`,
        env: { APP_ENV: 'e2e' },
        url: `http://localhost:${porta}/up`,
        reuseExistingServer: false,
        timeout: 60_000,
    },
});
