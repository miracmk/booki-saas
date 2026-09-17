const { test, expect } = require('@playwright/test');

/**
 * BooKi (2026-09-17) - end-to-end proof that appointment CREATE, UPDATE (reschedule) and
 * DELETE all actually attempt a WhatsApp send (Notifications::do_send_whatsapp(), already
 * fixed in an earlier tour to log every outcome to `whatsapp_messages` instead of silently
 * swallowing it - see docs/SESSION_NOTES.md). This spec only drives the UI; the actual
 * whatsapp_messages row-count assertions are done by the caller via direct DB read
 * (tests/e2e/README or the tour's own shell check), because Playwright has no DB access here -
 * this file's job is solely to perform the three real actions a receptionist would.
 */

test.use({ storageState: 'tests/e2e/.auth/admin.json' });

test.describe('BooKi - randevu bildirim akışı (create/update/delete)', () => {
  test('NF-01: Yeni randevu oluşturulur (İlk Müsaitlik önerisiyle)', async ({ page }) => {
    await page.goto('/calendar');
    await page.locator('.dropdown:has(#insert-appointment) > button[data-bs-toggle="dropdown"]').click();
    await page.locator('#insert-appointment').click();
    await expect(page.locator('#appointments-modal, .modal.show')).toBeVisible({ timeout: 8000 });

    await page.locator('#salonflora-first-availability-btn').click();
    await page.locator('#salonflora-first-availability-results button').first().click({ timeout: 10000 });

    await page.locator('#select-customer').click();
    await page.locator('#filter-existing-customers').fill('James');
    await page.locator('#existing-customers-list div', { hasText: 'James Doe' }).first().click();

    await page.locator('#save-appointment').click();
    await expect(page.locator('#appointments-modal, .modal.show')).not.toBeVisible({ timeout: 10000 });
  });

  test('NF-02: Mevcut randevu güncellenir (yeniden planlama)', async ({ page }) => {
    await page.goto('/calendar');
    // Clicking a FullCalendar event opens a POPOVER (not the edit modal directly) - "Düzenle"
    // (.edit-popover) opens #appointments-modal, "Sil" (.delete-popover) deletes in place.
    await page.locator('.fc-event').first().click();
    await expect(page.locator('.popover .edit-popover')).toBeVisible({ timeout: 8000 });
    await page.locator('.popover .edit-popover').click();
    await expect(page.locator('#appointments-modal, .modal.show')).toBeVisible({ timeout: 8000 });

    // Move the appointment via the same first-availability helper (any different slot counts
    // as a real reschedule/update from the backend's point of view).
    await page.locator('#salonflora-first-availability-btn').click();
    const options = page.locator('#salonflora-first-availability-results button');
    await expect(options.first()).toBeVisible({ timeout: 10000 });
    const count = await options.count();
    await options.nth(count > 1 ? 1 : 0).click();

    await page.locator('#save-appointment').click();
    await expect(page.locator('#appointments-modal, .modal.show')).not.toBeVisible({ timeout: 10000 });
  });

  test('NF-03: Randevu silinir', async ({ page }) => {
    await page.goto('/calendar');
    await page.locator('.fc-event').first().click();
    await expect(page.locator('.popover .delete-popover')).toBeVisible({ timeout: 8000 });

    page.once('dialog', (dialog) => dialog.accept());
    await page.locator('.popover .delete-popover').click();
    await expect(page.locator('.popover')).not.toBeVisible({ timeout: 10000 });
  });
});
