import { expect, test } from '@playwright/test'

test('Working Groups show panel counts and select the first panel after loading', async ({ page, baseURL }) => {
    const errors = []
    const origin = new URL(baseURL).origin
    const isApplicationUrl = url => url && new URL(url).origin === origin
    page.on('pageerror', error => errors.push(error.message))
    page.on('console', message => {
        if (message.type() === 'error' && (!message.location().url || isApplicationUrl(message.location().url))) {
            errors.push(message.text())
        }
    })
    page.on('requestfailed', request => {
        if (isApplicationUrl(request.url())) errors.push(`${request.url()}: ${request.failure()?.errorText}`)
    })
    page.on('response', response => {
        if (isApplicationUrl(response.url()) && response.status() >= 400) errors.push(`${response.status()} ${response.url()}`)
    })

    const listResponse = page.waitForResponse(response => new URL(response.url()).pathname === '/api/working-groups')
    await page.goto('/home#/working-groups')
    const groups = await (await listResponse).json()
    const group = groups.find(item => item.id === 16)
    expect(group).toBeTruthy()
    expect(group.expert_panels_count).toBe(2)
    await page.getByPlaceholder('search working groups').fill(group.name)
    const row = page.getByRole('row').filter({ has: page.getByRole('link', { name: group.name, exact: true }) })
    await expect(row.getByRole('cell').last()).toHaveText(String(group.expert_panels_count))

    const detailResponse = page.waitForResponse(response => new URL(response.url()).pathname === `/api/working-groups/${group.id}`)
    await page.getByRole('link', { name: group.name, exact: true }).click()
    const { data } = await (await detailResponse).json()
    const firstPanel = page.getByRole('tab', { name: data.expert_panels[0].name, exact: true })
    await expect(firstPanel).toHaveAttribute('aria-selected', 'true')
    await expect(page.getByRole('tab', { name: /^People/ })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Email', exact: true })).toBeVisible()
    await page.getByRole('tab', { name: /^Curations/ }).click()
    if (data.expert_panels[0].curations_count > 0) {
        await expect(page.getByText('Total Records:')).toBeVisible()
    } else {
        await expect(page.getByText(`${data.expert_panels[0].name} doesn't have any curations yet.`)).toBeVisible()
    }

    const secondPanel = page.getByRole('tab', { name: data.expert_panels[1].name, exact: true })
    await secondPanel.click()
    await expect(secondPanel).toHaveAttribute('aria-selected', 'true')
    await expect(firstPanel).toHaveAttribute('aria-selected', 'false')
    await expect(page.getByRole('columnheader', { name: 'Email', exact: true })).toBeVisible()
    expect(errors).toEqual([])
})
