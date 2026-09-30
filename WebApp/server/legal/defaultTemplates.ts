import { DocumentType, LegalTemplateDto } from "@shared/legalTypes";

/**
 * BooKi SaaS - Ceza-Geçirmez Hukuk ve Dijital Onam Şablonları Kataloğu
 *
 * Referans Hukuki Standartlar ve Kurumsal Belgeler:
 * - Türk Oftalmoloji Derneği (TOD) Göz Cerrahisi ve Klinik Onam Standartları
 * - İstanbul Dişhekimleri Odası (İDO) Diş Hekimliği Hasta Bilgilendirilmiş Onam Formları
 * - Türk Dermatoloji Derneği & DK Klinik Lazer ve Cilt Yenileme Onam Formları
 * - Dr. Figen Beauty Medikal Estetik, Botoks, Dolgu ve Mezoterapi Onam Protokolleri
 * - Türk Yoğun Bakım Derneği ve SKS (Sağlıkta Kalite Standartları) Hastane/Klinik Belgeleri
 * - Julian Hotels KVKK Misafir Aydınlatma ve Açık Rıza Metni (1774 KBS Uyumlu)
 * - Baia Hotels & Zigana Tatil Köyü Konaklama Hizmet Sözleşmeleri (TBK m. 20-25 & TKHK m. 5)
 * - TÜRSAB Seyahat Acentaları Güvenlik ve Sorumluluk Kuralları
 * - AvEvrak & Lexpera Araç Bakım ve Onarım / Yetkili Servis Hizmet Sözleşmeleri
 * - TMMOB Makina Mühendisleri Odası (MMO) Araç Kontrol ve Hasar Tespit Tutanakları
 * - 30442 Sayılı Taşınmaz Ticareti Hakkında Yönetmelik Madde 19 Taşınmaz Gösterme Belgesi
 */

