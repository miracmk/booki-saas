/**
 * BooKi SaaS - Ceza-Geçirmez Dijital Sözleşme & E-İmza Entegrasyon Motoru (LegalTech)
 * Mevzuat: HMK m. 199/202/205, TBK m. 20-25 (GİK), TKHK m. 5 (Haksız Şart), 5070 E-İmza, 6698 KVKK
 */

export type SectorFamily =
  | "beauty_wellness"
  | "restaurant_food"
  | "health_clinical"
  | "sports_fitness"
  | "automotive"
  | "hospitality"
  | "experience"
  | "education"
  | "professional"
  | "real_estate";

export type DocumentType =
  | "KVKK_AYDINLATMA"
  | "KVKK_OZEL_NITELIKLI_RIZA"
  | "MESAFELI_SATIS_SOZLESMESI"
  | "ON_BILGILENDIRME_FORMU"
  | "AYDINLATILMIS_ONAM_FORMU"
  | "SAGLIK_BEYANI_WAIVER"
  | "TESLIM_TESELLUM_HASAR_TUTANAGI"
  | "TASINMAZ_GOSTERME_BELGESI"
  | "GIZLILIK_VE_NDA"
  | "HIZMET_VE_IPTAL_SOZLESMESI"
  | "ALERJI_VE_ISLEM_RIZA_FORMU"
  | "OZEL_NITELIKLI_SAGLIK_VERISI_RIZASI"
  | "SAGLIK_BEYANI_VE_SORUMLULUK_REDDI"
  | "ARAC_TESLIM_TESELLUM_HASAR_TUTANAGI"
  | "IS_EMRI_VE_TESLIMAT_ONAYI"
  | "NO_SHOW_VE_IPTAL_SOZLESMESI"
  | "ALERJEN_BILDIRIM_ONAYI"
  | "EGITIM_SOZLESMESI"
  | "NDA_VE_DANISMANLIK_SLA";

// Backward compatibility alias
export type DocType = DocumentType;

export type SignatureType =
  | "CANVAS_BIOMETRIC"
  | "SMS_OTP_VERIFIED"
  | "QUALIFIED_E_SIGNATURE";

export type SignatureLevel = SignatureType | "SIMPLE_BIOMETRIC" | "OTP_2FA" | "QUALIFIED_E_SIGN";

export type DeliveryStatus = "PENDING" | "SENT" | "DELIVERED" | "FAILED";
export type DeliveryChannel = "EMAIL" | "SMS" | "WHATSAPP";

export type FlowContext = "CHECKOUT" | "CHECK_IN" | "CHECK_OUT" | "ONBOARDING";

export interface LegalTemplateDto {
  id: string;
  tenantId?: string | null;
  sectorFamily: SectorFamily;
  blueprintType?: string | null;
  docType: DocumentType;
  version: number;
  title: string;
  description?: string | null;
  bodyTemplateMarkdown: string;
  requiresSignature: boolean;
  requiresOtp?: boolean;
  requiresGps?: boolean;
  isRequired: boolean;
  isActive: boolean;
  signatureLevel?: SignatureLevel;
  validityDays?: number | null;
}

export interface BiometricMetadata {
  strokeCount: number;
  durationMs: number;
  pointCount: number;
  devicePixelRatio?: number;
  screenResolution?: string;
  hasPressureSupport?: boolean;
}

export interface BookingContextData {
  tenantId: string;
  tenantName?: string;
  tenantLegalName?: string;
  tenantTaxId?: string;       // VKN
  tenantMersis?: string;      // MERSİS No
  tenantAddress?: string;
  tenantPhone?: string;

  bookingId: string;
  serviceId?: string;
  serviceName: string;
  serviceCategory?: string;
  servicePrice: number;
  currency?: string;
  depositAmount?: number;
  hasPrepayment: boolean;
  appointmentDateTime: string;
  staffName?: string;

  // GİK Uyumlu Kademeli İptal ve Ceza Parametreleri (TBK m. 20-25 & TKHK m. 5)
  cancellationDeadlineHours?: number; // Örn: 24 saat
  penaltyRatePercent?: number;        // Örn: %50 veya %100

  // Müşteri Kimlik ve İletişim Bilgileri
  customerId: string;
  customerFullName?: string;
  customerName?: string;
  customerNationalId?: string;        // TCKN
  customerPhone: string;
  customerEmail: string;
  isFirstVisit?: boolean;

