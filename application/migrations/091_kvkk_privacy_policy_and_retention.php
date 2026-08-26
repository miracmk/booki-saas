<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Salon Flora customization (2026-08-24, KVKK hardening):
 *
 * 1. Sets `data_retention_days` to 1825 (5 years) so the (now anonymize-in-place, see Cleanup.php)
 *    nightly retention job actually runs instead of staying disabled (stock default is 0/off). 5 years
 *    was chosen to stay comfortably clear of VUK (Turkish Tax Procedure Law) financial record-keeping
 *    minimums, since a customer's appointment/payment rows survive anonymization regardless - this
 *    setting only controls how long their PERSONAL data (name/phone/email/etc) stays attached to an
 *    otherwise-inactive record. Adjust by editing this value directly in Settings > General (it's a
 *    normal EA setting) if the business wants a different period - this migration only seeds it once.
 *
 * 2. Seeds `privacy_policy_content` with a KVKK-oriented Aydınlatma Metni (Turkish "Illumination Text")
 *    draft and turns on `display_privacy_policy`, activating EA's existing (previously present but
 *    unused - display_privacy_policy was '0') consent-checkbox mechanism on the public booking form:
 *    Booking.php's register() already logs a Consents_model record whenever this setting is on (no
 *    code change needed there, see project notes). This text is a DRAFT - a lawyer should review it
 *    before treating it as the business's actual legal position; it is not legal advice.
 * ---------------------------------------------------------------------------- */

class Migration_Kvkk_privacy_policy_and_retention extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        $this->set_setting('data_retention_days', '1825');
        $this->set_setting('display_privacy_policy', '1');
        $this->set_setting('privacy_policy_content', $this->privacy_policy_html());
    }

    /**
     * Downgrade method.
     *
     * Restores the stock defaults (retention disabled, privacy policy hidden with placeholder text).
     */
    public function down(): void
    {
        $this->set_setting('data_retention_days', '0');
        $this->set_setting('display_privacy_policy', '0');
        $this->set_setting('privacy_policy_content', 'Privacy policy content.');
    }

    private function set_setting(string $name, string $value): void
    {
        $existing = $this->db->get_where('settings', ['name' => $name])->row_array();

        if ($existing) {
            $this->db->where('name', $name)->update('settings', ['value' => $value]);
        } else {
            $this->db->insert('settings', ['name' => $name, 'value' => $value]);
        }
    }

    private function privacy_policy_html(): string
    {
        return <<<'HTML'
            <h4>Kişisel Verilerin Korunması Hakkında Aydınlatma Metni</h4>
            <p><em>Bu metin, 6698 sayılı Kişisel Verilerin Korunması Kanunu ("KVKK") uyarınca hazırlanmış bir taslaktır. Yürürlüğe konulmadan önce bir hukuk danışmanı tarafından gözden geçirilmesi önerilir.</em></p>

            <h5>1. Veri Sorumlusu</h5>
            <p>Kişisel verileriniz, veri sorumlusu sıfatıyla <strong>Salon Flora</strong> ("Salon Flora", "biz") tarafından, aşağıda açıklanan kapsamda işlenmektedir. İletişim: <a href="mailto:iletisim@salonflora.tr">iletisim@salonflora.tr</a></p>

            <h5>2. İşlenen Kişisel Veriler ve İşlenme Amaçları</h5>
            <p>Online randevu sistemimiz veya diğer kanallar üzerinden bizimle paylaştığınız ad-soyad, telefon numarası, e-posta adresi, adres/semt bilgisi ve varsa randevu notlarınız; aşağıdaki amaçlarla işlenmektedir:</p>
            <ul>
                <li>Randevu talebinizin alınması, planlanması ve size hizmet sunulması,</li>
                <li>Randevu onayı, hatırlatma ve değişiklik bildirimlerinin e-posta yoluyla tarafınıza iletilmesi,</li>
                <li>Hizmet kalitesinin takibi ve müşteri ilişkilerinin yönetimi,</li>
                <li>Yasal yükümlülüklerimizin (vergi, muhasebe kaydı vb.) yerine getirilmesi.</li>
            </ul>

            <h5>3. Kişisel Verilerin Aktarılması</h5>
            <p>Kişisel verileriniz; yalnızca yasal yükümlülüklerimiz gereği yetkili kamu kurum ve kuruluşlarıyla (talep halinde), hizmet aldığımız muhasebe/mali müşavirlik firmasıyla ve randevu bildirimlerinin gönderilmesini sağlayan e-posta altyapı sağlayıcımızla (kendi sunucularımızda barındırılmaktadır) sınırlı olmak üzere paylaşılabilir. Verileriniz pazarlama amacıyla üçüncü taraflarla paylaşılmaz veya satılmaz.</p>

            <h5>4. Kişisel Veri Toplamanın Yöntemi ve Hukuki Sebebi</h5>
            <p>Kişisel verileriniz, online randevu formu, telefon veya yüz yüze randevu talebiniz sırasında elektronik ortamda toplanmaktadır. Verileriniz, KVKK m.5/2(c) uyarınca "bir sözleşmenin kurulması veya ifasıyla doğrudan doğruya ilgili olması" ve m.5/2(ç) uyarınca "veri sorumlusunun hukuki yükümlülüğünü yerine getirebilmesi" hukuki sebeplerine dayanılarak işlenmektedir.</p>

            <h5>5. Veri Saklama Süresi</h5>
            <p>Kişisel verileriniz, aramızdaki hizmet ilişkisi devam ettiği sürece ve ilişkinin sona ermesinden itibaren makul bir süre (randevu geçmişinizin bulunmaması halinde en fazla 5 yıl) boyunca saklanır. Bu sürenin sonunda, sizinle ilişkilendirilebilecek kişisel verileriniz (ad-soyad, telefon, e-posta, adres, notlar) otomatik olarak anonim hale getirilir; randevu ve ödeme kayıtlarınız ise yasal muhasebe yükümlülüklerimiz gereği anonim şekilde saklanmaya devam edebilir.</p>

            <h5>6. KVKK Kapsamındaki Haklarınız</h5>
            <p>KVKK'nın 11. maddesi uyarınca bize başvurarak; kişisel verilerinizin işlenip işlenmediğini öğrenme, işlenmişse buna ilişkin bilgi talep etme, işlenme amacını ve amacına uygun kullanılıp kullanılmadığını öğrenme, yurt içinde/yurt dışında aktarıldığı üçüncü kişileri bilme, eksik/yanlış işlenmişse düzeltilmesini isteme, KVKK'da öngörülen şartlar çerçevesinde silinmesini/yok edilmesini isteme, işlenen verilerin münhasıran otomatik sistemler vasıtasıyla analiz edilmesi suretiyle aleyhinize bir sonucun ortaya çıkmasına itiraz etme ve kanuna aykırı işlenme sebebiyle zarara uğramanız hâlinde zararın giderilmesini talep etme haklarına sahipsiniz.</p>

            <h5>7. Başvuru Yöntemi</h5>
            <p>Yukarıda sayılan haklarınızı kullanmak için taleplerinizi <a href="mailto:iletisim@salonflora.tr">iletisim@salonflora.tr</a> adresine yazılı olarak iletebilirsiniz. Talebiniz, niteliğine göre en kısa sürede ve en geç 30 gün içinde ücretsiz olarak sonuçlandırılacaktır.
            HTML;
    }
}
