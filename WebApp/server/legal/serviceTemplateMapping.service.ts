import { DocumentType, SectorFamily } from "@shared/legalTypes";
import { SECTOR_ONAM_CATALOG } from "./sectorOnamCatalog";
import { DEFAULT_LEGAL_TEMPLATES } from "./defaultTemplates";

export interface ServiceOnamMappingRule {
  id: string;
  tenantId?: string;
  serviceId?: string;
  serviceCategory?: string;
  serviceNamePattern?: string; // Regex veya anahtar kelime eşleşmesi
  sectorFamily: SectorFamily;
  requiredTemplateIds: string[]; // Zorunlu imzalatılacak şablon ID'leri
  optionalTemplateIds?: string[]; // İhtiyari açık rıza veya bilgilendirme şablonları
  requiresScrollEnforcement: boolean;
  signatureLevel: "CANVAS_BIOMETRIC" | "OTP_2FA" | "QUALIFIED_E_SIGN";
  description: string;
}

/**
 * BooKi Service-to-Onam Mapping Service
 * Hizmet tipleri ile yasal onam ve sözleşme şablonlarını akıllıca eşleştiren motor.
 */
export class ServiceTemplateMappingService {
  // Kurumsal ve yasal varsayılan eşleştirme matrisi
  private static defaultRules: ServiceOnamMappingRule[] = [
    // 1. Göz Cerrahisi / Oftalmoloji (TOD)
    {
      id: "map_eye_cataract",
      sectorFamily: "health_clinical",
      serviceNamePattern: "katarakt|fako|göz içi lens|trifokal|monofokal",
      requiredTemplateIds: ["TOD_KATARAKT_FAKO_ONAM", "KVKK_OZEL_NITELIKLI_RIZA"],
      optionalTemplateIds: ["KVKK_AYDINLATMA"],
      requiresScrollEnforcement: true,
      signatureLevel: "OTP_2FA",
      description: "TOD Uyumlu Katarakt ve Göz İçi Lens Cerrahisi Onamı (SMS OTP Zorunlu)",
    },
    {
      id: "map_eye_laser_refractive",
      sectorFamily: "health_clinical",
      serviceNamePattern: "lazer|lasik|prk|no-touch|refraktif|göz çizdirme",
      requiredTemplateIds: ["TOD_REFRAKTIF_LAZER_ONAM", "KVKK_OZEL_NITELIKLI_RIZA"],
      optionalTemplateIds: ["KVKK_AYDINLATMA"],
      requiresScrollEnforcement: true,
      signatureLevel: "OTP_2FA",
      description: "TOD Uyumlu Eksimer Lazer Refraktif Cerrahi Onamı",
    },

    // 2. Diş Hekimliği (İDO)
    {
      id: "map_dental_implant",
      sectorFamily: "health_clinical",
      serviceNamePattern: "implant|kemik tozu|greft|çene cerrahi|gömülü diş",
      requiredTemplateIds: ["IDO_DENTAL_IMPLANT_ONAM", "KVKK_OZEL_NITELIKLI_RIZA"],
      requiresScrollEnforcement: true,
      signatureLevel: "OTP_2FA",
      description: "İDO Uyumlu Dental İmplant ve Cerrahi Onam Formu",
    },
    {
      id: "map_dental_endo",
      sectorFamily: "health_clinical",
      serviceNamePattern: "kanal tedavisi|endodonti|kök kanal",
      requiredTemplateIds: ["IDO_KANAL_TEDAVISI_ONAM", "KVKK_OZEL_NITELIKLI_RIZA"],
      requiresScrollEnforcement: true,
      signatureLevel: "CANVAS_BIOMETRIC",
      description: "İDO Uyumlu Kök Kanal Tedavisi Onam Formu",
    },

    // 3. Dermatoloji & Medikal Estetik
    {
      id: "map_derm_laser",
      sectorFamily: "beauty_wellness",
      serviceNamePattern: "fraksiyonel lazer|cilt yenileme|akne izi|leke lazeri|lazer epilasyon",
      requiredTemplateIds: ["DERMATOLOJI_LAZER_CILT_YENILEME", "ALERJI_VE_ISLEM_RIZA_FORMU", "KVKK_AYDINLATMA"],
      requiresScrollEnforcement: true,
      signatureLevel: "CANVAS_BIOMETRIC",
      description: "Türk Dermatoloji Derneği Uyumlu Lazer Cilt Yenileme ve Alerji Onamı",
    },
    {
      id: "map_aesthetic_botox_filler",
      sectorFamily: "beauty_wellness",
      serviceNamePattern: "botoks|botox|dolgu|hyaluronik|dudak dolgusu|gençlik aşısı|mezoterapi|prp",
      requiredTemplateIds: ["ESTETIK_BOTOKS_DOLGU_ONAM", "ALERJI_VE_ISLEM_RIZA_FORMU"],
      requiresScrollEnforcement: true,
      signatureLevel: "CANVAS_BIOMETRIC",
      description: "Dr. Figen Beauty & DK Klinik Uyumlu Botoks, Dolgu ve Mezoterapi Onamı",
    },
    {
      id: "map_beauty_microblading",
      sectorFamily: "beauty_wellness",
      serviceNamePattern: "microblading|kalıcı makyaj|kaş|dudak renklendirme|eyeliner|kirpik",
      requiredTemplateIds: ["GUZELLIK_MICROBLADING_KALICI_MAKYAJ", "ALERJI_VE_ISLEM_RIZA_FORMU"],
      requiresScrollEnforcement: true,
      signatureLevel: "CANVAS_BIOMETRIC",
      description: "Gizem Türk Uyumlu Kalıcı Makyaj ve Microblading Onamı",
    },

    // 4. Genel Cerrahi & Yoğun Bakım
    {
      id: "map_surgery_general",
      sectorFamily: "health_clinical",
      serviceNamePattern: "ameliyat|laparoskopi|fıtık|safra kesesi|obezite|bariatrik|mide küçültme",
      requiredTemplateIds: ["GENEL_CERRAHI_AMELIYAT_ONAM", "KVKK_OZEL_NITELIKLI_RIZA"],
      requiresScrollEnforcement: true,
      signatureLevel: "OTP_2FA",
      description: "Dr. Ahmet Çaymaz & SKS Uyumlu Genel Cerrahi ve Ameliyat Onamı",
    },
    {
      id: "map_icu_care",
      sectorFamily: "health_clinical",
      serviceNamePattern: "yoğun bakım|entübasyon|santral kateter|kritik bakım",
      requiredTemplateIds: ["YOGUN_BAKIM_INVAZIV_GIRISIM_ONAM", "KVKK_OZEL_NITELIKLI_RIZA"],
      requiresScrollEnforcement: true,
      signatureLevel: "OTP_2FA",
      description: "Türk Yoğun Bakım Derneği Uyumlu Kritik Hasta Onam Kılavuzu",
    },

    // 5. Konaklama & Otelcilik & Turizm
    {
      id: "map_hotel_stay",
      sectorFamily: "hospitality",
      serviceNamePattern: "oda|konaklama|suit|otel|bungalov|villa|rezervasyon",
      requiredTemplateIds: ["BAIA_HOTELS_KONAKLAMA_SOZLESMESI", "JULIAN_HOTELS_KVKK_MISAFIR_ONAM"],
      requiresScrollEnforcement: true,
      signatureLevel: "CANVAS_BIOMETRIC",
      description: "Baia Hotels & Julian Hotels Uyumlu Konaklama ve 1774 KBS KVKK Sözleşmesi",
    },
    {
      id: "map_tour_travel",
      sectorFamily: "hospitality",
      serviceNamePattern: "tur|transfer|gezi|paket tur|rehberlik|türsab",
      requiredTemplateIds: ["TURSAB_ACENTA_GUVENLIK_SOZLESMESI"],
      requiresScrollEnforcement: true,
      signatureLevel: "CANVAS_BIOMETRIC",
      description: "TÜRSAB Standartlarında Seyahat Acentası ve Güvenlik Sözleşmesi",
    },
    {
      id: "map_mother_guesthouse",
      sectorFamily: "hospitality",
      serviceNamePattern: "anne misafirhanesi|refakat|sağlık konaklama",
      requiredTemplateIds: ["PROKALITE_ANNE_MISAFIRHANESI_ONAM"],
      requiresScrollEnforcement: true,
      signatureLevel: "CANVAS_BIOMETRIC",
      description: "Prokalite Uyumlu Anne Misafirhanesi ve Refakatçi Konaklama Onamı",
    },

    // 6. Otomotiv Bakım & Onarım
    {
      id: "map_auto_repair",
      sectorFamily: "automotive",
      serviceNamePattern: "bakım|onarım|servis|yağ değişimi|fren|mekanik|periyodik bakım",
      requiredTemplateIds: ["AVEVERAK_ARAC_BAKIM_ONARIM_SOZLESMESI", "TESLIM_TESELLUM_HASAR_TUTANAGI"],
      requiresScrollEnforcement: true,
      signatureLevel: "CANVAS_BIOMETRIC",
      description: "AvEvrak & Lexpera Uyumlu Araç Bakım-Onarım ve Hasar Tespit Sözleşmesi",
    },
    {
      id: "map_auto_inspection",
      sectorFamily: "automotive",
      serviceNamePattern: "ekspertiz|muayene|kontrol|kaporta test|dyno",
      requiredTemplateIds: ["MMO_ARAC_KONTROL_EKSPERTIZ_TUTANAGI"],
      requiresScrollEnforcement: true,
      signatureLevel: "CANVAS_BIOMETRIC",
      description: "TMMOB MMO Uyumlu Araç Muayene ve Ekspertiz Giriş Tutanağı",
    },

    // 7. Gayrimenkul / Emlak
    {
      id: "map_real_estate",
      sectorFamily: "real_estate",
      serviceNamePattern: "yer gösterme|daire inceleme|portföy gezisi|kiralık|satılık",
      requiredTemplateIds: ["TASINMAZ_GOSTERME_BELGESI"],
      requiresScrollEnforcement: true,
      signatureLevel: "CANVAS_BIOMETRIC",
      description: "30442 Sayılı Yönetmelik m. 19 Uyarınca GPS Teyitli Taşınmaz Gösterme Belgesi",
    },

    // 8. Spor, Fitness ve Macera
    {
      id: "map_sports_fitness",
      sectorFamily: "sports_fitness",
      serviceNamePattern: "fitness|pilates|crossfit|pt|özel ders|dalış|yamaç paraşütü|rafting",
      requiredTemplateIds: ["SAGLIK_BEYANI_WAIVER"],
      requiresScrollEnforcement: true,
      signatureLevel: "CANVAS_BIOMETRIC",
      description: "Fiziksel Aktivite Sağlık Beyanı ve Risk Kabul Feragatnamesi (Waiver)",
    },

    // 9. Yeme-İçme & Restoran
    {
      id: "map_restaurant",
      sectorFamily: "restaurant_food",
      serviceNamePattern: "masa|rezervasyon|vip oda|şef tadım|özel menü",
      requiredTemplateIds: ["NO_SHOW_VE_IPTAL_SOZLESMESI", "ALERJEN_BILDIRIM_ONAYI"],
      requiresScrollEnforcement: false,
      signatureLevel: "CANVAS_BIOMETRIC",
      description: "No-Show Güvence Depozito Sözleşmesi ve 14 Temel Alerjen Beyanı",
    },

    // 10. Eğitim & B2B Danışmanlık
    {
      id: "map_education",
      sectorFamily: "education",
      serviceNamePattern: "kurs|eğitim|seminer|atölye|sertifika",
      requiredTemplateIds: ["EGITIM_SOZLESMESI"],
      requiresScrollEnforcement: true,
      signatureLevel: "CANVAS_BIOMETRIC",
      description: "Özel Kurs ve Eğitim Hizmet Sözleşmesi",
    },
    {
      id: "map_professional_nda",
      sectorFamily: "professional",
      serviceNamePattern: "danışmanlık|hukuk|mali|yazılım|strateji",
      requiredTemplateIds: ["GIZLILIK_VE_NDA"],
      requiresScrollEnforcement: true,
      signatureLevel: "QUALIFIED_E_SIGN",
      description: "Karşılıklı Gizlilik Sözleşmesi (NDA) ve Hizmet SLA'sı",
    },
  ];

