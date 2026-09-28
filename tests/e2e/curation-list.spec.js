import { expect, test } from '@playwright/test'

const curationListPath = '/home#/curations'

function waitForCurationList(page) {
    return page.waitForResponse(response => {
        const url = new URL(response.url())
        return url.pathname === '/api/curations' && response.request().method() === 'GET'
    })
}

function monitorApplicationErrors(page, baseURL) {
    const applicationOrigin = new URL(baseURL).origin
    const errors = []
    const isApplicationUrl = url => url && new URL(url).origin === applicationOrigin

    page.on('console', message => {
        const location = message.location().url
        if (message.type() === 'error' && (!location || isApplicationUrl(location))) {
            errors.push(`console: ${message.text()}`)
        }
    })
    page.on('pageerror', error => errors.push(`page: ${error.message}`))
    page.on('requestfailed', request => {
        if (isApplicationUrl(request.url())) {
            errors.push(`request: ${request.method()} ${request.url()}: ${request.failure()?.errorText}`)
        }
    })
    page.on('response', response => {
        if (isApplicationUrl(response.url()) && response.status() >= 400) {
            errors.push(`response: ${response.status()} ${response.url()}`)
        }
    })

    return () => expect(errors).toEqual([])
}

async function openCurationList(page) {
    const response = waitForCurationList(page)
    await page.goto(curationListPath)
    await response
    await expect(page.getByRole('heading', { name: 'All Curations' })).toBeVisible()
}

