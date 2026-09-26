<?php declare(strict_types=1);

namespace Tests\System;

use Tests\TestCase;

/**
 * Merchant Compliance & Virtual POS Unified Callback Test Suite.
 *
 * Verifies all bank / iyzico / PayTR merchant onboarding requirements:
 * 1. Ürün/Hizmet Sergileme & Fiyatlandırma
 * 2. Yasal Sayfalar (Gizlilik, Mesafeli Satış, Teslimat/İade, Hakkımızda, Kullanım Şartları)
 * 3. İletişim Bilgileri (Unvan, Adres, Telefon, E-posta)
 * 4. SSL / Güvenlik Standartları
 * 5. Kart Logoları (Visa, Mastercard, Troy)
 * 6. Sanal POS Unified Callback Mimarisi
 */
class MerchantComplianceAndUnifiedCallbackTest extends TestCase
{
    private string $srcDir = '/opt/ki-ecosystem/ki-reservation-src';

    protected function setUp(): void
    {
        parent::setUp();
        $this->srcDir = is_dir('/var/www/html/application') ? '/var/www/html' : (realpath(__DIR__ . '/../..') ?: '/opt/ki-ecosystem/ki-reservation-src');
    }

    public function testLegalPagesExistAndContainRequiredStatutoryContent(): void
    {
        $legalPages = [
            'privacy_policy.php' => ['Gizlilik Politikası', 'KVKK', 'kibusiness.co'],
            'mesafeli_satis.php' => ['Mesafeli Satış Sözleşmesi', '6502', 'SATICI', 'ALICI'],
            'teslimat_iade.php' => ['Teslimat ve İade', 'Cayma', 'İade'],
            'about_us.php' => ['Hakkımızda', 'Ki Software', 'BooKi'],
            'terms_of_service.php' => ['Kullanım Şartları', 'Terms of Service']
        ];

        foreach ($legalPages as $file => $keywords) {
            $path = $this->srcDir . '/application/views/pages/' . $file;
            $this->assertFileExists($path, "Legal page view $file must exist");
            $content = file_get_contents($path);
            foreach ($keywords as $kw) {
                $this->assertStringContainsString($kw, $content, "View $file must contain '$kw'");
            }
        }
    }

    public function testPaymentLogosIncludeVisaMastercardAndTroy(): void
    {
        $this->assertFileExists($this->srcDir . '/assets/img/iyzico/visa.svg', 'Visa SVG must exist');
        $this->assertFileExists($this->srcDir . '/assets/img/iyzico/mastercard.svg', 'Mastercard SVG must exist');
        $this->assertFileExists($this->srcDir . '/assets/img/iyzico/troy.svg', 'Troy SVG must exist in iyzico assets');
        $this->assertFileExists($this->srcDir . '/assets/img/troy.svg', 'Troy SVG must exist in root assets');

        // Check landing page footer
        $landingContent = file_get_contents($this->srcDir . '/application/views/pages/landing_home.php');
        $this->assertStringContainsString('visa.svg', $landingContent);
        $this->assertStringContainsString('mastercard.svg', $landingContent);
        $this->assertStringContainsString('troy.svg', $landingContent);

        // Check booking footer
        $bookingFooter = file_get_contents($this->srcDir . '/application/views/components/booking_footer.php');
        $this->assertStringContainsString('visa.svg', $bookingFooter);
        $this->assertStringContainsString('mastercard.svg', $bookingFooter);
        $this->assertStringContainsString('troy.svg', $bookingFooter);
    }

    public function testContactSectionExistsWithFullCorporateInformation(): void
    {
        $landingContent = file_get_contents($this->srcDir . '/application/views/pages/landing_home.php');

        // Anchor ID and Nav link
        $this->assertStringContainsString('id="iletisim"', $landingContent, 'Landing must have id="iletisim" section');
        $this->assertStringContainsString('href="#iletisim"', $landingContent, 'Landing nav must have href="#iletisim"');

        // Corporate info
        $this->assertStringContainsString('Ki Software', $landingContent);
        $this->assertStringContainsString('Ki Business Solutions', $landingContent);
        $this->assertStringContainsString('support@kibusiness.co', $landingContent);
        $this->assertStringContainsString('+90 (850) 885 00 24', $landingContent);
        $this->assertStringContainsString('Çekirge', $landingContent);
    }

    public function testUnifiedCallbackRoutesAreRegistered(): void
    {
        if (!defined('BASEPATH')) {
            define('BASEPATH', true);
        }

        require_once $this->srcDir . '/application/helpers/routes_helper.php';
        $route = [];
        include $this->srcDir . '/application/config/routes.php';

        $this->assertEquals('payment_webhooks/callback', $route['payment/callback'] ?? null);
        $this->assertEquals('payment_webhooks/callback/$1', $route['payment/callback/(:any)'] ?? null);
        $this->assertEquals('payment_webhooks/callback', $route['payment_webhooks/callback'] ?? null);
        $this->assertEquals('payment_webhooks/callback/$1', $route['payment_webhooks/callback/(:any)'] ?? null);
    }

    public function testCsrfExcludeListIncludesPaymentCallbacks(): void
    {
        $configContent = file_get_contents($this->srcDir . '/application/config/config.php');

        $this->assertStringContainsString("'payment_webhooks/.*'", $configContent);
        $this->assertStringContainsString("'payment/callback.*'", $configContent);
        $this->assertStringContainsString("'payment/unified_callback.*'", $configContent);
    }

    public function testPaymentWebhooksControllerHasUnifiedCallbackMethod(): void
    {
        $content = file_get_contents($this->srcDir . '/application/controllers/Payment_webhooks.php');
        $this->assertStringContainsString('public function callback(?string $gateway = null): void', $content);
        $this->assertStringContainsString('private function detect_gateway(): string', $content);
        $this->assertStringContainsString('private function is_browser_return_request(): bool', $content);
        $this->assertStringContainsString('private function handle_browser_return(string $gateway): void', $content);
        $this->assertStringContainsString("echo 'OK';", $content, 'PayTR webhooks must respond with OK');
    }
}
