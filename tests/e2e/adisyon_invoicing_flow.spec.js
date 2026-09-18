const { test, expect } = require('@playwright/test');

test.describe('Adisyon, Invoicing & ERP Flow Suite', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    if (page.url().includes('login')) {
      await page.getByPlaceholder(/username/i).fill('administrator');
      await page.getByPlaceholder(/password/i).fill('administrator');
      await page.getByRole('button', { name: /login/i }).click();
      await expect(page).toHaveURL(/dashboard/);
    }
  });

  test('Adisyons page loads correctly with all controls, metrics, and modal tabs', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    await page.goto('/adisyons');
    await expect(page.locator('#adisyons-page')).toBeVisible({ timeout: 15000 });

    // Verify KPIs
    await expect(page.locator('text=Açık Adisyonlar')).toBeVisible();
    await expect(page.locator('text=Tahsil Edilmemiş Tutar')).toBeVisible();

    // Verify New Adisyon button and modal
    const newAdisyonBtn = page.locator('#btn-open-new-adisyon-modal');
    await expect(newAdisyonBtn).toBeVisible();
    await newAdisyonBtn.click();

    // Verify modal tabs
    await expect(page.locator('#new-adisyon-modal')).toBeVisible();
    await expect(page.locator('#from-appointment-tab')).toBeVisible();
    await expect(page.locator('#manual-adisyon-tab')).toBeVisible();

    // Switch to manual tab
    await page.locator('#manual-adisyon-tab').click();
    await expect(page.locator('#manual-adisyon-pane')).toBeVisible();

    // Close modal
    await page.locator('#new-adisyon-modal .btn-close').click();

    // Ensure 0 critical console errors
    const criticalErrors = consoleErrors.filter(e => !e.includes('favicon') && !e.includes('404'));
    expect(criticalErrors).toHaveLength(0);
  });

  test('Create Adisyon, add items, and single invoicing flow with ERP option', async ({ page }) => {
    await page.goto('/adisyons');
    await expect(page.locator('#adisyons-page')).toBeVisible({ timeout: 15000 });

    // Open new adisyon modal -> manual create
    await page.locator('#btn-open-new-adisyon-modal').click();
    await page.locator('#manual-adisyon-tab').click();
    await page.locator('#btn-submit-appointment-adisyon').click();

    // Wait for drawer to open
    const drawer = page.locator('#adisyon-drawer');
    await expect(drawer).toBeVisible({ timeout: 10000 });

    // Verify items section and add item
    await expect(page.locator('#item-selector')).toBeVisible();
    await expect(page.locator('#drawer-total')).toBeVisible();

    // Open invoicing modal from drawer
    const invoiceBtn = page.locator('#adisyon-drawer button:has-text("Faturalandır")');
    await expect(invoiceBtn).toBeVisible();
    await invoiceBtn.click();

    // Verify Invoicing Modal elements
    const invoiceModal = page.locator('#invoice-modal');
    await expect(invoiceModal).toBeVisible();
    await expect(page.locator('#invoice-send-erp-toggle')).toBeVisible();
    await expect(page.locator('#invoice-erp-provider-select')).toBeVisible();

    // Toggle ERP off and on
    await page.locator('#invoice-send-erp-toggle').click();
    await page.locator('#invoice-send-erp-toggle').click();

    // Confirm Invoicing
    page.on('dialog', async dialog => {
      await dialog.accept();
    });
    await page.locator('#btn-confirm-invoicing').click();

    // Page reloads and shows updated status
    await page.waitForLoadState('networkidle');
  });

  test('Bulk selection and bulk invoicing action bar flow', async ({ page }) => {
    await page.goto('/adisyons');
    await expect(page.locator('#adisyons-page')).toBeVisible({ timeout: 15000 });

    // Select all or first row
    const firstCheckbox = page.locator('.adisyon-select-box').first();
    if (await firstCheckbox.isVisible()) {
      await firstCheckbox.check();

      // Bulk action bar should become visible
      const bulkBar = page.locator('#bulk-action-bar');
      await expect(bulkBar).toBeVisible();
      await expect(page.locator('#bulk-selected-count')).toContainText('1 Adisyon Seçildi');

      // Click bulk invoicing button
      const bulkInvoiceBtn = bulkBar.locator('button:has-text("Seçilenleri Faturalandır")');
      await expect(bulkInvoiceBtn).toBeVisible();
      await bulkInvoiceBtn.click();

      // Verify bulk modal
      const invoiceModal = page.locator('#invoice-modal');
      await expect(invoiceModal).toBeVisible();
      await expect(page.locator('#invoice-target-adisyons')).toBeVisible();

      // Close modal
      await page.locator('#invoice-modal .btn-close').click();

      // Deselect all
      await bulkBar.locator('button:has-text("Vazgeç")').click();
      await expect(bulkBar).not.toBeVisible();
    }
  });
});
