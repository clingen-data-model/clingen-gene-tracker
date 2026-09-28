import { expect, test } from '@playwright/test'

test('document upload displays 6 MB and accepts a file at that limit', async ({ page, baseURL }) => {
    const errors = []
    const local = url => !url || new URL(url).origin === new URL(baseURL).origin
    page.on('pageerror', error => errors.push(error.message))
    page.on('console', message => { if (message.type() === 'error' && local(message.location().url)) errors.push(message.text()) })
    page.on('requestfailed', request => { if (local(request.url())) errors.push(request.url()) })
    page.on('response', response => { if (local(response.url()) && response.status() >= 400) errors.push(`${response.status()} ${response.url()}`) })
    let uploadId
    try {
        await page.goto('/home#/curations/9102')
        await page.getByRole('button', { name: 'Add Document', exact: true }).click()
        const modal = page.getByRole('dialog')
        await modal.getByText('info', { exact: true }).click()
        await expect(modal.getByText('Max size: 6 MB', { exact: true })).toBeVisible()
        await modal.getByLabel('File:', { exact: true }).setInputFiles({
            name: 'e2e-six-mb-document.txt', mimeType: 'text/plain', buffer: Buffer.alloc(6 * 1024 * 1024, 'a'),
        })
        const saved = page.waitForResponse(response => new URL(response.url()).pathname === '/api/curations/9102/uploads' && response.request().method() === 'POST')
        await modal.getByRole('button', { name: 'Upload', exact: true }).click()
        const response = await saved
        expect(response.status()).toBe(201)
        uploadId = (await response.json()).data.id
        await expect(modal).toBeHidden()
        await expect(page.getByText('e2e-six-mb-document.txt', { exact: true }).first()).toBeVisible()
    } finally {
        if (uploadId) await page.evaluate(id => window.axios.delete(`/api/curations/9102/uploads/${id}`), uploadId)
    }
    expect(errors).toEqual([])
})