  // Sektöre Özel Parametreler
  complicationsList?: string[];       // Klinik / Estetik Aydınlatılmış Onam
  alternativeTreatments?: string[];   // Tıbbi alternatifler
  allergies?: string[];               // Alerji beyanları
  medicalNotes?: string[];
  propertyParcelAddress?: string;     // Emlak: Ada/Parsel ve Açık Adres
  propertyAddress?: string;
  propertyListingNo?: string;         // Emlak portföy no
  vehicleVinPlate?: string;           // Otomotiv: Plaka ve Şasi No
  vehiclePlate?: string;
  vehicleKm?: number;
  vehicleBrandModel?: string;

  // Backwards compatibility aliases
  businessName?: string;
  businessLegalName?: string;
  businessMersisNo?: string;
  businessVkn?: string;
  businessAddress?: string;
  businessPhone?: string;
}

export interface DamagePoint {
  id: string;
  view: "front" | "back" | "left" | "right" | "top";
  x: number;
  y: number;
  damageType: "scratch" | "dent" | "broken" | "paint";
  notes?: string;
}

export interface SignContractRequest {
  templateId: string;
  docType: DocumentType;
  tenantId: string;
  bookingId?: string;
  customerId: string;

  // Signer
  signerName: string;
  signerNationalId?: string;
  signerPhone: string;
  signerEmail?: string;

  // Signature Data
  signatureType?: SignatureType;
  signatureLevel?: SignatureLevel;
  signatureCanvasSvg?: string;
  signatureCanvasBase64?: string;
  biometricMetadata?: BiometricMetadata;
  acceptedTerms: boolean;
  scrollCompleted: boolean;

  // 2FA OTP
  otpMobileNumber?: string;
  otpCode?: string;
  otpReferenceCode?: string;

  // GPS (Emlak)
  gpsLatitude?: number;
  gpsLongitude?: number;
  gpsAccuracyMeters?: number;

  // Otomotiv Hasar Şeması
  damagePoints?: DamagePoint[];

  context: BookingContextData;
}

export interface SignatureAuditPayload {
  contractId: string;
  documentType: DocumentType;
  docType?: DocumentType;
  signerName: string;
  signerNationalIdMasked?: string;
  signerPhoneMasked?: string;
  ipAddress: string;
  userAgent: string;
  screenResolution?: string;
  sessionId?: string;
  timestampUtc: string;
  signatureType: SignatureType;
  signatureLevel?: SignatureLevel;
  contentSha256: string;
  signatureSha256?: string;
  signatureCanvasSha256?: string;
  biometrics?: BiometricMetadata;
  otp?: {
    referenceCode: string;
    verifiedAtUtc: string;
    deliveryChannel: "SMS";
    mobileNumberMasked?: string;
  };
  gps?: {
    latitude: number;
    longitude: number;
    accuracyMeters?: number;
    timestampUtc: string;
  };
  automotiveDamage?: {
    totalPoints: number;
    points: DamagePoint[];
  };
  rfc3161Token?: string;
}

export interface ContractDeliveryRecord {
  id: string;
  contractId: string;
  channel: DeliveryChannel;
  destination: string;
  status: DeliveryStatus;
  trackingId?: string;
  deliveredAt?: string;
  createdAt: string;
}

export interface SignedContractResponse {
  success: boolean;
  contractId: string;
  docType: DocumentType;
  title: string;
  sha256Hash: string;
  signedPdfUrl: string;
  signedAtUtc: string;
  verificationUrl: string;
  deliveries?: ContractDeliveryRecord[];
  auditTrail: {
    blockHash: string;
    ipAddress: string;
    signatureType?: SignatureType;
    signatureLevel?: SignatureLevel;
    timestampToken?: string;
  };
}

export interface ContractVerificationResponse {
  isValid: boolean;
  sha256Hash: string;
  title: string;
  docType: DocumentType;
  sectorFamily: SectorFamily;
  tenantName: string;
  signerName: string;
  signedAtUtc: string;
  signatureType?: SignatureType;
  signatureLevel?: SignatureLevel;
  ipAddress: string;
  hasOtpVerification: boolean;
  hasGpsVerification: boolean;
  blockHash: string;
  timestampToken?: string;
  legalNotice: string;
  deliveries?: ContractDeliveryRecord[];
}

export interface RequiredContractRule {
  docType: DocumentType;
  title: string;
  description: string;
  isRequired: boolean;
  requiresSignature: boolean;
  signatureType: SignatureType;
  signatureLevel?: SignatureLevel;
  flowContext: FlowContext;
  requiresScroll: boolean;
  requiresOtp: boolean;
  requiresGps: boolean;
  requiresDamageInspection: boolean;
  alreadySigned?: boolean;
  signedContractId?: string;
}
