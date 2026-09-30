import { BookingContextData, SectorFamily } from "@shared/legalTypes";

export interface DynamicFormFieldDefinition {
  name: string;
  label: string;
  type: "text" | "number" | "select" | "multiselect" | "checkbox" | "textarea" | "date";
  placeholder?: string;
  required: boolean;
  options?: { value: string; label: string }[];
  helpText?: string;
  category: "kimlik" | "medikal" | "arac" | "konaklama" | "emlak" | "diger";
}

/**
 * BooKi Sector Dynamic Booking Fields Service
 * Her sektörün kendine özgü veri toplama ve yasal beyan gereksinimlerini tanımlar.
 */
export class SectorDynamicFieldsService {
  /**
   * Returns sector-specific booking form field definitions.
   */
  public static getFieldsForSector(sectorFamily: SectorFamily): DynamicFormFieldDefinition[] {
    switch (sectorFamily) {
      case "health_clinical":
        return [
          {
            name: "chronicDiseases",
            label: "Kronik Hastalıklar (Diyabet, Hipertansiyon, Kalp vb.)",
            type: "textarea",
            placeholder: "Varsa kronik hastalıklarınızı belirtiniz (yoksa 'Yok' yazınız)",
            required: true,
            category: "medikal",
            helpText: "Hekim müdahalesi öncesi yasal anamnez zorunluluğudur.",
          },
          {
            name: "activeMedications",
            label: "Düzenli Kullanılan İlaçlar (Kan Sulandırıcı, Aspirin vb.)",
            type: "textarea",
            placeholder: "Kullandığınız ilaçları ve dozlarını yazınız",
            required: true,
            category: "medikal",
          },
          {
            name: "allergiesInput",
            label: "Alerji Beyanı (Lokal Anestezi, Antibiyotik vb.)",
            type: "text",
            placeholder: "Bilinen alerjileriniz",
            required: true,
            category: "medikal",
          },
          {
            name: "previousSurgeries",
            label: "Geçirilmiş Ameliyatlar ve Müdahaleler",
            type: "textarea",
            placeholder: "Son 5 yılda geçirdiğiniz cerrahi operasyonlar",
            required: false,
            category: "medikal",
          },
          {
            name: "emergencyContact",
            label: "Acil Durum Yakını Adı ve Telefonu",
            type: "text",
            placeholder: "Örn: Ahmet Yılmaz - 0532 XXX XX XX",
            required: true,
            category: "kimlik",
          },
        ];

      case "beauty_wellness":
        return [
          {
            name: "skinType",
            label: "Cilt Tipi (Fitzpatrick Skalası)",
            type: "select",
            required: true,
            category: "medikal",
            options: [
              { value: "TYPE_1", label: "Tip I - Çok Açık Renkli, Kolay Yanar, Bronzlaşmaz" },
              { value: "TYPE_2", label: "Tip II - Açık Renkli, Kolay Yanar, Zor Bronzlaşır" },
              { value: "TYPE_3", label: "Tip III - Buğday Tenli, Bazen Yanar, Yavaş Bronzlaşır" },
              { value: "TYPE_4", label: "Tip IV - Koyu Buğday / Esmer, Kolay Bronzlaşır" },
              { value: "TYPE_5_6", label: "Tip V-VI - Koyu Esmer / Siyah Ten" },
            ],
          },
          {
            name: "roaccutaneHistory",
            label: "Son 6 Ayda Roaccutane / İzotretinoin Kullandınız mı?",
            type: "select",
            required: true,
            category: "medikal",
            options: [
              { value: "NO", label: "Hayır, kullanmadım" },
              { value: "YES_ACTIVE", label: "Evet, halen kullanıyorum" },
              { value: "YES_PAST", label: "Evet, son 6 ay içinde kullandım ve bıraktım" },
            ],
            helpText: "Lazer ve soyucu işlemlerde kalıcı leke/skar riskini önlemek için zorunludur.",
          },
          {
            name: "pregnancyStatus",
            label: "Hamilelik veya Emzirme Durumu",
            type: "select",
            required: true,
            category: "medikal",
            options: [
              { value: "NONE", label: "Hamilelik veya emzirme bulunmuyor" },
              { value: "PREGNANT", label: "Hamileyim" },
              { value: "BREASTFEEDING", label: "Bebeğimi emziriyorum" },
            ],
          },
          {
            name: "keloidTendency",
            label: "Aşırı Yara İzi / Keloid Eğilimi Var mı?",
            type: "checkbox",
            required: false,
            category: "medikal",
          },
        ];

      case "hospitality":
        return [
          {
            name: "nationalIdOrPassport",
            label: "T.C. Kimlik No veya Yabancı Pasaport No",
            type: "text",
            placeholder: "11 Haneli TCKN veya Pasaport Numarası",
            required: true,
            category: "konaklama",
            helpText: "1774 Sayılı Kimlik Bildirme Kanunu (KBS) uyarınca zorunludur.",
          },
          {
            name: "guestCountAdults",
            label: "Yetişkin Misafir Sayısı",
            type: "number",
            required: true,
            category: "konaklama",
          },
          {
            name: "guestCountChildren",
            label: "Çocuk Misafir Sayısı (0-12 Yaş)",
            type: "number",
            required: false,
            category: "konaklama",
          },
          {
            name: "estimatedCheckInTime",
            label: "Tahmini Tesise Giriş Saati",
            type: "text",
            placeholder: "Örn: 15:30",
            required: false,
            category: "konaklama",
          },
          {
            name: "specialRequests",
            label: "Özel Oda Talepleri (Sessiz oda, zemin kat vb.)",
            type: "textarea",
            required: false,
            category: "konaklama",
          },
        ];

      case "automotive":
        return [
          {
            name: "vehiclePlate",
            label: "Araç Plaka Numarası",
            type: "text",
            placeholder: "Örn: 34 BK 2026",
            required: true,
            category: "arac",
          },
          {
            name: "vehicleBrandModel",
            label: "Araç Marka, Model ve Model Yılı",
            type: "text",
            placeholder: "Örn: 2022 Volkswagen Golf 1.5 eTSI",
            required: true,
            category: "arac",
          },
          {
            name: "vehicleVin",
            label: "Şasi Numarası (VIN)",
            type: "text",
            placeholder: "17 Karakterli Şasi No (Opsiyonel)",
            required: false,
            category: "arac",
          },
          {
            name: "vehicleCurrentKm",
            label: "Mevcut Kilometre Sayacı (KM)",
            type: "number",
            placeholder: "Örn: 65400",
            required: true,
            category: "arac",
          },
          {
            name: "reportedFaults",
            label: "Müşteri Şikayeti / Talep Edilen Bakım Ayrıntısı",
            type: "textarea",
            placeholder: "Yaşanan ses, uyarı lambası veya talep edilen periyodik bakım detayları",
            required: true,
            category: "arac",
          },
        ];

      case "real_estate":
        return [
          {
            name: "listingId",
            label: "İlan / Portföy Referans No",
            type: "text",
            placeholder: "Örn: PORT-8890",
            required: false,
            category: "emlak",
          },
          {
            name: "propertyAddress",
            label: "Görülecek Taşınmazın Açık Adresi",
            type: "textarea",
            placeholder: "İlçe, Mahalle, Ada, Parsel veya Bina No",
            required: true,
            category: "emlak",
            helpText: "30442 Sayılı Yönetmelik Madde 19 gereğince açıkça belirtilmelidir.",
          },
        ];

      case "restaurant_food":
        return [
          {
            name: "guestCount",
            label: "Kişi Sayısı",
            type: "number",
            required: true,
            category: "diger",
          },
          {
            name: "allergensDeclared",
            label: "Gıda Alerjenleri (Gluten, Laktoz, Fıstık vb.)",
            type: "textarea",
            placeholder: "Masada gıda alerjisi veya intoleransı olan misafirler için belirtiniz",
            required: false,
            category: "diger",
          },
        ];

      default:
        return [];
    }
  }

