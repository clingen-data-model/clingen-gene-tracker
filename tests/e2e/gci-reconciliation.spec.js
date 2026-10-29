import { expect, test } from '@playwright/test'
import { readFile } from 'node:fs/promises'

test('admin uploads a paired run, reviews a manual match, previews, applies and exports JSON', async ({ page, baseURL }) => {
    const errors = []
    const local = url => !url || new URL(url).origin === new URL(baseURL).origin
    page.on('pageerror', error => errors.push(error.message))
    page.on('console', message => { if (message.type() === 'error' && local(message.location().url)) errors.push(message.text()) })
    page.on('requestfailed', request => { if (local(request.url())) errors.push(request.url()) })
    page.on('response', response => { if (local(response.url()) && response.status() >= 400) errors.push(`${response.status()} ${response.url()}`) })
    await page.goto('/home#/admin/gci-reconciliation')
    await expect(page.getByRole('heading', { name: 'GCI reconciliation', exact: true })).toBeVisible()
    await page.getByLabel('Optional run label').fill('E2E GCI review and export')
    await page.getByRole('button', { name: 'Create run', exact: true }).click()
    await expect(page.getByRole('heading', { name: 'Upload GCI JSON' })).toBeVisible()
    const item = { gene: 'E2E-BRAVO', modeInheritance: 'Autosomal dominant inheritance (HP:0000006)', affiliation: '998811', disease: 'MONDO_9988110', PK: '20000000-0000-4000-8000-000000009102' }
    for (const [type, items] of [['HAS_PRECURATION_ID', []], ['NO_PRECURATION_ID', [item]]]) {
        await page.getByLabel('File type', { exact: true }).selectOption(type)
        await page.getByLabel('JSON file', { exact: true }).setInputFiles({ name: 'arbitrary.json', mimeType: 'application/json', buffer: Buffer.from(JSON.stringify({ Items: items })) })
        const uploaded = page.waitForResponse(r => r.url().endsWith('/uploads') && r.request().method() === 'POST')
        await page.getByRole('button', { name: 'Upload to selected run' }).click()
        expect((await uploaded).ok()).toBe(true)
        await expect(page.getByRole('button', { name: 'Upload to selected run' })).toBeEnabled()
    }
    await page.getByRole('button', { name: 'Reconcile both files' }).click()
    const row = page.getByRole('row').filter({ hasText: 'E2E-BRAVO' })
    await expect(row).toContainText('NOT_FOUND_IN_GT')
    await row.getByRole('button', { name: /Review/ }).click()
    await expect(page.getByText('Possible GT candidates (investigation hints only):')).toBeVisible()
    await page.getByLabel('Review status', { exact: true }).selectOption('REVIEWED')
    await page.getByLabel('Resolution', { exact: true }).selectOption('MATCH_EXISTING_GT_CURATION')
    await page.getByLabel('Resolved GT curation ID (manual match)').fill('9102')
    await page.getByLabel('Review notes', { exact: true }).fill('Deterministic manual identity review')
    await page.getByRole('button', { name: 'Record decision for selected rows' }).click()
    await expect(page.getByRole('status')).toContainText('Review decisions saved')
    await page.getByRole('button', { name: 'Preview selected decisions' }).click()
    await expect(page.getByRole('heading', { name: 'Execution preview' })).toBeVisible()
    await page.getByRole('button', { name: 'Confirm Apply 1 previewed decisions' }).click()
    await expect(page.getByRole('status')).toContainText('APPLIED')
    await expect(page.getByText('Execution: APPLIED', { exact: false })).toBeVisible()
    const downloadPromise = page.waitForEvent('download')
    await page.getByRole('button', { name: 'Download eligible selected GCI records' }).click()
    const download = await downloadPromise
    expect(download.suggestedFilename()).toBe('gdmHasPreCurId.json')
    const payload = JSON.parse(await readFile(await download.path(), 'utf8'))
    expect(payload.Items).toEqual([{ ...item, precurationId: '9102' }])
    expect(payload.Count).toBe(1)
    expect(errors).toEqual([])
})
