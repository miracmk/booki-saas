const { test, expect } = require('@playwright/test');

const authFile = 'tests/e2e/.auth/admin.json';
test.use({ storageState: authFile });

test.describe('Business Settings & Working Plan Save Suite', () => {
  test('Save Business Settings & Working Plan successfully', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    await page.goto('/business_settings');
    await expect(page.locator('#business-logic-page')).toBeVisible();

    // Verify Save button exists
    const saveBtn = page.locator('#save-settings');
    await expect(saveBtn).toBeVisible();

    // Change a setting value (e.g. book advance timeout)
    const timeoutInput = page.locator('#book-advance-timeout');
    await expect(timeoutInput).toBeVisible();
    await timeoutInput.fill('45');

    // Click Save
    await saveBtn.click();

    // Expect success notification toast
    await expect(page.locator('.backend-notification, .toast')).toBeVisible({ timeout: 10000 });

    // Ensure 0 critical console errors
    const criticalErrors = consoleErrors.filter(e => !e.includes('favicon') && !e.includes('404'));
    expect(criticalErrors).toHaveLength(0);
  });
});
