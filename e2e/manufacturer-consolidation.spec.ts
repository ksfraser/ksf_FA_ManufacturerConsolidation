import { test, expect } from '@playwright/test';

test.describe('Manufacturer Consolidation Module', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/');
    });

    test('shows consolidation recommendations', async ({ page }) => {
        await expect(page.locator('h1')).toContainText('Consolidation');
    });

    test('displays MOQ recommendations', async ({ page }) => {
        await page.goto('/modules/ksf_FA_ManufacturerConsolidation/');
        const moqColumn = page.locator('text=MOQ');
        await expect(moqColumn).toBeVisible();
    });
});