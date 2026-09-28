<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi / RandevuBurada Ecosystem
 * Library: LegalContractsHelper
 * 
 * Enforces legal compliance under Turkish Law:
 * 1. TBK Art. 178 (Türk Borçlar Kanunu m. 178) - Cayma Parası ve Yararlanılmayan Hizmet Tazminatı
 * 2. 25% Proportionality ceiling enforcement
 * 3. Doctor / Clinic statutory prohibition
 * 4. Tamper-evident contract versioning & acceptance validation
 * ---------------------------------------------------------------------------- */

class LegalContractsHelper
{
    public const MAX_DEPOSIT_RATIO = 0.25; // Strict 25% cap per TBK Art. 178
    public const CONTRACT_VERSION   = 'TBK178-V2026.1';

    /**
     * Generate the legally compliant disclosure text for the customer.
     *
     * @param string $merchantName Business title
     * @param string $serviceName  Service name
     * @param float  $totalPrice   Total service price
     * @param float  $depositPrice Deposit amount
     * @return string
     */
    public static function getLegalTermsText(
        string $merchantName,
        string $serviceName,
        float $totalPrice,
        float $depositPrice
    ): string {
        $formattedTotal = number_format($totalPrice, 2, ',', '.') . ' TL';
        $formattedDeposit = number_format($depositPrice, 2, ',', '.') . ' TL';

        return "REZERVASYON VE ÖN BİLGİLENDİRME KOŞULLARI\n"
            . "HUKUKİ NİTELENDİRME: CAYMA PARASI VE YARARLANILMAYAN HİZMET TAZMİNATI (TBK M. 178)\n\n"
            . "1. TARAFLAR VE HİZMET: Bu işlem, {$merchantName} ('İşletme') nezdinde rezerve edilen "
            . "{$serviceName} hizmeti (Toplam Bedel: {$formattedTotal}) için düzenlenmiştir.\n\n"
            . "2. CAYMA PARASI MAHYETİ: Kartınızdan bloke edilen {$formattedDeposit} tutarındaki provizyon, "
            . "6098 sayılı Türk Borçlar Kanunu'nun 178. maddesi hükmü uyarınca 'Cayma Parası ve Yararlanılmayan "
            . "Hizmet Tazminatı' mahiyetinde olup; yasal azami sınır olan toplam bedelin %25'ini aşmamaktadır.\n\n"
            . "3. HİZMETİN İFASI (MÜŞTERİ GELDİ): Randevu saatinde hizmet mahalline iştirak edilmesi halinde, "
            . "kartınızdaki {$formattedDeposit} tutarındaki provizyon derhal ve tamamen iptal edilecek (blokaj kaldırılacak); "
            . "hizmet bedelinin tamamı işletme kasasında ödenecektir.\n\n"
            . "4. RANDEVUYA GELMEME (NO-SHOW): Mazeretsiz olarak randevuya gelinmemesi veya belirlenen iptal süresinden "
            . "sonra vazgeçilmesi halinde, ayrılan zaman dilimi ve işletmenin uğradığı rezervasyon zararı karşılığı "
            . "olarak bloke edilen {$formattedDeposit} cayma parası olarak kesin tahsil edilecektir.\n\n"
            . "Sözleşme Versiyonu: " . self::CONTRACT_VERSION . " | Onay Zamanı: " . date('Y-m-d H:i:s') . "\n";
    }

    /**
     * Validate deposit proportionality against TBK Art. 178 (Max 25%).
     *
     * @param float $servicePrice
     * @param float $depositAmount
     * @throws InvalidArgumentException
     */
    public static function validateProportionality(float $servicePrice, float $depositAmount): void
    {
        if ($servicePrice <= 0) {
            throw new InvalidArgumentException("Hizmet bedeli 0'dan büyük olmalıdır.");
        }

        $maxAllowed = round($servicePrice * self::MAX_DEPOSIT_RATIO, 2);

        if ($depositAmount > $maxAllowed) {
            throw new InvalidArgumentException(
                sprintf(
                    "Hukuki İhlal (TBK m. 178): Kapora tutarı (%s TL), toplam hizmet bedelinin (%s TL) %%25'ini (%s TL) aşamaz.",
                    number_format($depositAmount, 2),
                    number_format($servicePrice, 2),
                    number_format($maxAllowed, 2)
                )
            );
        }
    }

    /**
     * Verify that the merchant type is legally permitted to collect deposits.
     * Doctors and medical clinics are globally banned under medical ethics regulations.
     *
     * @param string $merchantType
     * @throws DomainException
     */
    public static function assertMerchantDepositAllowed(string $merchantType): void
    {
        $bannedTypes = ['doctor', 'clinic', 'medical', 'hospital', 'dentist'];
        
        if (in_array(strtolower(trim($merchantType)), $bannedTypes, true)) {
            throw new DomainException(
                "Mevzuat Engeli: Sağlık Hizmetleri mevzuatı ve Tıbbi Deontoloji Nizamnamesi uyarınca "
                . "doktor ve klinik randevularında cayma parası/kapora tahsilatı yapılması kesinlikle yasaktır."
            );
        }
    }

    /**
     * Validate customer's digital acceptance of the legal terms.
     *
     * @param array  $requestPayload
     * @param string $legalText
     * @param string $ipAddress
     * @return array Validation metadata record
     * @throws InvalidArgumentException
     */
    public static function validateTermsAcceptance(
        array $requestPayload,
        string $legalText,
        string $ipAddress
    ): array {
        $accepted = filter_var($requestPayload['legal_terms_accepted'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$accepted) {
            throw new InvalidArgumentException(
                "TBK m. 178 uyarınca 'Cayma Parası ve Yararlanılmayan Hizmet Tazminatı' şartlarının "
                . "müşteri tarafından açıkça onaylanması zorunludur."
            );
        }

        return [
            'legal_terms_accepted'    => true,
            'legal_terms_text'        => $legalText,
            'legal_terms_hash'        => hash('sha256', $legalText),
            'legal_terms_version'     => self::CONTRACT_VERSION,
            'legal_terms_accepted_at' => date('Y-m-d H:i:s'),
            'legal_terms_ip'          => $ipAddress ?: '127.0.0.1'
        ];
    }
}
