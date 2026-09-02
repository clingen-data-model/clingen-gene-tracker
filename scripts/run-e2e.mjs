import { spawn } from 'node:child_process'
import { createRequire } from 'node:module'

const require = createRequire(import.meta.url)
const playwrightCli = require.resolve('@playwright/test/cli')

const baseURL = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8013'
const environment = {
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

const server = spawn(
    'php',
    ['-S', '127.0.0.1:8013', '-t', 'public', 'scripts/e2e-server.php'],
    { env: environment, stdio: ['ignore', 'inherit', 'inherit'] },
)

let cleanedUp = false

async function stopServer() {
    if (cleanedUp || server.exitCode !== null) {
        return
    }
    cleanedUp = true

    if (process.platform === 'win32') {
        await new Promise(resolve => {
            const taskkill = spawn('taskkill', ['/pid', String(server.pid), '/T', '/F'], {
                stdio: 'ignore',
            })
            taskkill.on('exit', resolve)
            taskkill.on('error', resolve)
        })
        return
    }

    server.kill('SIGTERM')
}

async function waitForServer() {
    const deadline = Date.now() + 120_000
    while (Date.now() < deadline) {
        if (server.exitCode !== null) {
            throw new Error(`E2E server exited with code ${server.exitCode}`)
        }
        try {
            const response = await fetch(`${baseURL}/login`, { redirect: 'manual' })
            if (response.status < 500) {
                return
            }
        } catch {}
        await new Promise(resolve => setTimeout(resolve, 250))
    }
    throw new Error(`E2E server did not become ready at ${baseURL}`)
}

process.once('SIGINT', async () => {
    await stopServer()
    process.exit(130)
})
process.once('SIGTERM', async () => {
    await stopServer()
    process.exit(143)
})

let exitCode = 1
try {
    await waitForServer()
    exitCode = await new Promise((resolve, reject) => {
        const playwright = spawn(process.execPath, [playwrightCli, 'test'], {
            env: {
                ...environment,
                PLAYWRIGHT_EXTERNAL_SERVER: '1',
            },
            stdio: 'inherit',
        })
        playwright.on('exit', code => resolve(code ?? 1))
        playwright.on('error', reject)
    })
} finally {
    await stopServer()
}

process.exit(exitCode)
