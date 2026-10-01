<?php defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__ . '/PaymentGatewayAdapterInterface.php';
require_once __DIR__ . '/ToslaPaymentGatewayAdapter.php';
require_once __DIR__ . '/LegalContractsHelper.php';
require_once __DIR__ . '/PlatformBillingConfig.php';

/* ----------------------------------------------------------------------------
 * BooKi / RandevuBurada Ecosystem
 * Service: AppointmentPaymentService
 * 
 * Core Orchestrator for:
 * 1. Pre-Authorization Deposit Holds with 7-Day Window & TBK Art. 178 Compliance
 * 2. Proportional Commission Engine:
 *    - Platform Gateway (Ödeme + Kapora): %20 Ödeme + %5 Kapora = %25 Toplam Kesinti
 *      (No-Show'da kaporadan %25 kesilir; Geldiğinde hakedişten %25 kesilir)
 *    - Standalone Kapora Altyapısı: %20 Komisyon (No-Show veya Geldi fark etmeksizin)
 * 3. Scenario A (Customer Arrived): Void Pre-Auth + Proportional Platform Fee Deduction
 * 4. Scenario B (Customer No-Show): Capture Pre-Auth + Proportional Split Payout
 * 5. Multi-Tenant Gateway Resolution (Platform Gateway [Tosla] vs Custom Gateway)
 * ---------------------------------------------------------------------------- */

class AppointmentPaymentService
{
    public const MAX_HOLD_DAYS = 7; // Banking restriction on pre-auth holds

    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    /**
     * Issue a Pre-Authorization deposit hold on customer's card.
     * Enforces:
     * - Rule 1: Doctor / Clinic global deposit ban
     * - Rule 2: "Ödeme altyapısı kullanımında kapora sistemi şarttır"
     * - Rule 3: Legal Proportionality (TBK Art. 178: Deposit <= 25% of service price)
     * - Rule 4: Temporal 7-day pre-auth hold window
     * - Rule 5: Digital acceptance of TBK 178 terms
     *
     * @param int   $appointmentId
     * @param array $cardPayload
     * @param array $clientContext ['ip' => string, 'terms_accepted' => bool]
     * @return array
     * @throws Exception
     */
    public function holdDepositPreAuth(int $appointmentId, array $cardPayload, array $clientContext): array
    {
        $appointment = $this->getAppointment($appointmentId);
        $merchant = $this->getMerchant((int)$appointment['merchant_id']);

        // Rule 1: Doctor / Clinic Exception
        LegalContractsHelper::assertMerchantDepositAllowed($merchant['merchant_type']);

        // Rule 2: Enforce payment gateway requirement
        // "Ödeme altyapısı kullanımında kapora sistemi şarttır."
        if ($merchant['payment_gateway_mode'] === 'platform_gateway') {
            if (empty($merchant['deposit_enabled']) || (float)$appointment['deposit_amount'] <= 0) {
                throw new DomainException("Kural İhlali: Ödeme altyapısı kullanımında kapora sistemi zorunludur.");
            }
        } elseif ($merchant['payment_gateway_mode'] === 'disabled' || !$merchant['deposit_enabled']) {
            throw new DomainException("Bu işletme için online kapora / ön provizyon tahsilatı aktif değildir.");
        }

        $servicePrice = (float)$appointment['service_price'];
        $depositAmount = (float)$appointment['deposit_amount'];

        // Rule 3: Legal Proportionality (TBK Art. 178: Deposit <= 25% of service price)
        LegalContractsHelper::validateProportionality($servicePrice, $depositAmount);

        // Rule 4: Temporal Rule (Pre-auth holds expire in 7 days)
        $appointmentDate = new DateTime($appointment['start_datetime']);
        $now = new DateTime();
        $diffDays = (int)$now->diff($appointmentDate)->format('%r%a');

        if ($diffDays > self::MAX_HOLD_DAYS) {
            throw new DomainException(
                sprintf(
                    "Geçersiz Provizyon Süresi: Banka ön provizyon bloke süresi azami %d gündür. "
                    . "Randevu tarihi (%s) 7 günden ileri olduğu için provizyon işlemi randevuya 7 gün kala başlatılmalıdır.",
                    self::MAX_HOLD_DAYS,
                    $appointmentDate->format('d.m.Y H:i')
                )
            );
        }

        // Rule 5: Terms of Service Acceptance (TBK Art. 178)
        $legalDisclosure = LegalContractsHelper::getLegalTermsText(
            $merchant['name'],
            $appointment['service_name'] ?? 'Hizmet',
            $servicePrice,
            $depositAmount
        );
        $termsLog = LegalContractsHelper::validateTermsAcceptance(
            $clientContext,
            $legalDisclosure,
            $clientContext['ip'] ?? '127.0.0.1'
        );

        // Resolve Gateway Adapter
        $adapter = $this->resolveGatewayAdapter($merchant);

        // Execute Pre-Auth Hold via Adapter
        $metadata = [
            'appointment_id' => $appointmentId,
            'merchant_id'    => $merchant['id'],
            'service_name'   => $appointment['service_name'] ?? '',
            'customer_id'    => $appointment['customer_id'] ?? null,
            'callback_url'   => site_url("payment_webhooks/tosla_preauth_callback/{$appointmentId}")
        ];

        $preAuthResult = $adapter->holdPreAuth($depositAmount, 'TRY', $cardPayload, $metadata);

        // Update Appointment in Database Transaction
        $this->CI->db->trans_begin();
        try {
            $updateData = [
                'preauth_status'          => $preAuthResult['status'] === 'held' ? 'held' : 'pending',
                'payment_gateway_used'    => $merchant['payment_gateway_mode'] === 'custom_gateway' 
                    ? ($merchant['custom_gateway_provider'] ?? 'custom') 
                    : 'platform_tosla',
                'preauth_transaction_id'  => $preAuthResult['transaction_id'],
                'preauth_held_at'         => date('Y-m-d H:i:s'),
                'preauth_expires_at'      => date('Y-m-d H:i:s', strtotime('+7 days')),
                'legal_terms_accepted'    => 1,
                'legal_terms_text'        => $termsLog['legal_terms_text'],
                'legal_terms_accepted_at' => $termsLog['legal_terms_accepted_at'],
                'legal_terms_ip'          => $termsLog['legal_terms_ip']
            ];

            $this->CI->db->where('id', $appointmentId)->update('appointments', $updateData);

            $this->CI->db->trans_commit();
        } catch (Exception $e) {
            $this->CI->db->trans_rollback();
            throw $e;
        }

        return $preAuthResult;
    }