export const DEFAULT_LEGAL_TEMPLATES: Record<DocumentType, LegalTemplateDto> = {
  // 1. GENEL KVKK AYDINLATMA METNİ
  KVKK_AYDINLATMA: {
    id: "tpl_kvkk_aydinlatma_v1",
    sectorFamily: "beauty_wellness",
    blueprintType: null,
    docType: "KVKK_AYDINLATMA",
    version: 1,
    title: "6698 Sayılı KVKK Kapsamında Müşteri ve Misafir Aydınlatma Metni",
    description: "6698 sayılı Kişisel Verilerin Korunması Kanunu Madde 10 uyarınca veri sorumlusu aydınlatma bildirimi.",
    requiresSignature: false,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "SIMPLE_BIOMETRIC",
    validityDays: 365,
    bodyTemplateMarkdown: `# 6698 SAYILI KİŞİSEL VERİLERİN KORUNMASI KANUNU (KVKK) AYDINLATMA METNİ

**Veri Sorumlusu:** {{TENANT_LEGAL_NAME}}  
**Vergi Kimlik No (VKN):** {{TENANT_TAX_ID}} | **MERSİS No:** {{TENANT_MERSIS}}  
**Adres:** {{TENANT_ADDRESS}} | **Telefon:** {{TENANT_PHONE}}  
**Tarih:** {{SYSTEM_DATE}}

İşbu Aydınlatma Metni, 6698 sayılı Kişisel Verilerin Korunması Kanunu'nun ("KVKK") 10. maddesi ile Aydınlatma Yükümlülüğünün Yerine Getirilmesinde Uyulacak Usul ve Esaslar Hakkında Tebliğ kapsamında, Veri Sorumlusu sıfatıyla **{{TENANT_LEGAL_NAME}}** ("İşletme") tarafından hazırlanmıştır.

---

### 1. İşlenen Kişisel Verileriniz
Hizmet ve randevu süreçlerimizde tarafınızca sağlanan aşağıdaki kategorilerdeki kişisel verileriniz işlenmektedir:
- **Kimlik Bilgileri:** Ad, soyad, T.C. Kimlik Numarası ({{CUSTOMER_MASKED_TCKN}}).
- **İletişim Bilgileri:** Telefon numarası ({{CUSTOMER_PHONE}}), e-posta adresi ({{CUSTOMER_EMAIL}}), adres bilgisi.
- **Müşteri İşlem & Rezervasyon Bilgileri:** Talep edilen hizmet ({{SERVICE_NAME}}), randevu tarihi/saati ({{APPOINTMENT_DATE_TIME}}), hizmet tutarı, ödeme ve fatura bilgileri.
- **İşlem Güvenliği Verileri:** IP adresi, oturum log kayıtları, dijital onay zaman damgası (RFC 3161 uyumlu).

### 2. Kişisel Verilerin İşlenme Amaçları ve Hukuki Sebepleri
Kişisel verileriniz, KVKK'nın 5. maddesinde belirtilen hukuki sebeplere dayalı olarak:
1. **Sözleşmenin Kurulması ve İfası (m. 5/2-c):** Randevuların oluşturulması, teyit edilmesi, hizmetin eksiksiz sunulması,
2. **Hukuki Yükümlülüğün Yerine Getirilmesi (m. 5/2-ç):** Vergi mevzuatı, 6502 sayılı Tüketicinin Korunması Hakkında Kanun ve ilgili sektörel mevzuat uyarınca fatura ve kayıt saklama yükümlülükleri,
3. **Meşru Menfaat (m. 5/2-f):** Müşteri memnuniyeti ölçümü, hizmet kalitesinin geliştirilmesi ve hukuki ihtilaflarda delil güvenliği amaçlarıyla işlenmektedir.

### 3. Kişisel Verilerin Aktarımı
Kişisel verileriniz; kanunen yetkili kamu kurum ve kuruluşlarına, adli makamlara, mali müşavir ve denetçilere, sözleşmeli SMS ve e-posta bildirim altyapı sağlayıcılarına yalnızca mevzuatın çizdiği sınırlar dahilinde aktarılmaktadır.

### 4. KVKK Madde 11 Kapsamındaki Haklarınız
İlgili kişi olarak; verilerinizin işlenip işlenmediğini öğrenme, işlenmişse bilgi talep etme, işlenme amacına uygun kullanılıp kullanılmadığını öğrenme, eksik/yanlış işlenmişse düzeltilmesini isteme ve silinmesini talep etme haklarına sahipsiniz. Taleplerinizi **{{TENANT_ADDRESS}}** adresine yazılı olarak veya teyitli iletişim kanallarımız üzerinden iletebilirsiniz.

*Sayın **{{CUSTOMER_FULL_NAME}}**, işbu aydınlatma metnini okuduğunuzu ve bilgilendirildiğinizi sistemimiz üzerinden teyit etmektesiniz.*`,
  },

  // 2. KVKK ÖZEL NİTELİKLİ SAĞLIK VERİSİ AÇIK RIZA
  KVKK_OZEL_NITELIKLI_RIZA: {
    id: "tpl_kvkk_ozel_nitelikli_riza_v1",
    sectorFamily: "health_clinical",
    blueprintType: null,
    docType: "KVKK_OZEL_NITELIKLI_RIZA",
    version: 1,
    title: "Özel Nitelikli Sağlık Verilerinin İşlenmesine İlişkin Açık Rıza Formu",
    description: "6698 Sayılı KVKK Madde 6 uyarınca torba rıza yasağına uygun bağımsız sağlık verisi açık rızası.",
    requiresSignature: true,
    requiresOtp: true,
    requiresGps: false,
    isRequired: false, // Torba rıza yasağı gereğince zorunlu tutulamaz
    isActive: true,
    signatureLevel: "OTP_2FA",
    validityDays: 365,
    bodyTemplateMarkdown: `# ÖZEL NİTELİKLİ SAĞLIK VE BİYOMETRİK VERİLERİN İŞLENMESİNE İLİŞKİN AÇIK RIZA METNİ

**Veri Sorumlusu:** {{TENANT_LEGAL_NAME}}  
**Hasta / Danışan:** {{CUSTOMER_FULL_NAME}} (TCKN: {{CUSTOMER_MASKED_TCKN}})  
**Tarih:** {{SYSTEM_DATE}}

6698 sayılı Kişisel Verilerin Korunması Kanunu'nun ("KVKK") 6. maddesi uyarınca, kişilerin sağlık verileri ve biyometrik verileri **Özel Nitelikli Kişisel Veri** statüsündedir. Bu verilerin işlenmesi ilgili kişinin açık rızasına tabidir.

---

### 1. İşlenecek Özel Nitelikli Kişisel Veriler
- **Sağlık Verileri:** Tıbbi geçmiş, alerji ve duyarlılıklar, kronik hastalıklar, devam eden tedaviler, kullanılan ilaçlar, uygulama öncesi ve sonrası medikal fotoğraflar/klinik gözlemler.
- **Biyometrik Veriler:** Dijital onay sürecinde kullanılan imza dinamikleri (zaman, basınç ve vuruş karakteristiği).

### 2. İşleme Amacı ve Kapsamı
Belirtilen özel nitelikli kişisel verilerim;
1. Planlanan **{{SERVICE_NAME}}** işleminin sağlık durumuma uygunluğunun hekim/uzman tarafından değerlendirilmesi,
2. Olası tıbbi komplikasyon ve alerjik risklerin önceden öngörülmesi ve önlenmesi,
3. Tıbbi teşhis, tedavi, bakım ve estetik sürecinin takibi ile mevzuat gereği hasta dosyası oluşturulması amacıyla sınırlı olarak işlenecektir.

### 3. Açık Rızanın İhtiyariliği ve Geri Alınması
İşbu açık rıza beyanı hiçbir hizmetin veya sözleşmenin zorunlu ön şartı olmayıp, tamamen özgür irademle verilmektedir. Rızamı dilediğim an hiçbir gerekçe göstermeksizin geri alma hakkım saklıdır.

### 4. Açık Rıza Beyanı
KVKK Aydınlatma Metni'ni okudum. Tarafıma ait özel nitelikli sağlık verilerimin yukarıda açıklanan amaçlarla sınırlı olarak işlenmesine ve arşivlenmesine:

**[ ] AÇIK RIZA VERİYORUM**  
*İmza Tarihi:* {{SYSTEM_DATE}}  
*Onaylayan:* {{CUSTOMER_FULL_NAME}} (SMS OTP Doğrulamalı Dijital İmza)`,
  },

  // 3. MESAFELİ SATIŞ SÖZLEŞMESİ (TBK m. 20-25 & TKHK m. 5 Haksız Şart Savunmalı)
  MESAFELI_SATIS_SOZLESMESI: {
    id: "tpl_mesafeli_satis_v1",
    sectorFamily: "beauty_wellness",
    blueprintType: null,
    docType: "MESAFELI_SATIS_SOZLESMESI",
    version: 1,
    title: "Mesafeli Hizmet Satış ve Rezervasyon Sözleşmesi",
    description: "6502 sayılı TKHK, Mesafeli Sözleşmeler Yönetmeliği ve TBK m. 20-25 Genel İşlem Koşulları uyumlu sözleşme.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: null,
    bodyTemplateMarkdown: `# MESAFELİ HİZMET SATIŞ VE REZERVASYON SÖZLEŞMESİ

**Sözleşme No:** {{SYSTEM_DATE}}-REZ  
**Hukuki Dayanak:** 6502 sayılı Tüketicinin Korunması Hakkında Kanun, Mesafeli Sözleşmeler Yönetmeliği, 6098 sayılı Türk Borçlar Kanunu m. 20-25 (Genel İşlem Koşulları).

---

### MADDE 1 - TARAFLAR
**SATICI / HİZMET SAĞLAYICI:**  
- **Unvan:** {{TENANT_LEGAL_NAME}}  
- **VKN / Vergi Dairesi:** {{TENANT_TAX_ID}} | **MERSİS:** {{TENANT_MERSIS}}  
- **Adres:** {{TENANT_ADDRESS}} | **Telefon:** {{TENANT_PHONE}}

**ALICI / TÜKETİCİ:**  
- **Adı Soyadı:** {{CUSTOMER_FULL_NAME}}  
- **T.C. Kimlik No:** {{CUSTOMER_MASKED_TCKN}}  
- **Telefon:** {{CUSTOMER_PHONE}} | **E-Posta:** {{CUSTOMER_EMAIL}}

---

### MADDE 2 - SÖZLEŞMENİN KONUSU VE HİZMET NİTELİĞİ
İşbu sözleşmenin konusu; ALICI'nın SATICI'ya ait elektronik rezervasyon platformu üzerinden siparişini verdiği aşağıda nitelikleri ve satış fiyatı belirtilen hizmetin satışı ve ifasına ilişkin tarafların hak ve yükümlülüklerinin belirlenmesidir.

- **Hizmet Adı:** {{SERVICE_NAME}}
- **Randevu Tarihi ve Saati:** {{APPOINTMENT_DATE_TIME}}
- **Toplam Hizmet Bedeli:** {{SERVICE_PRICE}}
- **Ön Ödeme / Depozito Tutarı:** {{DEPOSIT_AMOUNT}}

---

### MADDE 3 - KADEMELİ İPTAL, NO-SHOW VE DEPOZİTO ŞARTLARI (TBK m. 20-25 KORUMASI)
İşletme kapasitesi ALICI adına rezerve edildiğinden, taraflar arasında dürüstlük kuralı ve menfaat dengesi gözetilerek aşağıdaki iptal politikası kararlaştırılmıştır:
1. **Zamanında İptal:** ALICI, randevu saatinden en az **{{CANCELLATION_DEADLINE}}** öncesine kadar bildirimde bulunarak randevusunu ücretsiz iptal edebilir veya erteleyebilir; ödenen ön ödeme kesintisiz iade edilir.
2. **Geç İptal veya Gelmeme (No-Show):** Randevuya **{{CANCELLATION_DEADLINE}}** süreden daha az zaman kala yapılan iptallerde veya randevuya gelinmemesi halinde, ayrılan zaman diliminin başka bir müşteriye tahsis edilememesi sebebiyle oluşan zarara karşılık **{{PENALTY_RATE}}** oranında kesinti/tazminat uygulanacaktır.

---

### MADDE 4 - CAYMA HAKKI VE İSTİSNASI
Mesafeli Sözleşmeler Yönetmeliği'nin 15. maddesinin 1. fıkrasının (g) bendi uyarınca; *"Belirli bir tarihte veya dönemde yapılması gereken, konaklama, eşya taşıma, araba kiralama, yiyecek-içecek tedariki ve eğlence veya dinlenme amacıyla yapılan boş zamanın değerlendirilmesine ilişkin sözleşmelerde"* tüketici cayma hakkını kullanamaz. Belirli gün ve saat tahsisli randevulu hizmetler bu kapsamdadır.

---

### MADDE 5 - UYUŞMAZLIKLARIN ÇÖZÜMÜ
İşbu sözleşmeden doğabilecek uyuşmazlıklarda; Ticaret Bakanlığı'nca ilan edilen parasal sınırlar dahilinde ALICI'nın veya SATICI'nın yerleşim yerindeki Tüketici Hakem Heyetleri ile Tüketici Mahkemeleri yetkilidir.

ALICI, işbu sözleşmenin tüm maddelerini okuduğunu, anladığını ve elektronik ortamda onaylayarak imzaladığını beyan ve kabul eder.

**SATICI:** {{TENANT_LEGAL_NAME}}  
**ALICI:** {{CUSTOMER_FULL_NAME}} (Dijital Onaylı ve İmzalı)`,
  },

  // 4. AYDINLATILMIŞ ONAM FORMU (TOD, İDO, Türk Yoğun Bakım, SKS Standartları)
  AYDINLATILMIS_ONAM_FORMU: {
    id: "tpl_aydinlatilmis_onam_v1",
    sectorFamily: "health_clinical",
    blueprintType: null,
    docType: "AYDINLATILMIS_ONAM_FORMU",
    version: 1,
    title: "Tıbbi ve Girişimsel İşlemler Hasta Bilgilendirilmiş Onam Formu",
    description: "Tabip Odası, TOD, İDO ve SKS uyumlu SMS OTP doğrulamalı bilgilendirilmiş onam formu.",
    requiresSignature: true,
    requiresOtp: true,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "OTP_2FA",
    validityDays: 180,
    bodyTemplateMarkdown: `# HASTA BİLGİLENDİRİLMİŞ ONAM VE RIZA FORMU
*(1219 Sayılı Kanun, Hasta Hakları Yönetmeliği m. 24-26, Tıbbi Deontoloji Tüzüğü ve Biyotıp Sözleşmesi Uyarınca)*

**Sağlık Kuruluşu / Klinik:** {{TENANT_LEGAL_NAME}}  
**Hasta Adı Soyadı:** {{CUSTOMER_FULL_NAME}}  
**T.C. Kimlik No:** {{CUSTOMER_MASKED_TCKN}} | **Telefon:** {{CUSTOMER_PHONE}}  
**Uygulanacak İşlem:** {{SERVICE_NAME}}  
**İşlem Tarihi / Saati:** {{APPOINTMENT_DATE_TIME}}

---

### 1. İŞLEMİN AMACI VE GEREKÇESİ
Tarafıma teşhis edilen durum doğrultusunda **{{SERVICE_NAME}}** işleminin uygulanması planlanmıştır. İşlemin tıbbi gerekçesi, yöntemi, beklenen faydaları ve başarı oranları hekimim tarafından tarafıma ayrıntılı olarak izah edilmiştir.

### 2. OLASI RİSKLER VE KOMPLİKASYONLAR
Tıbbi müdahalelerin doğası gereği, en yüksek tıbbi özen ve standartlar uygulansa dahi belirli riskler ve komplikasyonlar gelişebilir:
- **Genel Tıbbi Riskler:** Lokal anesteziye bağlı reaksiyonlar, kanama, işlem bölgesinde hematom, enfeksiyon riski, geçici ağrı ve hassasiyet.
- **İşleme Özgü Riskler:**
{{COMPLICATIONS_LIST}}

### 3. ALTERNATİF TEDAVİ SEÇENEKLERİ VE İŞLEMİ REDDETME HAKKI
Hekimim, durumuma uygun diğer alternatif tedavi yöntemlerini, cerrahi ve medikal seçenekleri, tedaviyi kabul etmemem durumunda karşılaşabileceğim olası sağlık risklerini açıklamıştır. Kararımı hiçbir baskı altında kalmadan, alternatifleri değerlendirerek vermiş bulunmaktayım.

### 4. HASTA SORUMLULUKLARI VE İŞLEM SONRASI TALİMATLAR
1. İşlem öncesinde mevcut tüm kronik hastalıklarımı, kullandığım düzenli ilaçları (kan sulandırıcılar vb.) ve alerjilerimi eksiksiz beyan ettiğimi,
2. İşlem sonrasında hekimimin önerdiği bakım, pansuman, istirahat ve ilaç kullanım protokollerine harfiyen uyacağımı,
3. Beklenmeyen bir reaksiyon durumunda derhal kliniğe başvuracağımı taahhüt ederim.

---

### 5. HASTA ONAM BEYANI
*“Hekimim tarafından planlanan işlem hakkında bilgilendirildim. Olası komplikasyonları, başarı şansını ve alternatifleri anladım. Aklıma gelen tüm soruları sorma ve tatmin edici yanıtlar alma fırsatım oldu. Bu koşullar altında **{{SERVICE_NAME}}** işleminin tarafıma uygulanmasına özgür irademle rıza gösteriyorum.”*

**Hasta Adı Soyadı:** {{CUSTOMER_FULL_NAME}}  
**Tarih:** {{SYSTEM_DATE}} | **Onay Türü:** SMS OTP Doğrulamalı Elektronik İmza`,
  },

  // 5. GÜZELLİK VE ESTETİK İŞLEMİ ALERJİ VE RIZA FORMU (DK Klinik, Dr. Figen Beauty, Gizem Türk, Türk Dermatoloji Derneği)
  ALERJI_VE_ISLEM_RIZA_FORMU: {
    id: "tpl_alerji_islem_riza_v1",
    sectorFamily: "beauty_wellness",
    blueprintType: null,
    docType: "ALERJI_VE_ISLEM_RIZA_FORMU",
    version: 1,
    title: "Güzellik, Medikal Estetik ve Cilt Bakım İşlemi Bilgilendirilmiş Rıza ve Alerji Formu",
    description: "Lazer, cilt yenileme, medikal estetik ve kalıcı makyaj uygulamaları için risk ve alerji kabul onayı.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: 180,
    bodyTemplateMarkdown: `# GÜZELLİK VE ESTETİK İŞLEMLERİ BİLGİLENDİRİLMİŞ RIZA VE ALERJİ FORMU

**Uygulayıcı Merkez:** {{TENANT_NAME}} ({{TENANT_LEGAL_NAME}})  
**Danışan:** {{CUSTOMER_FULL_NAME}} (TCKN: {{CUSTOMER_MASKED_TCKN}})  
**İşlem:** {{SERVICE_NAME}} | **Tarih:** {{APPOINTMENT_DATE_TIME}}

---

### 1. İŞLEMİN NİTELİĞİ VE BEKLENEN ETKİLER
Tarafıma uygulanacak olan **{{SERVICE_NAME}}** uygulamasının mahiyeti, seans süresi ve aşamaları uzman tarafından açıklanmıştır. Uygulamanın kişisel cilt yapısı, metabolizma, hormonal durum ve genetik faktörlere bağlı olarak değişken sonuçlar doğurabileceği tarafıma bildirilmiştir.

### 2. ANAMNEZ VE ALERJİ SORGULAMASI
Danışan olarak aşağıdaki durumlar hakkında doğru beyanda bulunduğumu kabul ederim:
- Hamilelik veya emzirme döneminde olmadığımı,
- Son 6 ay içerisinde Roaccutane / İzotretinoin veya benzeri ağır akne tedavisi almadığımı,
- Lokal anestezik kremlere, metallere, boya pigmentlerine veya kimyasallara karşı bilinen alerjim olmadığını (varsa yazılı bildirdiğimi),
- Keloid / hipertrofik skar (aşırı doku iyileşmesi) eğilimim bulunmadığını beyan ederim.

### 3. İŞLEM SONRASI GEÇİCİ VE OLASI REAKSİYONLAR
{{COMPLICATIONS_LIST}}
- Uygulama bölgesinde hafif eritem (kızarıklık), ödem, geçici hassasiyet ve ince kabuklanma normal iyileşme sürecinin bir parçası olabilir.
- Güneş koruyucu (SPF 50+) kullanma, sıcak su, sauna, hamam ve solaryumdan kaçınma talimatlarına uyacağımı kabul ederim.

### 4. ONAY BEYANI
Uygulama öncesi tüm riskler ve dikkat edilmesi gereken hususlar tarafıma anlatılmıştır. Tarafıma ait fotoğraf ve kayıtların yalnızca medikal dosyamda saklanmasını, şahsıma **{{SERVICE_NAME}}** işleminin yapılmasını kabul ediyorum.

**Danışan:** {{CUSTOMER_FULL_NAME}}  
**İmza:** (Elektronik Kanvas Biyometrik İmza) | **Tarih:** {{SYSTEM_DATE}}`,
  },

  // 6. SPOR, FİTNESS VE MACERA SAĞLIK BEYANI / WAIVER
  SAGLIK_BEYANI_WAIVER: {
    id: "tpl_saglik_beyani_waiver_v1",
    sectorFamily: "sports_fitness",
    blueprintType: null,
    docType: "SAGLIK_BEYANI_WAIVER",
    version: 1,
    title: "Spor ve Fiziksel Aktivite Sağlık Beyanı ve Risk Kabul Feragatnamesi (Waiver)",
    description: "1 yıl geçerli fiziksel aktivite sağlık beyanı ve tesis risk kabul feragatnamesi.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: 365,
    bodyTemplateMarkdown: `# SPOR VE FİZİKSEL AKTİVİTE SAĞLIK BEYANI VE RİSK KABUL FERAGATNAMESİ (WAIVER)

**Tesis / İşletme:** {{TENANT_LEGAL_NAME}}  
**Üye / Katılımcı:** {{CUSTOMER_FULL_NAME}}  
**T.C. Kimlik No:** {{CUSTOMER_MASKED_TCKN}} | **Telefon:** {{CUSTOMER_PHONE}}  
**Aktivite:** {{SERVICE_NAME}} | **Düzenleme Tarihi:** {{SYSTEM_DATE}}

---

### 1. SAĞLIK DURUMU BEYANI
Katılımcı olarak;
1. Tarafıma sunulacak olan **{{SERVICE_NAME}}** antrenman ve aktivitelerine katılmama engel oluşturabilecek kardiyovasküler (kalp-damar), solunumsal, ortopedik veya nörolojik herhangi bir sağlık sorunumun bulunmadığını,
2. Doktor tarafından ağır egzersiz yapmamı kısıtlayan bir teşhis veya uyarı bulunmadığını,
3. Tesis yetkililerine ve eğitmenlerine bildirdiğim sağlık bilgilerinin eksiksiz ve doğru olduğunu beyan ederim.

### 2. RİSKLERİN KABULÜ VE KİŞİSEL SORUMLULUK
- Fiziksel egzersizlerin, spor aletleri kullanımının ve grup derslerinin doğası gereği kas zorlanması, burkulma, düşme veya yaralanma riskleri barındırdığını biliyorum.
- Eğitmenlerin verdiği güvenlik talimatlarına ve tesis kurallarına harfiyen uyacağımı, kendi sınırlarımı aşan ağırlık ve hareketleri denemeyeceğimi kabul ederim.
- Acil tıbbi müdahale gerektiren bir durum gelişmesi halinde, en yakın sağlık kuruluşuna sevk edilmeme ve ilk yardım uygulanmasına muvafakat ederim.

### 3. FERAGAT VE KABUL
Kendi kusurumdan veya sağlık durumumu gizlememden kaynaklanan bedensel ve maddi zararlardan dolayı **{{TENANT_LEGAL_NAME}}** ve personelini sorumlu tutmayacağımı, işbu feragatnamenin imza tarihinden itibaren 1 (bir) yıl süreyle geçerli olacağını beyan ve taahhüt ederim.

**Katılımcı:** {{CUSTOMER_FULL_NAME}}  
**İmza:** (Biyometrik Kanvas İmza)`,
  },

  // 7. ARAC TESLİM-TESELLÜM VE HASAR TUTANAĞI (TMMOB MMO & AvEvrak & Lexpera)
  TESLIM_TESELLUM_HASAR_TUTANAGI: {
    id: "tpl_arac_hasar_tutanak_v1",
    sectorFamily: "automotive",
    blueprintType: null,
    docType: "TESLIM_TESELLUM_HASAR_TUTANAGI",
    version: 1,
    title: "Araç Kabul, Teslim-Tesellüm ve Mevcut Hasar Tespit Tutanağı",
    description: "TMMOB MMO ve Lexpera uyumlu görsel kaporta/cam hasar tespiti ve araç kabul tutanağı.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: null,
    bodyTemplateMarkdown: `# ARAÇ KABUL, TESLİM-TESELLÜM VE MEVCUT HASAR TESPİT TUTANAĞI

**Servis / İşletme:** {{TENANT_LEGAL_NAME}}  
**Müşteri / Araç Sahibi:** {{CUSTOMER_FULL_NAME}} (Tel: {{CUSTOMER_PHONE}})  
**Plaka / Şasi No:** {{VEHICLE_VIN_PLATE}}  
**Talep Edilen İşlem:** {{SERVICE_NAME}}  
**Kabul Tarihi:** {{SYSTEM_DATE}} {{SYSTEM_TIME}}

---

### 1. ARAÇ GİRİŞ BİLGİLERİ VE TESPİTLER
- **Plaka No:** {{VEHICLE_VIN_PLATE}}
- **Giriş Kilometresi:** Belirtilen KM teyit edilmiştir.
- **Mevcut Kaporta ve Cam Durumu:** Aracın servise girişi anında yapılan fiziki kontrolde tespit edilen mevcut çizik, göçük, taş vuruğu, çatlak veya deformasyonlar dijital hasar şeması üzerinde işaretlenmiş ve kayıt altına alınmıştır.

### 2. SERVİS VE ÇALIŞMA ŞARTLARI
1. **Ek Maliyet Onayı:** Bakım ve onarım esnasında tespit edilebilecek ek arıza ve parça değişimleri için müşteri onayı alınmadan ilave maliyetli işlem yapılmayacaktır.
2. **Kıymetli Eşya Sorumluluğu:** Araç içerisinde bırakılan nakit para, mücevher, elektronik cihaz ve benzeri kişisel kıymetli eşyalardan servisimiz sorumlu tutulamaz. Araç teslimi öncesinde şahsi eşyaların tahliye edildiği kabul edilir.
3. **Test Sürüşü Muvafakati:** Arızanın tespiti ve yapılan onarımın kalite kontrolü amacıyla servis teknik personelinin aracı karayolunda test sürüşüne çıkarmasına muvafakat verilmiştir.

### 3. İMZA VE ONAY
Yukarıda bilgileri yer alan aracımı, mevcut fiziki durumu ve işaretlenen hasarları ile birlikte eksiksiz olarak bakım/onarım amacıyla servise teslim ettiğimi kabul ve beyan ederim.

**Teslim Eden (Müşteri):** {{CUSTOMER_FULL_NAME}}  
**Teslim Alan (Servis Yetkilisi):** {{TENANT_NAME}} Yetkili Personeli  
**İmza:** (Dijital Biyometrik Kanvas İmza)`,
  },

  // 8. TAŞINMAZ GÖSTERME BELGESİ (30442 Sayılı Yönetmelik m. 19 - Emlak)
  TASINMAZ_GOSTERME_BELGESI: {
    id: "tpl_tasinmaz_gosterme_v1",
    sectorFamily: "real_estate",
    blueprintType: null,
    docType: "TASINMAZ_GOSTERME_BELGESI",
    version: 1,
    title: "Taşınmaz Gösterme Belgesi (Yönetmelik Madde 19 Uyumlu)",
    description: "30442 Sayılı Taşınmaz Ticareti Yönetmeliği Madde 19 uyarınca GPS damgalı zorunlu yer gösterme sözleşmesi.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: true,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: 180,
    bodyTemplateMarkdown: `# TAŞINMAZ GÖSTERME BELGESİ
*(30442 Sayılı Taşınmaz Ticareti Hakkında Yönetmelik Madde 19 Uyarınca Düzenlenmiştir)*

**Yetkili Emlak İşletmesi:** {{TENANT_LEGAL_NAME}}  
**Yetki Belgesi No / MERSİS:** {{TENANT_MERSIS}}  
**Taşınmaz Gösterilen Müşteri:** {{CUSTOMER_FULL_NAME}}  
**T.C. Kimlik No:** {{CUSTOMER_MASKED_TCKN}} | **Telefon:** {{CUSTOMER_PHONE}}  
**Tarih / Saat:** {{SYSTEM_DATE}} {{SYSTEM_TIME}}

---

### 1. GÖSTERİLEN TAŞINMAZIN BİLGİLERİ
- **Taşınmaz Adresi:** {{PROPERTY_PARCEL_ADDRESS}}
- **İnceleme Amacı:** Satın Alma / Kiralama İncelemesi
- **Doğrulama Yöntemi:** Mobil GPS Koordinat Teyidi ve RFC 3161 Zaman Damgası

### 2. YASAL YÜKÜMLÜLÜKLER VE HİZMET BEDELİ
1. Alıcı/Kiracı adayı olarak; yukarıda açık adresi ve nitelikleri belirtilen taşınmazı **{{TENANT_LEGAL_NAME}}** aracılığıyla bizzat gezdiğimi, incelediğimi ve taşınmaz hakkında bilgilendirildiğimi kabul ederim.
2. İşbu yer gösterme hizmeti için herhangi bir ücret talep edilmemiştir.
3. Gösterilen bu taşınmazı veya bağımsız bölümü; gösterilme tarihinden itibaren 1 (bir) yıl içerisinde şahsım, eşim, 1. derece kan veya kayın hısımlarım, ortağı veya yetkilisi olduğum tüzel kişilikler adına doğrudan mal sahibinden veya başka bir aracı üzerinden satın almam veya kiralamam halinde, Taşınmaz Ticareti Yönetmeliği ve TBK tellallık hükümleri uyarınca hak edilen yasal hizmet bedelini (komisyonu) **{{TENANT_LEGAL_NAME}}**'ne ödemeyi gayrikabili rücu kabul ve taahhüt ederim.

**Taşınmaz Ticareti Yetkilisi:** {{TENANT_NAME}}  
**Müşteri (Alıcı/Kiracı Adayı):** {{CUSTOMER_FULL_NAME}}  
**İmza:** (GPS Doğrulamalı Dijital İmza)`,
  },

  // 9. KONAKLAMA VE OTEL HİZMET SÖZLEŞMESİ (Baia Hotels, Zigana Tatil Köyü, Julian Hotels)
  HIZMET_VE_IPTAL_SOZLESMESI: {
    id: "tpl_konaklama_hizmet_v1",
    sectorFamily: "hospitality",
    blueprintType: null,
    docType: "HIZMET_VE_IPTAL_SOZLESMESI",
    version: 1,
    title: "Otel Konaklama ve Tesis Hizmet Sözleşmesi",
    description: "Baia Hotels ve Zigana Tatil Köyü standartlarında TBK, TKHK ve 1774 KBS uyumlu konaklama sözleşmesi.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: null,
    bodyTemplateMarkdown: `# OTEL VE KONAKLAMA HİZMET SÖZLEŞMESİ

**Tesis / İşletme:** {{TENANT_LEGAL_NAME}}  
**Vergi Kimlik No:** {{TENANT_TAX_ID}} | **Adres:** {{TENANT_ADDRESS}}  
**Misafir Adı Soyadı:** {{CUSTOMER_FULL_NAME}}  
**T.C. Kimlik / Pasaport No:** {{CUSTOMER_MASKED_TCKN}}  
**Telefon:** {{CUSTOMER_PHONE}} | **E-Posta:** {{CUSTOMER_EMAIL}}

---

### 1. REZERVASYON VE KONAKLAMA DETAYLARI
- **Hizmet / Oda Tipi:** {{SERVICE_NAME}}
- **Giriş (Check-in) Tarihi:** {{APPOINTMENT_DATE_TIME}} (Saat: 14:00 itibarıyla)
- **Çıkış (Check-out) Saati:** En geç saat 12:00
- **Toplam Konaklama Bedeli:** {{SERVICE_PRICE}} (Alınan Depozito/Ön Ödeme: {{DEPOSIT_AMOUNT}})

---

### 2. KİMLİK BİLDİRİMİ VE GÜVENLİK (1774 SAYILI KANUN)
1774 sayılı Kimlik Bildirme Kanunu gereğince; tesiste konaklayacak tüm misafirlerin (çocuklar dahil) geçerli kimlik kartı veya pasaport ibraz etmesi yasal zorunluluktur. Kimlik bilgileri Emniyet Genel Müdürlüğü / Jandarma KBS (Kimlik Bildirim Sistemi) sistemine anlık olarak aktarılmaktadır.

---

### 3. İPTAL, NO-SHOW VE ERKEN AYRILIŞ ŞARTLARI
1. **İptal Bildirimi:** Misafir, giriş tarihinden en az **{{CANCELLATION_DEADLINE}}** öncesine kadar rezervasyonunu cezasız iptal edebilir.
2. **Geç İptal / No-Show:** Belirtilen süreden sonra yapılan iptallerde veya tesise giriş yapılmaması (no-show) halinde, oda blokajı sebebiyle **{{PENALTY_RATE}}** oranında kesinti uygulanacaktır.
3. **Erken Ayrılış:** Rezervasyon süresinden önce tesisten ayrılma durumunda ücret iadesi tesisin müsaitlik ve doluluk politikasına tabidir.

---

### 4. TESİS KURALLARI VE KIYMETLİ EŞYA GÜVENLİĞİ
- Misafirler tesis oda ve ortak alan eşyalarını özenle kullanmakla yükümlüdür; meydana gelen hasarlar misafire fatura edilir.
- Türk Borçlar Kanunu Madde 576-581 hükümleri uyarınca; odalarda bırakılan para, mücevher ve değerli eşyaların kaybolmasından tesisimiz sorumlu değildir. Misafirlerin değerli eşyalarını resepsiyondaki emanet kasalarına makbuz karşılığı teslim etmeleri gerekmektedir.

Misafir, sözleşme şartlarını okuduğunu, anladığını ve kabul ettiğini beyan eder.

**İşletme:** {{TENANT_LEGAL_NAME}}  
**Misafir:** {{CUSTOMER_FULL_NAME}} (Elektronik İmza)`,
  },

  // 10. GİZLİLİK VE NDA SÖZLEŞMESİ (Profesyonel Danışmanlık & B2B)
  GIZLILIK_VE_NDA: {
    id: "tpl_gizlilik_nda_v1",
    sectorFamily: "professional",
    blueprintType: null,
    docType: "GIZLILIK_VE_NDA",
    version: 1,
    title: "Karşılıklı Ticari Gizlilik Sözleşmesi (NDA) ve Hizmet SLA Taahhüdü",
    description: "Nitelikli elektronik imza ve 2FA uyumlu kurumsal gizlilik ve ticari sır koruma sözleşmesi.",
    requiresSignature: true,
    requiresOtp: true,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "QUALIFIED_E_SIGN",
    validityDays: 730,
    bodyTemplateMarkdown: `# KARŞILIKLI GİZLİLİK VE TİCARİ SIR SÖZLEŞMESİ (NDA)

**Taraflar:**  
1. **Hizmet Sağlayıcı:** {{TENANT_LEGAL_NAME}} (MERSİS: {{TENANT_MERSIS}}, VKN: {{TENANT_TAX_ID}})  
2. **Müşteri / Danışan:** {{CUSTOMER_FULL_NAME}} (TCKN: {{CUSTOMER_MASKED_TCKN}}, Tel: {{CUSTOMER_PHONE}})  
**Hizmet Konusu:** {{SERVICE_NAME}} | **Tarih:** {{SYSTEM_DATE}}

---

### 1. GİZLİ BİLGİNİN TANIMI VE KAPSAMI
İşbu Sözleşme kapsamında "Gizli Bilgi"; taraflardan birinin diğerine yazılı, sözlü veya elektronik ortamda açıkladığı tüm ticari, mali, hukuki, teknik, operasyonel bilgiler, müşteri portföyleri, yazılım kodları, algoritmalar ve fikri mülkiyete konu verileri ifade eder.

### 2. GİZLİLİK YÜKÜMLÜLÜKLERİ
Taraflar;
1. Gizli Bilgileri yalnızca **{{SERVICE_NAME}}** danışmanlık hizmetinin ifası amacıyla kullanmayı,
2. Karşı tarafın yazılı izni olmaksızın Gizli Bilgileri üçüncü kişilere, kurumlara veya kamuoyuna açıklamamayı,
3. Bilgilerin yetkisiz erişime karşı korunması için en yüksek düzeyde siber güvenlik ve idari tedbirleri almayı taahhüt ederler.

### 3. CEZAİ ŞART VE TAZMİNAT
Gizlilik yükümlülüğünün ihlali halinde; ihlal eden taraf karşı tarafın uğradığı tüm doğrudan ve dolaylı zararları tazmin etmekle yükümlüdür. İşbu sözleşme ticari ilişkinin sona ermesinden itibaren 2 (iki) yıl süreyle yürürlükte kalacaktır.

**Hizmet Sağlayıcı:** {{TENANT_LEGAL_NAME}}  
**Müşteri:** {{CUSTOMER_FULL_NAME}} (E-İmza / 2FA Doğrulamalı)`,
  },

  // 11. ÖN BİLGİLENDİRME FORMU (6502 SKHK & Türk Gıda Kodeksi)
  ON_BILGILENDIRME_FORMU: {
    id: "tpl_on_bilgilendirme_v1",
    sectorFamily: "restaurant_food",
    blueprintType: null,
    docType: "ON_BILGILENDIRME_FORMU",
    version: 1,
    title: "Mesafeli Sözleşmeler Ön Bilgilendirme ve Tüketici Hakları Formu",
    description: "6502 sayılı TKHK uyarınca rezervasyon öncesi zorunlu tüketici ön bilgilendirmesi.",
    requiresSignature: false,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "SIMPLE_BIOMETRIC",
    validityDays: null,
    bodyTemplateMarkdown: `# MESAFELİ HİZMET SÖZLEŞMESİ ÖN BİLGİLENDİRME FORMU

**Sağlayıcı:** {{TENANT_LEGAL_NAME}} | **VKN:** {{TENANT_TAX_ID}}  
**Adres:** {{TENANT_ADDRESS}} | **Telefon:** {{TENANT_PHONE}}  
**Tüketici:** {{CUSTOMER_FULL_NAME}} ({{CUSTOMER_PHONE}})

1. **Hizmet Bilgisi:** {{SERVICE_NAME}}
2. **Toplam Fiyat (Vergiler Dahil):** {{SERVICE_PRICE}}
3. **Depozito / Rezervasyon Güvence Bedeli:** {{DEPOSIT_AMOUNT}}
4. **İptal ve Değişiklik:** Randevu saatinden **{{CANCELLATION_DEADLINE}}** öncesine kadar bildirim yapıldığında depozito iade edilir; aksi halde **{{PENALTY_RATE}}** oranında kesinti uygulanır.
5. **Şikayet ve İtiraz:** Tüketiciler şikayetlerini Tüketici Hakem Heyetleri ve Tüketici Mahkemelerine iletebilirler.`,
  },

  // 12. ÖZEL NİTELİKLİ SAĞLIK VERİSİ RIZASI (Alias)
  OZEL_NITELIKLI_SAGLIK_VERISI_RIZASI: {
    id: "tpl_ozel_saglik_verisi_v1",
    sectorFamily: "health_clinical",
    blueprintType: null,
    docType: "OZEL_NITELIKLI_SAGLIK_VERISI_RIZASI",
    version: 1,
    title: "Sağlık ve Klinik Verilerinin İşlenmesi Açık Rıza Formu",
    description: "Klinik anamnez ve tedavi kayıtları için KVKK Madde 6 uyumlu açık rıza.",
    requiresSignature: true,
    requiresOtp: true,
    requiresGps: false,
    isRequired: false,
    isActive: true,
    signatureLevel: "OTP_2FA",
    validityDays: 365,
    bodyTemplateMarkdown: `# ÖZEL NİTELİKLİ SAĞLIK VERİLERİNİN İŞLENMESİNE İLİŞKİN AÇIK RIZA FORMU

**Klinik:** {{TENANT_LEGAL_NAME}}  
**Hasta:** {{CUSTOMER_FULL_NAME}} (TCKN: {{CUSTOMER_MASKED_TCKN}})

Tıbbi muayene, tetkik ve **{{SERVICE_NAME}}** tedavim kapsamında elde edilen kan tahlili, radyoloji, fotoğraf ve klinik gözlem verilerimin Sağlık Bakanlığı mevzuatı ve KVKK m. 6 uyarınca hasta takip dosyamda saklanmasına açık rıza gösteriyorum.`,
  },

  // 13. SAĞLIK BEYANI VE SORUMLULUK REDDİ (Alias)
  SAGLIK_BEYANI_VE_SORUMLULUK_REDDI: {
    id: "tpl_saglik_sorumluluk_reddi_v1",
    sectorFamily: "sports_fitness",
    blueprintType: null,
    docType: "SAGLIK_BEYANI_VE_SORUMLULUK_REDDI",
    version: 1,
    title: "Katılımcı Sağlık Beyanı ve Sorumluluk Reddi Protokolü",
    description: "Spor salonu ve rekreasyon aktiviteleri için feragatname.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: 365,
    bodyTemplateMarkdown: `# KATILIMCI SAĞLIK BEYANI VE SORUMLULUK REDDİ

**İşletme:** {{TENANT_NAME}}  
**Katılımcı:** {{CUSTOMER_FULL_NAME}}  
**Aktivite:** {{SERVICE_NAME}}

Egzersiz yapmama engel fiziki veya kardiyak bir rahatsızlığım olmadığını, kendi kusurumdan doğan yaralanmalardan şahsımın sorumlu olduğunu taahhüt ederim.`,
  },

  // 14. ARAÇ TESLİM-TESELLÜM VE HASAR TUTANAĞI (Alias)
  ARAC_TESLIM_TESELLUM_HASAR_TUTANAGI: {
    id: "tpl_arac_hasar_alias_v1",
    sectorFamily: "automotive",
    blueprintType: null,
    docType: "ARAC_TESLIM_TESELLUM_HASAR_TUTANAGI",
    version: 1,
    title: "Oto Servis Giriş Hasar Tespit ve Teslim Tutanağı",
    description: "Araç kaporta ve mekanik kabul tutanağı.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: null,
    bodyTemplateMarkdown: `# ARAÇ TESLİM VE MEVCUT HASAR TUTANAĞI

**Servis:** {{TENANT_LEGAL_NAME}}  
**Araç Sahibi:** {{CUSTOMER_FULL_NAME}} | **Plaka:** {{VEHICLE_VIN_PLATE}}  
**İşlem:** {{SERVICE_NAME}}

Aracın servise teslimi sırasında mevcut kaporta çizik ve göçükleri kayıt altına alınmış olup, araç içinde kıymetli eşya bırakılmadığı onaylanmıştır.`,
  },

  // 15. İŞ EMRİ VE TESLİMAT ONAYI
  IS_EMRI_VE_TESLIMAT_ONAYI: {
    id: "tpl_is_emri_teslimat_v1",
    sectorFamily: "automotive",
    blueprintType: null,
    docType: "IS_EMRI_VE_TESLIMAT_ONAYI",
    version: 1,
    title: "Servis İş Emri Kapanışı ve Araç Eksiksiz Teslimat Onay Belgesi",
    description: "Bakım ve onarımı tamamlanan aracın test edilmiş olarak eksiksiz teslim alındığı beyanı.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: null,
    bodyTemplateMarkdown: `# SERVİS İŞ EMRİ KAPANIŞI VE ARAÇ EKSİKSİZ TESLİMAT ONAYI

**Servis:** {{TENANT_LEGAL_NAME}}  
**Müşteri:** {{CUSTOMER_FULL_NAME}}  
**Plaka:** {{VEHICLE_VIN_PLATE}}  
**Yapılan İşlem:** {{SERVICE_NAME}}

Yukarıda plakası belirtilen aracımın iş emrinde talep ettiğim tüm bakım ve onarımlarının eksiksiz yapıldığını, parça değişimlerinin ve yol testinin gerçekleştirildiğini, aracımı tam ve kusursuz olarak teslim aldığımı beyan ederim.

**Müşteri:** {{CUSTOMER_FULL_NAME}}  
**İmza:** (Çıkış İmzası) | **Tarih:** {{SYSTEM_DATE}}`,
  },

  // 16. NO-SHOW VE İPTAL SÖZLEŞMESİ
  NO_SHOW_VE_IPTAL_SOZLESMESI: {
    id: "tpl_no_show_iptal_v1",
    sectorFamily: "restaurant_food",
    blueprintType: null,
    docType: "NO_SHOW_VE_IPTAL_SOZLESMESI",
    version: 1,
    title: "Rezervasyon Güvence, Depozito ve No-Show Kesinti Sözleşmesi",
    description: "Restoran, VIP salon ve özel rezervasyonlar için TBK m. 20-25 uyumlu no-show sözleşmesi.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: null,
    bodyTemplateMarkdown: `# REZERVASYON GÜVENCE VE NO-SHOW SÖZLEŞMESİ

**İşletme:** {{TENANT_LEGAL_NAME}}  
**Müşteri:** {{CUSTOMER_FULL_NAME}} (Tel: {{CUSTOMER_PHONE}})  
**Rezervasyon:** {{SERVICE_NAME}} | {{APPOINTMENT_DATE_TIME}}

Masa ve personel tahsisi yapıldığından; randevu saatinden **{{CANCELLATION_DEADLINE}}** öncesine kadar bildirilmeyen iptallerde veya randevuya gelinmemesi halinde alınan **{{DEPOSIT_AMOUNT}}** tutarındaki depozito no-show tazminatı olarak irat kaydedilir.`,
  },

  // 17. ALERJEN BİLDİRİM ONAYI (Türk Gıda Kodeksi)
  ALERJEN_BILDIRIM_ONAYI: {
    id: "tpl_alerjen_bildirim_v1",
    sectorFamily: "restaurant_food",
    blueprintType: null,
    docType: "ALERJEN_BILDIRIM_ONAYI",
    version: 1,
    title: "Türk Gıda Kodeksi Kapsamında Alerjen Bildirim ve Tüketici Beyan Formu",
    description: "14 temel gıda alerjeni bilgilendirmesi ve misafir alerji bildirim taahhüdü.",
    requiresSignature: false,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "SIMPLE_BIOMETRIC",
    validityDays: null,
    bodyTemplateMarkdown: `# TÜRK GIDA KODEKSİ 14 TEMEL ALERJEN BİLDİRİM VE BEYAN FORMU

**İşletme:** {{TENANT_NAME}}  
**Misafir:** {{CUSTOMER_FULL_NAME}}

Türk Gıda Kodeksi Etiketleme ve Tüketicileri Bilgilendirme Yönetmeliği uyarınca; ürünlerimizde gluten, kabuklular, yumurta, balık, yer fıstığı, soya, süt/laktoz, sert kabuklu meyveler, kereviz, hardal, susam, sülfitler, acı bakla ve yumuşakçalar bulunabilir. Şahsınızda veya beraberinizdekilerde gıda alerjisi varsa sipariş öncesinde personele bildirmeniz zorunludur.`,
  },

  // 18. EĞİTİM SÖZLEŞMESİ
  EGITIM_SOZLESMESI: {
    id: "tpl_egitim_sozlesmesi_v1",
    sectorFamily: "education",
    blueprintType: null,
    docType: "EGITIM_SOZLESMESI",
    version: 1,
    title: "Özel Kurs ve Eğitim Hizmet Sözleşmesi",
    description: "Kurs, seminer ve atölye katılım koşulları, devam zorunluluğu ve ödeme sözleşmesi.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: 365,
    bodyTemplateMarkdown: `# ÖZEL KURS VE EĞİTİM HİZMET SÖZLEŞMESİ

**Kurum:** {{TENANT_LEGAL_NAME}}  
**Kursiyer / Katılımcı:** {{CUSTOMER_FULL_NAME}} (TCKN: {{CUSTOMER_MASKED_TCKN}})  
**Eğitim:** {{SERVICE_NAME}} | **Tarih:** {{APPOINTMENT_DATE_TIME}}  
**Eğitim Bedeli:** {{SERVICE_PRICE}}

Kursiyer, ders programına ve kurum disiplin kurallarına uyacağını, eğitim materyallerinin telif haklarını ihlal etmeyeceğini ve iptal/iade şartlarının MEB ve mesafeli satış mevzuatına tabi olduğunu kabul eder.`,
  },

  // 19. NDA VE DANIŞMANLIK SLA (Alias)
  NDA_VE_DANISMANLIK_SLA: {
    id: "tpl_nda_sla_v1",
    sectorFamily: "professional",
    blueprintType: null,
    docType: "NDA_VE_DANISMANLIK_SLA",
    version: 1,
    title: "Kurumsal Danışmanlık Hizmet Düzeyi (SLA) ve Gizlilik Anlaşması",
    description: "Profesyonel danışmanlık hizmet kalitesi ve gizlilik taahhüdü.",
    requiresSignature: true,
    requiresOtp: true,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "QUALIFIED_E_SIGN",
    validityDays: 730,
    bodyTemplateMarkdown: `# KURUMSAL DANIŞMANLIK HİZMET DÜZEYİ (SLA) VE GİZLİLİK ANLAŞMASI

**Danışman:** {{TENANT_LEGAL_NAME}}  
**Müşteri:** {{CUSTOMER_FULL_NAME}}  
**Hizmet:** {{SERVICE_NAME}}

Taraflar, danışmanlık sürecinde paylaşılan her türlü teknik ve ticari bilginin gizli tutulacağını, belirlenen teslim sürelerine ve kalite standartlarına riayet edileceğini taahhüt ederler.`,
  },
};
