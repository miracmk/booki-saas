<?php defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/payment/ToslaPaymentGatewayAdapter.php';

/**
 * Service: Marketplace_ad_service
 *
 * Manages sponsored ads, package purchases via Tosla, and search boosts.
 */
class Marketplace_ad_service
{
    private CI_Controller $CI;
    private ToslaPaymentGatewayAdapter $tosla;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('bk_ads_model');
        $this->tosla = new ToslaPaymentGatewayAdapter();
    }

    public function get_packages(): array
    {
        return Bk_ads_model::PACKAGES;
    }

    /**
     * Purchase a sponsored ad package using Tosla Sanal POS.
     */
    public function purchase_package(
        int $tenantId,
        string $packageKey,
        array $cardPayload,
        array $targeting = []
    ): array {
        if (!isset(Bk_ads_model::PACKAGES[$packageKey])) {
            throw new InvalidArgumentException("Geçersiz reklam paketi seçimi: {$packageKey}");
        }

        $package = Bk_ads_model::PACKAGES[$packageKey];
        $amount = (float) $package['price'];
        $orderId = 'BK_AD_' . time() . rand(10, 99);
        $callbackUrl = site_url('marketplace_ads/tosla_ad_callback');

        // Charge card through Tosla Direct 3D
        $paymentResult = $this->tosla->directThreeDPayment(
            $amount,
            $orderId,
            $callbackUrl,
            $cardPayload,
            [
                'type'        => 'marketplace_ad_purchase',
                'tenant_id'   => $tenantId,
                'package_key' => $packageKey
            ]
        );

        $adId = null;
        if (($paymentResult['status'] ?? '') === 'success') {
            $adId = $this->CI->bk_ads_model->create_ad(
                $tenantId,
                $packageKey,
                $packageKey === 'homepage_monthly' ? 'homepage_featured' : 'category_top',
                $targeting['category'] ?? null,
                $targeting['city'] ?? null,
                $targeting['district'] ?? null,
                $orderId
            );
        }

        return [
            'status'        => 'success',
            'ad_id'         => $adId,
            'package'       => $package,
            'amount'        => $amount,
            'order_id'      => $orderId,
            'payment'       => $paymentResult
        ];
    }
}
