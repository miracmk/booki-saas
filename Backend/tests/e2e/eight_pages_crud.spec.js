const { test, expect } = require('@playwright/test');

const authFile = 'tests/e2e/.auth/admin.json';
test.use({ storageState: authFile });

test.describe('8-Page Audit & CRUD Verification', () => {

  test('1. Waitlist (/waitlist) - Add entry, list in table, cancel entry', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    await page.goto('/waitlist');
    await expect(page.locator('#waitlist-page')).toBeVisible();
    await expect(page.locator('h4')).toContainText(/Bekleme Listesi/i);

    // Click "Ekle" button
    await page.locator('#add-entry').click();
    await expect(page.locator('#entry-modal')).toBeVisible();

    // Populate form selects
    const customerSelect = page.locator('#entry-customer');
    await customerSelect.waitFor();
    const customerOptions = await customerSelect.locator('option').all();
    if (customerOptions.length > 1) {
      await customerSelect.selectOption({ index: 1 });
    }

    const serviceSelect = page.locator('#entry-service');
    const serviceOptions = await serviceSelect.locator('option').all();
    if (serviceOptions.length > 1) {
      await serviceSelect.selectOption({ index: 1 });
    }

    const tomorrow = new Date(Date.now() + 86400000).toISOString().split('T')[0];
    await page.locator('input[name="entry[requested_date]"]').fill(tomorrow);

    // Save entry
    await page.locator('#save-entry').click();
    await expect(page.locator('#entry-modal')).not.toBeVisible();

    // Verify row in table
    const tableRows = page.locator('table tbody tr');
    await expect(tableRows.first()).toBeVisible();

    // Cancel entry
    const cancelBtn = page.locator('table tbody tr .cancel-btn').first();
    await cancelBtn.click();

    expect(consoleErrors.filter(e => !e.includes('favicon'))).toHaveLength(0);
  });

  test('2. Memberships (/memberships) - Plan creation, membership sale & cancel', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    await page.goto('/memberships');
    await expect(page.locator('#memberships-page')).toBeVisible();
    await expect(page.locator('h4')).toContainText(/Üyelikler/i);

    // Create plan
    await page.locator('#add-plan').click();
    await expect(page.locator('#plan-modal')).toBeVisible();

    const planName = 'Test Plan ' + Date.now();
    await page.locator('input[name="plan[name]"]').fill(planName);

    const serviceSelect = page.locator('#plan-service');
    const serviceOptions = await serviceSelect.locator('option').all();
    if (serviceOptions.length > 1) {
      await serviceSelect.selectOption({ index: 1 });
    }

    await page.locator('input[name="plan[price]"]').fill('500');
    await page.locator('input[name="plan[sessions_per_period]"]').fill('5');
    await page.locator('#save-plan').click();
    await expect(page.locator('#plan-modal')).not.toBeVisible();

    // Sell membership
    await page.locator('#add-membership').click();
    await expect(page.locator('#membership-modal')).toBeVisible();

    const customerSelect = page.locator('#membership-customer');
    const customerOptions = await customerSelect.locator('option').all();
    if (customerOptions.length > 1) {
      await customerSelect.selectOption({ index: 1 });
    }

    const membershipPlanSelect = page.locator('#membership-plan');
    const planOptions = await membershipPlanSelect.locator('option').all();
    if (planOptions.length > 1) {
      await membershipPlanSelect.selectOption({ index: planOptions.length - 1 });
    }

    await page.locator('#save-membership').click();
    await expect(page.locator('#membership-modal')).not.toBeVisible();

    // Verify membership appears in table
    const tableRows = page.locator('table tbody tr');
    await expect(tableRows.first()).toBeVisible();

    // Cancel membership
    const cancelBtn = page.locator('table tbody tr button.cancel-btn').first();
    await cancelBtn.click();

    expect(consoleErrors.filter(e => !e.includes('favicon'))).toHaveLength(0);
  });

  test('3. Data Requests (/data_requests) - Status counters and filter rendering', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    await page.goto('/data_requests');
    await expect(page.locator('#data-requests-page')).toBeVisible();
    await expect(page.locator('h4')).toContainText(/Veri Talepleri/i);

    await expect(page.locator('#count-pending')).toBeVisible();
    await expect(page.locator('#count-processing')).toBeVisible();
    await expect(page.locator('#count-ready')).toBeVisible();

    await page.locator('#filter-type').selectOption('export');
    await page.locator('#filter-status').selectOption('pending');

    expect(consoleErrors.filter(e => !e.includes('favicon'))).toHaveLength(0);
  });

  test('4. Invoices (/invoices) - Header, export collapse, new invoice modal', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    await page.goto('/invoices');
    await expect(page.locator('#invoices-page')).toBeVisible();
    await expect(page.locator('h4')).toContainText(/Faturalar/i);

    // Check export collapse
    await page.locator('[data-bs-target="#invoice-export-collapse"]').click();
    await expect(page.locator('#invoice-export-csv')).toBeVisible();

    // Click new invoice
    await page.locator('#add-invoice').click();
    await expect(page.locator('#invoice-modal')).toBeVisible();

    const customerSelect = page.locator('#invoice-customer');
    const customerOptions = await customerSelect.locator('option').all();
    if (customerOptions.length > 1) {
      await customerSelect.selectOption({ index: 1 });
      await expect(page.locator('#billable-items-container')).toBeVisible();
    }

    await page.locator('#invoice-modal [data-bs-dismiss="modal"]').first().click();
    expect(consoleErrors.filter(e => !e.includes('favicon'))).toHaveLength(0);
  });

  test('5. POS (/pos) - Product selection, basket calculation, order creation', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    await page.goto('/pos');
    await expect(page.locator('#pos-page')).toBeVisible();
    await expect(page.locator('h4')).toContainText(/Satış Noktası/i);

    const productSelect = page.locator('#pos-product');
    await productSelect.waitFor();
    const productOptions = await productSelect.locator('option').all();

    if (productOptions.length > 1) {
      await productSelect.selectOption({ index: 1 });
      await page.locator('#pos-quantity').fill('2');
      await page.locator('#add-item').click();

      // Check basket has item
      await expect(page.locator('#pos-basket tr')).toBeVisible();

      // Create order
      await page.locator('#create-order').click();

      // Check table has orders
      await expect(page.locator('table tbody tr').first()).toBeVisible();
    }

    expect(consoleErrors.filter(e => !e.includes('favicon'))).toHaveLength(0);
  });

  test('6. Reports (/reports) - Date filter and EOD export collapse', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    await page.goto('/reports');
    await expect(page.locator('#reports-page')).toBeVisible();
    await expect(page.getByRole('heading', { name: /Günlük Ciro Raporu/i })).toBeVisible();

    const today = new Date().toISOString().split('T')[0];
    await page.locator('#report-date').fill(today);

    // Check export collapse
    await page.locator('[data-bs-target="#export-collapse"]').click();
    await expect(page.locator('#export-end-of-day')).toBeVisible();
    await expect(page.locator('#export-csv')).toBeVisible();

    expect(consoleErrors.filter(e => !e.includes('favicon'))).toHaveLength(0);
  });

  test('7. Marketing (/marketing) - Segments CRUD & Campaigns creation', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    await page.goto('/marketing');
    await expect(page.locator('#marketing-page')).toBeVisible();
    await expect(page.locator('h4')).toContainText(/Pazarlama/i);

    // Segments tab
    await expect(page.locator('#segments-tab')).toHaveClass(/active/);

    // Add segment
    await page.locator('#add-segment').click();
    await expect(page.locator('#segment-modal')).toBeVisible();

    const segName = 'VIP Test ' + Date.now();
    await page.locator('#segment-name').fill(segName);
    await page.locator('#segment-type').selectOption('all');
    await page.locator('#save-segment').click();
    await expect(page.locator('#segment-modal')).not.toBeVisible();

    // Verify in segments table
    await expect(page.locator('#segments-table')).toContainText(segName);

    // Switch to campaigns tab
    await page.locator('#campaigns-tab').click();
    await expect(page.locator('#campaigns-pane')).toBeVisible();

    // Add campaign
    await page.locator('#add-campaign').click();
    await expect(page.locator('#campaign-modal')).toBeVisible();

    const campName = 'Kampanya ' + Date.now();
    await page.locator('#campaign-name').fill(campName);
    await page.locator('#campaign-segment').selectOption({ index: 1 });
    await page.locator('#campaign-channel').selectOption('email');
    await page.locator('#campaign-subject').fill('Test Fırsat');
    await page.locator('#campaign-message').fill('Merhaba {{customer_name}}, özel indirim.');
    await page.locator('#save-campaign').click();
    await expect(page.locator('#campaign-modal')).not.toBeVisible();

    // Verify in campaigns table
    await expect(page.locator('#campaigns-table')).toContainText(campName);

    expect(consoleErrors.filter(e => !e.includes('favicon'))).toHaveLength(0);
  });

  test('8. Reviews (/reviews) - Tabs navigation, counts and moderation view', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    await page.goto('/reviews');
    await expect(page.locator('#reviews-page')).toBeVisible();
    await expect(page.locator('h4')).toContainText(/Yorumlar/i);

    // Tabs
    await page.locator('#requested-tab').click();
    await expect(page.locator('#requested-pane')).toBeVisible();

    await page.locator('#pending-tab').click();
    await expect(page.locator('#pending-pane')).toBeVisible();

    await page.locator('#published-tab').click();
    await expect(page.locator('#published-pane')).toBeVisible();

    await page.locator('#rejected-tab').click();
    await expect(page.locator('#rejected-pane')).toBeVisible();

    expect(consoleErrors.filter(e => !e.includes('favicon'))).toHaveLength(0);
  });
});
