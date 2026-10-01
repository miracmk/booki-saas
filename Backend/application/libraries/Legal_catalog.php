<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi SaaS - Comprehensive Legal Consent & Contract Engine
 * ---------------------------------------------------------------------------- */

class Legal_catalog
{
    protected CI_Controller $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('settings_model');
    }

    /**
     * Return all pre-configured sector-specific legal templates and consent forms.
     */
    public function get_catalog(): array
    {
        return [
            // -----------------------------------------------------------------
            // 1. CİLT BAKIMI, MEDİKAL ESTETİK & DERMATOLOJİ
            // -----------------------------------------------------------------
            'CILT_BAKIMI_MEDIKAL_ESTETIK' => [
                'code' => 'CILT_BAKIMI_MEDIKAL_ESTETIK',
                'sector' => 'beauty_wellness',
                'category' => 'Cilt Bakımı & Medikal Estetik',
                'doc_type' => 'AYDINLATILMIS_ONAM_FORMU',
                'title' => 'Medikal Cilt Bakımı ve Dermakozmetik Uygulama Bilgilendirilmiş Onam Formu',
                'source_reference' => 'Türk Dermatoloji Derneği & Sağlıkta Kalite Standartları',
                'is_mandatory' => 1,
                'description' => 'Klasik/medikal cilt bakımı, hydrafacial, kimyasal peeling, dermapen ve aktif serum protokolleri.',
                'keywords' => ['cilt', 'bakım', 'medikal', 'hydra', 'peeling', 'dermapen', 'akne', 'gözenek', 'yüz', 'anti-aging'],
                'content_html' => '<div class="legal-doc-header">
<h3>MEDİKAL CİLT BAKIMI VE UYGULAMA BİLGİLENDİRİLMİŞ ONAM FORMU</h3>
<p class="text-muted small">6502 Sayılı Tüketicinin Korunması Hakkında Kanun ve İlgili Sağlık/Güzellik Mevzuatı Uyarınca Düzenlenmiştir</p>
</div>

<table class="table table-bordered table-sm my-3 small">
  <tr><th width="30%">Uygulayıcı Merkez:</th><td>{{TENANT_NAME}} ({{TENANT_LEGAL_NAME}})</td></tr>
  <tr><th>Danışan Adı Soyadı:</th><td><strong>{{CUSTOMER_FULL_NAME}}</strong></td></tr>
  <tr><th>İletişim:</th><td>{{CUSTOMER_PHONE}} | {{CUSTOMER_EMAIL}}</td></tr>
  <tr><th>Planlanan İşlem:</th><td><span class="badge bg-primary text-white">{{SERVICE_NAME}}</span></td></tr>
  <tr><th>Uygulayıcı Uzman:</th><td>{{PROVIDER_NAME}}</td></tr>
  <tr><th>Randevu Tarihi & Saati:</th><td>{{APPOINTMENT_DATE_TIME}}</td></tr>
</table>

<h4>1. İŞLEMİN MAHİYETİ VE AMACI</h4>
<p>İşbu işlem; cildin derinlemesine temizlenmesi, ölü hücrelerin arındırılması, sebum dengesinin sağlanması, gözeneklerin sıkılaştırılması ve cildin ihtiyaç duyduğu nem/vitamin/aktif bileşenlerin (AHA, BHA, Hyaluronik Asit, C Vitamini, Peptitler) dermakozmetik yöntemlerle cilde kazandırılmasını amaçlar.</p>

<h4>2. DANIŞAN SAĞLIK BEYANI VE ÖN BİLGİLENDİRME</h4>
<p>Danışan olarak aşağıdaki durumlar hakkında uzmana doğru ve eksiksiz bilgi verdiğimi kabul ve taahhüt ederim:</p>
<ul>
  <li>Son 6 ay içinde sistemik akne ilacı (Roaccutane, Zoretanin vb.) kullanmadığımı,</li>
  <li>Son 1 hafta içinde glikolik asit, retinol veya tahriş edici asit içerikleri kullanmadığımı,</li>
  <li>Uygulama bölgesinde açık yara, aktif uçuk (herpes), egzama veya iltihaplı lezyon bulunmadığını,</li>
  <li>Hamilelik, emzirme, alerjik bünye veya bilinen kozmetik bileşen alerjilerimi uzmana eksiksiz bildirdiğimi beyan ederim.</li>
</ul>

<h4>3. BEKLENEN ETKİLER VE OLASI GEÇİCİ REAKSİYONLAR</h4>
<p>İşlem sonrasında cilt hassasiyetine bağlı olarak 2 ila 24 saat arasında geçici hafif kızarıklık, karıncalanma, hafif ödem veya peeling işlemlerini takiben 3-5 gün içinde çok hafif pullanma oluşması normal ve beklenen bir reaksiyondur. İşlem sonrası uzman tarafından önerilen yatıştırıcı ve yüksek faktörlü (SPF 50+) güneş koruyucuların kullanılması esastır.</p>

<h4>4. DANIŞAN ONAYI</h4>
<p>İşlem hakkında tarafıma sözlü ve yazılı gerekli tüm açıklamalar yapılmış, sorularım yanıtlanmıştır. İşlemin olası sonuçlarını, bakım talimatlarını anladım ve <strong>{{SERVICE_NAME}}</strong> uygulamasının gerçekleştirilmesini hür irademle kabul ve onaylıyorum.</p>

<div class="legal-signature-box mt-4 p-3 border rounded bg-light">
  <div class="row">
    <div class="col-6"><strong>Danışan:</strong> {{CUSTOMER_FULL_NAME}}</div>
    <div class="col-6 text-end"><strong>Tarih:</strong> {{SYSTEM_DATE}}</div>
  </div>
</div>',
            ],

            // -----------------------------------------------------------------
            // 2. LAZER EPİLASYON
            // -----------------------------------------------------------------
            'LAZER_EPILASYON_ONAM' => [
                'code' => 'LAZER_EPILASYON_ONAM',
                'sector' => 'beauty_wellness',
                'category' => 'Lazer Epilasyon',
                'doc_type' => 'AYDINLATILMIS_ONAM_FORMU',
                'title' => 'Lazer Epilasyon Bilgilendirilmiş Onam ve Hizmet Protokolü',
                'source_reference' => 'Türk Dermatoloji Derneği & Güzellik Merkezleri Yönetmeliği',
                'is_mandatory' => 1,
                'description' => 'Alexandrite, Diode, Nd:YAG lazer ve foto epilasyon uygulamaları için kapsamlı aydınlatılmış onam.',
                'keywords' => ['lazer', 'epilasyon', 'alexandrite', 'diode', 'buz lazer', 'ipl', 'kıl'],
                'content_html' => '<div class="legal-doc-header">
<h3>LAZER EPİLASYON BİLGİLENDİRİLMİŞ ONAM VE HİZMET SÖZLEŞMESİ</h3>
<p class="text-muted small">Güzellik Salonları Yönetmeliği ve Tüketici Mevzuatı Kapsamında Düzenlenmiştir</p>
</div>

<table class="table table-bordered table-sm my-3 small">
  <tr><th width="30%">Merkez:</th><td>{{TENANT_NAME}}</td></tr>
  <tr><th>Danışan:</th><td><strong>{{CUSTOMER_FULL_NAME}}</strong> ({{CUSTOMER_PHONE}})</td></tr>
  <tr><th>Uygulama:</th><td>{{SERVICE_NAME}}</td></tr>
  <tr><th>Uzman / Uygulayıcı:</th><td>{{PROVIDER_NAME}}</td></tr>
  <tr><th>Tarih & Saat:</th><td>{{APPOINTMENT_DATE_TIME}}</td></tr>
</table>

<h4>1. LAZER EPİLASYONUN ETKİ MEKANİZMASI</h4>
<p>Lazer epilasyon sistemi; kıl kökündeki melanin pigmentini hedef alarak selektif fototermoliz prensibiyle kıl kökünü tahrip etmeyi amaçlar. Kıllar büyüme (anajen), gerileme (katajen) ve dinlenme (telojen) evrelerinden geçer. Lazer yalnızca aktif anajen evredeki kıllara etki ettiğinden kalıcı azalma için seansların düzenli tekrarlanması gereklidir.</p>

<h4>2. UYGULAMA ÖNCESİ VE DİKKAT EDİLECEK HUSUSLAR</h4>
<ul>
  <li>Son 4 hafta içinde bölgedeki kıllara ağda, cımbız, epilatör veya sarartıcı uygulanmamış olmalıdır.</li>
  <li>Son 1 ay içinde yoğun güneşlenilmemiş, solaryuma girilmemiş, bronzlaşılmamış olmalıdır.</li>
  <li>Işığa duyarlılık yaratan ilaçlar (Tetrasiklin, Roaccutane vb.) kullanılıyorsa uzmana bildirilmelidir.</li>
  <li>Hamilelik şüphesi olan veya hamile danışanlara uygulama yapılmaz.</li>
</ul>

<h4>3. OLASI REAKSİYONLAR VE SONRASI BAKIM</h4>
<p>İşlem sonrası kıl köklerinde kızarıklık ve perifoliküler ödem (hafif şişlik) normaldir. 24 saat boyunca sıcak duş, sauna, terletici spor yasaktır. En az 2 hafta güneşten korunulmalı ve SPF 50 koruyucu kullanılmalıdır.</p>

<p>İşbu bilgilendirmeyi okudum, anladım, risk ve sorumlulukları kabul ederek işlemi onaylıyorum.</p>',
            ],

            // -----------------------------------------------------------------
            // 3. BOTOKS VE DERMAL DOLGU (MEDİKAL ESTETİK)
            // -----------------------------------------------------------------
            'BOTOKS_DOLGU_ESTETIK_ONAM' => [
                'code' => 'BOTOKS_DOLGU_ESTETIK_ONAM',
                'sector' => 'beauty_wellness',
                'category' => 'Medikal Estetik',
                'doc_type' => 'AYDINLATILMIS_ONAM_FORMU',
                'title' => 'Botulinum Toksin (Botoks) ve Dermal Dolgu Bilgilendirilmiş Onam Formu',
                'source_reference' => 'Dr. Figen Beauty & DK Klinik Standartları, 1219 Sayılı Tababet Kanunu',
                'is_mandatory' => 1,
                'description' => 'Mimik kırışıklıkları, masseter, alın, dudak, nazolabial ve hyaluronik asit dolgu uygulamaları.',
                'keywords' => ['botoks', 'botox', 'dolgu', 'filler', 'hyaluronik', 'dudak', 'masseter', 'gençlik aşısı', 'mezoterapi', 'prp'],
                'content_html' => '<div class="legal-doc-header">
<h3>BOTULİNUM TOKSİN VE DERMAL DOLGU BİLGİLENDİRİLMİŞ ONAM FORMU</h3>
<p class="text-muted small">1219 Sayılı Tababet ve Şuabatı Sanatlarının Tarzı İcrasına Dair Kanun & Hasta Hakları Yönetmeliği Uyarınca</p>
</div>

<table class="table table-bordered table-sm my-3 small">
  <tr><th width="30%">Klinik / Merkez:</th><td>{{TENANT_NAME}} ({{TENANT_LEGAL_NAME}})</td></tr>
  <tr><th>Danışan / Hasta:</th><td><strong>{{CUSTOMER_FULL_NAME}}</strong></td></tr>
  <tr><th>Planlanan İşlem:</th><td>{{SERVICE_NAME}}</td></tr>
  <tr><th>Hekim / Uygulayıcı:</th><td>{{PROVIDER_NAME}}</td></tr>
  <tr><th>Randevu:</th><td>{{APPOINTMENT_DATE_TIME}}</td></tr>
</table>

<h4>1. İŞLEMİN NİTELİĞİ VE GEÇİCİLİĞİ</h4>
<p>Botulinum toksin; kas hareketlerini geçici bloke ederek dinamik mimik çizgilerini yumuşatır, etki süresi ortalama 4-6 aydır. Hyaluronik asit dermal dolgular ise hacim kayıplarını ve derin çizgileri gidermek için uygulanır; kalıcılığı ortalama 6-18 aydır.</p>

<h4>2. RİSKLER VE KOMPLİKASYONLAR</h4>
<p>Enjeksiyon yerinde geçici morarma, ödem, kızarıklık, asimetri görülebilir. Nadiren botoks sonrası geçici kaş/göz kapağı düşüklüğü (ptozis); dolgu sonrası dolaşım bozuklukları veya granülom riski mevcuttur. Hamilelik, emzirme ve myasthenia gravis hastalarına uygulanmaz.</p>

<h4>3. İŞLEM SONRASI DİKKAT EDİLECEKLER</h4>
<p>İlk 24 saat baş öne eğilmemeli, yüze masaj yapılmamalı, hamam/sauna ve alkol tüketiminden kaçınılmalıdır.</p>
<p>Yukarıdaki risk ve maddeleri okudum, anladım. İşlemin uygulanmasını onaylıyorum.</p>',
            ],

            // -----------------------------------------------------------------
            // 4. KALICI MAKYAJ & MICROBLADING
            // -----------------------------------------------------------------
            'KALICI_MAKYAJ_MICROBLADING_ONAM' => [
                'code' => 'KALICI_MAKYAJ_MICROBLADING_ONAM',
                'sector' => 'beauty_wellness',
                'category' => 'Kalıcı Makyaj & Güzellik',
                'doc_type' => 'ALERJI_VE_ISLEM_RIZA_FORMU',
                'title' => 'Kalıcı Makyaj, Microblading ve Kaş/Kirpik Uygulama Onam Formu',
                'source_reference' => 'Gizem Türk Kalıcı Makyaj Standartları & Güzellik Salonları Yönetmeliği',
                'is_mandatory' => 1,
                'description' => 'Microblading, dudak renklendirme, dipliner, kaş laminasyonu ve kirpik lifting.',
                'keywords' => ['microblading', 'kalıcı makyaj', 'kaş', 'dudak', 'dipliner', 'eyeliner', 'laminasyon', 'lifting', 'kirpik'],
                'content_html' => '<div class="legal-doc-header">
<h3>KALICI MAKYAJ VE MİCROBLADİNG DANIŞAN RIZA METNİ</h3>
</div>

<table class="table table-bordered table-sm my-3 small">
  <tr><th width="30%">Merkez:</th><td>{{TENANT_NAME}}</td></tr>
  <tr><th>Danışan:</th><td><strong>{{CUSTOMER_FULL_NAME}}</strong></td></tr>
  <tr><th>İşlem:</th><td>{{SERVICE_NAME}}</td></tr>
  <tr><th>Tarih:</th><td>{{APPOINTMENT_DATE_TIME}}</td></tr>
</table>

<h4>1. İŞLEM DETAYI</h4>
<p>Microblading ve kalıcı makyaj uygulamaları; steril tek kullanımlık mikro iğneler vasıtasıyla cildin epidermisine organik/mineral pigmentlerin işlenmesidir. Kalıcılık cilt tipine göre 1-2 yıldır.</p>

<h4>2. İYİLEŞME PROTOKOLÜ</h4>
<p>İlk 3 gün renk tonu beklenenden %30-40 daha koyu görünecektir. 4-7. günlerde hafif kabuklanma ve dökülme gerçekleşir. Kabuklar kesinlikle soyulmamalı, 7 gün boyunca su ve buhardan uzak tutulmalıdır.</p>
<p>İşlem şartlarını, renk pigment testini ve bakım kurallarını kabul ediyorum.</p>',
            ],

            // -----------------------------------------------------------------
            // 5. SAÇ BAKIMI & KUAFÖR HİZMETLERİ
            // -----------------------------------------------------------------
            'KUAFOR_SAC_KIMYASAL_ISLEM_ONAM' => [
                'code' => 'KUAFOR_SAC_KIMYASAL_ISLEM_ONAM',
                'sector' => 'beauty_wellness',
                'category' => 'Kuaför & Saç Hizmetleri',
                'doc_type' => 'HIZMET_SOZLESMESI',
                'title' => 'Saç Açma, Boya ve Kimyasal İşlemler Danışan Bilgilendirme ve Onam Formu',
                'source_reference' => 'Kuaförler ve Berberler Federasyonu Standartları',
                'is_mandatory' => 0,
                'description' => 'Ombre, röfle, dekolorasyon, keratin bakım ve perma gibi kimyasal işlemler için saç geçmişi ve onay formu.',
                'keywords' => ['saç', 'boya', 'ombre', 'röfle', 'açma', 'keratin', 'perma', 'balyaj', 'kuaför'],
                'content_html' => '<div class="legal-doc-header">
<h3>SAÇ AÇMA, BOYA VE KİMYASAL İŞLEMLER BİLGİLENDİRME FORMU</h3>
</div>

<table class="table table-bordered table-sm my-3 small">
  <tr><th width="30%">Salon:</th><td>{{TENANT_NAME}}</td></tr>
  <tr><th>Müşteri:</th><td><strong>{{CUSTOMER_FULL_NAME}}</strong> ({{CUSTOMER_PHONE}})</td></tr>
  <tr><th>İşlem:</th><td>{{SERVICE_NAME}}</td></tr>
  <tr><th>Stilist / Uzman:</th><td>{{PROVIDER_NAME}}</td></tr>
  <tr><th>Tarih:</th><td>{{APPOINTMENT_DATE_TIME}}</td></tr>
</table>

<h4>1. SAÇ GEÇMİŞİ VE BEYAN</h4>
<p>Müşteri olarak; saçımda daha önce kına, hint kınası, metalik boyalar, ev tipi açıcı veya yoğun ısı hasarı olup olmadığını uzmana doğru olarak aktardığımı, saçın elastikiyet durumuna göre uygulanacak dekolorasyon/açma işlemine onay verdiğimi beyan ederim.</p>',
            ],

            // -----------------------------------------------------------------
            // 6. GENEL SAĞLIK & DİŞ HEKİMLİĞİ (İDO)
            // -----------------------------------------------------------------
            'DIS_HEKIMLIGI_TEDAVI_ONAM' => [
                'code' => 'DIS_HEKIMLIGI_TEDAVI_ONAM',
                'sector' => 'health_clinical',
                'category' => 'Diş Hekimliği',
                'doc_type' => 'AYDINLATILMIS_ONAM_FORMU',
                'title' => 'İstanbul Dişhekimleri Odası (İDO) Uyumlu Genel Diş Tedavisi ve Cerrahi Onam Formu',
                'source_reference' => 'İstanbul Dişhekimleri Odası (ido.org.tr) & Hasta Hakları Yönetmeliği',
                'is_mandatory' => 1,
                'description' => 'Dolgu, kanal tedavisi, diş çekimi, implant ve protetik diş tedavileri için resmi aydınlatılmış onam.',
                'keywords' => ['diş', 'implant', 'kanal', 'dolgu', 'çekim', 'protez', 'zirkonyum', 'ortodonti', 'diş hekimi', 'çene'],
                'content_html' => '<div class="legal-doc-header">
<h3>DİŞ HEKİMLİĞİ UYGULAMALARI HASTA AYDINLATILMIŞ ONAM FORMU</h3>
<p class="text-muted small">İstanbul Dişhekimleri Odası Standart Formu Esas Alınmıştır</p>
</div>

<table class="table table-bordered table-sm my-3 small">
  <tr><th width="30%">Sağlık Kuruluşu:</th><td>{{TENANT_NAME}}</td></tr>
  <tr><th>Hasta:</th><td><strong>{{CUSTOMER_FULL_NAME}}</strong></td></tr>
  <tr><th>Uygulama:</th><td>{{SERVICE_NAME}}</td></tr>
  <tr><th>Hekim:</th><td>{{PROVIDER_NAME}}</td></tr>
  <tr><th>Tarih:</th><td>{{APPOINTMENT_DATE_TIME}}</td></tr>
</table>

<h4>1. PLANLANAN TEDAVİ</h4>
<p>Ağız ve diş sağlığımın korunması veya rehabilite edilmesi amacıyla planlanan <strong>{{SERVICE_NAME}}</strong> işlemi, alternatif yöntemler ve olası komplikasyonlar hekimim tarafından detaylıca anlatılmıştır.</p>',
            ],

            // -----------------------------------------------------------------
            // 7. GENEL CERRAHİ & KLİNİK (DR. AHMET ÇAYMAZ & SKS)
            // -----------------------------------------------------------------
            'KLINIK_CERRAHI_GIRISIM_ONAM' => [
                'code' => 'KLINIK_CERRAHI_GIRISIM_ONAM',
                'sector' => 'health_clinical',
                'category' => 'Klinik & Cerrahi',
                'doc_type' => 'AYDINLATILMIS_ONAM_FORMU',
                'title' => 'Klinik ve Cerrahi Girişimler Aydınlatılmış Onam Formu',
                'source_reference' => 'Dr. Ahmet Çaymaz & SKS Sağlıkta Kalite Standartları',
                'is_mandatory' => 1,
                'description' => 'Küçük cerrahi işlemler, biyopsi, dikiş, sünnet ve girişimsel klinik müdahaleler.',
                'keywords' => ['cerrahi', 'ameliyat', 'müdahale', 'biyopsi', 'klinik', 'muayene', 'tedavi'],
                'content_html' => '<div class="legal-doc-header">
<h3>GİRİŞİMSEL TIBBİ İŞLEMLER AYDINLATILMIŞ ONAM FORMU</h3>
</div>

<table class="table table-bordered table-sm my-3 small">
  <tr><th width="30%">Sağlık Kuruluşu:</th><td>{{TENANT_NAME}}</td></tr>
  <tr><th>Hasta:</th><td><strong>{{CUSTOMER_FULL_NAME}}</strong></td></tr>
  <tr><th>İşlem:</th><td>{{SERVICE_NAME}}</td></tr>
  <tr><th>Tarih:</th><td>{{APPOINTMENT_DATE_TIME}}</td></tr>
</table>

<p>Hekimim tarafından planlanan müdahale, gerekçesi, riskleri ve anestezi detayları açıklanmış olup işlemi onaylıyorum.</p>',
            ],

            // -----------------------------------------------------------------
            // 8. KONAKLAMA & TURİZM (BAIA HOTELS & TÜRSAB)
            // -----------------------------------------------------------------
            'OTEL_KONAKLAMA_HIZMET_SOZLESMESI' => [
                'code' => 'OTEL_KONAKLAMA_HIZMET_SOZLESMESI',
                'sector' => 'hospitality',
                'category' => 'Konaklama & Turizm',
                'doc_type' => 'KONAKLAMA_HIZMET_SOZLESMESI',
                'title' => 'TÜRSAB & Kültür ve Turizm Bakanlığı Uyumlu Konaklama Hizmet Sözleşmesi',
                'source_reference' => 'Baia Hotels & TÜRSAB Güvenlik Standartları',
                'is_mandatory' => 1,
                'description' => 'Otel, pansiyon, tatil köyü ve tesis konaklamalarında giriş ve mesafeli hizmet sözleşmesi.',
                'keywords' => ['otel', 'oda', 'konaklama', 'rezervasyon', 'pansiyon', 'tatil', 'suit'],
                'content_html' => '<div class="legal-doc-header">
<h3>KONAKLAMA HİZMET SÖZLEŞMESİ VE TESİS KURALLARI</h3>
</div>

<table class="table table-bordered table-sm my-3 small">
  <tr><th width="30%">Tesis:</th><td>{{TENANT_NAME}}</td></tr>
  <tr><th>Misafir:</th><td><strong>{{CUSTOMER_FULL_NAME}}</strong> ({{CUSTOMER_PHONE}})</td></tr>
  <tr><th>Hizmet / Oda:</th><td>{{SERVICE_NAME}}</td></tr>
  <tr><th>Giriş / Tarih:</th><td>{{APPOINTMENT_DATE_TIME}}</td></tr>
</table>

<p>KBS Kimlik Bildirimi Kanunu uyarınca kimlik bilgilerimin doğruluğunu, tesis güvenlik ve oda kullanım kurallarına uyacağımı beyan ve taahhüt ederim.</p>',
            ],

            // -----------------------------------------------------------------
            // 9. OTOMOTİV & ARAÇ SERVİSİ
            // -----------------------------------------------------------------
            'ARAC_BAKIM_SERVIS_SOZLESMESI' => [
                'code' => 'ARAC_BAKIM_SERVIS_SOZLESMESI',
                'sector' => 'automotive',
                'category' => 'Otomotiv & Servis',
                'doc_type' => 'HIZMET_SOZLESMESI',
                'title' => 'TMMOB & AvEvrak Uyumlu Araç Bakım, Onarım ve Servis Sözleşmesi',
                'source_reference' => 'AvEvrak & Lexpera Yetkili Servis Standartları',
                'is_mandatory' => 1,
                'description' => 'Araç kabul, periyodik bakım, mekanik onarım ve ekspertiz iş emri sözleşmesi.',
                'keywords' => ['araç', 'oto', 'servis', 'bakım', 'tamir', 'yağ', 'fren', 'balata', 'ekspertiz', 'lastik', 'yıkama'],
                'content_html' => '<div class="legal-doc-header">
<h3>ARAÇ BAKIM, ONARIM VE SERVİS SÖZLEŞMESİ</h3>
</div>

<table class="table table-bordered table-sm my-3 small">
  <tr><th width="30%">Servis:</th><td>{{TENANT_NAME}}</td></tr>
  <tr><th>Müşteri:</th><td><strong>{{CUSTOMER_FULL_NAME}}</strong> ({{CUSTOMER_PHONE}})</td></tr>
  <tr><th>Talep Edilen Servis:</th><td>{{SERVICE_NAME}}</td></tr>
  <tr><th>Randevu Tarihi:</th><td>{{APPOINTMENT_DATE_TIME}}</td></tr>
</table>

<p>Aracımın servis alanında test sürüşü ve onarım amacıyla kullanılmasına, tespit edilen ek arızalarda onay alınmasına muvafakat ediyorum.</p>',
            ],

            // -----------------------------------------------------------------
            // 10. KVKK & AÇIK RIZA (HER SEKTÖR İÇİN ZORUNLU / ORTAK)
            // -----------------------------------------------------------------
            'KVKK_AYDINLATMA_VE_ACIK_RIZA' => [
                'code' => 'KVKK_AYDINLATMA_VE_ACIK_RIZA',
                'sector' => 'general',
                'category' => 'Yasal & KVKK',
                'doc_type' => 'KVKK_ONAM_METNI',
                'title' => '6698 Sayılı KVKK Kapsamında Kişisel Verilerin İşlenmesi ve Açık Rıza Metni',
                'source_reference' => 'Kişisel Verileri Koruma Kurumu (KVKK) Resmi Kılavuzu',
                'is_mandatory' => 1,
                'description' => 'Tüm sektörlerde müşteri ve danışan verilerinin güvenli işlenmesi için genel açık rıza metni.',
                'keywords' => ['kvkk', 'açık rıza', 'aydınlatma', 'kişisel veri'],
                'content_html' => '<div class="legal-doc-header">
<h3>KİŞİSEL VERİLERİN KORUNMASI KANUNU (KVKK) AYDINLATMA VE AÇIK RIZA BEYANI</h3>
<p class="text-muted small">6698 Sayılı Kişisel Verilerin Korunması Kanunu Madde 10 ve 11 Uyarınca</p>
</div>

<p>Veri Sorumlusu sıfatıyla <strong>{{TENANT_NAME}} ({{TENANT_LEGAL_NAME}})</strong> tarafından yürütülen hizmet faaliyetleri kapsamında;</p>
<ul>
  <li>Ad, soyad, telefon numarası, e-posta adresi gibi kimlik ve iletişim verilerimin,</li>
  <li>Randevu oluşturma, hatırlatma mesajları gönderme, fatura düzenleme ve hizmet ifası amacıyla,</li>
  <li>6698 sayılı Kanun’un 5. ve 6. maddelerinde belirtilen şartlara uygun olarak işlenmesine ve mevzuatın izin verdiği ölçüde güvenli sistemlerde saklanmasına açık rıza gösteriyorum.</li>
</ul>

<div class="legal-signature-box mt-3 p-2 border rounded bg-light">
  <strong>Onaylayan:</strong> {{CUSTOMER_FULL_NAME}} | <strong>Tarih:</strong> {{SYSTEM_DATE}}
</div>',
            ],
        ];
    }

    /**
     * Match templates automatically based on service details.
     */
    public function get_suggested_templates_for_service(string $service_name, string $category_name = ''): array
    {
        $catalog = $this->get_catalog();
        $matched = [];
        $text = mb_strtolower($service_name . ' ' . $category_name, 'UTF-8');

        foreach ($catalog as $code => $template) {
            $keywords = $template['keywords'] ?? [];
            foreach ($keywords as $kw) {
                if (mb_strpos($text, mb_strtolower($kw, 'UTF-8'), 0, 'UTF-8') !== false) {
                    $matched[$code] = $template;
                    break;
                }
            }
        }

        // Always suggest KVKK if matched anything or fallback
        if (empty($matched)) {
            $matched['CILT_BAKIMI_MEDIKAL_ESTETIK'] = $catalog['CILT_BAKIMI_MEDIKAL_ESTETIK'];
            $matched['KVKK_AYDINLATMA_VE_ACIK_RIZA'] = $catalog['KVKK_AYDINLATMA_VE_ACIK_RIZA'];
        } else {
            $matched['KVKK_AYDINLATMA_VE_ACIK_RIZA'] = $catalog['KVKK_AYDINLATMA_VE_ACIK_RIZA'];
        }

        return $matched;
    }

    /**
     * Compile template placeholders into personalized customer text.
     */
    public function compile(string $template_text, array $context): string
    {
        $tenant_name = $context['tenant_name'] ?? setting('company_name') ?: 'BooKi İşletmesi';
        $customer_name = trim(($context['customer_first_name'] ?? '') . ' ' . ($context['customer_last_name'] ?? ''));
        if (empty($customer_name)) {
            $customer_name = $context['customer_full_name'] ?? $context['signer_full_name'] ?? 'Değerli Danışanımız';
        }

        $service_name = $context['service_name'] ?? 'Hizmet';
        $service_category = $context['service_category'] ?? 'Genel';
        $provider_name = $context['provider_name'] ?? $context['provider_full_name'] ?? 'Merkez Uzmanı';
        
        $appt_date = $context['appointment_date'] ?? date('d.m.Y');
        $appt_time = $context['appointment_time'] ?? date('H:i');
        $appt_datetime = $context['appointment_date_time'] ?? $context['appointment_datetime'] ?? ($appt_date . ' ' . $appt_time);

        $price = isset($context['service_price']) ? number_format((float) $context['service_price'], 2) . ' ₺' : (isset($context['price']) ? number_format((float) $context['price'], 2) . ' ₺' : 'Belirtilmedi');
        $phone = $context['customer_phone'] ?? $context['phone_number'] ?? '-';
        $email = $context['customer_email'] ?? $context['email'] ?? '-';
        $tckn = $context['customer_tckn'] ?? $context['tckn'] ?? $context['customer_masked_tckn'] ?? 'Belirtilmedi';

        // Masked TCKN if length >= 11
        if (strlen($tckn) === 11) {
            $masked_tckn = substr($tckn, 0, 3) . '*****' . substr($tckn, 8, 3);
        } else {
            $masked_tckn = $tckn;
        }

        $system_date = date('d.m.Y H:i');

        $replacements = [
            '{{CUSTOMER_FULL_NAME}}' => htmlspecialchars($customer_name, ENT_QUOTES, 'UTF-8'),
            '{{DANISAN_ADI}}' => htmlspecialchars($customer_name, ENT_QUOTES, 'UTF-8'),
            '{{MUSTERI_ADI}}' => htmlspecialchars($customer_name, ENT_QUOTES, 'UTF-8'),

            '{{CUSTOMER_PHONE}}' => htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'),
            '{{TELEFON}}' => htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'),

            '{{CUSTOMER_EMAIL}}' => htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
            '{{EPOSTA}}' => htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),

            '{{CUSTOMER_TCKN}}' => htmlspecialchars($tckn, ENT_QUOTES, 'UTF-8'),
            '{{CUSTOMER_MASKED_TCKN}}' => htmlspecialchars($masked_tckn, ENT_QUOTES, 'UTF-8'),
            '{{TCKN}}' => htmlspecialchars($masked_tckn, ENT_QUOTES, 'UTF-8'),

            '{{SERVICE_NAME}}' => htmlspecialchars($service_name, ENT_QUOTES, 'UTF-8'),
            '{{HIZMET_ADI}}' => htmlspecialchars($service_name, ENT_QUOTES, 'UTF-8'),

            '{{SERVICE_CATEGORY}}' => htmlspecialchars($service_category, ENT_QUOTES, 'UTF-8'),
            '{{KATEGORI}}' => htmlspecialchars($service_category, ENT_QUOTES, 'UTF-8'),

            '{{SERVICE_PRICE}}' => htmlspecialchars($price, ENT_QUOTES, 'UTF-8'),
            '{{PRICE}}' => htmlspecialchars($price, ENT_QUOTES, 'UTF-8'),
            '{{UCRET}}' => htmlspecialchars($price, ENT_QUOTES, 'UTF-8'),

            '{{PROVIDER_NAME}}' => htmlspecialchars($provider_name, ENT_QUOTES, 'UTF-8'),
            '{{UZMAN_ADI}}' => htmlspecialchars($provider_name, ENT_QUOTES, 'UTF-8'),

            '{{APPOINTMENT_DATE}}' => htmlspecialchars($appt_date, ENT_QUOTES, 'UTF-8'),
            '{{RANDEVU_TARIHI}}' => htmlspecialchars($appt_date, ENT_QUOTES, 'UTF-8'),

            '{{APPOINTMENT_TIME}}' => htmlspecialchars($appt_time, ENT_QUOTES, 'UTF-8'),
            '{{RANDEVU_SAATI}}' => htmlspecialchars($appt_time, ENT_QUOTES, 'UTF-8'),

            '{{APPOINTMENT_DATE_TIME}}' => htmlspecialchars($appt_datetime, ENT_QUOTES, 'UTF-8'),
            '{{RANDEVU_TARIH_SAAT}}' => htmlspecialchars($appt_datetime, ENT_QUOTES, 'UTF-8'),

            '{{TENANT_NAME}}' => htmlspecialchars($tenant_name, ENT_QUOTES, 'UTF-8'),
            '{{ISLETME_ADI}}' => htmlspecialchars($tenant_name, ENT_QUOTES, 'UTF-8'),
            '{{TENANT_LEGAL_NAME}}' => htmlspecialchars($tenant_name, ENT_QUOTES, 'UTF-8'),
            '{{TENANT_ADDRESS}}' => htmlspecialchars(setting('company_address') ?: 'Merkez Adresi', ENT_QUOTES, 'UTF-8'),
            '{{TENANT_PHONE}}' => htmlspecialchars(setting('company_phone') ?: '-', ENT_QUOTES, 'UTF-8'),

            '{{SYSTEM_DATE}}' => $system_date,
            '{{TARIH}}' => $system_date,
            '{{BUGUN}}' => date('d.m.Y'),
        ];

        return strtr($template_text, $replacements);
    }

    /**
     * Automatically ensure standard templates are seeded into digital_waivers table
     * and clean up any out-of-context items (e.g. Macera parkı in beauty salons).
     */
    public function ensure_seeded_templates(): void
    {
        if (!$this->CI->db->table_exists('digital_waivers')) {
            return;
        }

        // Clean out legacy inappropriate sample if any
        $this->CI->db->where('title LIKE', '%Macera Parkı%')->delete('digital_waivers');

        // Check current count
        $count = $this->CI->db->count_all('digital_waivers');
        if ($count < 3) {
            $catalog = $this->get_catalog();
            $now = date('Y-m-d H:i:s');
            
            // Priority beauty & clinical templates
            $priority_keys = [
                'CILT_BAKIMI_MEDIKAL_ESTETIK',
                'LAZER_EPILASYON_ONAM',
                'BOTOKS_DOLGU_ESTETIK_ONAM',
                'KALICI_MAKYAJ_MICROBLADING_ONAM',
                'KVKK_AYDINLATMA_VE_ACIK_RIZA'
            ];

            foreach ($priority_keys as $key) {
                if (isset($catalog[$key])) {
                    $item = $catalog[$key];
                    $exists = $this->CI->db->where('title', $item['title'])->get('digital_waivers')->row_array();
                    if (!$exists) {
                        $this->CI->db->insert('digital_waivers', [
                            'title' => $item['title'],
                            'content_html' => $item['content_html'],
                            'is_mandatory' => $item['is_mandatory'],
                            'applicable_service_ids' => '', // Will be dynamically linked or linked to all
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }
}
