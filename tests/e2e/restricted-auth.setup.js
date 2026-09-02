import { mkdir } from 'node:fs/promises'
import { dirname } from 'node:path'
import { expect, test as setup } from '@playwright/test'

const authFile = 'playwright/.auth/restricted-user.json'

setup('authenticate seeded restricted user through the login form', async ({ page }) => {
    await page.goto('/login')

    await page.getByLabel('E-Mail Address').fill('viewer@example.com')
    await page.getByLabel('Password').fill('tester')
    await page.getByRole('button', { name: 'Login' }).click()

    await page.waitForURL('**/home')
    await expect(page.getByText('Curation Viewer', { exact: false })).toBeVisible()

    await mkdir(dirname(authFile), { recursive: true })
    await page.context().storageState({ path: authFile })
})
