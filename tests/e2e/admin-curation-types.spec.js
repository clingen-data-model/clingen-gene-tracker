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
    test('dashboard cards show deterministic report counts and navigate to report rows', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        await page.goto('/home#/admin')
        await expect(page.getByRole('heading', { name: 'Administration Dashboard' })).toBeVisible()

        const outdatedCard = page.locator('.card').filter({ hasText: 'Outdated Phenotype Labels' }).first()
        const affectedCard = page.locator('.card').filter({ hasText: 'Affected Curations' })
        const usedCard = page.locator('.card').filter({ hasText: 'Outdated Phenotype Labels used on Curations' })
        await expect(outdatedCard.locator('.h2')).toHaveText('3')
        await expect(affectedCard.locator('.h2')).toHaveText('2')
        await expect(usedCard.locator('.h2')).toHaveText('2')

        await outdatedCard.getByRole('link', { name: 'View report' }).click()
        await expect(page).toHaveURL(/#\/admin\/outdated-phenotypes\?tab=phenotypes$/)
        await expect(page.getByRole('heading', { name: 'Outdated Phenotype Labels' })).toBeVisible()
        await expect(page.getByRole('row').filter({ hasText: 'E2E outdated phenotype used twice' })).toContainText('2 Curation(s)')
        assertNoApplicationErrors()
    })

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
            { name: 'Affiliations', path: 'affiliations', heading: 'Affiliation Administration' },
            { name: 'Users', path: 'users', heading: 'User Administration' },
            { name: 'Emails', path: 'emails', heading: 'Email Log' },
            { name: 'Notifications', path: 'notifications', heading: 'Notifications' },
            { name: 'API Clients', path: 'api-clients', heading: 'API Clients' },
        ]) {
            await page.getByRole('navigation', { name: 'Administration' })
                .getByRole('link', { name: section.name, exact: true }).click()
            await expect(page).toHaveURL(new RegExp(`#\/admin\/${section.path}$`))
            await expect(page.getByRole('heading', { name: section.heading || section.name, exact: true })).toBeVisible()
        }

        assertNoApplicationErrors()
    })

    test('privileged user sees the Admin and Logs menu links and can use both', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        await page.goto('/home')
        await page.getByRole('button', { name: /Super User/ }).click()
        const adminLink = page.getByRole('link', { name: 'Admin', exact: true })
        const logsLink = page.getByRole('link', { name: 'Logs', exact: true })
        await expect(adminLink).toHaveAttribute('href', '/admin/dashboard')
        await expect(logsLink).toHaveAttribute('href', '/admin/logs')

        await adminLink.click()
        await expect(page).toHaveURL(/\/admin\/dashboard#\/admin$/)
        await expect(page.getByRole('heading', { name: 'Administration Dashboard' })).toBeVisible()

        await page.getByRole('button', { name: /Super User/ }).click()
        await page.getByRole('link', { name: 'Logs', exact: true }).click()
        await expect(page).toHaveURL(/\/admin\/logs$/)
        await expect(page.getByRole('heading', { name: 'Laravel Log Viewer' })).toBeVisible()
        await page.getByRole('link', { name: 'e2e-admin-viewer.log' }).click()
        await expect(page.getByText('Deterministic E2E log viewer entry')).toBeVisible()
        assertNoApplicationErrors()
    })

    test('privileged user can inspect deterministic email and notification records', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        await page.goto('/home#/admin/emails')
        const emailRow = page.getByRole('row').filter({ hasText: 'Deterministic E2E email log' })
        await expect(emailRow).toBeVisible()
        await emailRow.getByRole('button', { name: 'View' }).click()
        await expect(page.getByText('<p>Deterministic E2E email body</p>')).toBeVisible()

        await page.goto('/home#/admin/notifications')
        const noticeRow = page.getByRole('row').filter({ hasText: 'E2EDeterministicNotice' })
        await expect(noticeRow).toContainText('E2E Managed User')
        await noticeRow.getByRole('button', { name: 'View' }).click()
        await expect(page.getByText(/Deterministic E2E notification payload/)).toBeVisible()
        assertNoApplicationErrors()
    })

    test('privileged user can delete an isolated deterministic notification', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        await page.goto('/home#/admin/notifications')
        const row = page.getByRole('row').filter({ hasText: 'E2EDeletableNotice' })
        await expect(row).toBeVisible()
        const responsePromise = page.waitForResponse(response => (
            new URL(response.url()).pathname === '/api/admin/notifications/20000000-0000-4000-8000-000000000002'
            && response.request().method() === 'DELETE'
        ))
        page.once('dialog', dialog => dialog.accept())
        await row.getByRole('button', { name: 'Delete' }).click()
        expect((await responsePromise).status()).toBe(204)
        await expect(page.getByText('Notification deleted successfully.')).toBeVisible()
        await expect(row).toHaveCount(0)
        assertNoApplicationErrors()
    })

    test('privileged user can create and update a disposable API client and manage one token', async ({ page, baseURL }, testInfo) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        const name = `E2E API Client ${testInfo.retry}`
        const updatedName = `E2E Updated API Client ${testInfo.retry}`
        const tokenName = `e2e-token-${testInfo.retry}`
        await page.goto('/home#/admin/api-clients')
        await page.getByRole('button', { name: 'Add API Client' }).click()
        await page.getByLabel('Name').fill(name)
        await page.getByLabel('Contact Email').fill(`e2e-api-client-${testInfo.retry}@example.com`)
        const createResponse = page.waitForResponse(response => new URL(response.url()).pathname === '/api/admin/api-clients' && response.request().method() === 'POST')
        await page.getByRole('button', { name: 'Create API Client' }).click()
        expect((await createResponse).status()).toBe(201)
        await expect(page.getByRole('heading', { name: 'API Client Details' })).toBeVisible()

        await page.getByRole('button', { name: 'Close', exact: true }).last().click()
        const row = page.getByRole('row').filter({ hasText: name })
        await row.getByRole('button', { name: 'Edit' }).click()
        await page.getByLabel('Name').fill(updatedName)
        const updateResponse = page.waitForResponse(response => response.request().method() === 'PUT' && new URL(response.url()).pathname.startsWith('/api/admin/api-clients/'))
        await page.getByRole('button', { name: 'Save Changes' }).click()
        expect((await updateResponse).status()).toBe(200)

        await page.getByLabel('Token Name').fill(tokenName)
        const tokenResponse = page.waitForResponse(response => response.request().method() === 'POST' && new URL(response.url()).pathname.endsWith('/tokens'))
        await page.getByRole('button', { name: 'Create Token' }).click()
        expect((await tokenResponse).status()).toBe(201)
        await expect(page.getByText('Copy this token now. It cannot be retrieved later.')).toBeVisible()
        await expect(page.locator('code')).not.toBeEmpty()

        const tokenRow = page.getByRole('row').filter({ hasText: tokenName })
        const revokeResponse = page.waitForResponse(response => response.request().method() === 'DELETE' && new URL(response.url()).pathname.includes('/tokens/'))
        page.once('dialog', dialog => dialog.accept())
        await tokenRow.getByRole('button', { name: 'Revoke' }).click()
        expect((await revokeResponse).status()).toBe(204)
        await expect(page.getByText('Token revoked successfully.')).toBeVisible()
        await expect(tokenRow).toHaveCount(0)
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

    test('privileged user can update and restore a local affiliation short name', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        let affiliationId
        let originalShortName

        try {
            await page.goto('/home#/admin/affiliations')
            await expect(page.getByRole('heading', { name: 'Affiliation Administration' })).toBeVisible()
            const pagination = page.getByRole('menubar', { name: 'Pagination' })
            await expect(pagination).toBeVisible()
            const pageResponsePromise = page.waitForResponse(response => (
                new URL(response.url()).pathname === '/api/admin/affiliations'
                && new URL(response.url()).searchParams.get('page') === '4'
            ))
            await pagination.getByRole('menuitem', { name: 'Go to page 4' }).click()
            expect((await pageResponsePromise).status()).toBe(200)

            const row = page.getByRole('row').filter({ hasText: '10001' })
            await expect(row).toContainText('KCNQ1')
            await row.getByRole('button', { name: 'Edit Short Name' }).click()
            const input = page.getByLabel('Short Name')
            originalShortName = await input.inputValue()
            await input.fill('E2E KCNQ1')

            const responsePromise = page.waitForResponse(response => (
                new URL(response.url()).pathname.startsWith('/api/admin/affiliations/')
                && response.request().method() === 'PUT'
            ))
            await page.getByRole('button', { name: 'Save Short Name' }).click()
            const response = await responsePromise
            expect(response.status()).toBe(200)
            affiliationId = (await response.json()).id
            await expect(row).toContainText('E2E KCNQ1')
            assertNoApplicationErrors()
        } finally {
            if (affiliationId) {
                await page.evaluate(async ({ id, shortName }) => {
                    await window.axios.put(`/api/admin/affiliations/${id}`, { short_name: shortName || null })
                }, { id: affiliationId, shortName: originalShortName })
            }
        }
    })

    test('privileged user can update and restore a deterministic user account lifecycle', async ({ page, baseURL }) => {
        const assertNoApplicationErrors = monitorApplicationErrors(page, baseURL)
        const email = 'e2e-managed-user@example.com'
        const originalName = 'E2E Managed User'
        const updatedName = 'E2E Updated Managed User'
        let userId
        let needsRestoration = false

        try {
            await page.goto('/home#/admin/users')
            await expect(page.getByRole('heading', { name: 'User Administration' })).toBeVisible()
            let row = page.getByRole('row').filter({ hasText: email })
            await expect(row).toContainText(originalName)
            await row.getByRole('button', { name: 'Edit' }).click()
            await page.getByLabel('Name').fill(updatedName)

            const updateResponsePromise = page.waitForResponse(response => (
                new URL(response.url()).pathname.startsWith('/api/admin/users/')
                && response.request().method() === 'PUT'
            ))
            await page.getByRole('button', { name: 'Save Changes' }).click()
            const updateResponse = await updateResponsePromise
            expect(updateResponse.status()).toBe(200)
            userId = (await updateResponse.json()).id
            needsRestoration = true

            row = page.getByRole('row').filter({ hasText: email })
            await expect(row).toContainText(updatedName)
            page.once('dialog', dialog => dialog.accept())
            const deactivateResponsePromise = page.waitForResponse(response => (
                new URL(response.url()).pathname === `/api/admin/users/${userId}/deactivate`
                && response.request().method() === 'PATCH'
            ))
            await row.getByRole('button', { name: 'Deactivate' }).click()
            expect((await deactivateResponsePromise).status()).toBe(200)
            await expect(row).toContainText('Deactivated')

            page.once('dialog', dialog => dialog.accept())
            const reactivateResponsePromise = page.waitForResponse(response => (
                new URL(response.url()).pathname === `/api/admin/users/${userId}/reactivate`
                && response.request().method() === 'PATCH'
            ))
            await row.getByRole('button', { name: 'Reactivate' }).click()
            expect((await reactivateResponsePromise).status()).toBe(200)
            await expect(row).toContainText('Active')
            assertNoApplicationErrors()
        } finally {
            if (userId && needsRestoration) {
                await page.evaluate(async ({ id, name }) => {
                    const users = await window.axios.get('/api/admin/users')
                    const user = users.data.data.find(item => item.id === id)
                    await window.axios.patch(`/api/admin/users/${id}/reactivate`)
                    await window.axios.put(`/api/admin/users/${id}`, {
                        name,
                        email: user.email,
                        role_ids: user.roles.map(role => role.id),
                        permission_ids: user.permissions.map(permission => permission.id),
                    })
                }, { id: userId, name: originalName })
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
            await page.getByRole('button', { name: /Super User/ }).click()
            await page.getByRole('link', { name: 'Admin', exact: true }).click()
            await expect(page).toHaveURL(/\/admin\/dashboard#\/admin$/)
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
        await page.getByRole('button', { name: /Curation Viewer/ }).click()
        await expect(page.getByRole('link', { name: 'Admin', exact: true })).toHaveCount(0)
        await expect(page.getByRole('link', { name: 'Logs', exact: true })).toHaveCount(0)

        const dashboardResponse = await page.request.get('/admin/dashboard')
        expect(dashboardResponse.status()).toBe(403)
        const logsResponse = await page.request.get('/admin/logs')
        expect(logsResponse.status()).toBe(403)

        await page.goto('/home#/admin/curation-types')
        await expect(page).toHaveURL(/#\/curations$/)
        await expect(page.getByRole('heading', { name: 'Curation Types' })).toHaveCount(0)

        for (const path of ['outdated-phenotypes', 'rationales', 'curation-statuses', 'upload-categories', 'mois', 'working-groups', 'expert-panels', 'affiliations', 'users', 'emails', 'notifications', 'api-clients']) {
            await page.goto(`/home#/admin/${path}`)
            await expect(page).toHaveURL(/#\/curations$/)
        }
        assertNoApplicationErrors()
    })
})