  /**
   * Resolves matching onam templates based on service name, category, and sector family.
   */
  public static resolveTemplatesForService(params: {
    sectorFamily: SectorFamily;
    serviceName: string;
    serviceCategory?: string;
    serviceId?: string;
  }): { requiredTemplateIds: string[]; optionalTemplateIds: string[]; signatureLevel: string; ruleDescription: string } {
    const { sectorFamily, serviceName } = params;
    const lowerServiceName = (serviceName || "").toLowerCase();

    // Check specific pattern matches
    for (const rule of this.defaultRules) {
      if (rule.sectorFamily === sectorFamily) {
        if (rule.serviceNamePattern) {
          const regex = new RegExp(rule.serviceNamePattern, "i");
          if (regex.test(lowerServiceName)) {
            return {
              requiredTemplateIds: rule.requiredTemplateIds,
              optionalTemplateIds: rule.optionalTemplateIds || [],
              signatureLevel: rule.signatureLevel,
              ruleDescription: rule.description,
            };
          }
        }
      }
    }

    // Default sector fallbacks
    switch (sectorFamily) {
      case "health_clinical":
        return {
          requiredTemplateIds: ["AYDINLATILMIS_ONAM_FORMU", "KVKK_OZEL_NITELIKLI_RIZA"],
          optionalTemplateIds: ["KVKK_AYDINLATMA"],
          signatureLevel: "OTP_2FA",
          ruleDescription: "Genel Tıbbi Müdahale Aydınlatılmış Onam ve Özel Nitelikli Rıza",
        };
      case "beauty_wellness":
        return {
          requiredTemplateIds: ["ALERJI_VE_ISLEM_RIZA_FORMU", "KVKK_AYDINLATMA"],
          optionalTemplateIds: ["MESAFELI_SATIS_SOZLESMESI"],
          signatureLevel: "CANVAS_BIOMETRIC",
          ruleDescription: "Güzellik ve Bakım İşlemi Bilgilendirilmiş Rıza ve Alerji Formu",
        };
      case "hospitality":
        return {
          requiredTemplateIds: ["HIZMET_VE_IPTAL_SOZLESMESI", "KVKK_AYDINLATMA"],
          optionalTemplateIds: [],
          signatureLevel: "CANVAS_BIOMETRIC",
          ruleDescription: "Konaklama Hizmet Sözleşmesi ve 1774 KBS KVKK Metni",
        };
      case "automotive":
        return {
          requiredTemplateIds: ["TESLIM_TESELLUM_HASAR_TUTANAGI", "IS_EMRI_VE_TESLIMAT_ONAYI"],
          optionalTemplateIds: [],
          signatureLevel: "CANVAS_BIOMETRIC",
          ruleDescription: "Araç Teslim-Tesellüm Hasar Tutanağı ve İş Emri Kapanış Onayı",
        };
      case "real_estate":
        return {
          requiredTemplateIds: ["TASINMAZ_GOSTERME_BELGESI"],
          optionalTemplateIds: [],
          signatureLevel: "CANVAS_BIOMETRIC",
          ruleDescription: "30442 Sayılı Yönetmelik Yer Gösterme Belgesi",
        };
      case "sports_fitness":
      case "experience":
        return {
          requiredTemplateIds: ["SAGLIK_BEYANI_WAIVER"],
          optionalTemplateIds: [],
          signatureLevel: "CANVAS_BIOMETRIC",
          ruleDescription: "Sağlık Beyanı ve Risk Kabul Feragatnamesi (Waiver)",
        };
      default:
        return {
          requiredTemplateIds: ["KVKK_AYDINLATMA", "MESAFELI_SATIS_SOZLESMESI"],
          optionalTemplateIds: [],
          signatureLevel: "CANVAS_BIOMETRIC",
          ruleDescription: "Standart Hizmet ve Rezervasyon Sözleşmesi",
        };
    }
  }

  public static getAllRules(): ServiceOnamMappingRule[] {
    return this.defaultRules;
  }
}
