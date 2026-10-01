import { LegalTemplateDto, SectorFamily } from "@shared/legalTypes";

/**
 * BooKi SaaS - Sektörel Yasal Onam Formları ve Sözleşme Şablonları Havuzu
 *
 * İşbu katalog, kullanıcının talep ettiği 20 kurumsal ve resmi kaynak baz alınarak hazırlanmıştır:
 *
 * SAĞLIK & CERRAHİ & DİŞ & TIP:
 * - Türk Oftalmoloji Derneği (TOD) (todnet.org)
 * - İstanbul Dişhekimleri Odası (İDO) (ido.org.tr)
 * - Türk Dermatoloji Derneği (turkdermatoloji.org.tr)
 * - DK Klinik Dermatoloji & Lazer (dk-klinik.com.tr)
 * - Dr. Figen Beauty Medikal Estetik (drfigenbeauty.com)
 * - Mersin Sistem Cerrahi Tıp Merkezi (mersinsistem.com)
 * - Koru Hastanesi Kalite Standartları (kalite.koruhastanesi.com)
 * - Dr. Ahmet Aşçıoğlu Ortopedi (ahmetascioglu.com)
 * - Dr. Ahmet Çaymaz Genel Cerrahi (drahmetcaymaz.com)
 * - Türk Yoğun Bakım Derneği (akademi.yogunbakim.org.tr)
 * - Gizem Türk Güzellik & Kalıcı Makyaj (gizemturk.net)
 *
 * TURİZM & OTELCİLİK & KONAKLAMA:
 * - Julian Hotels KVKK & Açık Rıza (julianhotels.com)
 * - Baia Hotels Konaklama Hizmet Sözleşmesi (baiahotels.com)
 * - Zigana Tatil Köyü Konaklama Sözleşmesi (ziganatatilkoyu.com)
 * - TÜRSAB Güvenlik El Kitabı & Taşımacılık (tursab.org.tr)
 * - Türkiye Turizm Ansiklopedisi Acenta-Konaklama Sözleşmeleri
 * - Prokalite Anne Misafirhanesi & Refakatçi Konaklama (prokalite.com)
 *
 * OTOMOTİV BAKIM & ONARIM:
 * - AvEvrak Araç Bakım ve Onarım Sözleşmesi (avevrak.com)
 * - Lexpera Araç Yetkili Servis Sözleşmesi (lexpera.com.tr)
 * - TMMOB Makina Mühendisleri Odası Araç Kontrol Tutanakları (mmo.org.tr)
 */

export interface SectorOnamEntry extends LegalTemplateDto {
  category: "saglik_cerrahi" | "dis_hekimligi" | "dermatoloji_estetik" | "konaklama_turizm" | "otomotiv_servis" | "genel";
  sourceReference: string;
  legalBasis: string;
}

