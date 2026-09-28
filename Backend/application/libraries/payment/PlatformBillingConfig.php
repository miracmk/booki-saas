<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi / RandevuBurada Ecosystem
 * Library: PlatformBillingConfig
 * 
 * Central financial tariff configuration for:
 * 1. Proportional Commission Rates (Ödeme ve Kapora Altyapısı)
 * 2. SaaS Subscription Plans (Başlangıç, Profesyonel, Premium)
 * 3. AI Asistan Quotas & Extra Conversation Packages (1K, 5K, 10K)
 * ---------------------------------------------------------------------------- */

class PlatformBillingConfig
{
    // =========================================================================
    // 1. KOMİSYON ORANLARI (ORANTISAL)
    // =========================================================================

    /**
     * Ödeme altyapısı komisyonu (%20).
     */
    public const RATE_PAYMENT_GATEWAY = 0.20;

    /**
     * Kapora altyapısı komisyonu - Ödeme altyapısı ile birlikte (%5).
     * Ödeme altyapısı kullanımında kapora şarttır; toplam kesinti %25 olur.
     */
    public const RATE_DEPOSIT_GATEWAY_COMBINED = 0.05;

    /**
     * Ödeme + Kapora Kombine Komisyon Oranı (%25).
     * No-Show'da kaporadan %25; Müşteri Geldiğinde hakedişten %25 kesilir.
     */
    public const RATE_FULL_GATEWAY_COMBINED = 0.25;

    /**
     * Yalnızca Kapora altyapısı komisyonu (%20).
     * No-Show veya Geldi fark etmeksizin kaporadan %20 kesilir.
     */
    public const RATE_STANDALONE_DEPOSIT = 0.20;

    // =========================================================================
    // 2. SAAS ABONELİK PAKETLERİ (BOO-KI)
    // =========================================================================

    public const PLANS = [
        'starter' => [
            'key'               => 'starter',
            'name'              => 'Başlangıç Paketi',
            'monthly_price'     => 1250.00,
            'annual_unit_price' => 1000.00,
            'annual_total_price'=> 12000.00, // 1000 TL x 12 ay
            'annual_months'     => 12,
            'included_ai_limit' => 1000,
            'description'       => 'Aylık 1.250 TL veya Yıllık peşin 1.000 TL x 12 ay (12.000 TL). 1.000 AI görüşme dahil.'
        ],
        'professional' => [
            'key'               => 'professional',
            'name'              => 'Profesyonel Paket (Orta Paket)',
            'monthly_price'     => 2450.00,
            'annual_unit_price' => 1950.00,
            'annual_total_price'=> 23400.00, // 1950 TL x 12 ay
            'annual_months'     => 12,
            'included_ai_limit' => 1000,
            'description'       => 'Aylık 2.450 TL veya Yıllık peşin 1.950 TL x 12 ay (23.400 TL). 1.000 AI görüşme dahil.'
        ],
        'premium' => [
            'key'               => 'premium',
            'name'              => 'Premium Paket',
            'monthly_price'     => 4750.00,
            'annual_unit_price' => 3800.00,
            'annual_total_price'=> 45600.00, // 3800 TL x 12 ay
            'annual_months'     => 12,
            'included_ai_limit' => 1000,
            'description'       => 'Aylık 4.750 TL veya Yıllık peşin 3.800 TL x 12 ay (45.600 TL). 1.000 AI görüşme dahil.'
        ]
    ];

    // =========================================================================
    // 3. AI ASİSTAN KOTA & EK GÖRÜŞME PAKETLERİ
    // =========================================================================

    public const INCLUDED_AI_CONVERSATIONS = 1000; // Paketlere dahil temel kota

    public const AI_CONVERSATION_ADDONS = [
        'ai_1k' => [
            'key'           => 'ai_1k',
            'conversations' => 1000,
            'price'         => 300.00,
            'label'         => '1.000 Görüşme Paketi (300 TL)'
        ],
        'ai_5k' => [
            'key'           => 'ai_5k',
            'conversations' => 5000,
            'price'         => 1400.00,
            'label'         => '5.000 Görüşme Paketi (1.400 TL)'
        ],
        'ai_10k' => [
            'key'           => 'ai_10k',
            'conversations' => 10000,
            'price'         => 2500.00,
            'label'         => '10.000 Görüşme Paketi (2.500 TL)'
        ]
    ];

    /**
     * Calculate proportional commission for an appointment based on merchant setup and scenario.
     *
     * @param string $gatewayMode   'platform_gateway' (Ödeme+Kapora) | 'custom_gateway' (Yalnızca Kapora)
     * @param float  $servicePrice  Total price of the reserved service
     * @param float  $depositAmount Pre-auth deposit amount (max 25% by TBK m. 178)
     * @param string $scenario      'show' (Customer Arrived) | 'no_show' (No-Show)
     * 
     * @return array [
     *   'platform_commission_amount',
     *   'merchant_payout_amount',
     *   'commission_rate_applied',
     *   'rate_breakdown'
     * ]
     */
    public static function calculateCommission(
        string $gatewayMode,
        float $servicePrice,
        float $depositAmount,
        string $scenario
    ): array {
        if ($gatewayMode === 'platform_gateway') {
            // Ödeme Altyapısı (%20) + Kapora Altyapısı (%5) = %25
            $commissionRate = self::RATE_FULL_GATEWAY_COMBINED; // 0.25
            $rateBreakdown = [
                'payment_gateway_rate' => self::RATE_PAYMENT_GATEWAY,       // 0.20
                'deposit_gateway_rate' => self::RATE_DEPOSIT_GATEWAY_COMBINED // 0.05
            ];

            if ($scenario === 'no_show') {
                // No-Show'da %25 kaporadan kesilir
                $platformCommission = round($depositAmount * $commissionRate, 2);
                $merchantPayout = round($depositAmount - $platformCommission, 2);
            } else {
                // Müşteri geldiğinde: Müşteriye/işletmeye gidecek meblağdan %25 kesilir
                // Eğer platform üzerinden tahsil edilen tutar varsa ondan, yoksa toplam hizmet bedelinden %25
                $platformCommission = round($depositAmount * $commissionRate, 2);
                $merchantPayout = round($depositAmount - $platformCommission, 2);
            }
        } else {
            // Yalnızca Kapora Altyapısı: %20 (No-Show veya Geldi fark etmeksizin kaporadan kesilir)
            $commissionRate = self::RATE_STANDALONE_DEPOSIT; // 0.20
            $rateBreakdown = [
                'deposit_gateway_rate' => self::RATE_STANDALONE_DEPOSIT // 0.20
            ];

            $platformCommission = round($depositAmount * $commissionRate, 2);
            $merchantPayout = round($depositAmount - $platformCommission, 2);
        }

        return [
            'platform_commission_amount' => $platformCommission,
            'merchant_payout_amount'     => $merchantPayout,
            'commission_rate_applied'    => $commissionRate,
            'rate_breakdown'             => $rateBreakdown
        ];
    }
}
