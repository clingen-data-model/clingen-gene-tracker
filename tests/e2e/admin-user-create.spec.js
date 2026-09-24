import { execFileSync } from 'node:child_process'
import { expect, test } from '@playwright/test'

function cleanFixture() {
    execFileSync('php', ['tests/e2e/support/cleanup-created-user.php'], {
        env: { ...process.env, APP_ENV: 'testing', DB_CONNECTION: 'testing', DB_DATABASE_TEST: 'genetracker_e2e' },
        stdio: 'pipe', windowsHide: true,
    })
}

test('Admin creates a User with roles, permissions and Expert Panel membership', async ({ page, baseURL }) => {
    const errors = []
    const local = url => !url || new URL(url).origin === new URL(baseURL).origin
    page.on('pageerror', error => errors.push(error.message))
    page.on('console', message => { if (message.type() === 'error' && local(message.location().url)) errors.push(message.text()) })
    page.on('requestfailed', request => { if (local(request.url())) errors.push(request.url()) })
    page.on('response', response => { if (local(response.url()) && response.status() >= 400) errors.push(`${response.status()} ${response.url()}`) })
    cleanFixture()
    try {
        await page.goto('/home#/admin/users')
        await page.getByRole('button', { name: 'Add User', exact: true }).click()
        await expect(page.getByRole('heading', { name: 'Create User', exact: true })).toBeVisible()
        await expect(page.locator('input[type="password"]')).toHaveCount(0)
        await page.getByLabel('Name', { exact: true }).fill('E2E Created User')
        await page.getByLabel('Email', { exact: true }).fill('e2e-created-user@example.com')
        await page.getByLabel('Roles', { exact: true }).selectOption({ label: 'viewer' })
        await page.getByLabel('Extra Permissions', { exact: true }).selectOption({ label: 'list curations' })
        await page.getByRole('button', { name: 'Add Expert Panel', exact: true }).click()
        const panel = page.getByRole('combobox', { name: 'Expert Panel 1', exact: true })
        await panel.fill('Epilepsy GCEP')
        await expect(page.getByRole('listbox').getByRole('option', { name: 'Epilepsy GCEP', exact: true })).toBeVisible()
        await panel.press('Enter')
        await page.getByRole('checkbox', { name: 'Curator', exact: true }).check()
        await page.getByRole('checkbox', { name: 'Edit Curations', exact: true }).check()
        const saved = page.waitForResponse(response => new URL(response.url()).pathname === '/api/admin/users' && response.request().method() === 'POST')
        await page.getByRole('button', { name: 'Create User', exact: true }).click()
        expect((await saved).status()).toBe(201)
        await expect(page.getByText('User created successfully.')).toBeVisible()
        await page.reload()
        await page.getByRole('searchbox').fill('e2e-created-user@example.com')
        await page.getByRole('search').getByRole('button', { name: 'Search', exact: true }).click()
        await page.getByRole('row').filter({ hasText: 'e2e-created-user@example.com' }).getByRole('button', { name: 'Edit', exact: true }).click()
        await expect(page.getByLabel('Name', { exact: true })).toHaveValue('E2E Created User')
        await expect(page.getByLabel('Roles', { exact: true }).locator('option:checked')).toHaveText('viewer')
        await expect(page.getByLabel('Extra Permissions').locator('option:checked')).toHaveText('list curations')
        await expect(page.getByRole('group', { name: 'Expert Panels', exact: true })).toContainText('Epilepsy GCEP')
        await expect(page.getByRole('checkbox', { name: 'Curator', exact: true })).toBeChecked()
        await expect(page.getByRole('checkbox', { name: 'Coordinator', exact: true })).not.toBeChecked()
        await expect(page.getByRole('checkbox', { name: 'Edit Curations', exact: true })).toBeChecked()
        expect(errors).toEqual([])
    } finally {
        cleanFixture()
    }
})