export const SECTOR_ONAM_CATALOG: Record<string, SectorOnamEntry> = {
  // =========================================================================
  // 1. TÜRK OFTALMOLOJİ DERNEĞİ (TOD) - GÖZ HASTALIKLARI VE CERRAHİSİ
  // =========================================================================
  TOD_KATARAKT_FAKO_ONAM: {
    id: "TOD_KATARAKT_FAKO_ONAM",
    sectorFamily: "health_clinical",
    blueprintType: "eye_clinic",
    docType: "AYDINLATILMIS_ONAM_FORMU",
    category: "saglik_cerrahi",
    version: 1,
    title: "TOD Uyumlu Katarakt Cerrahisi (Fakoemülsifikasyon) ve Göz İçi Lens İmplantasyonu Onam Formu",
    sourceReference: "Türk Oftalmoloji Derneği (TOD) Standart Onam Formları (todnet.org)",
    legalBasis: "1219 Sayılı Tababet Kanunu, Hasta Hakları Yönetmeliği m. 24-26, Biyotıp Sözleşmesi m. 5",
    description: "Katarakt ameliyatı, fako yöntemi, trifokal/monofokal mercek yerleştirilmesi ve olası risklere ilişkin aydınlatılmış onam.",
    requiresSignature: true,
    requiresOtp: true,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "OTP_2FA",
    validityDays: 90,
    bodyTemplateMarkdown: `# KATARAKT CERRAHİSİ (FAKOEMÜLSİFİKASYON) VE GÖZ İÇİ LENS İMPLANTASYONU HASTA BİLGİLENDİRİLMİŞ ONAM FORMU
*(Türk Oftalmoloji Derneği - TOD Standart Formu Esas Alınmıştır)*

**Sağlık Kuruluşu:** {{TENANT_LEGAL_NAME}}  
**Hasta Adı Soyadı:** {{CUSTOMER_FULL_NAME}} (TCKN: {{CUSTOMER_MASKED_TCKN}})  
**Uygulayıcı Hekim:** {{TENANT_NAME}} Göz Hastalıkları ve Cerrahisi Uzmanı  
**Planlanan İşlem:** {{SERVICE_NAME}}  
**Tarih / Saat:** {{APPOINTMENT_DATE_TIME}}

---

### 1. HASTALIĞIN VE PLANLANAN GİRİŞİMİN TANIMI
Katarakt; gözümüzün içinde bulunan doğal merceğin saydamlığını kaybederek bulanıklaşması durumudur. Kataraktın tek kesin tedavisi cerrahidir. Ameliyatta modern **Fakoemülsifikasyon (ultrases)** yöntemi ile bulanıklaşan mercek parçalanarak temizlenecek ve yerine ömür boyu kalıcı yapay bir göz içi lensi yerleştirilecektir.

### 2. İŞLEMİN AŞAMALARI VE ANESTEZİ
İşlem genellikle damla anestezisi (topikal) veya lokal enjeksiyon ile ağrısız olarak gerçekleştirilir. Ameliyat süresi ortalama 15-30 dakikadır. Ameliyat sırasında hekiminizin ve cerrahi mikroskobun ışığına bakmanız ve başınızı oynatmamanız gerekmektedir.

### 3. AMELİYATIN OLASI RİSK VE KOMPLİKASYONLARI
Tüm cerrahi müdahalelerde olduğu gibi, en ileri teknoloji ve uzmanlıkta dahi bazı komplikasyon riskleri mevcuttur:
- **Ameliyat Sırasındaki Riskler:** Arka kapsül açılması, merceğin göz arkasına (vitreusa) düşmesi, göz içi kanama, iris hasarı. Bu durumlarda ek cerrahi girişimler (vitrektomi) gerekebilir.
- **Ameliyat Sonrası Riskler:** 
  1. Göz içi enfeksiyonu (Endoftalmi - nadir fakat görmeyi ciddi tehdit eden acil tablo),
  2. Kornea ödemi, geçici veya kalıcı görme bulanıklığı,
  3. Göz tansiyonu (glokom) yükselmesi, retina dekolmanı, makula ödemi,
  4. Ameliyattan aylar/yıllar sonra arka kapsülde kesifleşme gelişmesi (YAG Lazer ile açılabilir).
{{COMPLICATIONS_LIST}}

### 4. ALTERNATİF TEDAVİLER VE REDDETME HAKKI
Kataraktın ilaçla, damlayla veya gözlükle geriletilmesi mümkün değildir. Ameliyatı reddetmeniz halinde katarakt ilerleyerek görme kaybına, göz içi tansiyonunun yükselmesine ve ameliyatın ileride teknik olarak daha zor ve riskli hale gelmesine yol açabilir.

### 5. HASTA BEYANI VE KABUL
Hekimim kataraktımın derecesini, cerrahi yöntemi, başarı şansını, alternatif mercek seçeneklerini ve riskleri bana anlayabileceğim bir dilde açıkladı. Aklıma gelen tüm soruları sordum. Hekimimin talimatlarına uyacağımı, ameliyat sonrası koruyucu damlalarımı düzenli kullanacağımı ve kontrol muayenelerine geleceğimi taahhüt ederim.

**Hasta / Yasal Temsilci:** {{CUSTOMER_FULL_NAME}}  
**Tarih:** {{SYSTEM_DATE}} | **Onay:** SMS OTP Doğrulamalı Dijital İmza`,
  },

  TOD_REFRAKTIF_LAZER_ONAM: {
    id: "TOD_REFRAKTIF_LAZER_ONAM",
    sectorFamily: "health_clinical",
    blueprintType: "eye_clinic",
    docType: "AYDINLATILMIS_ONAM_FORMU",
    category: "saglik_cerrahi",
    version: 1,
    title: "TOD Uyumlu Eksimer Lazer (LASIK / PRK / No-Touch) Refraktif Cerrahi Onam Formu",
    sourceReference: "Türk Oftalmoloji Derneği (TOD) Refraktif Cerrahi Birimi",
    legalBasis: "1219 Sayılı Tababet Kanunu, Hasta Hakları Yönetmeliği m. 24",
    description: "Miyopi, hipermetropi ve astigmatizma tedavisinde uygulanan korneal lazer cerrahisi bilgilendirilmiş onamı.",
    requiresSignature: true,
    requiresOtp: true,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "OTP_2FA",
    validityDays: 90,
    bodyTemplateMarkdown: `# EKSİMER LAZER REFRAKTİF CERRAHİ (LASIK / PRK / NO-TOUCH) HASTA ONAM FORMU
*(Türk Oftalmoloji Derneği - TOD Kılavuzlarına Uygun)*

**Sağlık Kuruluşu:** {{TENANT_LEGAL_NAME}}  
**Hasta Adı:** {{CUSTOMER_FULL_NAME}} (TCKN: {{CUSTOMER_MASKED_TCKN}})  
**İşlem:** {{SERVICE_NAME}}  
**Tarih:** {{APPOINTMENT_DATE_TIME}}

---

### 1. İŞLEMİN AMACI VE YÖNTEMİ
Refraktif lazer cerrahisi; gözün kırma kusurlarını (miyop, hipermetrop, astigmat) kornea tabakasının şeklini lazer ışınları ile mikron düzeyinde değiştirerek düzeltmeyi ve gözlük/kontakt lens bağımlılığını azaltmayı amaçlar.

### 2. İŞLEMİN OLASI RİSK VE ETKİLERİ
- Geçici batma, sulanma, ışık hassasiyeti ve kuruluk,
- Gece görüşünde hareler (halo), parlama veya kontrast kaybı,
- Hedeflenen numaranın tam sıfırlanamaması (az veya aşırı düzeltme - under/overcorrection),
- Flep komplikasyonları (LASIK yönteminde), korneada enfeksiyon (keratit), ektazi riski.
{{COMPLICATIONS_LIST}}

### 3. HASTA ONAYI
Gözlerimin kornea topoğrafisi, kalınlığı ve retina muayenesi yapılarak lazer işlemine uygun bulunduğumu, işlemin risklerini ve beklentilerimi hekimimle paylaştığımı beyan ederim.

**Hasta:** {{CUSTOMER_FULL_NAME}}  
**Onay Tarihi:** {{SYSTEM_DATE}}`,
  },

  // =========================================================================
  // 2. İSTANBUL DİŞHEKİMLERİ ODASI (İDO) - DİŞ HEKİMLİĞİ VE CERRAHİSİ
  // =========================================================================
  IDO_DENTAL_IMPLANT_ONAM: {
    id: "IDO_DENTAL_IMPLANT_ONAM",
    sectorFamily: "health_clinical",
    blueprintType: "dental_clinic",
    docType: "AYDINLATILMIS_ONAM_FORMU",
    category: "dis_hekimligi",
    version: 1,
    title: "İDO Uyumlu Dental İmplant Uygulaması ve Kemik Grefti Bilgilendirilmiş Onam Formu",
    sourceReference: "İstanbul Dişhekimleri Odası (İDO) Hasta Onam Formları (ido.org.tr)",
    legalBasis: "1219 Sayılı Kanun, 3224 Sayılı Türk Dişhekimleri Birliği Kanunu, Hasta Hakları Yönetmeliği",
    description: "Diş eksikliklerinin giderilmesinde uygulanan titanyum dental implant cerrahisi ve kemik tozu işlemleri onamı.",
    requiresSignature: true,
    requiresOtp: true,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "OTP_2FA",
    validityDays: 180,
    bodyTemplateMarkdown: `# DENTAL İMPLANT VE KEMİK REJENERASYONU HASTA BİLGİLENDİRİLMİŞ ONAM FORMU
*(İstanbul Dişhekimleri Odası - İDO Standartlarına Uygun Olarak Hazırlanmıştır)*

**Klinik / Hekim:** {{TENANT_LEGAL_NAME}}  
**Hasta Adı Soyadı:** {{CUSTOMER_FULL_NAME}} (TCKN: {{CUSTOMER_MASKED_TCKN}})  
**Tedavi:** {{SERVICE_NAME}}  
**Randevu Zamanı:** {{APPOINTMENT_DATE_TIME}}

---

### 1. TEDAVİNİN TANIMI VE GEREKÇESİ
Dental implant; eksik dişlerin yerine çene kemiğine yerleştirilen, dokuyla biyouyumlu titanyum vidalardır. Bu vidalar kemikle kaynaştıktan (osteointegrasyon, genellikle 2-6 ay) sonra üzerine sabit veya hareketli protez diş yapılacaktır. Gerekli görüldüğünde kemik hacmini artırmak için kemik tozu (greft) ve membran uygulaması yapılacaktır.

### 2. ANESTEZİ VE CERRAHİ SÜREÇ
İşlem lokal anestezi altında steril cerrahi ortamda gerçekleştirilir. Çene kemiğine yuva açılarak implant yerleştirilir ve diş eti dikişle kapatılır. Dikişler 7-10 gün sonra alınır.

### 3. RİSKLER VE OLASI KOMPLİKASYONLAR
- **Ameliyat Sırası ve Sonrası:** Ağrı, şişlik, morarma (hematom), geçici çene kısıtlılığı,
- **Sinir Hasarı:** Alt çenede işlem yapılıyorsa mandibular sinirin etkilenmesine bağlı alt dudak ve çenede geçici veya nadiren kalıcı uyuşukluk/hissizlik (parestezi),
- **Sinüs Açılması:** Üst arka bölgede sinüs boşluğuna penetrasyon veya sinüs enfeksiyonu,
- **İmplant Kaybı (Erken/Geç Başarısızlık):** İmplantın kemikle kaynaşamaması, enfeksiyon (peri-implantitis) veya aşırı sigara kullanımına bağlı implantın gevşeyerek düşmesi.
{{COMPLICATIONS_LIST}}

### 4. HASTA YÜKÜMLÜLÜKLERİ
1. Diyabet, osteoporoz, bifosfonat kullanımı, kalp rahatsızlığı veya kan sulandırıcı ilaçlarımı eksiksiz hekime bildirdim.
2. İmplant başarısını doğrudan olumsuz etkileyen sigara kullanımını hekimin talimatı doğrultusunda keseceğimi veya azaltacağımı taahhüt ederim.
3. Ağız hijyenime azami özen göstereceğimi ve 6 aylık periyodik kontrollere geleceğimi kabul ederim.

**Hasta:** {{CUSTOMER_FULL_NAME}}  
**İmza:** SMS OTP Doğrulamalı Onay | **Tarih:** {{SYSTEM_DATE}}`,
  },

  IDO_KANAL_TEDAVISI_ONAM: {
    id: "IDO_KANAL_TEDAVISI_ONAM",
    sectorFamily: "health_clinical",
    blueprintType: "dental_clinic",
    docType: "AYDINLATILMIS_ONAM_FORMU",
    category: "dis_hekimligi",
    version: 1,
    title: "İDO Uyumlu Kök Kanal Tedavisi (Endodonti) Bilgilendirilmiş Onam Formu",
    sourceReference: "İstanbul Dişhekimleri Odası (İDO) Endodonti Onam Formu",
    legalBasis: "Hasta Hakları Yönetmeliği, Türk Dişhekimleri Birliği Etik Kuralları",
    description: "Diş pulpası iltihap ve nekrozlarında uygulanan kök kanal tedavisi risk ve başarı koşulları onamı.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: 180,
    bodyTemplateMarkdown: `# KÖK KANAL TEDAVİSİ (ENDODONTİ) BİLGİLENDİRİLMİŞ ONAM FORMU
*(İstanbul Dişhekimleri Odası Standart Formu)*

**Hekim / Sağlık Merkezi:** {{TENANT_LEGAL_NAME}}  
**Hasta:** {{CUSTOMER_FULL_NAME}} (Tel: {{CUSTOMER_PHONE}})  
**Tedavi:** {{SERVICE_NAME}}

### 1. TEDAVİNİN AMACI
Derin çürük, travma veya iltihap nedeniyle canlılığını kaybeden diş pulpası temizlenerek, kök kanalları şekillendirilir, dezenfekte edilir ve özel dolgu maddeleri ile doldurulur. Amaç dişin çekilmesini önleyip ağızda tutmaktır.

### 2. OLASI RİSK VE DURUMLAR
- Tedavi sırasında veya sonrasında birkaç gün sürebilen çiğneme hassasiyeti ve ağrı,
- Kök kanallarının aşırı eğri veya kalsifiye (tıkalı) olması durumunda kanal aletinin kırılması,
- İnatçı enfeksiyonlarda periapikal cerrahi (kök ucu rezeksiyonu) veya dişin çekiminin gerekebilmesi,
- Kanal tedavili dişlerin kırılganlaşması sebebiyle kaplama/kron yapılması zorunluluğu.
{{COMPLICATIONS_LIST}}

**Hasta:** {{CUSTOMER_FULL_NAME}}  
**İmza:** (Biyometrik Kanvas İmza) | **Tarih:** {{SYSTEM_DATE}}`,
  },

  // =========================================================================
  // 3. DERMATOLOJİ VE MEDİKAL ESTETİK (TÜRK DERMATOLOJİ DERNEĞİ & DK KLİNİK & DR. FİGEN BEAUTY)
  // =========================================================================
  DERMATOLOJI_LAZER_CILT_YENILEME: {
    id: "DERMATOLOJI_LAZER_CILT_YENILEME",
    sectorFamily: "beauty_wellness",
    blueprintType: "dermatology",
    docType: "AYDINLATILMIS_ONAM_FORMU",
    category: "dermatoloji_estetik",
    version: 1,
    title: "Türk Dermatoloji Derneği Uyumlu Nonablatif Lazerle Cilt Yenileme Bilgilendirilmiş Onam Formu",
    sourceReference: "Türk Dermatoloji Derneği (turkdermatoloji.org.tr) Resmi Kılavuzu & DK Klinik (dk-klinik.com.tr)",
    legalBasis: "Hasta Hakları Yönetmeliği m. 24-26, Tababet ve Şuabatı Kanunu",
    description: "Cilt gençleştirme, akne izi, gözenek ve leke tedavisinde fraksiyonel ve nonablatif lazer onam formu.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: 180,
    bodyTemplateMarkdown: `# NONABLATİF LAZERLE CİLT YENİLEME VE GENÇLEŞTİRME BİLGİLENDİRİLMİŞ ONAM FORMU
*(Türk Dermatoloji Derneği Resmi Onam Şablonu Esas Alınmıştır)*

**Uygulayıcı Merkez:** {{TENANT_LEGAL_NAME}}  
**Hasta / Danışan:** {{CUSTOMER_FULL_NAME}} (TCKN: {{CUSTOMER_MASKED_TCKN}})  
**İşlem:** {{SERVICE_NAME}}  
**Tarih:** {{APPOINTMENT_DATE_TIME}}

---

### 1. İŞLEMİN MAHİYETİ VE ETKİ MEKANİZMASI
Nonablatif lazerler; cildin en üst katmanı olan epidermisi soymadan ve bütünlüğünü bozmadan, alt katman olan dermiste kontrollü ısı hasarı yaratarak kolajen ve elastin sentezini uyarır. Cilt tonu, ince kırışıklıklar, gözenek genişliği ve akne izlerinin giderilmesi amacıyla uygulanır.

### 2. İŞLEM ÖNCESİ SORGULAMA (KONTRENDİKASYONLAR)
Danışan olarak;
- Son 6 ay içerisinde sistemik izotretinoin (Roaccutane vb.) kullanmadığımı,
- Son 1 ay içerisinde aktif güneşe maruz kalmadığımı, solaryuma girmediğimi,
- Tedavi alanında aktif uçuk (herpes simplex), açık yara veya cilt enfeksiyonu bulunmadığını,
- Hamile veya emziren anne olmadığımı beyan ederim.

### 3. BEKLENEN ETKİLER VE OLASI KOMPLİKASYONLAR
Lazer uygulaması sonrasında aşağıdaki etkiler gelişebilir:
- **Normal Karşılanan Etkiler:** Uygulama bölgesinde 2-48 saat sürebilen kızarıklık (eritem), hafif ödem ve güneş yanığı hissi,
- **Olası Riskler:** Geçici veya nadiren kalıcı lekelenme (hiperpigmentasyon veya hipopigmentasyon), herpes reaktivasyonu, çok nadiren yüzeyel kabuklanma ve kılcal damar çatlaması.
{{COMPLICATIONS_LIST}}

### 4. İŞLEM SONRASI BAKIM KURALLARI
1. En az 4 hafta boyunca yüksek faktörlü geniş spektrumlu (UVA/UVB) SPF 50+ güneş koruyucu krem kullanılacaktır.
2. İşlem sonrası ilk 24 saat bölge sıcak suyla yıkanmayacak, ovulmayacak, kese/peeling yapılmayacaktır.

**Danışan / Hasta:** {{CUSTOMER_FULL_NAME}}  
**İmza:** (Biyometrik Dijital İmza) | **Tarih:** {{SYSTEM_DATE}}`,
  },

  ESTETIK_BOTOKS_DOLGU_ONAM: {
    id: "ESTETIK_BOTOKS_DOLGU_ONAM",
    sectorFamily: "beauty_wellness",
    blueprintType: "medical_aesthetic",
    docType: "AYDINLATILMIS_ONAM_FORMU",
    category: "dermatoloji_estetik",
    version: 1,
    title: "Dr. Figen Beauty & DK Klinik Uyumlu Botulinum Toksin ve Dermal Dolgu Onam Formu",
    sourceReference: "Dr. Figen Beauty (drfigenbeauty.com) & DK Klinik (dk-klinik.com.tr)",
    legalBasis: "1219 Sayılı Kanun, Hasta Hakları Yönetmeliği, Tabip Odası Estetik Kuralları",
    description: "Mimik kırışıklıkları ve hacim kayıplarında uygulanan botoks ve hyaluronik asit dolgu bilgilendirilmiş onamı.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: 180,
    bodyTemplateMarkdown: `# BOTULİNUM TOKSİN (BOTOKS) VE DERMAL DOLGU BİLGİLENDİRİLMİŞ ONAM FORMU

**Uygulayıcı Klinik:** {{TENANT_LEGAL_NAME}}  
**Danışan:** {{CUSTOMER_FULL_NAME}} (TCKN: {{CUSTOMER_MASKED_TCKN}})  
**Uygulama:** {{SERVICE_NAME}} | **Tarih:** {{APPOINTMENT_DATE_TIME}}

---

### 1. İŞLEMİN NİTELİĞİ
- **Botulinum Toksin:** Kas hareketlerini geçici olarak bloke ederek dinamik mimik kırışıklıklarını (alın, kaş arası, kaz ayakları) yumuşatır; kalıcılığı ortalama 4-6 aydır.
- **Hyaluronik Asit Dolgu:** Hacim kaybı olan dokuları (dudak, elmacık, nazolabial vb.) doldurur; kalıcılığı 6-18 ay arasında değişir.

### 2. RİSKLER VE KOMPLİKASYONLAR
- Enjeksiyon noktalarında geçici ekimoz (morarma), ödem, kızarıklık ve asimetri,
- Botoks sonrası geçici kaş/göz kapağı düşmesi (ptozis - birkaç hafta içinde geriler),
- Dolgu sonrası çok nadir damar tıkanıklığı (vasküler oklüzyon), doku nekrozu veya granülom riski.
{{COMPLICATIONS_LIST}}

### 3. İŞLEM SONRASI DİKKAT EDİLECEK HUSUSLAR
İlk 24 saat baş öne eğilmeyecek, ağır spor yapılmayacak, hamam/saunaya girilmeyecek ve yüze masaj yapılmayacaktır.

**Danışan:** {{CUSTOMER_FULL_NAME}}  
**İmza:** (Biyometrik Kanvas İmza) | **Tarih:** {{SYSTEM_DATE}}`,
  },

  GUZELLIK_MICROBLADING_KALICI_MAKYAJ: {
    id: "GUZELLIK_MICROBLADING_KALICI_MAKYAJ",
    sectorFamily: "beauty_wellness",
    blueprintType: "beauty_salon",
    docType: "ALERJI_VE_ISLEM_RIZA_FORMU",
    category: "dermatoloji_estetik",
    version: 1,
    title: "Gizem Türk Uyumlu Kalıcı Makyaj, Microblading ve Kaş/Kirpik Onam Formu",
    sourceReference: "Gizem Türk Güzellik & Kalıcı Makyaj Merkezi (gizemturk.net)",
    legalBasis: "Güzellik Salonları Yönetmeliği, 6502 sayılı TKHK",
    description: "Microblading, dudak renklendirme, eyeliner, kaş laminasyonu ve kirpik lifting rıza ve alerji onamı.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: 180,
    bodyTemplateMarkdown: `# KALICI MAKYAJ, MİCROBLADİNG VE ESTETİK UYGULAMALAR ONAM FORMU

**Güzellik Merkezi:** {{TENANT_NAME}}  
**Danışan:** {{CUSTOMER_FULL_NAME}} (Tel: {{CUSTOMER_PHONE}})  
**Uygulama:** {{SERVICE_NAME}}

1. **İşlem:** Organik ve steril boya pigmentlerinin tek kullanımlık steril iğnelerle epidermisin üst tabakasına yerleştirilmesidir.
2. **Kalıcılık:** Cildin yağlılık oranına ve metabolizmaya bağlı olarak 1-2 yıl kalıcıdır; ilk ay sonunda rötuş yapılması önerilir.
3. **Beklenen Reaksiyonlar:** İlk 3 gün rengin daha koyu görünmesi, 4-7. günlerde hafif kabuk dökülmesi ve pullanma normaldir.
4. **Taahhüt:** Kabukları soymayacağımı, 7 gün boyunca su ve buhardan koruyacağımı, önerilen bakım kremini kullanacağımı taahhüt ederim.

**Danışan:** {{CUSTOMER_FULL_NAME}}  
**İmza:** (Dijital İmza) | **Tarih:** {{SYSTEM_DATE}}`,
  },

  // =========================================================================
  // 4. CERRAHİ, ORTOPEDİ VE YOĞUN BAKIM (DR. AHMET ÇAYMAZ, DR. AHMET AŞÇIOĞLU, TÜRK YOĞUN BAKIM)
  // =========================================================================
  GENEL_CERRAHI_AMELIYAT_ONAM: {
    id: "GENEL_CERRAHI_AMELIYAT_ONAM",
    sectorFamily: "health_clinical",
    blueprintType: "surgery_clinic",
    docType: "AYDINLATILMIS_ONAM_FORMU",
    category: "saglik_cerrahi",
    version: 1,
    title: "Dr. Ahmet Çaymaz & SKS Uyumlu Genel Cerrahi ve Girişimsel Ameliyat Onam Formu",
    sourceReference: "Dr. Ahmet Çaymaz Ameliyat Bilgi ve Onam Formları (drahmetcaymaz.com) & Mersin Sistem",
    legalBasis: "1219 Sayılı Tababet Kanunu, Sağlık Bakanlığı Sağlıkta Kalite Standartları (SKS)",
    description: "Laparoskopik ve açık cerrahi müdahaleler, fıtık, safra kesesi ve bariatrik cerrahi aydınlatılmış onamı.",
    requiresSignature: true,
    requiresOtp: true,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "OTP_2FA",
    validityDays: 90,
    bodyTemplateMarkdown: `# GENEL CERRAHİ VE LAPAROSKOPİK AMELİYAT BİLGİLENDİRİLMİŞ ONAM FORMU

**Sağlık Kuruluşu:** {{TENANT_LEGAL_NAME}}  
**Hasta Adı Soyadı:** {{CUSTOMER_FULL_NAME}} (TCKN: {{CUSTOMER_MASKED_TCKN}})  
**Ameliyat:** {{SERVICE_NAME}} | **Tarih:** {{APPOINTMENT_DATE_TIME}}

---

### 1. CERRAHİ GİRİŞİMİN TANIMI VE GEREKÇESİ
Tarafıma konulan tanı neticesinde **{{SERVICE_NAME}}** ameliyatının kapalı (laparoskopik) veya gerekli hallerde açık cerrahi yöntemle yapılması kararlaştırılmıştır. Cerrahinin amacı patolojinin giderilmesi ve sağlığıma kavuşmamdır.

### 2. ANESTEZİ VE CERRAHİ RİSKLER
- Anestezi ilaçlarına bağlı alerjik şok, solunum güçlüğü, aspirasyon,
- Ameliyat sırasında veya sonrasında kanama, kan transfüzyonu gereksinimi,
- Komşu organ veya damar yaralanmaları (bağırsak, karaciğer, safra yolları),
- Tromboemboli (damar tıkanıklığı / pıhtı atması), yara yeri enfeksiyonu, insizyonel fıtık.
{{COMPLICATIONS_LIST}}

### 3. LAPAROSKOPİDEN AÇIK CERRAHİYE GEÇİŞ ONAYI
Laparoskopik (kapalı) başlanan ameliyat esnasında teknik zorluk, yoğun yapışıklık veya kontrol altına alınamayan kanama gelişmesi halinde, hastanın hayatını ve güvenliğini korumak amacıyla hekimin açık ameliyata geçme yetkisini onaylıyorum.

**Hasta:** {{CUSTOMER_FULL_NAME}}  
**Onay:** SMS OTP Doğrulamalı Dijital İmza | **Tarih:** {{SYSTEM_DATE}}`,
  },

  YOGUN_BAKIM_INVAZIV_GIRISIM_ONAM: {
    id: "YOGUN_BAKIM_INVAZIV_GIRISIM_ONAM",
    sectorFamily: "health_clinical",
    blueprintType: "intensive_care",
    docType: "AYDINLATILMIS_ONAM_FORMU",
    category: "saglik_cerrahi",
    version: 1,
    title: "Türk Yoğun Bakım Derneği Uyumlu Yoğun Bakım ve İnvaziv Girişim Onam Kılavuzu",
    sourceReference: "Türk Yoğun Bakım Derneği Akademik Kılavuzu (akademi.yogunbakim.org.tr)",
    legalBasis: "Hasta Hakları Yönetmeliği m. 24, Tıbbi Deontoloji Tüzüğü",
    description: "Yoğun bakım ünitesinde takip, santral venöz kateter, mekanik ventilasyon ve kritik girişimler onamı.",
    requiresSignature: true,
    requiresOtp: true,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "OTP_2FA",
    validityDays: 30,
    bodyTemplateMarkdown: `# YOĞUN BAKIM ÜNİTESİ VE İNVAZİV İŞLEMLER BİLGİLENDİRİLMİŞ ONAM FORMU
*(Türk Yoğun Bakım Derneği Kılavuzlarına Uygundur)*

**Klinik:** {{TENANT_LEGAL_NAME}} Yoğun Bakım Ünitesi  
**Hasta:** {{CUSTOMER_FULL_NAME}} (TCKN: {{CUSTOMER_MASKED_TCKN}})  
**İşlem:** {{SERVICE_NAME}}

### GİRİŞİM VE TAKİP ŞARTLARI
Kritik organ yetmezliği veya hayati tehlike sebebiyle yoğun bakım takibi, entübasyon, mekanik solunum desteği, arteriyel/santral kateterizasyon, diyaliz ve yaşam destek tedavilerinin uygulanmasını kabul ediyorum. Bu işlemlerin hayati riskler ve enfeksiyon tehlikeleri barındırdığı tarafıma anlatılmıştır.

**Hasta / Yasal Vasi:** {{CUSTOMER_FULL_NAME}}  
**Onay:** SMS OTP Doğrulamalı İmza | **Tarih:** {{SYSTEM_DATE}}`,
  },

  // =========================================================================
  // 5. KONAKLAMA, OTELCİLİK VE TURİZM (JULIAN HOTELS, BAIA HOTELS, ZİGANA, TÜRSAB, PROKALİTE)
  // =========================================================================
  JULIAN_HOTELS_KVKK_MISAFIR_ONAM: {
    id: "JULIAN_HOTELS_KVKK_MISAFIR_ONAM",
    sectorFamily: "hospitality",
    blueprintType: "hotel_resort",
    docType: "KVKK_AYDINLATMA",
    category: "konaklama_turizm",
    version: 1,
    title: "Julian Hotels Uyumlu Konaklama Misafiri KVKK Aydınlatma ve Açık Rıza Metni",
    sourceReference: "Julian Hotels KVKK Misafir Aydınlatma ve Açık Rıza Belgesi (julianhotels.com)",
    legalBasis: "1774 Sayılı Kimlik Bildirme Kanunu, 6698 Sayılı KVKK, EGM KBS Sistemi",
    description: "Otel misafirlerinin KBS bildirimi, pasaport/kimlik işleme ve ticari ileti onay protokolü.",
    requiresSignature: false,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "SIMPLE_BIOMETRIC",
    validityDays: 365,
    bodyTemplateMarkdown: `# OTEL KONAKLAMA MİSAFİRİ KVKK AYDINLATMA VE AÇIK RIZA METNİ
*(1774 Sayılı Kimlik Bildirme Kanunu ve 6698 Sayılı KVKK Uyumlu)*

**Tesis:** {{TENANT_LEGAL_NAME}}  
**Misafir:** {{CUSTOMER_FULL_NAME}} ({{CUSTOMER_MASKED_TCKN}})  
**Telefon:** {{CUSTOMER_PHONE}} | **Tarih:** {{SYSTEM_DATE}}

### 1. YASAL ZORUNLU KİMLİK BİLDİRİMİ (KBS)
1774 sayılı Kimlik Bildirme Kanunu gereğince; otelimizde konaklayan misafirlerimizin kimlik ve pasaport bilgileri, Emniyet Genel Müdürlüğü ve Jandarma Genel Komutanlığı'nın Kimlik Bildirim Sistemi'ne (KBS) anlık olarak işlenmek zorundadır. Bu veri işleme faaliyeti kanunun açık amir hükmü (KVKK m. 5/2-ç) uyarınca misafirin rızasına bağlı olmaksızın yürütülür.

### 2. GÜVENLİK KAMERALARI VE KAPALI DEVRE KAYIT
Tesis güvenliği amacıyla ortak alanlar (lobi, koridorlar, otopark) 7/24 güvenlik kameralarıyla kayıt altına alınmaktadır.

**Misafir Beyanı:** Aydınlatma metnini okudum, kimlik bildirim yükümlülüklerimi biliyorum.`,
  },

  BAIA_HOTELS_KONAKLAMA_SOZLESMESI: {
    id: "BAIA_HOTELS_KONAKLAMA_SOZLESMESI",
    sectorFamily: "hospitality",
    blueprintType: "hotel_resort",
    docType: "HIZMET_VE_IPTAL_SOZLESMESI",
    category: "konaklama_turizm",
    version: 1,
    title: "Baia Hotels & Zigana Tatil Köyü Uyumlu Konaklama Hizmet Sözleşmesi",
    sourceReference: "Baia Hotels & Zigana Tatil Köyü Konaklama Hizmet Sözleşmeleri",
    legalBasis: "6098 TBK m. 20-25 & 576-581, 6502 TKHK m. 15/g, 1774 Kimlik Bildirme Kanunu",
    description: "Giriş-çıkış saatleri, depozito, erken ayrılış, no-show ve emanet eşya sorumluluk kuralları.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: null,
    bodyTemplateMarkdown: `# OTEL VE TATİL KÖYÜ KONAKLAMA HİZMET SÖZLEŞMESİ

**Tesis:** {{TENANT_LEGAL_NAME}} | **VKN:** {{TENANT_TAX_ID}}  
**Misafir:** {{CUSTOMER_FULL_NAME}} (TCKN/Pasaport: {{CUSTOMER_MASKED_TCKN}})  
**Rezervasyon Detayı:** {{SERVICE_NAME}}  
**Giriş:** {{APPOINTMENT_DATE_TIME}} | **Toplam Tutar:** {{SERVICE_PRICE}}

---

### SÖZLEŞME ŞARTLARI:
1. **Giriş ve Çıkış Saatleri:** Giriş saati 14:00, çıkış saati en geç 12:00'dir. Geç çıkışlarda yarım günlük veya tam günlük oda ücreti tahakkuk ettirilir.
2. **Kademeli İptal ve No-Show:** Giriş tarihine **{{CANCELLATION_DEADLINE}}** kalana kadar yapılan iptallerde kesintisiz iade yapılır; daha sonraki iptallerde veya tesise gelinmemesi halinde **{{PENALTY_RATE}}** oranında kesinti uygulanır.
3. **TBK m. 576-581 Emanet Hükümleri:** Odalarda bırakılan ziynet eşyası, nakit para ve kıymetli evraktan tesis sorumlu değildir; bu kıymetlerin resepsiyondaki emanet kasalarına teslimi şarttır.
4. **Hasar Sorumluluğu:** Oda demirbaşlarına veya tesis eşyalarına verilen zararlar çıkış esnasında misafirden tahsil edilir.

**Misafir:** {{CUSTOMER_FULL_NAME}}  
**İmza:** (Biyometrik Kanvas İmza) | **Tarih:** {{SYSTEM_DATE}}`,
  },

  TURSAB_ACENTA_GUVENLIK_SOZLESMESI: {
    id: "TURSAB_ACENTA_GUVENLIK_SOZLESMESI",
    sectorFamily: "hospitality",
    blueprintType: "tour_operator",
    docType: "MESAFELI_SATIS_SOZLESMESI",
    category: "konaklama_turizm",
    version: 1,
    title: "TÜRSAB Standartlarında Seyahat Acentası, Paket Tur ve Transfer Güvenlik Sözleşmesi",
    sourceReference: "TÜRSAB Güvenlik El Kitabı & Türkiye Turizm Ansiklopedisi Acente Sözleşmeleri",
    legalBasis: "1618 Sayılı Seyahat Acentaları Kanunu, Paket Tur Sözleşmeleri Yönetmeliği",
    description: "Tur, transfer, acente sorumluluk sınırları, zorunlu seyahat sigortası ve mücbir sebep şartları.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: null,
    bodyTemplateMarkdown: `# TÜRSAB SEYAHAT ACENTASI TUR, REZERVASYON VE TRANSFER SÖZLEŞMESİ

**Acenta:** {{TENANT_LEGAL_NAME}} (TÜRSAB Belge No: {{TENANT_MERSIS}})  
**Müşteri / Katılımcı:** {{CUSTOMER_FULL_NAME}}  
**Hizmet:** {{SERVICE_NAME}} | **Tarih:** {{APPOINTMENT_DATE_TIME}}

1. **Acente Sorumluluk Sınırları:** Acenta, taahhüt edilen tur ve transfer hizmetlerinin ifasında aracı ve organizatör konumundadır. Doğal afet, hava muhalefeti, grev gibi mücbir sebeplerden doğan gecikmelerden acenta sorumlu tutulamaz.
2. **Güvenlik Kuralları:** Katılımcı, araç içi emniyet kemeri takma zorunluluğuna, rehber ve kaptanın güvenlik uyarılarına uymakla yükümlüdür.
3. **Bagaj ve Değerli Eşya:** Bagajların korunması katılımcının kendi sorumluluğundadır.

**Katılımcı:** {{CUSTOMER_FULL_NAME}}  
**İmza:** (Dijital İmza) | **Tarih:** {{SYSTEM_DATE}}`,
  },

  PROKALITE_ANNE_MISAFIRHANESI_ONAM: {
    id: "PROKALITE_ANNE_MISAFIRHANESI_ONAM",
    sectorFamily: "hospitality",
    blueprintType: "medical_guesthouse",
    docType: "AYDINLATILMIS_ONAM_FORMU",
    category: "konaklama_turizm",
    version: 1,
    title: "Prokalite Uyumlu Anne Misafirhanesi ve Tıbbi Refakatçi Konaklama Bilgilendirme Onam Formu",
    sourceReference: "Prokalite Kalite Doküman Yönetim Sistemi (prokalite.com Doküman 4361)",
    legalBasis: "Sağlık Bakanlığı Anne Dostu Hastane Kriterleri, SKS Konaklama Standartları",
    description: "Sağlık tesislerinde anne ve refakatçi konaklama hakları, enfeksiyon kuralları ve oda kullanım onamı.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: 30,
    bodyTemplateMarkdown: `# ANNE MİSAFİRHANESİ VE REFAKATÇİ KONAKLAMA BİLGİLENDİRME VE ONAM FORMU
*(Prokalite Kalite Standartları Doküman No: 4361 Esas Alınmıştır)*

**Sağlık Kuruluşu:** {{TENANT_LEGAL_NAME}}  
**Misafir Anne / Refakatçi:** {{CUSTOMER_FULL_NAME}} (TCKN: {{CUSTOMER_MASKED_TCKN}})  
**Tahsis Edilen Alan:** Anne Misafirhanesi / Refakat Odası

1. Misafirhane kurallarına ve enfeksiyon kontrol yönergelerine tam uyum sağlayacağımı,
2. Ortak kullanım alanlarını temiz tutacağımı, sigara ve tütün ürünleri kullanmayacağımı,
3. Bebeğimin veya hastamın bakım saatlerine riayet edeceğimi kabul ve taahhüt ederim.

**Onaylayan:** {{CUSTOMER_FULL_NAME}}  
**İmza:** (Dijital İmza) | **Tarih:** {{SYSTEM_DATE}}`,
  },

  // =========================================================================
  // 6. OTOMOTİV BAKIM, ONARIM VE SERVİS (AVEVERAK, LEXPERA, TMMOB MMO)
  // =========================================================================
  AVEVERAK_ARAC_BAKIM_ONARIM_SOZLESMESI: {
    id: "AVEVERAK_ARAC_BAKIM_ONARIM_SOZLESMESI",
    sectorFamily: "automotive",
    blueprintType: "auto_service",
    docType: "IS_EMRI_VE_TESLIMAT_ONAYI",
    category: "otomotiv_servis",
    version: 1,
    title: "AvEvrak & Lexpera Uyumlu Araç Bakım ve Onarım Servis Sözleşmesi",
    sourceReference: "AvEvrak (avevrak.com) & Lexpera Yetkili Servis Sözleşmesi (lexpera.com.tr)",
    legalBasis: "6098 sayılı TBK Eser Sözleşmesi m. 470-486, Satış Sonrası Hizmetler Yönetmeliği",
    description: "İş emri, yedek parça ve işçilik garantisi, ilave masraf onayı, test sürüşü ve teslimat sözleşmesi.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: null,
    bodyTemplateMarkdown: `# ARAÇ BAKIM, ONARIM VE SERVİS HİZMET SÖZLEŞMESİ
*(6098 Sayılı Türk Borçlar Kanunu Eser Sözleşmesi Hükümlerine Uygun Olarak Düzenlenmiştir)*

**YETKİLİ SERVİS / İŞLETME:**  
- **Unvan:** {{TENANT_LEGAL_NAME}} | **VKN:** {{TENANT_TAX_ID}}  
- **Adres:** {{TENANT_ADDRESS}} | **Tel:** {{TENANT_PHONE}}

**MÜŞTERİ / ARAÇ SAHİBİ:**  
- **Adı Soyadı:** {{CUSTOMER_FULL_NAME}} (TCKN: {{CUSTOMER_MASKED_TCKN}})  
- **Telefon:** {{CUSTOMER_PHONE}} | **Plaka No:** {{VEHICLE_VIN_PLATE}}

---

### MADDE 1 - SÖZLEŞMENİN KONUSU
İşbu sözleşmenin konusu; Müşteri'ye ait yukarıda plakası belirtilen aracın Servis'e kabulü, talep edilen arıza tespiti, periyodik bakım veya mekanik/kaporta onarımlarının yapılması, parça değişimi ve aracın teslimine ilişkin usul ve esasların belirlenmesidir.

- **Talep Edilen Hizmet:** {{SERVICE_NAME}}
- **Tahmini Servis Bedeli:** {{SERVICE_PRICE}}

---

### MADDE 2 - SERVİS VE ÇALIŞMA ESASLARI
1. **Ek Arıza ve İlave Masraf Bildirimi:** Onarım esnasında önceden öngörülemeyen ilave arıza veya parça ihtiyacı tespit edilirse, Müşteri'nin telefon veya dijital mesaj yoluyla yazılı/sözlü teyidi alınmadan ek maliyetli işleme başlanmayacaktır.
2. **Yedek Parça ve Garanti:** Kullanılan orijinal veya eşdeğer yedek parçalar üretici garantisi altındadır. Servisimizde yapılan işçilik, faturada belirtilen süre/kilometre boyunca garanti kapsamındadır.
3. **Eski Parçaların Teslimi:** Müşteri'nin talebi halinde değiştirilen eski parçalar araç tesliminde müşteriye verilir; talep edilmeyen parçalar çevre mevzuatına uygun olarak bertaraf edilir.
4. **Test Sürüşü Muvafakati:** Yapılan onarımın teknik doğruluğunu test etmek üzere servis teknisyenlerinin aracı karayolunda test sürüşüne çıkarmasına izin verilmiştir.
5. **Kıymetli Eşya Feragati:** Müşteri araç içinde para, cep telefonu, dizüstü bilgisayar vb. kişisel kıymetli eşya bırakmadığını teyit eder. Araçta unutulan eşyalardan servis sorumlu tutulamaz.

Müşteri, yukarıdaki şartları okuyup kabul ettiğini beyan eder.

**Servis Yetkilisi:** {{TENANT_NAME}}  
**Araç Sahibi / Müşteri:** {{CUSTOMER_FULL_NAME}} (Biyometrik Dijital İmza)`,
  },

  MMO_ARAC_KONTROL_EKSPERTIZ_TUTANAGI: {
    id: "MMO_ARAC_KONTROL_EKSPERTIZ_TUTANAGI",
    sectorFamily: "automotive",
    blueprintType: "auto_inspection",
    docType: "TESLIM_TESELLUM_HASAR_TUTANAGI",
    category: "otomotiv_servis",
    version: 1,
    title: "TMMOB Makina Mühendisleri Odası (MMO) Uyumlu Araç Kontrol ve Hasar Teslim Tutanağı",
    sourceReference: "TMMOB Makina Mühendisleri Odası Araç Kontrol Teknik Şartnamesi (mmo.org.tr)",
    legalBasis: "Karayolları Trafik Kanunu, İkinci El Motorlu Kara Taşıtlarının Ticareti Yönetmeliği",
    description: "Görsel hasar şeması, kilometre, lastik diş derinliği ve kaporta ekspertiz teslim tutanağı.",
    requiresSignature: true,
    requiresOtp: false,
    requiresGps: false,
    isRequired: true,
    isActive: true,
    signatureLevel: "CANVAS_BIOMETRIC",
    validityDays: null,
    bodyTemplateMarkdown: `# ARAÇ KONTROL, EKSPERTİZ VE GİRİŞ HASAR TESPİT PROTOKOLÜ
*(TMMOB Makina Mühendisleri Odası Standart Kontrol Formları Uyarınca)*

**Merkez / Ekspertiz:** {{TENANT_LEGAL_NAME}}  
**Müşteri:** {{CUSTOMER_FULL_NAME}}  
**Plaka:** {{VEHICLE_VIN_PLATE}} | **Tarih:** {{SYSTEM_DATE}} {{SYSTEM_TIME}}

1. **Görsel Hasar Kaydı:** Araç kabulü esnasında tespit edilen mevcut çizik, taş vuruğu, dolu hasarı, tampon sürtmeleri ve cam çatlakları teslim şemasında işaretlenmiştir.
2. **Kilometre ve Yakıt:** Giriş kilometresi ve yakıt seviyesi dijital sayaçtan okunarak tutanağa geçirilmiştir.
3. **Teslim Teyidi:** Araç mevcut durumuyla teslim alınmış olup onarım sonrası bu tutanaktaki işaretlemeler baz alınacaktır.

**Teslim Eden:** {{CUSTOMER_FULL_NAME}}  
**Teslim Alan:** {{TENANT_NAME}} Ekspertiz Personeli`,
  },
};