test.describe('authenticated curation list', () => {
    test('renders deterministic rows and supports global search and clearing', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        await openCurationList(page)

        await expect(page.getByLabel('Archived curations')).toHaveValue('exclude')
        await expect(page.getByRole('link', { name: 'E2E-ALPHA', exact: true })).toHaveCount(0)
        await expect(page.getByRole('link', { name: 'E2E-BRAVO', exact: true })).toBeVisible()
        await expect(page.getByRole('link', { name: 'E2E-JULIET', exact: true })).toBeVisible()
        await expect(page.getByRole('link', { name: 'E2E-KILO', exact: true })).toBeVisible()
        await expect(page.getByText('Total Records: 11', { exact: true })).toBeVisible()

        const search = page.getByPlaceholder('Search curations by gene, disease, curator, status, or ID')
        const searched = waitForCurationList(page)
        await search.fill('E2E-FOXTROT')
        await searched

        await expect(page.getByRole('link', { name: 'E2E-FOXTROT', exact: true })).toBeVisible()
        await expect(page.getByRole('link', { name: 'E2E-ALPHA', exact: true })).toHaveCount(0)
        await expect(page.getByText('Total Records: 1', { exact: true })).toBeVisible()

        const restored = waitForCurationList(page)
        await page.getByRole('button', { name: 'Clear' }).click()
        await restored

        await expect(page.getByRole('link', { name: 'E2E-ALPHA', exact: true })).toHaveCount(0)
        await expect(page.getByRole('link', { name: 'E2E-BRAVO', exact: true })).toBeVisible()
        await expect(page.getByText('Total Records: 11', { exact: true })).toBeVisible()
        assertNoApplicationErrors()
    })

    test('changes server-side ordering when a sortable column is selected', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        await openCurationList(page)

        const sorted = waitForCurationList(page)
        await page.getByRole('columnheader', { name: /Gene Symbol/ }).click()
        await sorted

        const geneLinks = page.locator('tbody').getByRole('link', { name: /^E2E-/ })
        await expect(geneLinks.first()).toHaveText('E2E-LIMA')
        await expect(page.getByRole('columnheader', { name: /Gene Symbol/ })).toHaveAttribute('aria-sort', 'descending')
        assertNoApplicationErrors()
    })

    test('applies advanced and archived filters and clears them', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        await openCurationList(page)

        await page.getByRole('button', { name: 'More filters' }).click()
        const panelFilter = page.getByPlaceholder('Filter by Expert Panel')
        const filtered = waitForCurationList(page)
        await panelFilter.fill('Epilepsy GCEP')
        await filtered

        await expect(page.getByText('Total Records: 5', { exact: true })).toBeVisible()
        await expect(page.getByRole('link', { name: 'E2E-ALPHA', exact: true })).toHaveCount(0)
        await expect(page.getByRole('link', { name: 'E2E-BRAVO', exact: true })).toHaveCount(0)

        const archivedFiltered = waitForCurationList(page)
        await page.getByLabel('Archived curations').selectOption('include')
        await archivedFiltered

        await expect(page.getByText('Total Records: 6', { exact: true })).toBeVisible()
        await expect(page.getByRole('link', { name: 'E2E-ALPHA', exact: true })).toBeVisible()

        const onlyArchived = waitForCurationList(page)
        await page.getByLabel('Archived curations').selectOption('only')
        await onlyArchived
        await expect(page.getByText('Total Records: 1', { exact: true })).toBeVisible()
        await expect(page.getByRole('link', { name: 'E2E-ALPHA', exact: true })).toBeVisible()
        await expect(page.getByRole('link', { name: 'E2E-CHARLIE', exact: true })).toHaveCount(0)

        const restored = waitForCurationList(page)
        await page.getByRole('button', { name: 'Clear' }).click()
        await restored

        await expect(page.getByText('Total Records: 11', { exact: true })).toBeVisible()
        await expect(page.getByRole('link', { name: 'E2E-ALPHA', exact: true })).toHaveCount(0)
        await expect(page.getByLabel('Archived curations')).toHaveValue('exclude')
        await expect(panelFilter).toHaveValue('')
        assertNoApplicationErrors()
    })

    test('paginates deterministic rows and navigates to a curation', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        await openCurationList(page)

        const secondPage = waitForCurationList(page)
        await page.getByRole('menuitem', { name: 'Go to page 2' }).first().click()
        await secondPage

        await expect(page.getByRole('link', { name: 'E2E-KILO', exact: true })).toHaveCount(0)
        await expect(page.getByRole('link', { name: 'E2E-LIMA', exact: true })).toBeVisible()
        await expect(page.getByRole('link', { name: 'E2E-ALPHA', exact: true })).toHaveCount(0)

        await page.getByRole('link', { name: 'E2E-LIMA', exact: true }).click()
        await expect(page).toHaveURL(/\/home#\/curations\/9112$/)
        await expect(page.getByRole('heading', { name: /Curation: E2E-LIMA/ })).toBeVisible()
        assertNoApplicationErrors()
    })

    test('searches GCI UUIDs, filters their presence, and displays them with Precuration ID', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        const uuid = '10000000-0000-4000-8000-000000009103'
        await openCurationList(page)
        const searched = waitForCurationList(page)
        await page.getByPlaceholder('Search curations by gene, disease, curator, status, or ID').fill(uuid)
        await searched
        await expect(page.getByText('Total Records: 1', { exact: true })).toBeVisible()
        const row = page.getByRole('row').filter({ has: page.getByRole('link', { name: 'E2E-CHARLIE', exact: true }) })
        const identifiers = row.getByRole('cell').filter({ hasText: 'Precuration ID:' })
        await expect(identifiers).toContainText('9103')
        await expect(identifiers).toContainText(`GCI UUID: ${uuid}`)
        const cleared = waitForCurationList(page)
        await page.getByRole('button', { name: 'Clear', exact: true }).click()
        await cleared
        await page.getByRole('button', { name: 'More filters' }).click()
        const gciStatus = page.getByRole('combobox').filter({ has: page.getByRole('option', { name: 'Has GCI UUID', exact: true }) })
        for (const [status, count] of [['has', 1], ['none', 10]]) {
            const filtered = waitForCurationList(page)
            await gciStatus.selectOption(status)
            await filtered
            await expect(page.getByText(`Total Records: ${count}`, { exact: true })).toBeVisible()
            await expect(page.getByRole('link', { name: 'E2E-CHARLIE', exact: true })).toHaveCount(status === 'has' ? 1 : 0)
        }
        const restored = waitForCurationList(page)
        await page.getByRole('button', { name: 'Clear', exact: true }).click()
        await restored
        await expect(gciStatus).toHaveValue('')
        await expect(page.getByText('Total Records: 11', { exact: true })).toBeVisible()
        assertNoApplicationErrors()
    })
})