    /**
     * WORKFLOW SCENARIO A: Customer SHOWS UP
     * Trigger: Merchant clicks "Customer Arrived".
     * Payment Action: Void PreAuth (full hold released). Customer pays at venue register.
     * Billing Action:
     * - If Platform Gateway (Ödeme + Kapora): Orantısal %25 komisyon (%20 Ödeme + %5 Kapora)
     *   müşteriye gidecek meblağdan/cari bakiyeden düşülür.
     * - If Standalone Kapora Altyapısı: %20 kapora altyapısı komisyonu cari bakiyeden düşülür.
     * If balance < 200 TL, allow negative balance and flag low-balance alert.
     *
     * @param int $appointmentId
     * @return array
     * @throws Exception
     */
    public function handleCustomerShow(int $appointmentId): array
    {
        $appointment = $this->getAppointment($appointmentId);
        $merchant = $this->getMerchant((int)$appointment['merchant_id']);

        if ($appointment['status'] === 'customer_arrived' || $appointment['preauth_status'] === 'voided') {
            throw new DomainException("Randevu zaten tamamlanmış veya provizyon önceden serbest bırakılmıştır.");
        }

        // 1. Payment Action: Void Pre-Auth Hold (100% hold released back to customer card)
        $adapter = $this->resolveGatewayAdapter($merchant);
        $voidResult = ['status' => 'voided', 'simulated' => true];

        if (!empty($appointment['preauth_transaction_id']) && $appointment['preauth_status'] === 'held') {
            $voidResult = $adapter->voidPreAuth($appointment['preauth_transaction_id'], [
                'appointment_id' => $appointmentId,
                'reason'         => 'Müşteri randevuya geldi - TBK 178 gereği blokaj serbest bırakıldı'
            ]);
        }

        // 2. Proportional Commission Calculation
        $servicePrice = (float)$appointment['service_price'];
        $depositAmount = (float)$appointment['deposit_amount'];
        $gatewayMode = $merchant['payment_gateway_mode'] ?? 'platform_gateway';

        $calc = PlatformBillingConfig::calculateCommission($gatewayMode, $servicePrice, $depositAmount, 'show');
        $feeToDeduct = $calc['platform_commission_amount'];
        $appliedRatePercent = ($calc['commission_rate_applied'] * 100) . '%';

        // 3. Billing Action & Database Updates (Transactional)
        $this->CI->db->trans_begin();
        try {
            $currentBalance = (float)$merchant['credit_balance'];
            $newBalance = $currentBalance - $feeToDeduct;
            $isLowBalance = $newBalance < (float)$merchant['min_credit_balance_alert'];

            // Update merchant credit balance (allows negative balance)
            $this->CI->db->where('id', $merchant['id'])->update('merchants', [
                'credit_balance' => $newBalance
            ]);

            // Description formatted with exact breakdown
            $breakdownDesc = ($gatewayMode === 'platform_gateway')
                ? "Ödeme Altyapısı (%20) + Kapora Altyapısı (%5) = %25 Kesinti"
                : "Kapora Altyapısı Komisyonu (%20 Kesinti)";

            // Insert audit log in merchant_credit_logs
            $logData = [
                'merchant_id'       => $merchant['id'],
                'appointment_id'    => $appointmentId,
                'amount'            => -$feeToDeduct,
                'balance_before'    => $currentBalance,
                'balance_after'     => $newBalance,
                'transaction_type'  => 'service_fee_deduction',
                'description'       => sprintf(
                    "Randevu #%d ifa edildi (Müşteri Geldi). %s kapsamında %s TL (%s) hizmet bedeli tahakkuk etti. Blokaj kaldırıldı.",
                    $appointmentId,
                    $breakdownDesc,
                    number_format($feeToDeduct, 2),
                    $appliedRatePercent
                ),
                'is_negative_alert' => $isLowBalance ? 1 : 0,
                'created_at'        => date('Y-m-d H:i:s')
            ];
            $this->CI->db->insert('merchant_credit_logs', $logData);

            // Update appointment status
            $this->CI->db->where('id', $appointmentId)->update('appointments', [
                'status'                     => 'customer_arrived',
                'preauth_status'             => 'voided',
                'platform_commission_amount' => $feeToDeduct,
                'merchant_payout_amount'     => 0.00 // In-store cash/card collection
            ]);

            $this->CI->db->trans_commit();

            return [
                'success'           => true,
                'scenario'          => 'SCENARIO_A_CUSTOMER_SHOW',
                'appointment_id'    => $appointmentId,
                'preauth_void_data' => $voidResult,
                'fee_deducted'      => $feeToDeduct,
                'commission_rate'   => $appliedRatePercent,
                'previous_balance'  => $currentBalance,
                'current_balance'   => $newBalance,
                'low_balance_alert' => $isLowBalance
            ];
        } catch (Exception $e) {
            $this->CI->db->trans_rollback();
            throw $e;
        }
    }