  /**
   * Enriches standard BookingContextData with user-submitted sector fields.
   */
  public static enrichContextWithSectorFields(
    baseContext: BookingContextData,
    submittedFields: Record<string, any>
  ): BookingContextData {
    const enriched = { ...baseContext };

    if (submittedFields.vehiclePlate) {
      enriched.vehiclePlate = submittedFields.vehiclePlate;
      enriched.vehicleVinPlate = submittedFields.vehicleVin || submittedFields.vehiclePlate;
    }
    if (submittedFields.vehicleBrandModel) {
      enriched.vehicleBrandModel = submittedFields.vehicleBrandModel;
    }
    if (submittedFields.vehicleCurrentKm) {
      enriched.vehicleKm = Number(submittedFields.vehicleCurrentKm);
    }
    if (submittedFields.propertyAddress) {
      enriched.propertyAddress = submittedFields.propertyAddress;
      enriched.propertyParcelAddress = submittedFields.propertyAddress;
    }
    if (submittedFields.listingId) {
      enriched.propertyListingNo = submittedFields.listingId;
    }
    if (submittedFields.allergiesInput) {
      enriched.allergies = [submittedFields.allergiesInput];
    }
    if (submittedFields.chronicDiseases || submittedFields.reportedFaults) {
      enriched.medicalNotes = [
        submittedFields.chronicDiseases ? `Kronik: ${submittedFields.chronicDiseases}` : "",
        submittedFields.activeMedications ? `İlaç: ${submittedFields.activeMedications}` : "",
        submittedFields.reportedFaults ? `Servis Şikayeti: ${submittedFields.reportedFaults}` : "",
      ].filter(Boolean);
    }

    return enriched;
  }
}
