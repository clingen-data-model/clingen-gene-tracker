import { expect, test } from '@playwright/test'

function monitorErrors(page, baseURL) {
    const errors = []
    const origin = new URL(baseURL).origin
    const isApplicationUrl = url => url && new URL(url).origin === origin
    page.on('pageerror', error => errors.push(error.message))
    page.on('console', message => {
        if (message.type() === 'error' && (!message.location().url || isApplicationUrl(message.location().url))) {
            errors.push(message.text())
        }
    })
    page.on('requestfailed', request => {
        if (isApplicationUrl(request.url())) errors.push(`${request.url()}: ${request.failure()?.errorText}`)
    })
    page.on('response', response => {
        if (isApplicationUrl(response.url()) && response.status() >= 400) errors.push(`${response.status()} ${response.url()}`)
    })
    return () => expect(errors).toEqual([])
}

for (const lookup of [
    { link: 'Curation Lookup', heading: 'Bulk Curation Lookup', path: '/api/bulk-lookup' },
    { link: 'Gene/Phenotype Lookup', heading: 'Bulk Gene/Phenotype Lookup', path: '/api/genes' },
]) {
    test(`${lookup.link} is reachable through Bulk Lookup and supports both input modes`, async ({ page, baseURL }) => {
        const assertNoErrors = monitorErrors(page, baseURL)
        await page.goto('/home')
        await page.getByRole('button', { name: 'Bulk Lookup' }).click()
        await page.getByRole('link', { name: lookup.link, exact: true }).click()
        await expect(page.getByRole('heading', { name: lookup.heading, exact: true })).toBeVisible()
        await expect(page.getByRole('tab', { name: 'Manual entry' })).toHaveAttribute('aria-selected', 'true')

        if (lookup.path === '/api/genes') {
            await expect(page.getByText('OMIM data last updated: September 23, 2026')).toBeVisible()
        }
        const input = page.getByLabel('Gene Symbols:')
        await input.fill('discard this')
        await page.getByRole('button', { name: 'Clear', exact: true }).click()
        await expect(input).toHaveValue('')
        await input.fill('E2E-BRAVO')
        let response = page.waitForResponse(response => new URL(response.url()).pathname === lookup.path && response.request().method() === 'POST')
        await page.getByRole('button', { name: 'Search', exact: true }).click()
        expect((await response).ok()).toBe(true)
        await expect(page.getByRole('cell', { name: 'E2E-BRAVO', exact: true })).toBeVisible()
        if (lookup.path === '/api/genes') {
            await expect(page.getByRole('cell', { name: 'Deterministic E2E autosave phenotype', exact: true })).toBeVisible()
            await expect(page.getByRole('cell', { name: '990001', exact: true })).toBeVisible()
            await expect(page.getByRole('cell', { name: 'Autosomal dominant', exact: true })).toBeVisible()
        }

        await page.getByRole('button', { name: 'Clear', exact: true }).click()
        await expect(input).toHaveValue('')
        await page.getByRole('tab', { name: 'CSV Upload' }).click()
        await expect(page.getByLabel('CSV file:')).toBeVisible()
        await page.getByLabel('has header row').check()
        await page.getByLabel('CSV file:').setInputFiles({
            name: 'genes.csv', mimeType: 'text/csv', buffer: Buffer.from('gene\nE2E-BRAVO'),
        })
        // Switching back exposes the parsed value, so no file-read timing assumption is needed.
        await page.getByRole('tab', { name: 'Manual entry' }).click()
        await expect(input).toHaveValue('E2E-BRAVO')
        await page.getByRole('tab', { name: 'CSV Upload' }).click()
        response = page.waitForResponse(response => new URL(response.url()).pathname === lookup.path && response.request().method() === 'POST')
        await page.getByRole('button', { name: 'Search', exact: true }).click()
        expect((await response).ok()).toBe(true)
        await expect(page.getByRole('cell', { name: 'E2E-BRAVO', exact: true })).toBeVisible()
        assertNoErrors()
    })
}
