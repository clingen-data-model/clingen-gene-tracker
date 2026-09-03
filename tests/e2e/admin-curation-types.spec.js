import { expect, test } from '@playwright/test'

function monitorApplicationErrors(page, baseURL) {
    const origin = new URL(baseURL).origin
    const errors = []
    const isApplicationUrl = url => url && new URL(url).origin === origin

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

test.describe('Curation Type administration', () => {
    test('privileged user can navigate to each lookup and organization administration section', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        await page.goto('/home#/admin')

        for (const section of [
            { name: 'Rationales', path: 'rationales' },
            { name: 'Curation Statuses', path: 'curation-statuses' },
            { name: 'Upload Categories', path: 'upload-categories' },
            { name: 'Modes of Inheritance', path: 'mois' },
            { name: 'Working Groups', path: 'working-groups', heading: 'Working Group Administration' },
            { name: 'Expert Panels', path: 'expert-panels', heading: 'Expert Panel Administration' },
        ]) {
            await page.getByRole('navigation', { name: 'Administration' })
                .getByRole('link', { name: section.name, exact: true }).click()
            await expect(page).toHaveURL(new RegExp(`#\/admin\/${section.path}$`))
            await expect(page.getByRole('heading', { name: section.heading || section.name, exact: true })).toBeVisible()
        }

        assertNoApplicationErrors()
    })

    test('privileged user can complete a disposable Working Group CRUD workflow', async ({ page, baseURL }, testInfo) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        const originalName = `E2E Admin Working Group ${testInfo.retry}`
        const updatedName = `E2E Updated Working Group ${testInfo.retry}`
        let createdId

        try {
            await page.goto('/home#/admin/working-groups')
            await expect(page.getByRole('heading', { name: 'Working Group Administration' })).toBeVisible()
            await page.getByRole('button', { name: 'Add Working Group' }).click()
            await page.getByLabel('Name').fill(originalName)

            const createResponsePromise = page.waitForResponse(response => (
                new URL(response.url()).pathname === '/api/admin/working-groups'
                && response.request().method() === 'POST'
            ))
            await page.getByRole('button', { name: 'Create Working Group' }).click()
            const createResponse = await createResponsePromise
            expect(createResponse.status()).toBe(201)
            createdId = (await createResponse.json()).id

            const row = page.getByRole('row').filter({ hasText: originalName })
            await expect(row).toBeVisible()
            await row.getByRole('button', { name: 'Edit' }).click()
            await page.getByLabel('Name').fill(updatedName)

            const updateResponsePromise = page.waitForResponse(response => (
                new URL(response.url()).pathname === `/api/admin/working-groups/${createdId}`
                && response.request().method() === 'PUT'
            ))
            await page.getByRole('button', { name: 'Save Changes' }).click()
            expect((await updateResponsePromise).status()).toBe(200)

            const updatedRow = page.getByRole('row').filter({ hasText: updatedName })
            await expect(updatedRow).toBeVisible()
            const deleteResponsePromise = page.waitForResponse(response => (
                new URL(response.url()).pathname === `/api/admin/working-groups/${createdId}`
                && response.request().method() === 'DELETE'
            ))
            page.once('dialog', dialog => dialog.accept())
            await updatedRow.getByRole('button', { name: 'Delete' }).click()
            expect((await deleteResponsePromise).status()).toBe(204)
            createdId = null
            await expect(updatedRow).toHaveCount(0)
            assertNoApplicationErrors()
        } finally {
            if (createdId) {
                await page.evaluate(async id => {
                    await window.axios.delete(`/api/admin/working-groups/${id}`)
                }, createdId)
            }
        }
    })

    test('privileged user can update and restore a deterministic curatable setting', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        let moiId
        let originalCuratable

        try {
            await page.goto('/home#/admin/mois')
            await expect(page.getByRole('heading', { name: 'Modes of Inheritance' })).toBeVisible()

            const row = page.getByRole('row').filter({ hasText: 'HP:0000006' })
            await expect(row).toContainText('Autosomal dominant inheritance')
            originalCuratable = (await row.getByRole('cell').nth(4).textContent()).trim() === 'Yes'
            await row.getByRole('button', { name: 'Edit Curatable' }).click()
            await page.getByLabel('Curatable').selectOption(originalCuratable ? 'false' : 'true')

            const updateResponsePromise = page.waitForResponse(response => (
                new URL(response.url()).pathname.startsWith('/api/admin/mois/')
                && response.request().method() === 'PUT'
            ))
            await page.getByRole('button', { name: 'Save Curatable Setting' }).click()
            const updateResponse = await updateResponsePromise
            expect(updateResponse.status()).toBe(200)
            const updatedMoi = await updateResponse.json()
            moiId = updatedMoi.id
            await expect(page.getByText('Mode of inheritance updated successfully.')).toBeVisible()
            await expect(row.getByRole('cell').nth(4)).toHaveText(originalCuratable ? 'No' : 'Yes')
            assertNoApplicationErrors()
        } finally {
            if (moiId && originalCuratable !== undefined) {
                await page.evaluate(async ({ id, curatable }) => {
                    await window.axios.put(`/api/admin/mois/${id}`, { curatable })
                }, { id: moiId, curatable: originalCuratable })
            }
        }
    })

    test('privileged user can navigate to Curation Types and complete a CRUD workflow', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        const originalName = 'E2E Admin Curation Type'
        const updatedName = 'E2E Updated Curation Type'
        let createdId

        try {
            await page.goto('/home')
            await page.getByRole('link', { name: 'Administration', exact: true }).click()
            await expect(page).toHaveURL(/#\/admin$/)
            await page.getByRole('link', { name: 'Curation Types', exact: true }).click()
            await expect(page).toHaveURL(/#\/admin\/curation-types$/)
            await expect(page.getByRole('heading', { name: 'Curation Types' })).toBeVisible()

            await page.getByRole('button', { name: 'Add Curation Type' }).click()
            await page.getByLabel('Name').fill(originalName)
            await page.getByLabel('Description').fill('Created by deterministic Playwright coverage.')

            const createResponsePromise = page.waitForResponse(response => (
                new URL(response.url()).pathname === '/api/admin/curation-types'
                && response.request().method() === 'POST'
            ))
            await page.getByRole('button', { name: 'Create Curation Type' }).click()
            const createResponse = await createResponsePromise
            expect(createResponse.status()).toBe(201)
            createdId = (await createResponse.json()).id
            await expect(page.getByText('Curation type created successfully.')).toBeVisible()

            const row = page.getByRole('row').filter({ hasText: originalName })
            await row.getByRole('button', { name: 'Edit' }).click()
            await page.getByLabel('Name').fill(updatedName)

            const updateResponsePromise = page.waitForResponse(response => (
                new URL(response.url()).pathname === `/api/admin/curation-types/${createdId}`
                && response.request().method() === 'PUT'
            ))
            await page.getByRole('button', { name: 'Save Changes' }).click()
            expect((await updateResponsePromise).status()).toBe(200)
            await expect(page.getByRole('row').filter({ hasText: updatedName })).toBeVisible()

            const deleteResponsePromise = page.waitForResponse(response => (
                new URL(response.url()).pathname === `/api/admin/curation-types/${createdId}`
                && response.request().method() === 'DELETE'
            ))
            page.once('dialog', dialog => dialog.accept())
            await page.getByRole('row').filter({ hasText: updatedName })
                .getByRole('button', { name: 'Delete' }).click()
            expect((await deleteResponsePromise).status()).toBe(204)
            await expect(page.getByText('Curation type deleted successfully.')).toBeVisible()
            await expect(page.getByRole('row').filter({ hasText: updatedName })).toHaveCount(0)
            createdId = null
            assertNoApplicationErrors()
        } finally {
            if (createdId) {
                await page.evaluate(async id => {
                    await window.axios.delete(`/api/admin/curation-types/${id}`)
                }, createdId)
            }
        }
    })
})

test.describe('Curation Type administration as a restricted user', () => {
    test.use({ storageState: 'playwright/.auth/restricted-user.json' })

    test('cannot see or directly access administration', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        await page.goto('/home')
        await expect(page.getByRole('link', { name: 'Administration', exact: true })).toHaveCount(0)

        await page.goto('/home#/admin/curation-types')
        await expect(page).toHaveURL(/#\/curations$/)
        await expect(page.getByRole('heading', { name: 'Curation Types' })).toHaveCount(0)

        for (const path of ['rationales', 'curation-statuses', 'upload-categories', 'mois', 'working-groups', 'expert-panels']) {
            await page.goto(`/home#/admin/${path}`)
            await expect(page).toHaveURL(/#\/curations$/)
        }
        assertNoApplicationErrors()
    })
})
