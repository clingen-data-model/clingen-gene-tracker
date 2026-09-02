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

        await expect(page.getByRole('link', { name: 'E2E-ALPHA', exact: true })).toBeVisible()
        await expect(page.getByRole('link', { name: 'E2E-JULIET', exact: true })).toBeVisible()
        await expect(page.getByText('Total Records: 12')).toBeVisible()

        const search = page.getByPlaceholder('Search curations by gene, disease, curator, status, or ID')
        const searched = waitForCurationList(page)
        await search.fill('E2E-FOXTROT')
        await searched

        await expect(page.getByRole('link', { name: 'E2E-FOXTROT', exact: true })).toBeVisible()
        await expect(page.getByRole('link', { name: 'E2E-ALPHA', exact: true })).toHaveCount(0)
        await expect(page.getByText('Total Records: 1')).toBeVisible()

        const restored = waitForCurationList(page)
        await page.getByRole('button', { name: 'Clear' }).click()
        await restored

        await expect(page.getByRole('link', { name: 'E2E-ALPHA', exact: true })).toBeVisible()
        await expect(page.getByText('Total Records: 12')).toBeVisible()
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

        await expect(page.getByText('Total Records: 6')).toBeVisible()
        await expect(page.getByRole('link', { name: 'E2E-ALPHA', exact: true })).toBeVisible()
        await expect(page.getByRole('link', { name: 'E2E-BRAVO', exact: true })).toHaveCount(0)

        const archivedFiltered = waitForCurationList(page)
        await page.getByLabel('Exclude archived').check()
        await archivedFiltered

        await expect(page.getByText('Total Records: 5')).toBeVisible()
        await expect(page.getByRole('link', { name: 'E2E-ALPHA', exact: true })).toHaveCount(0)

        const restored = waitForCurationList(page)
        await page.getByRole('button', { name: 'Clear' }).click()
        await restored

        await expect(page.getByText('Total Records: 12')).toBeVisible()
        await expect(page.getByRole('link', { name: 'E2E-ALPHA', exact: true })).toBeVisible()
        await expect(page.getByLabel('Exclude archived')).not.toBeChecked()
        assertNoApplicationErrors()
    })

    test('paginates deterministic rows and navigates to a curation', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        await openCurationList(page)

        const secondPage = waitForCurationList(page)
        await page.getByRole('menuitem', { name: 'Go to page 2' }).first().click()
        await secondPage

        await expect(page.getByRole('link', { name: 'E2E-KILO', exact: true })).toBeVisible()
        await expect(page.getByRole('link', { name: 'E2E-LIMA', exact: true })).toBeVisible()
        await expect(page.getByRole('link', { name: 'E2E-ALPHA', exact: true })).toHaveCount(0)

        await page.getByRole('link', { name: 'E2E-LIMA', exact: true }).click()
        await expect(page).toHaveURL(/\/home#\/curations\/9112$/)
        await expect(page.getByRole('heading', { name: /Curation: E2E-LIMA/ })).toBeVisible()
        assertNoApplicationErrors()
    })
})
