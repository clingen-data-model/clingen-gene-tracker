import { expect, test } from '@playwright/test'

const curationId = 9102
const editPath = `/home#/curations/${curationId}/edit/`
const phenotypeName = 'Deterministic E2E autosave phenotype'

function isCurationRequest(response, method) {
    return (
        new URL(response.url()).pathname === `/api/curations/${curationId}`
        && response.request().method() === method
    )
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

async function fetchCuration(page) {
    return page.evaluate(async id => {
        const response = await window.axios.get(`/api/curations/${id}`)
        return response.data.data
    }, curationId)
}

test('auto-selects and persists a single phenotype without duplicate saves across edit steps', async ({ page, baseURL }) => {
    test.setTimeout(45_000)
    const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
    const updateResponses = []
    page.on('response', response => {
        if (isCurationRequest(response, 'PUT')) {
            updateResponses.push(response)
        }
    })

    let originalCuration

    try {
        const initialResponsePromise = page.waitForResponse(response => isCurationRequest(response, 'GET'))
        await page.goto(editPath)
        await initialResponsePromise

        await expect(page.getByRole('heading', { name: /Edit Curation: E2E-BRAVO/ })).toBeVisible()
        await expect(page.getByLabel('Notes')).toHaveValue('Deterministic Playwright fixture')
        await expect(page).toHaveURL(new RegExp(`/home#/curations/${curationId}/edit/$`))
        originalCuration = await fetchCuration(page)
        expect(originalCuration.phenotypes).toEqual([])
        expect(originalCuration.rationales.map(rationale => rationale.id)).toEqual([6])

        const phenotypeResponsePromise = page.waitForResponse(response => (
            new URL(response.url()).pathname === `/api/omim/curation/${curationId}`
            && response.request().method() === 'GET'
        ))
        const autosaveResponsePromise = page.waitForResponse(response => isCurationRequest(response, 'PUT'))
        await page.getByRole('link', { name: 'Phenotypes', exact: true }).click()
        await phenotypeResponsePromise
        const autosaveResponse = await autosaveResponsePromise

        expect(autosaveResponse.status()).toBe(200)
        const autosavedCuration = (await autosaveResponse.json()).data
        expect(autosavedCuration.curation_notes).toBe('Deterministic Playwright fixture')
        expect(updateResponses).toHaveLength(1)
        await expect(page).toHaveURL(new RegExp(`/home#/curations/${curationId}/edit/#phenotypes$`))
        await expect(page.getByRole('link', { name: 'Phenotypes', exact: true })).toHaveClass(/active/)
        await expect(page.getByText(phenotypeName, { exact: true })).toBeVisible()
        await expect(page.getByRole('checkbox')).toBeChecked()
        const selectedRationale = page.getByRole('option', {
            name: 'Insufficient evidence for single disease entity',
        })
        await expect(selectedRationale).toHaveCount(1)
        expect(await selectedRationale.evaluate(option => option.selected)).toBe(true)

        await page.getByRole('link', { name: 'Info', exact: true }).click()
        await expect(page).toHaveURL(new RegExp(`/home#/curations/${curationId}/edit/#info$`))
        await expect(page.getByRole('link', { name: 'Info', exact: true })).toHaveClass(/active/)
        await expect(page.getByLabel('Notes')).toHaveValue('Deterministic Playwright fixture')

        const revisitedPhenotypesPromise = page.waitForResponse(response => (
            new URL(response.url()).pathname === `/api/omim/curation/${curationId}`
            && response.request().method() === 'GET'
        ))
        await page.getByRole('link', { name: 'Phenotypes', exact: true }).click()
        await revisitedPhenotypesPromise
        await expect(page.getByRole('checkbox')).toBeChecked()
        expect(updateResponses).toHaveLength(1)

        const persistedCuration = await fetchCuration(page)
        expect(persistedCuration.phenotypes).toHaveLength(1)
        expect(persistedCuration.phenotypes[0].name).toBe(phenotypeName)
        expect(persistedCuration.rationales.map(rationale => rationale.id)).toEqual([6])
        assertNoApplicationErrors()
    } finally {
        if (originalCuration) {
            const restoreStatus = await page.evaluate(async ({ id, curation }) => {
                const response = await window.axios.put(`/api/curations/${id}`, {
                    ...curation,
                    page: 'phenotypes',
                    nav: null,
                    phenotypes: [],
                })
                return response.status
            }, { id: curationId, curation: originalCuration })
            expect(restoreStatus).toBe(200)

            const restoredCuration = await fetchCuration(page)
            expect(restoredCuration.phenotypes).toEqual([])
            expect(restoredCuration.rationales.map(rationale => rationale.id)).toEqual([6])
        }
    }
})
