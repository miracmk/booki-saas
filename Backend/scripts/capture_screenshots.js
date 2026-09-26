const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const outputDir = path.join(__dirname, '../docs/screenshots/audit_8_pages');
if (!fs.existsSync(outputDir)) {
  fs.mkdirSync(outputDir, { recursive: true });
}

const authFile = path.join(__dirname, '../tests/e2e/.auth/admin.json');

const pagesToCapture = [
  // Reference modern pages
  { name: '00_ref_customers', path: '/customers' },
  { name: '00_ref_services', path: '/services' },
  { name: '00_ref_providers', path: '/providers' },
  // 8 Audited pages
  { name: '01_waitlist', path: '/waitlist' },
  { name: '02_memberships', path: '/memberships' },
  { name: '03_data_requests', path: '/data_requests' },
  { name: '04_invoices', path: '/invoices' },
  { name: '05_pos', path: '/pos' },
  { name: '06_reports', path: '/reports' },
  { name: '07_marketing', path: '/marketing' },
  { name: '08_reviews', path: '/reviews' },
  // New features
  { name: '09_messaging_settings', path: '/messaging_settings' },
  { name: '10_instagram', path: '/instagram' },
];

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    storageState: authFile,
    baseURL: 'https://qatest-bookiapp.kibusiness.co',
    viewport: { width: 1440, height: 900 }
  });
  const page = await context.newPage();

  for (const item of pagesToCapture) {
    console.log(`Navigating to ${item.name} (${item.path})...`);
    try {
      await page.goto(item.path, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await page.locator('h4, .backend-page, .card, main').first().waitFor({ state: 'visible', timeout: 10000 });
      await page.waitForTimeout(600); // Allow fonts & tables to settle
      const filePath = path.join(outputDir, `${item.name}.png`);
      await page.screenshot({ path: filePath, fullPage: false });
      console.log(`Saved screenshot to ${filePath}`);
    } catch (err) {
      console.error(`Failed to capture ${item.name}: ${err.message}`);
    }
  }

  await browser.close();
  console.log('Visual capture complete!');
})();
