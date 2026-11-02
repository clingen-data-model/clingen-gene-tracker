import { expect, test } from '@playwright/test'

test('admin investigates reconciliation findings without GT mutation controls', async ({ page }) => {
    const errors = []
    page.on('pageerror', error => errors.push(error.message))
    await page.goto('/home#/admin/gci-reconciliation')
    await expect(page.getByRole('heading', { name: 'GCI reconciliation', exact: true })).toBeVisible()
    await expect(page.getByLabel('GT / Precuration ID', { exact: true })).toBeVisible()
    await expect(page.getByLabel('GCI / GDM UUID', { exact: true })).toBeVisible()
    await expect(page.locator('input[type=file]')).toHaveCount(0)
    await expect(page.getByRole('button', { name: /Create run|Preview selected|Confirm Apply|Download JSON/ })).toHaveCount(0)
    await page.getByLabel('GT / Precuration ID', { exact: true }).fill('9102')
    const response = page.waitForResponse(r => r.url().includes('/api/admin/gci-reconciliation/records?') && r.url().includes('curation_id=9102'))
    await page.getByRole('button', { name: 'Search / Apply filters' }).click()
    expect((await response).ok()).toBe(true)
    expect(errors).toEqual([])
})