    /**
     * WORKFLOW SCENARIO B: Customer NO-SHOW
     * Trigger: Merchant clicks "No-Show" or Cron Job triggers 3 hours post-appointment.
     * Payment Action: Call Capture for deposit amount.
     * Marketplace Split:
     * - If Platform Gateway: Kaporadan %25 kesilir (%20 Ödeme + %5 Kapora), kalan %75 salona hakediş.
     * - If Standalone Kapora Altyapısı: Kaporadan %20 kesilir, kalan %80 salona aktarılır.
     *
     * @param int $appointmentId
     * @return array
     * @throws Exception
     */
    public function handleCustomerNoShow(int $appointmentId): array
    {
        $appointment = $this->getAppointment($appointmentId);
        $merchant = $this->getMerchant((int)$appointment['merchant_id']);

        if ($appointment['status'] === 'no_show' || $appointment['preauth_status'] === 'captured') {
            throw new DomainException("Bu randevu için No-Show tahsilatı zaten gerçekleştirilmiştir.");
        }

        $depositAmount = (float)$appointment['deposit_amount'];
        $servicePrice = (float)$appointment['service_price'];

        if ($depositAmount <= 0) {
            throw new DomainException("Bu randevu için tanımlı bir kapora/provizyon tutarı bulunmamaktadır.");
        }

        $gatewayMode = $merchant['payment_gateway_mode'] ?? 'platform_gateway';

        // Calculate Proportional Marketplace Split
        $calc = PlatformBillingConfig::calculateCommission($gatewayMode, $servicePrice, $depositAmount, 'no_show');
        $platformFee = $calc['platform_commission_amount'];
        $merchantPayout = $calc['merchant_payout_amount'];
        $appliedRatePercent = ($calc['commission_rate_applied'] * 100) . '%';

        $splitData = [
            'sub_merchant_id' => $merchant['sub_merchant_id'] ?? null,
            'platform_amount' => $platformFee,
            'merchant_amount' => $merchantPayout
        ];

        // 1. Payment Action: Capture Pre-Auth Hold
        $adapter = $this->resolveGatewayAdapter($merchant);
        $captureResult = $adapter->capturePreAuth(
            (string)$appointment['preauth_transaction_id'],
            $depositAmount,
            $splitData
        );

        // 2. Billing Action & Database Updates (Transactional)
        $this->CI->db->trans_begin();
        try {
            $currentBalance = (float)$merchant['credit_balance'];
            $newBalance = $currentBalance + $merchantPayout;

            // Credit merchant balance with net payout
            $this->CI->db->where('id', $merchant['id'])->update('merchants', [
                'credit_balance' => $newBalance
            ]);

            $breakdownDesc = ($gatewayMode === 'platform_gateway')
                ? "Ödeme Altyapısı (%20) + Kapora Altyapısı (%5) = %25 Kesinti"
                : "Kapora Altyapısı Komisyonu (%20 Kesinti)";

            // Insert audit log in merchant_credit_logs
            $logData = [
                'merchant_id'       => $merchant['id'],
                'appointment_id'    => $appointmentId,
                'amount'            => $merchantPayout,
                'balance_before'    => $currentBalance,
                'balance_after'     => $newBalance,
                'transaction_type'  => 'deposit_split_credit',
                'description'       => sprintf(
                    "No-Show gerçekleşti (TBK m. 178 Cayma Tazminatı). "
                    . "Toplam %s TL provizyon kapatıldı: %s (%s TL Platform Payı), %s TL İşletme Net Hakedişi aktarıldı.",
                    number_format($depositAmount, 2),
                    $breakdownDesc,
                    number_format($platformFee, 2),
                    number_format($merchantPayout, 2)
                ),
                'is_negative_alert' => 0,
                'created_at'        => date('Y-m-d H:i:s')
            ];
            $this->CI->db->insert('merchant_credit_logs', $logData);

            // Update appointment status with exact proportional split records
            $this->CI->db->where('id', $appointmentId)->update('appointments', [
                'status'                     => 'no_show',
                'preauth_status'             => 'captured',
                'captured_amount'            => $depositAmount,
                'platform_commission_amount' => $platformFee,
                'merchant_payout_amount'     => $merchantPayout
            ]);

            $this->CI->db->trans_commit();

            return [
                'success'           => true,
                'scenario'          => 'SCENARIO_B_CUSTOMER_NO_SHOW',
                'appointment_id'    => $appointmentId,
                'captured_amount'   => $depositAmount,
                'platform_fee'      => $platformFee,
                'commission_rate'   => $appliedRatePercent,
                'merchant_payout'   => $merchantPayout,
                'capture_data'      => $captureResult,
                'updated_balance'   => $newBalance
            ];
        } catch (Exception $e) {
            $this->CI->db->trans_rollback();
            throw $e;
        }
    }

