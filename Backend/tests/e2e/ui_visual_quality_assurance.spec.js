const { test, expect } = require('@playwright/test');

const authFile = 'tests/e2e/.auth/admin.json';
test.use({ storageState: authFile });

test.describe('UI, Presentation & Visual Quality Assurance Guards', () => {

  test('1. Packages Page (/packages) - Visual & Data Quality Checks', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    await page.goto('/packages');
    await page.waitForLoadState('domcontentloaded');

    // Quality Guard: Zero console errors on render
    const filteredErrors = consoleErrors.filter(e => !e.includes('favicon'));
    expect(filteredErrors).toHaveLength(0);

    // Quality Guard: Table headers must not contain raw uppercase translation keys (e.g. TOTAL_SESSIONS, EXPIRES)
    const headerElements = await page.locator('table thead th').allTextContents();
    for (const header of headerElements) {
      const cleanHeader = header.trim();
      if (cleanHeader.length > 3) {
        expect(cleanHeader).not.toMatch(/^[A-Z_]{4,}$/);
      }
    }

    // Quality Guard: No PII ciphertext (SFENC1:) anywhere on the rendered page
    const pageHtml = await page.content();
    expect(pageHtml).not.toContain('SFENC1:');

    // Quality Guard: Check rows in table (if any)
    const rows = page.locator('table tbody tr');
    const rowCount = await rows.count();
    if (rowCount > 0) {
      const firstRowText = await rows.first().textContent();
      // If not empty state
      if (!firstRowText.includes('Henüz') && !firstRowText.includes('bulunmuyor')) {
        const customerCell = await rows.first().locator('td').nth(1).textContent();
        // Customer cell must not be a bare numeric ID like "20"
        expect(customerCell.trim()).not.toMatch(/^\d+$/);
      }
    }
  });

  test('2. Products Page (/products) - Visual & Data Quality Checks', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    await page.goto('/products');
    await page.waitForLoadState('domcontentloaded');

    const filteredErrors = consoleErrors.filter(e => !e.includes('favicon'));
    expect(filteredErrors).toHaveLength(0);

    // Quality Guard: No raw uppercase headers (e.g. SKU, SALE_PRICE, STOCK_QUANTITY)
    const headerElements = await page.locator('table thead th').allTextContents();
    for (const header of headerElements) {
      const cleanHeader = header.trim();
      if (cleanHeader.length > 3) {
        expect(cleanHeader).not.toMatch(/^[A-Z_]{4,}$/);
      }
    }

    // Quality Guard: No deprecated badge-success or badge-danger classes
    const deprecatedBadges = await page.locator('.badge-success, .badge-danger, .badge-warning').count();
    expect(deprecatedBadges).toBe(0);

    // Quality Guard: No PII ciphertext
    const pageHtml = await page.content();
    expect(pageHtml).not.toContain('SFENC1:');
  });

  test('3. Audit Log Page (/audit_log) - Visual Formatting & JSON Sanitization', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    await page.goto('/audit_log');
    await page.waitForLoadState('domcontentloaded');

    const filteredErrors = consoleErrors.filter(e => !e.includes('favicon'));
    expect(filteredErrors).toHaveLength(0);

    // Quality Guard: Filter card and inputs properly rendered
    await expect(page.locator('#filter-action')).toBeVisible();

    // Quality Guard: No raw PII ciphertext
    const pageHtml = await page.content();
    expect(pageHtml).not.toContain('SFENC1:');
  });

  test('4. Adisyons Page (/adisyons) - Table Quality & PII Decryption', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    await page.goto('/adisyons');
    await page.waitForLoadState('domcontentloaded');

    const filteredErrors = consoleErrors.filter(e => !e.includes('favicon'));
    expect(filteredErrors).toHaveLength(0);

    // Quality Guard: No PII ciphertext in adisyons table or customer information
    const pageHtml = await page.content();
    expect(pageHtml).not.toContain('SFENC1:');

    // Quality Guard: No unlocalized raw headers
    const headers = await page.locator('table thead th').allTextContents();
    for (const header of headers) {
      const clean = header.trim();
      if (clean.length > 3) {
        expect(clean).not.toMatch(/^[A-Z_]{4,}$/);
      }
    }
  });

  test('5. Marketing Page (/marketing) - Segment Integrity', async ({ page }) => {
    const consoleErrors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    await page.goto('/marketing');
    await page.waitForLoadState('domcontentloaded');

    const filteredErrors = consoleErrors.filter(e => !e.includes('favicon'));
    expect(filteredErrors).toHaveLength(0);

    // Quality Guard: No PII ciphertext
    const pageHtml = await page.content();
    expect(pageHtml).not.toContain('SFENC1:');
  });

});

