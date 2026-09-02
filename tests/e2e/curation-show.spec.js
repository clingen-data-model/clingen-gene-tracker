import { expect, test } from '@playwright/test'

const normalCuration = {
    id: 9102,
    gene: 'E2E-BRAVO',
    panel: 'ID-Autism GCEP',
}
const archivedCuration = {
    id: 9101,
    gene: 'E2E-ALPHA',
}
const gciCuration = {
    id: 9103,
    gene: 'E2E-CHARLIE',
    gdmUuid: '10000000-0000-4000-8000-000000009103',
}

function isApplicationRequest(url, baseURL) {
    return url && new URL(url).origin === new URL(baseURL).origin
}

function monitorApplicationErrors(page, baseURL) {
    const errors = []

    page.on('console', message => {
        const location = message.location().url
        if (message.type() === 'error' && (!location || isApplicationRequest(location, baseURL))) {
            errors.push(`console: ${message.text()}`)
        }
    })
    page.on('pageerror', error => errors.push(`page: ${error.message}`))
    page.on('requestfailed', request => {
        if (isApplicationRequest(request.url(), baseURL)) {
            errors.push(`request: ${request.method()} ${request.url()}: ${request.failure()?.errorText}`)
        }
    })
    page.on('response', response => {
        if (isApplicationRequest(response.url(), baseURL) && response.status() >= 400) {
            errors.push(`response: ${response.status()} ${response.url()}`)
        }
    })

    return () => expect(errors).toEqual([])
}

function waitForCuration(page, id) {
    return page.waitForResponse(response => (
        new URL(response.url()).pathname === `/api/curations/${id}`
        && response.request().method() === 'GET'
    ))
}

async function openCuration(page, curation) {
    const responsePromise = waitForCuration(page, curation.id)
    await page.goto(`/home#/curations/${curation.id}`)
    const response = await responsePromise

    expect(response.status()).toBe(200)
    await expect(page.getByRole('heading', { name: new RegExp(`Curation: ${curation.gene}`) })).toBeVisible()
    await expect(page.locator('#show-curation')).toBeVisible()
}

function controlLocators(page, id) {
    return {
        edit: page.getByRole('link', { name: 'Edit', exact: true }),
        delete: page.locator(`#delete-curation-${id}-btn`),
        transfer: page.getByRole('button', { name: 'Transfer Curation', exact: true }),
    }
}

test.describe('curation Show page', () => {
    test('renders deterministic content, history, sections, navigation, and privileged controls', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        await openCuration(page, normalCuration)

        const showCard = page.locator('#show-curation')
        await expect(page.locator('#info .row').filter({ hasText: 'Gene Symbol:' })).toContainText(normalCuration.gene)
        await expect(page.locator('#info .row').filter({ hasText: 'Expert Panel:' })).toContainText(normalCuration.panel)
        await expect(showCard.getByText('Super User', { exact: true })).toBeVisible()
        await expect(showCard.getByText('Deterministic Playwright fixture', { exact: true })).toBeVisible()

        const statusRow = page.locator('#info .row').filter({ hasText: 'Current Status:' })
        await expect(statusRow).toContainText('Uploaded')
        await statusRow.getByRole('button', { name: 'Show history' }).click()
        await expect(statusRow.getByRole('columnheader', { name: 'Status' })).toBeVisible()
        await expect(statusRow.getByText('2026-01-15', { exact: true })).toBeVisible()

        await expect(page.getByRole('heading', { name: /Documents/ })).toBeVisible()
        await expect(page.getByText('No documents found', { exact: true })).toBeVisible()
        await expect(page.getByRole('heading', { name: 'Administrative Notes' })).toBeVisible()
        await expect(page.getByText('Not administrative notes for this record.', { exact: true })).toBeVisible()
        await expect(page.getByRole('heading', { name: 'Linked Archived Curations' })).toBeVisible()

        const controls = controlLocators(page, normalCuration.id)
        await expect(controls.edit).toHaveAttribute('href', `#/curations/${normalCuration.id}/edit`)
        await expect(controls.delete).toBeVisible()
        await expect(controls.transfer).toBeVisible()
        await expect(page.getByRole('link', { name: '< Back to curations' })).toHaveAttribute('href', '#/curations')
        assertNoApplicationErrors()
    })

    test('communicates archived state and preserves privileged archived-control rules', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        await openCuration(page, archivedCuration)

        await expect(page.getByText(/marked the curation as archived for historical purposes/)).toBeVisible()
        await expect(page.getByText(/Reason:\s*Deterministic archived E2E fixture/)).toBeVisible()
        await expect(page.getByRole('heading', { name: 'Linked Current Curations' })).toBeVisible()

        const controls = controlLocators(page, archivedCuration.id)
        await expect(controls.edit).toBeVisible()
        await expect(controls.delete).toBeVisible()
        await expect(controls.transfer).toBeVisible()
        await expect(page.getByRole('button', { name: 'Add Document' })).toHaveCount(0)
        assertNoApplicationErrors()
    })

    test('renders a deterministic GCI link without contacting the external service', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        await openCuration(page, gciCuration)

        const gciLink = page.getByRole('link', { name: gciCuration.gdmUuid })
        await expect(page.getByText('GCI ID:', { exact: true })).toBeVisible()
        await expect(gciLink).toHaveAttribute(
            'href',
            `https://curation.clinicalgenome.org/curation-central/${gciCuration.gdmUuid}`,
        )
        await expect(page.locator('#info').getByText(/linked to a record in the GCI/)).toBeVisible()
        assertNoApplicationErrors()
    })
})

test.describe('curation Show page as an unrelated viewer', () => {
    test.use({ storageState: 'playwright/.auth/restricted-user.json' })

    test('hides permission-sensitive controls for normal and archived curations', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)

        await openCuration(page, normalCuration)
        let controls = controlLocators(page, normalCuration.id)
        await expect(controls.edit).toHaveCount(0)
        await expect(controls.delete).toHaveCount(0)
        await expect(controls.transfer).toHaveCount(0)

        await openCuration(page, archivedCuration)
        await expect(page.getByText(/marked the curation as archived for historical purposes/)).toBeVisible()
        controls = controlLocators(page, archivedCuration.id)
        await expect(controls.edit).toHaveCount(0)
        await expect(controls.delete).toHaveCount(0)
        await expect(controls.transfer).toHaveCount(0)
        await expect(page.getByRole('button', { name: 'Add Document' })).toHaveCount(0)
        assertNoApplicationErrors()
    })
})
