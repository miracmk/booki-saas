const { test, expect } = require('@playwright/test');

/**
 * BooKi (2026-09-17) - functional verification for the two "İlk Müsaitlik" ranking features:
 *   - Providers_model::get_satisfaction_scores() (Bayesian-weighted published-review average)
 *   - stations.display_order (admin-configurable room priority, migration 145)
 *
 * Runs against the `demo-guzellik` tenant, not `qatest` - qatest only has 1 seeded provider and
 * 0 stations (nothing to rank), while demo-guzellik has 2 providers + 3 stations and was seeded
 * with synthetic published reviews for this verification (Jane Doe: 3 reviews avg 3.33, Elif:
 * 1 review of 5 -> Bayesian scores ~3.59 vs ~3.96, Elif should rank first). This is a *separate*
 * base URL from playwright.config.js's default (qatest), so it doesn't use the shared
 * `tests/e2e/.auth/admin.json` storage state - it logs in for itself.
 */

test.describe('İlk Müsaitlik sıralama (demo-guzellik)', () => {
  test.use({ baseURL: 'https://demo-guzellik-bookiapp.kibusiness.co' });

  test('Sağlayıcılar müşteri memnuniyeti skoruna göre sıralanıyor', async ({ page }) => {
    await page.goto('/login');
    await page.getByPlaceholder(/username/i).fill('administrator');
    await page.getByPlaceholder(/password/i).fill('administrator');
    await page.getByRole('button', { name: /login/i }).click();
    await expect(page).toHaveURL(/dashboard/);
    await page.waitForFunction(() => typeof window.App !== 'undefined' && typeof window.App.Http !== 'undefined' && typeof window.App.Http.Calendar !== 'undefined');

    // Call the app's own authenticated JS client in-page (reuses the session cookie + CSRF token
    // already loaded into the page) instead of re-implementing the request by hand.
    const result = await page.evaluate(async () => {
      return new Promise((resolve) => {
        App.Http.Calendar.getNextAvailability(null)
          .done((response) => resolve({ ok: true, response }))
          .fail((xhr) => resolve({ ok: false, status: xhr.status, body: xhr.responseText }));
      });
    });

    expect(result.ok, 'get_next_availability request failed: ' + JSON.stringify(result)).toBe(true);
    expect(result.response.rows.length).toBeGreaterThanOrEqual(2);

    const names = result.response.rows.map((r) => r.provider_name);
    const elifIndex = names.findIndex((n) => n.includes('Elif'));
    const janeIndex = names.findIndex((n) => n.includes('Jane'));

    expect(elifIndex, 'Elif not found in ranked rows: ' + JSON.stringify(names)).toBeGreaterThanOrEqual(0);
    expect(janeIndex, 'Jane not found in ranked rows: ' + JSON.stringify(names)).toBeGreaterThanOrEqual(0);
    // Elif (1 review, 5 stars, weighted ~3.96) should outrank Jane (3 reviews, avg 3.33, weighted ~3.59).
    expect(elifIndex).toBeLessThan(janeIndex);
  });

  test('İstasyonlar display_order alanına göre sıralanıyor', async ({ page, request }) => {
    await page.goto('/login');
    await page.getByPlaceholder(/username/i).fill('administrator');
    await page.getByPlaceholder(/password/i).fill('administrator');
    await page.getByRole('button', { name: /login/i }).click();
    await expect(page).toHaveURL(/dashboard/);
    await page.waitForFunction(() => typeof window.App !== 'undefined' && typeof window.App.Http !== 'undefined' && typeof window.App.Http.Calendar !== 'undefined');

    const before = await page.evaluate(async () => {
      return new Promise((resolve) => {
        App.Http.Calendar.getRoomAvailability()
          .done((response) => resolve(response.rows.map((r) => r.station_name)))
          .fail(() => resolve(null));
      });
    });
    expect(before).not.toBeNull();
    expect(before.length).toBeGreaterThanOrEqual(3);

    // Give the LAST station (alphabetically/originally) the lowest display_order so it should
    // jump to the front - proves the field is actually read, not just stored cosmetically.
    await page.goto('/stations');
    await page.locator('.station-row', { hasText: before[before.length - 1] }).click();
    await page.locator('#display-order').fill('-1');
    await page.locator('#save-station').click();
    await expect(page.locator('.form-message.alert-success, .alert-success')).toBeVisible({ timeout: 8000 }).catch(() => {});

    await page.goto('/dashboard'); // App.Http.Calendar is only loaded on dashboard/calendar pages, not /stations
    await page.waitForFunction(() => typeof window.App !== 'undefined' && typeof window.App.Http !== 'undefined' && typeof window.App.Http.Calendar !== 'undefined');
    const after = await page.evaluate(async () => {
      return new Promise((resolve) => {
        App.Http.Calendar.getRoomAvailability()
          .done((response) => resolve(response.rows.map((r) => r.station_name)))
          .fail(() => resolve(null));
      });
    });

    expect(after[0]).toBe(before[before.length - 1]);

    // Revert so re-runs of this test stay idempotent.
    await page.goto('/stations');
    await page.locator('.station-row', { hasText: before[before.length - 1] }).click();
    await page.locator('#display-order').fill('0');
    await page.locator('#save-station').click();
  });
});
