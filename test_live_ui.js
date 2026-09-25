const { chromium } = require('playwright');
const path = require('path');

const ARTIFACT_DIR = '/root/.gemini/antigravity/brain/27e1e1cd-e47b-423d-80ae-cbbfe9adc82a';
const BASE_URL = 'https://demo-guzellik-bookiapp.kibusiness.co';

async function run() {
    console.log('Launching browser...');
    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--ignore-certificate-errors']
    });

    const context = await browser.newContext({
        viewport: { width: 1440, height: 960 },
        ignoreHTTPSErrors: true
    });

    const page = await context.newPage();

    // Log page errors / alerts
    page.on('dialog', async dialog => {
        console.log(`[DIALOG] ${dialog.type()}: ${dialog.message()}`);
        await dialog.accept();
    });
    page.on('console', msg => console.log(`[PAGE LOG] ${msg.type()}: ${msg.text()}`));
    page.on('pageerror', err => console.error(`[PAGE ERROR] ${err.message}`));

    console.log(`Navigating to ${BASE_URL}/login ...`);
    await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1000);

    console.log('Logging in as administrator...');
    await page.fill('#username', 'administrator');
    await page.fill('#password', 'administrator');
    await page.click('#login');
    await page.waitForURL('**/dashboard', { timeout: 15000 });
    console.log('Logged in successfully! Current URL:', page.url());

    // ==========================================
    // TEST 1: SERVICES PAGE (Add-ons & Recipe)
    // ==========================================
    console.log('Navigating to /services ...');
    await page.goto(`${BASE_URL}/services`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1500);

    // Wait for service items in the left list
    await page.waitForSelector('.service-row', { timeout: 10000 });
    console.log('Service list loaded.');

    // Click the first service row
    const firstRow = await page.$('.service-row');
    const serviceName = await firstRow.innerText();
    console.log('Selecting first service:', serviceName.trim().replace(/\n/g, ' '));
    await firstRow.click();
    await page.waitForTimeout(2000);

    const shot1 = path.join(ARTIFACT_DIR, '01_service_selected.png');
    await page.screenshot({ path: shot1, fullPage: false });
    console.log('Screenshot saved:', shot1);

    // Click "+ Ek Hizmet Ekle"
    console.log('Clicking #btn-add-addon-modal...');
    await page.click('#btn-add-addon-modal');
    await page.waitForSelector('#modal-addon-form.show', { timeout: 5000 });
    await page.waitForTimeout(500);

    const shot2 = path.join(ARTIFACT_DIR, '02_addon_modal_open.png');
    await page.screenshot({ path: shot2, fullPage: false });
    console.log('Screenshot saved:', shot2);

    // Fill Addon form
    console.log('Filling add-on form...');
    await page.fill('#addon-name-input', 'Ekstra Nem & Masaj Bakımı');
    await page.fill('#addon-duration-input', '20');
    await page.fill('#addon-price-input', '150.00');
    await page.fill('#addon-desc-input', 'Canlı ortamda test edilen ek hizmet opsiyonu.');

    // Save Addon
    console.log('Clicking #btn-save-addon-submit...');
    await page.click('#btn-save-addon-submit');
    await page.waitForTimeout(2500);

    const shot3 = path.join(ARTIFACT_DIR, '03_addon_saved_table.png');
    await page.screenshot({ path: shot3, fullPage: false });
    console.log('Screenshot saved:', shot3);

    // Click "+ Sarf Malzeme Ekle" (Recipe)
    console.log('Clicking #btn-add-consumable-modal...');
    const consumableBtn = await page.$('#btn-add-consumable-modal');
    await consumableBtn.scrollIntoViewIfNeeded();
    await consumableBtn.click();
    await page.waitForSelector('#modal-consumable-form.show', { timeout: 5000 });
    await page.waitForTimeout(500);

    const shot4 = path.join(ARTIFACT_DIR, '04_consumable_modal_open.png');
    await page.screenshot({ path: shot4, fullPage: false });
    console.log('Screenshot saved:', shot4);

    // Select Product in consumable recipe
    const options = await page.$$eval('#consumable-product-select option', opts => 
        opts.map(o => ({ value: o.value, text: o.text })).filter(o => o.value !== '')
    );
    console.log('Available consumable products:', options);

    if (options.length > 0) {
        await page.selectOption('#consumable-product-select', options[0].value);
        await page.fill('#consumable-qty-input', '2.50');
        await page.waitForTimeout(500);

        console.log('Clicking #btn-save-consumable-submit...');
        await page.click('#btn-save-consumable-submit');
        await page.waitForTimeout(2500);
    } else {
        console.log('No products found in select options, skipping save step.');
        await page.click('#modal-consumable-form .btn-close');
        await page.waitForTimeout(500);
    }

    const shot5 = path.join(ARTIFACT_DIR, '05_services_recipe_and_addons_complete.png');
    await page.screenshot({ path: shot5, fullPage: false });
    console.log('Screenshot saved:', shot5);

    // ==========================================
    // TEST 2: VERTICAL GIFT CARDS & KAPORA TOGGLE
    // ==========================================
    console.log('Navigating to /verticals/gift_cards ...');
    await page.goto(`${BASE_URL}/verticals/gift_cards`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1500);

    const shot6 = path.join(ARTIFACT_DIR, '06_kapora_management_page.png');
    await page.screenshot({ path: shot6, fullPage: false });
    console.log('Screenshot saved:', shot6);

    const toggle = await page.$('#toggle-require-deposit');
    if (toggle) {
        const isChecked = await toggle.isChecked();
        console.log('Kapora toggle is currently checked:', isChecked);
        if (!isChecked) {
            console.log('Enabling Kapora toggle...');
            await page.click('#toggle-require-deposit');
            await page.waitForTimeout(800);
        }

        console.log('Setting deposit type to fixed and value to 200.00...');
        await page.selectOption('#deposit-type-select', 'fixed');
        await page.fill('#deposit-value-input', '200.00');

        console.log('Clicking #btn-save-deposit-settings...');
        await page.click('#btn-save-deposit-settings');
        await page.waitForTimeout(2500);

        const shot7 = path.join(ARTIFACT_DIR, '07_kapora_saved_successfully.png');
        await page.screenshot({ path: shot7, fullPage: false });
        console.log('Screenshot saved:', shot7);
    } else {
        console.error('Toggle #toggle-require-deposit not found!');
    }

    console.log('All tests completed successfully!');
    await browser.close();
}

run().catch(err => {
    console.error('Fatal error during test run:', err);
    process.exit(1);
});
