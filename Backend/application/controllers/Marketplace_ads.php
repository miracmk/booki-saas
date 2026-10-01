<?php defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/payment/Marketplace_ad_service.php';

/**
 * Controller: Marketplace_ads
 *
 * Exposes Sponsor Ads & Featured Listing operations:
 * - Package catalog listing
 * - Ad purchase execution via Tosla POS
 * - Tenant active ads list
 */
class Marketplace_ads extends App_Controller
{
    private Marketplace_ad_service $adService;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('bk_ads_model');
        $this->adService = new Marketplace_ad_service();
    }

    /**
     * List available advertising packages.
     * GET /marketplace_ads/packages
     */
    public function packages(): void
    {
        $packages = $this->adService->get_packages();
        json_response([
            'status'   => 'success',
            'packages' => $packages
        ]);
    }

    /**
     * Purchase a sponsored advertising package.
     * POST /marketplace_ads/purchase
     */
    public function purchase(): void
    {
        try {
            method('post');
            $post = $this->input->post(null, true) ?: json_decode((string)file_get_contents('php://input'), true);

            $tenantId = (int) ($post['tenant_id'] ?? session('tenant_id') ?? session('user_id') ?? 0);
            $packageKey = trim((string)($post['package_key'] ?? ''));

            if ($tenantId <= 0 || empty($packageKey)) {
                throw new InvalidArgumentException('İşletme ve reklam paketi seçimi zorunludur.');
            }

            $cardPayload = [
                'card_holder_name' => $post['card_holder_name'] ?? $post['card_holder'] ?? '',
                'card_number'      => $post['card_number'] ?? '',
                'expire_month'     => $post['expire_month'] ?? '',
                'expire_year'      => $post['expire_year'] ?? '',
                'cvv'              => $post['cvv'] ?? ''
            ];

            $targeting = [
                'category' => $post['category'] ?? null,
                'city'     => $post['city'] ?? null,
                'district' => $post['district'] ?? null
            ];

            $res = $this->adService->purchase_package($tenantId, $packageKey, $cardPayload, $targeting);

            json_response([
                'status'  => 'success',
                'data'    => $res
            ]);
        } catch (Throwable $e) {
            json_response([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get active ads for a tenant.
     * GET /marketplace_ads/tenant_active_ads?tenant_id=1
     */
    public function tenant_active_ads(): void
    {
        $tenantId = (int) ($this->input->get('tenant_id') ?: session('tenant_id') ?: session('user_id') ?: 0);
        if ($tenantId <= 0) {
            json_response(['status' => 'error', 'message' => 'İşletme kimliği bulunamadı.'], 400);
            return;
        }

        $ads = $this->bk_ads_model->get_tenant_ads($tenantId);
        json_response([
            'status' => 'success',
            'tenant_id' => $tenantId,
            'ads' => $ads
        ]);
    }
}
