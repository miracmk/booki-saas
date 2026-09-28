import {
  BookingContextData,
  DocumentType,
  FlowContext,
  RequiredContractRule,
  SectorFamily,
} from "@shared/legalTypes";
import { DEFAULT_LEGAL_TEMPLATES } from "./defaultTemplates";

export interface EnforcementCheckOptions {
  sectorFamily: SectorFamily;
  flowContext: FlowContext;
  context: BookingContextData;
  customerExistingValidSignatures?: { docType: DocumentType; signedAt: string; expiresAt?: string }[];
}

export class LegalComplianceEngine {
  /**
   * Evaluates the legal requirements based on sector family, flow context, prepayment status,
   * customer history, and special vertical rules.
   */
  public static evaluateRequirements(options: EnforcementCheckOptions): RequiredContractRule[] {
    const { sectorFamily, flowContext, context, customerExistingValidSignatures = [] } = options;
    const rules: RequiredContractRule[] = [];

    const isAlreadySigned = (docType: DocumentType): boolean => {
      const existing = customerExistingValidSignatures.find((s) => s.docType === docType);
      if (!existing) return false;
      if (!existing.expiresAt) return true;
      return new Date(existing.expiresAt).getTime() > Date.now();
    };

    // 1. beauty_wellness
    if (sectorFamily === "beauty_wellness") {
      if (flowContext === "CHECKOUT") {
        rules.push({
          docType: "KVKK_AYDINLATMA",
          title: DEFAULT_LEGAL_TEMPLATES.KVKK_AYDINLATMA.title,
          description: "6698 KVKK aydınlatma metni bilgilendirmesi",
          isRequired: true,
          requiresSignature: false,
          signatureType: "CANVAS_BIOMETRIC",
          flowContext: "CHECKOUT",
          requiresScroll: true,
          requiresOtp: false,
          requiresGps: false,
          requiresDamageInspection: false,
          alreadySigned: isAlreadySigned("KVKK_AYDINLATMA"),
        });

        if (context.hasPrepayment || (context.depositAmount && context.depositAmount > 0)) {
          rules.push({
            docType: "MESAFELI_SATIS_SOZLESMESI",
            title: DEFAULT_LEGAL_TEMPLATES.MESAFELI_SATIS_SOZLESMESI.title,
            description: "TBK 20-25 ve TKHK 5 haksız şart savunmalı kademeli mesafeli satış sözleşmesi",
            isRequired: true,
            requiresSignature: true,
            signatureType: "CANVAS_BIOMETRIC",
            flowContext: "CHECKOUT",
            requiresScroll: true,
            requiresOtp: false,
            requiresGps: false,
            requiresDamageInspection: false,
            alreadySigned: isAlreadySigned("MESAFELI_SATIS_SOZLESMESI"),
          });
        }
      }

      if (flowContext === "CHECK_IN") {
        rules.push({
          docType: "AYDINLATILMIS_ONAM_FORMU",
          title: "Güzellik ve Bakım İşlemi Bilgilendirilmiş Rıza ve Alerji Formu",
          description: "Uygulama öncesi biyometrik kanvas imza ile risk ve alerji kabul onayı",
          isRequired: true,
          requiresSignature: true,
          signatureType: "CANVAS_BIOMETRIC",
          flowContext: "CHECK_IN",
          requiresScroll: true,
          requiresOtp: false,
          requiresGps: false,
          requiresDamageInspection: false,
          alreadySigned: isAlreadySigned("AYDINLATILMIS_ONAM_FORMU"),
        });
      }
    }

    // 2. health_clinical
    else if (sectorFamily === "health_clinical") {
      rules.push({
        docType: "KVKK_OZEL_NITELIKLI_RIZA",
        title: DEFAULT_LEGAL_TEMPLATES.KVKK_OZEL_NITELIKLI_RIZA.title,
        description: "KVKK Madde 6 uyarınca özel nitelikli tıbbi verilerin işlenmesine ilişkin açık rıza",
        isRequired: false, // Torba rıza yasağı
        requiresSignature: true,
        signatureType: "SMS_OTP_VERIFIED",
        flowContext,
        requiresScroll: true,
        requiresOtp: true,
        requiresGps: false,
        requiresDamageInspection: false,
        alreadySigned: isAlreadySigned("KVKK_OZEL_NITELIKLI_RIZA"),
      });

      rules.push({
        docType: "AYDINLATILMIS_ONAM_FORMU",
        title: DEFAULT_LEGAL_TEMPLATES.AYDINLATILMIS_ONAM_FORMU.title,
        description: "Hasta Hakları m. 24-26 ve Tabip Odası uyarınca SMS OTP doğrulamalı aydınlatılmış onam",
        isRequired: true,
        requiresSignature: true,
        signatureType: "SMS_OTP_VERIFIED",
        flowContext,
        requiresScroll: true,
        requiresOtp: true,
        requiresGps: false,
        requiresDamageInspection: false,
        alreadySigned: isAlreadySigned("AYDINLATILMIS_ONAM_FORMU"),
      });
    }

    // 3. sports_fitness & experience
    else if (sectorFamily === "sports_fitness" || sectorFamily === "experience") {
      const waiverSigned = isAlreadySigned("SAGLIK_BEYANI_WAIVER");
      rules.push({
        docType: "SAGLIK_BEYANI_WAIVER",
        title: DEFAULT_LEGAL_TEMPLATES.SAGLIK_BEYANI_WAIVER.title,
        description: "1 yıl geçerli tek seferlik sağlık beyanı ve risk kabul feragatnamesi",
        isRequired: !waiverSigned,
        requiresSignature: true,
        signatureType: "CANVAS_BIOMETRIC",
        flowContext,
        requiresScroll: true,
        requiresOtp: false,
        requiresGps: false,
        requiresDamageInspection: false,
        alreadySigned: waiverSigned,
      });

      if (context.hasPrepayment) {
        rules.push({
          docType: "MESAFELI_SATIS_SOZLESMESI",
          title: DEFAULT_LEGAL_TEMPLATES.MESAFELI_SATIS_SOZLESMESI.title,
          description: "Online seans/kort rezervasyon ödeme koşulları sözleşmesi",
          isRequired: true,
          requiresSignature: true,
          signatureType: "CANVAS_BIOMETRIC",
          flowContext: "CHECKOUT",
          requiresScroll: true,
          requiresOtp: false,
          requiresGps: false,
          requiresDamageInspection: false,
          alreadySigned: isAlreadySigned("MESAFELI_SATIS_SOZLESMESI"),
        });
      }
    }

    // 4. automotive
    else if (sectorFamily === "automotive") {
      if (flowContext === "CHECK_IN" || flowContext === "CHECKOUT") {
        rules.push({
          docType: "TESLIM_TESELLUM_HASAR_TUTANAGI",
          title: DEFAULT_LEGAL_TEMPLATES.TESLIM_TESELLUM_HASAR_TUTANAGI.title,
          description: "Girişte mevcut kaporta/cam hasarlarının görsel işaretlenmesi ve teslim tutanağı",
          isRequired: true,
          requiresSignature: true,
          signatureType: "CANVAS_BIOMETRIC",
          flowContext: "CHECK_IN",
          requiresScroll: false,
          requiresOtp: false,
          requiresGps: false,
          requiresDamageInspection: true,
          alreadySigned: isAlreadySigned("TESLIM_TESELLUM_HASAR_TUTANAGI"),
        });
      }

      if (flowContext === "CHECK_OUT") {
        rules.push({
          docType: "MESAFELI_SATIS_SOZLESMESI",
          title: "İş Emri Kapanış ve Araç Eksiksiz Teslimat Onayı",
          description: "İş emri kapanışı ve aracın eksiksiz teslim alındığına dair çıkış imzası",
          isRequired: true,
          requiresSignature: true,
          signatureType: "CANVAS_BIOMETRIC",
          flowContext: "CHECK_OUT",
          requiresScroll: true,
          requiresOtp: false,
          requiresGps: false,
          requiresDamageInspection: false,
          alreadySigned: isAlreadySigned("MESAFELI_SATIS_SOZLESMESI"),
        });
      }
    }

    // 5. real_estate
    else if (sectorFamily === "real_estate") {
      rules.push({
        docType: "TASINMAZ_GOSTERME_BELGESI",
        title: DEFAULT_LEGAL_TEMPLATES.TASINMAZ_GOSTERME_BELGESI.title,
        description: "30442 Sayılı Taşınmaz Ticareti Yönetmeliği Madde 19 uyarınca GPS damgalı zorunlu yer gösterme sözleşmesi",
        isRequired: true,
        requiresSignature: true,
        signatureType: "CANVAS_BIOMETRIC",
        flowContext,
        requiresScroll: true,
        requiresOtp: false,
        requiresGps: true,
        requiresDamageInspection: false,
        alreadySigned: isAlreadySigned("TASINMAZ_GOSTERME_BELGESI"),
      });
    }

    // 6. hospitality & restaurant_food
    else if (sectorFamily === "hospitality" || sectorFamily === "restaurant_food") {
      if (context.hasPrepayment || (context.depositAmount && context.depositAmount > 0)) {
        rules.push({
          docType: "HIZMET_VE_IPTAL_SOZLESMESI",
          title: DEFAULT_LEGAL_TEMPLATES.HIZMET_VE_IPTAL_SOZLESMESI.title,
          description: "Depozito iade koşulları ve no-show kesinti sözleşmesi",
          isRequired: true,
          requiresSignature: true,
          signatureType: "CANVAS_BIOMETRIC",
          flowContext: "CHECKOUT",
          requiresScroll: true,
          requiresOtp: false,
          requiresGps: false,
          requiresDamageInspection: false,
          alreadySigned: isAlreadySigned("HIZMET_VE_IPTAL_SOZLESMESI"),
        });
      }

      rules.push({
        docType: "ON_BILGILENDIRME_FORMU",
        title: "Türk Gıda Kodeksi Kapsamında Alerjen Bildirim ve Onay Formu",
        description: "Türk Gıda Kodeksi uyarınca 14 temel alerjen uyarısı ve misafir beyanı",
        isRequired: true,
        requiresSignature: false,
        signatureType: "CANVAS_BIOMETRIC",
        flowContext: "CHECKOUT",
        requiresScroll: false,
        requiresOtp: false,
        requiresGps: false,
        requiresDamageInspection: false,
        alreadySigned: isAlreadySigned("ON_BILGILENDIRME_FORMU"),
      });
    }

    // 7. education & professional
    else if (sectorFamily === "education") {
      rules.push({
        docType: "MESAFELI_SATIS_SOZLESMESI",
        title: "Özel Kurs ve Eğitim Hizmet Sözleşmesi",
        description: "Özel kurs ve eğitim hizmeti katılım ve ödeme sözleşmesi",
        isRequired: true,
        requiresSignature: true,
        signatureType: "CANVAS_BIOMETRIC",
        flowContext,
        requiresScroll: true,
        requiresOtp: false,
        requiresGps: false,
        requiresDamageInspection: false,
        alreadySigned: isAlreadySigned("MESAFELI_SATIS_SOZLESMESI"),
      });
    } else if (sectorFamily === "professional") {
      rules.push({
        docType: "GIZLILIK_VE_NDA",
        title: DEFAULT_LEGAL_TEMPLATES.GIZLILIK_VE_NDA.title,
        description: "Karşılıklı ticari gizlilik sözleşmesi (NDA) ve profesyonel SLA taahhüdü",
        isRequired: true,
        requiresSignature: true,
        signatureType: "QUALIFIED_E_SIGNATURE",
        flowContext,
        requiresScroll: true,
        requiresOtp: true,
        requiresGps: false,
        requiresDamageInspection: false,
        alreadySigned: isAlreadySigned("GIZLILIK_VE_NDA"),
      });
    }

    return rules;
  }
}
