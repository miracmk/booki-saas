const { test: setup, expect } = require('@playwright/test');

const authFile = 'tests/e2e/.auth/admin.json';

// Logs into the qatest tenant admin panel once; every other spec reuses this session via
// `storageState` instead of re-logging-in per test (see playwright.config.js projects, if added,
// or per-test `test.use({ storageState: authFile })`).
setup('authenticate as qatest admin', async ({ page }) => {
  await page.goto('/login');
  await page.getByPlaceholder(/username/i).fill('administrator');
  await page.getByPlaceholder(/password/i).fill('administrator');
  await page.getByRole('button', { name: /login/i }).click();
  await expect(page).toHaveURL(/dashboard/);
  await page.context().storageState({ path: authFile });
});
