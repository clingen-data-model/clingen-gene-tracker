import { expect, test } from '@playwright/test'

const createPath = '/home#/curations/create'

function monitorApplicationErrors(
    page,
    baseURL,
    expectedResponse = () => false,
    expectedConsoleError = () => false
) {
    const applicationOrigin = new URL(baseURL).origin
    const errors = []
    const isApplicationUrl = url => url && new URL(url).origin === applicationOrigin

    page.on('console', message => {
        const location = message.location().url
        if (
            message.type() === 'error'
            && (!location || isApplicationUrl(location))
            && !expectedConsoleError(message)
        ) {
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
        if (isApplicationUrl(response.url()) && response.status() >= 400 && !expectedResponse(response)) {
            errors.push(`response: ${response.status()} ${response.url()}`)
        }
    })

    return () => expect(errors).toEqual([])
}

async function openCreateForm(page) {
    await page.goto(createPath)
    await expect(page.getByRole('heading', { name: 'Add a curation to curate' })).toBeVisible()
    await expect(page.getByLabel('HGNC Gene Symbol')).toBeVisible()
    await expect(page.getByLabel('Gene Curation Expert Panel')).toBeEnabled()
}

test.describe('curation creation', () => {
    test('creates a valid curation and continues to its curation-type step', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        let createdCurationId

        try {
            await openCreateForm(page)

            await page.getByLabel('HGNC Gene Symbol').fill('BRCA1')
            await page.getByLabel('Mode of Inheritance').selectOption({
                label: 'Autosomal dominant inheritance (HP:0000006)',
            })
            await page.getByLabel('Gene Curation Expert Panel').selectOption({ label: 'Epilepsy GCEP' })
            await page.getByLabel('Notes').fill('Created by deterministic Playwright coverage')

            const createResponsePromise = page.waitForResponse(response => (
                new URL(response.url()).pathname === '/api/curations'
                && response.request().method() === 'POST'
            ))
            await page.getByRole('button', { name: 'Create curation' }).click()
            const createResponse = await createResponsePromise

            expect(createResponse.status()).toBe(201)
            const createdCuration = (await createResponse.json()).data
            createdCurationId = createdCuration.id
            expect(createdCuration.gene_symbol).toBe('BRCA1')

            await expect(page).toHaveURL(
                new RegExp(`/home#/curations/${createdCurationId}/edit/#curation-type$`)
            )
            await expect(page.getByRole('link', { name: 'Curation Type', exact: true })).toHaveClass(/active/)

            const persistedCuration = await page.evaluate(async curationId => {
                const response = await window.axios.get(`/api/curations/${curationId}`)
                return response.data.data
            }, createdCurationId)
            expect(persistedCuration.gene_symbol).toBe('BRCA1')
            expect(persistedCuration.expert_panel.name).toBe('Epilepsy GCEP')
            assertNoApplicationErrors()
        } finally {
            if (createdCurationId) {
                const deleted = await page.evaluate(async curationId => {
                    const response = await window.axios.delete(`/api/curations/${curationId}`)
                    return response.status
                }, createdCurationId)
                expect(deleted).toBe(200)
            }
        }
    })

    test('shows required-panel validation and remains on the create page', async ({ page, baseURL }) => {
        const isExpectedValidation = response => (
            response.status() === 422
            && new URL(response.url()).pathname === '/api/curations'
            && response.request().method() === 'POST'
        )
        const assertNoApplicationErrors = monitorApplicationErrors(
            page,
            baseURL,
            isExpectedValidation,
            message => message.text().includes('status of 422')
        )
        await openCreateForm(page)

        await page.getByLabel('HGNC Gene Symbol').fill('BRCA2')

        const validationResponsePromise = page.waitForResponse(isExpectedValidation)
        await page.getByRole('button', { name: 'Create curation' }).click()
        const validationResponse = await validationResponsePromise

        expect(validationResponse.status()).toBe(422)
        await expect(page.getByText('The expert panel id field is required.')).toBeVisible()
        await expect(page).toHaveURL(/\/home#\/curations\/create$/)

        const matchingCurations = await page.evaluate(async () => {
            const response = await window.axios.get('/api/curations?gene_symbol=BRCA2')
            return response.data.data
        })
        expect(matchingCurations).toEqual([])
        assertNoApplicationErrors()
    })
})
