import { expect, test } from '@playwright/test'

test.describe('application startup', () => {
    test('unauthenticated access redirects to the real login page', async ({ browser }) => {
        const context = await browser.newContext({ storageState: { cookies: [], origins: [] } })
        const page = await context.newPage()

        await page.goto('/home')

        await expect(page).toHaveURL(/\/login(?:#\/)?$/)
        await expect(page.getByRole('button', { name: 'Login' })).toBeVisible()
        await context.close()
    })

    test('reuses authentication and mounts the Vue application without startup failures', async ({ page, baseURL }) => {
        const consoleErrors = []
        const pageErrors = []
        const failedRequests = []
        const failedResponses = []
        const applicationOrigin = new URL(baseURL).origin

        page.on('console', message => {
            const location = message.location().url
            const isApplicationMessage = !location || new URL(location).origin === applicationOrigin
            if (message.type() === 'error' && isApplicationMessage) {
                consoleErrors.push(message.text())
            }
        })
        page.on('pageerror', error => {
            pageErrors.push(error.message)
        })
        page.on('requestfailed', request => {
            if (new URL(request.url()).origin === applicationOrigin) {
                failedRequests.push(`${request.method()} ${request.url()}: ${request.failure()?.errorText}`)
            }
        })
        page.on('response', response => {
            if (new URL(response.url()).origin === applicationOrigin && response.status() >= 400) {
                failedResponses.push(`${response.status()} ${response.url()}`)
            }
        })

        await page.goto('/home')
        await page.waitForLoadState('networkidle')

        await expect(page).toHaveURL(/\/home(?:#\/)?$/)
        await expect(page.locator('#app .clingen-app-container')).toBeVisible()
        await expect(page.getByRole('link', { name: 'Dashboard' })).toBeVisible()
        await expect(page.getByRole('link', { name: 'Curations', exact: true })).toBeVisible()
        await expect(page.getByRole('link', { name: 'Working Groups' })).toBeVisible()
        await expect(page.getByText('Dashboard: Your Curations')).toBeVisible()

        expect(consoleErrors).toEqual([])
        expect(pageErrors).toEqual([])
        expect(failedRequests).toEqual([])
        expect(failedResponses).toEqual([])
    })
})
