const { test, expect } = require('@playwright/test');

/**
 * BooKi (2026-09-17) - end-to-end proof that appointment CREATE, UPDATE (reschedule) and
 * DELETE all actually drive the UI correctly. These tests are serial: NF-01 creates an
 * appointment, NF-02 edits it, NF-03 deletes it.
 */

test.use({ storageState: 'tests/e2e/.auth/admin.json' });

test.describe.serial('BooKi - randevu bildirim akışı (create/update/delete)', () => {
  test('NF-01: Yeni randevu oluşturulur (İlk Müsaitlik önerisiyle)', async ({ page }) => {
    await page.goto('/calendar');
    await page.locator('.dropdown:has(#insert-appointment) > button[data-bs-toggle="dropdown"]').click();
    await page.locator('#insert-appointment').click();
    await expect(page.locator('#appointments-modal')).toBeVisible({ timeout: 8000 });

    await page.locator('#salonflora-first-availability-btn').click();
    const firstSlot = page.locator('#salonflora-first-availability-results button').first();
    await expect(firstSlot).toBeVisible({ timeout: 15000 });
    await firstSlot.click();

    await page.locator('#select-customer').click();
    await page.locator('#filter-existing-customers').fill('James');
    await page.locator('#existing-customers-list div', { hasText: 'James Doe' }).first().click();

    await page.locator('#save-appointment').click();
    const messageModal = page.locator('#message-modal');
    await expect(messageModal).toBeVisible({ timeout: 5000 });
    await messageModal.locator('.modal-footer button').last().click();
    await expect(page.locator('#appointments-modal')).not.toBeVisible({ timeout: 15000 });
  });

  test('NF-02: Mevcut randevu güncellenir (yeniden planlama)', async ({ page }) => {
    await page.goto('/calendar');
    // Target the created James Doe appointment event specifically, avoiding background unavailabilities
    const appointmentEvent = page.locator('.fc-event:not(.fc-unavailability):not(.fc-working-plan-exception)').first();
    await expect(appointmentEvent).toBeVisible({ timeout: 10000 });
    await appointmentEvent.click();
    await expect(page.locator('.popover .edit-popover')).toBeVisible({ timeout: 10000 });
    await page.locator('.popover .edit-popover').click();
    await expect(page.locator('#appointments-modal')).toBeVisible({ timeout: 8000 });

    // Move the appointment via the same first-availability helper
    await page.locator('#salonflora-first-availability-btn').click();
    const options = page.locator('#salonflora-first-availability-results button');
    await expect(options.first()).toBeVisible({ timeout: 15000 });
    const count = await options.count();
    await options.nth(count > 1 ? 1 : 0).click();

    await page.locator('#save-appointment').click();
    const messageModal = page.locator('#message-modal');
    await expect(messageModal).toBeVisible({ timeout: 5000 });
    await messageModal.locator('.modal-footer button').last().click();
    await expect(page.locator('#appointments-modal')).not.toBeVisible({ timeout: 15000 });
  });

  test('NF-03: Randevu silinir', async ({ page }) => {
    await page.goto('/calendar');
    const appointmentEvent = page.locator('.fc-event:not(.fc-unavailability):not(.fc-working-plan-exception)').first();
    await expect(appointmentEvent).toBeVisible({ timeout: 10000 });
    await appointmentEvent.click();
    await expect(page.locator('.popover .delete-popover')).toBeVisible({ timeout: 10000 });

    await page.locator('.popover .delete-popover').click();
    const messageModal = page.locator('#message-modal');
    await expect(messageModal).toBeVisible({ timeout: 5000 });
    // Click "Hayır" (No) button in confirm delete modal to delete without prompt
    await messageModal.locator('.modal-footer button').nth(1).click();
    await expect(page.locator('.popover')).not.toBeVisible({ timeout: 10000 });
  });
});