    /**
     * Resolve the appropriate gateway adapter based on merchant settings.
     */
    protected function resolveGatewayAdapter(array $merchant): PaymentGatewayAdapterInterface
    {
        $mode = $merchant['payment_gateway_mode'] ?? 'platform_gateway';

        if ($mode === 'disabled') {
            throw new DomainException("Ödeme sistemi bu işletme için pasif durumdadır.");
        }

        if ($mode === 'custom_gateway') {
            $credentials = json_decode((string)($merchant['custom_gateway_credentials'] ?? '{}'), true);
            $provider = strtolower($merchant['custom_gateway_provider'] ?? 'tosla');

            if ($provider === 'tosla') {
                return new ToslaPaymentGatewayAdapter($credentials ?? []);
            }

            throw new RuntimeException("Özel Sanal POS sağlayıcısı '{$provider}' henüz desteklenmiyor.");
        }

        // Platform Gateway: Tosla İşim Master Sanal POS (Environment-Aware)
        return new ToslaPaymentGatewayAdapter();
    }

    protected function getAppointment(int $id): array
    {
        $row = $this->CI->db->where('id', $id)->get('appointments')->row_array();
        if (!$row) {
            throw new InvalidArgumentException("Randevu bulunamadı (ID: {$id}).");
        }
        return $row;
    }

    protected function getMerchant(int $id): array
    {
        $row = $this->CI->db->where('id', $id)->get('merchants')->row_array();
        if (!$row) {
            throw new InvalidArgumentException("İşletme kaydı bulunamadı (ID: {$id}).");
        }
        return $row;
    }
}
