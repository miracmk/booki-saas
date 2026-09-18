const { test, expect } = require('@playwright/test');

const authFile = 'tests/e2e/.auth/admin.json';
test.use({ storageState: authFile });

test.describe('BooKi World-Class SaaS Platform Transformation E2E Suite', () => {

  test('1. Adisyons & Fast Checkout (/adisyons)', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    await page.goto('/adisyons');
    await expect(page.locator('#adisyons-page')).toBeVisible();
    await expect(page.locator('text=Açık Adisyonlar')).toBeVisible();
    await expect(page.locator('#btn-open-new-adisyon-modal')).toBeVisible();

    // Click "Yeni Adisyon Aç"
    await page.locator('#btn-open-new-adisyon-modal').click();
    await expect(page.locator('#new-adisyon-modal')).toBeVisible();

    // Select customer if available
    const custSelect = page.locator('#new-adisyon-modal select[name="id_users_customer"]');
    const options = await custSelect.locator('option').all();
    if (options.length > 1) {
      await custSelect.selectOption({ index: 1 }, { force: true });
    }

    // Submit new adisyon
    await page.locator('#btn-submit-new-adisyon').click();
    await page.waitForTimeout(1000);
    await page.goto('/adisyons');
    await expect(page.locator('#adisyons-page')).toBeVisible();

    // Verify row appears or open drawer
    const editBtn = page.locator('#adisyons-table button:has-text("Detay / Ödeme")').first();
    if (await editBtn.count() > 0) {
      await editBtn.click();
      await expect(page.locator('#adisyon-drawer')).toBeVisible();
      await page.locator('#adisyon-drawer .btn-close').click();
    }

    expect(consoleErrors.filter(e => !e.includes('favicon'))).toHaveLength(0);
  });

  test('2. Restaurant Visual Floor Plan & Reservations (/restaurant)', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    // Floor plan
    await page.goto('/restaurant');
    await expect(page.locator('#restaurant-floor-plan-page')).toBeVisible();
    await expect(page.locator('#floor-plan-canvas')).toBeVisible();
    await expect(page.locator('#btn-new-table-modal')).toBeVisible();

    // Reservations list
    await page.goto('/restaurant/reservations');
    await expect(page.locator('#restaurant-reservations-page')).toBeVisible();
    await expect(page.locator('#btn-new-res-modal')).toBeVisible();
    await expect(page.locator('#restaurant-res-table')).toBeVisible();

    expect(consoleErrors.filter(e => !e.includes('favicon'))).toHaveLength(0);
  });

  test('3. Operational Check-In Dashboard & Touch Kiosk (/checkin)', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    // Checkin Dashboard
    await page.goto('/checkin');
    await expect(page.locator('#checkin-dashboard-page')).toBeVisible();
    await expect(page.locator('#occupancy-bar')).toBeVisible();
    await expect(page.locator('#btn-kiosk-mode')).toBeVisible();
    await expect(page.locator('#checkin-quick-input')).toBeVisible();

    // Tablet Touch Kiosk
    await page.goto('/checkin/kiosk');
    await expect(page.locator('#kiosk-touch-page')).toBeVisible();
    await expect(page.locator('#kiosk-pass-input')).toBeVisible();
    await expect(page.locator('.keypad-btn').first()).toBeVisible();

    // Type on keypad
    await page.locator('.keypad-btn:has-text("5")').click();
    await page.locator('.keypad-btn:has-text("5")').click();
    await expect(page.locator('#kiosk-pass-input')).toHaveValue('55');

    // Clear
    await page.locator('.keypad-btn:has-text("Sil")').click();
    await expect(page.locator('#kiosk-pass-input')).toHaveValue('');

    expect(consoleErrors.filter(e => !e.includes('favicon'))).toHaveLength(0);
  });

  test('4. Financial Operations Control Center & Expenses (/finance, /expenses)', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    // Finance Hub
    await page.goto('/finance');
    await expect(page.locator('#finance-dashboard-page')).toBeVisible();
    await expect(page.locator('#kpi-total-income')).toBeVisible();
    await expect(page.locator('#kpi-total-expense')).toBeVisible();
    await expect(page.locator('#kpi-net-cash')).toBeVisible();
    await expect(page.locator('#cash-registers-table')).toBeVisible();
    await expect(page.locator('#bank-accounts-table')).toBeVisible();

    // Expenses page
    await page.goto('/expenses');
    await expect(page.locator('#expenses-page')).toBeVisible();
    await expect(page.locator('#btn-new-expense')).toBeVisible();
    await expect(page.locator('#expenses-table')).toBeVisible();

    // Open new expense modal
    await page.locator('#btn-new-expense').click();
    await expect(page.locator('#modal-expense-form')).toBeVisible();
    await page.locator('#modal-expense-form .btn-close').click();

    expect(consoleErrors.filter(e => !e.includes('favicon'))).toHaveLength(0);
  });

  test('5. Customer 360° Drawer & Activity Timeline (/customers)', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    await page.goto('/customers');
    await expect(page.locator('#customers')).toBeVisible();

    // Open 360 drawer if customer button exists
    const btn360 = page.locator('#btn-open-360-drawer');
    if (await btn360.count() > 0) {
      await expect(btn360).toBeVisible();
    }

    expect(consoleErrors.filter(e => !e.includes('favicon'))).toHaveLength(0);
  });

  test('6. Global Omnisearch Command Center API (/search/omnisearch)', async ({ request }) => {
    const response = await request.get('/search/omnisearch?q=demo');
    expect(response.status()).toBe(200);
    const body = await response.json();
    expect(body).toHaveProperty('success', true);
    expect(body).toHaveProperty('results');
    expect(Array.isArray(body.results)).toBe(true);
  });

});
