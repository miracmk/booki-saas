const { test: setup, expect } = require('@playwright/test');

const authFile = 'tests/e2e/.auth/admin.json';

// Logs into the qatest tenant admin panel once; every other spec reuses this session via
// `storageState` instead of re-logging-in per test (see playwright.config.js projects, if added,
// or per-test `test.use({ storageState: authFile })`).
setup('authenticate as qatest admin', async ({ page }) => {
  await page.goto('/login');
  await page.locator('#username').fill('administrator');
  await page.locator('#password').fill('administrator');
  await page.locator('#login').click();
  await expect(page).toHaveURL(/dashboard/);
  await page.context().storageState({ path: authFile });
});
