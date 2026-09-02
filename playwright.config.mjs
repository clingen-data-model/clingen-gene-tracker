import { defineConfig, devices } from '@playwright/test'

const baseURL = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8013'
const e2eEnvironment = {
    ...process.env,
    APP_ENV: 'testing',
    APP_URL: baseURL,
    DB_CONNECTION: 'testing',
    DB_DATABASE_TEST: 'genetracker_e2e',
    SESSION_DRIVER: 'file',
    SESSION_COOKIE: 'genetracker_e2e_session',
    CACHE_DRIVER: 'array',
    QUEUE_CONNECTION: 'sync',
    DX_ENABLE_PUSH: 'false',
}

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    timeout: 30_000,
    expect: {
        timeout: 5_000,
    },
    retries: process.env.CI ? 1 : 0,
    reporter: 'list',
    use: {
        baseURL,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    webServer: process.env.PLAYWRIGHT_EXTERNAL_SERVER ? undefined : {
        command: 'php -S 127.0.0.1:8013 -t public scripts/e2e-server.php',
        url: `${baseURL}/login`,
        env: e2eEnvironment,
        reuseExistingServer: false,
        timeout: 120_000,
    },
    projects: [
        {
            name: 'setup',
            testMatch: /auth\.setup\.js/,
        },
        {
            name: 'chromium',
            use: {
                ...devices['Desktop Chrome'],
                storageState: 'playwright/.auth/user.json',
            },
            dependencies: ['setup'],
        },
    ],
})
