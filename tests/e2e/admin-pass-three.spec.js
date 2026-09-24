import { expect, test } from '@playwright/test'

function monitor(page, baseURL) {
    const errors = []
    const local = url => !url || new URL(url).origin === new URL(baseURL).origin
    page.on('pageerror', error => errors.push(error.message))
    page.on('console', message => { if (message.type() === 'error' && local(message.location().url)) errors.push(message.text()) })
    page.on('requestfailed', request => { if (local(request.url())) errors.push(request.url()) })
    page.on('response', response => { if (local(response.url()) && response.status() >= 400) errors.push(`${response.status()} ${response.url()}`) })
    return () => expect(errors).toEqual([])
}

async function search(page, path, term) {
    await page.getByRole('searchbox').fill(term)
    const loaded = page.waitForResponse(response => {
        const url = new URL(response.url())
        return url.pathname === `/api/admin/${path}` && url.searchParams.get('search') === term && url.searchParams.get('page') === '1'
    })
    await page.getByRole('search').getByRole('button', { name: 'Search', exact: true }).click()
    const response = await loaded
    expect(response.status()).toBe(200)
    return response.json()
}

async function save(page, path) {
    const saved = page.waitForResponse(response => new URL(response.url()).pathname === path && response.request().method() === 'PUT')
    await page.getByRole('button', { name: 'Save Changes', exact: true }).click()
    const response = await saved
    expect(response.status()).toBe(200)
    return response.json()
}

test('Admin edits searchable Expert Panel memberships and persists flags', async ({ page, baseURL }) => {
    const assertClean = monitor(page, baseURL)
    await page.goto('/home#/admin/users')
    await expect(page.getByRole('heading', { name: 'User Administration' })).toBeVisible()
    const email = 'e2e-managed-user@example.com'
    const { data: [original] } = await search(page, 'users', email)
    const row = page.getByRole('row').filter({ hasText: email })
    try {
        await row.getByRole('button', { name: 'Edit', exact: true }).click()
        await expect(page.getByLabel('Extra Permissions')).toHaveAttribute('size', '8')
        await page.getByRole('button', { name: 'Add Expert Panel', exact: true }).click()
        const select = page.getByRole('combobox', { name: 'Expert Panel 1', exact: true })
        await select.fill('Epilepsy GCEP')
        await expect(page.getByRole('option', { name: 'Epilepsy GCEP', exact: true })).toBeVisible()
        await select.press('Enter')
        await page.getByRole('checkbox', { name: 'Curator', exact: true }).check()
        await page.getByRole('checkbox', { name: 'Edit Curations', exact: true }).check()
        const updated = await save(page, `/api/admin/users/${original.id}`)
        expect(updated.expert_panels).toHaveLength(1)
        await page.reload()
        await search(page, 'users', email)
        await row.getByRole('button', { name: 'Edit', exact: true }).click()
        await expect(page.getByRole('checkbox', { name: 'Curator', exact: true })).toBeChecked()
        await expect(page.getByRole('checkbox', { name: 'Coordinator', exact: true })).not.toBeChecked()
        await expect(page.getByRole('checkbox', { name: 'Edit Curations', exact: true })).toBeChecked()
        await page.getByRole('checkbox', { name: 'Coordinator', exact: true }).check()
        const changed = await save(page, `/api/admin/users/${original.id}`)
        expect(Number(changed.expert_panels[0].pivot.is_coordinator)).toBe(1)
        await row.getByRole('button', { name: 'Edit', exact: true }).click()
        await page.getByRole('button', { name: 'Remove Expert Panel 1', exact: true }).click()
        expect((await save(page, `/api/admin/users/${original.id}`)).expert_panels).toEqual([])
    } finally {
        await page.evaluate(async user => window.axios.put(`/api/admin/users/${user.id}`, {
            name: user.name, email: user.email,
            role_ids: user.roles.map(role => role.id), permission_ids: user.permissions.map(permission => permission.id),
            expert_panels: user.expert_panels.map(panel => ({ id: panel.id,
                is_curator: Boolean(Number(panel.pivot.is_curator)), is_coordinator: Boolean(Number(panel.pivot.is_coordinator)),
                can_edit_curations: Boolean(Number(panel.pivot.can_edit_curations)),
            })),
        }), original)
    }
    assertClean()
})

test('Admin changes an Expert Panel affiliation and restores the fixture', async ({ page, baseURL }) => {
    const assertClean = monitor(page, baseURL)
    await page.goto('/home#/admin/expert-panels')
    const { data } = await search(page, 'expert-panels', 'Epilepsy GCEP')
    const original = data.find(panel => panel.name === 'Epilepsy GCEP')
    const options = await page.evaluate(async () => (await window.axios.get('/api/admin/expert-panels/options')).data.affiliations)
    const affiliation = options.find(item => item.id !== original.affiliation_id && item.name && item.clingen_id)
    const row = page.getByRole('row').filter({ hasText: 'Epilepsy GCEP' })
    try {
        await row.getByRole('button', { name: 'Edit', exact: true }).click()
        if (original.affiliation) await page.getByRole('button', { name: 'Clear Affiliation', exact: true }).click()
        const select = page.getByRole('combobox', { name: 'Affiliation', exact: true })
        await select.fill(String(affiliation.clingen_id))
        await expect(page.getByRole('listbox').getByRole('option').filter({ hasText: affiliation.name }).first()).toBeVisible()
        await select.press('Enter')
        const updated = await save(page, `/api/admin/expert-panels/${original.id}`)
        expect(updated.affiliation_id).toBe(affiliation.id)
        await page.reload()
        await search(page, 'expert-panels', 'Epilepsy GCEP')
        await row.getByRole('button', { name: 'Edit', exact: true }).click()
        await expect(page.locator('form').filter({ has: page.getByLabel('Name', { exact: true }) })).toContainText(affiliation.name)
    } finally {
        await page.evaluate(async panel => window.axios.put(`/api/admin/expert-panels/${panel.id}`, {
            name: panel.name, working_group_id: panel.working_group_id, affiliation_id: panel.affiliation_id,
        }), original)
    }
    assertClean()
})

test('Admin server search resets affiliation pagination to page one', async ({ page, baseURL }) => {
    const assertClean = monitor(page, baseURL)
    const initial = page.waitForResponse(response => new URL(response.url()).pathname === '/api/admin/affiliations')
    await page.goto('/home#/admin/affiliations')
    const { data: [target], total } = await (await initial).json()
    expect(total).toBeGreaterThan(25)
    const next = page.waitForResponse(response => new URL(response.url()).pathname === '/api/admin/affiliations' && new URL(response.url()).searchParams.get('page') === '2')
    await page.getByRole('menuitem', { name: 'Go to page 2', exact: true }).click()
    expect((await next).status()).toBe(200)
    const result = await search(page, 'affiliations', String(target.clingen_id))
    expect(result.current_page).toBe(1)
    expect(result.data.map(item => item.id)).toContain(target.id)
    await expect(page.getByRole('cell', { name: String(target.clingen_id), exact: true })).toBeVisible()
    assertClean()
})
