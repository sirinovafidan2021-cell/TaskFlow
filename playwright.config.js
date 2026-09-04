import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: 'list',
    use: {
        baseURL: 'http://127.0.0.1:4173',
        trace: 'off',
        screenshot: 'only-on-failure',
        video: 'off',
    },
    projects: [
        { name: 'desktop', use: { ...devices['Desktop Chrome'] } },
        { name: 'mobile', use: { ...devices['iPhone 13'], browserName: 'chromium' } },
    ],
    webServer: {
        command: 'APP_ENV=testing APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= LOG_CHANNEL=errorlog DB_CONNECTION=sqlite DB_DATABASE=/tmp/taskflow-e2e.sqlite CACHE_STORE=array SESSION_DRIVER=file SESSION_PATH=/tmp/taskflow-e2e-sessions MAIL_MAILER=array QUEUE_CONNECTION=sync FILESYSTEM_DISK=local LARAVEL_STORAGE_PATH=/tmp/taskflow-e2e-storage VIEW_COMPILED_PATH=/tmp APP_PACKAGES_CACHE=/tmp/taskflow-e2e-packages.php APP_SERVICES_CACHE=/tmp/taskflow-e2e-services.php APP_CONFIG_CACHE=/tmp/taskflow-e2e-config.php APP_ROUTES_CACHE=/tmp/taskflow-e2e-routes.php APP_EVENTS_CACHE=/tmp/taskflow-e2e-events.php php artisan serve --host=127.0.0.1 --port=4173',
        url: 'http://127.0.0.1:4173/up',
        reuseExistingServer: false,
        timeout: 30_000,
    },
});
