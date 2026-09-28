import { BookingContextData } from "@shared/legalTypes";

/**
 * BooKi Dynamic Contract Compiler Engine
 * Hukuki Dayanak: TBK m. 20-25 (GİK), TKHK m. 5 (Haksız Şart Savunması)
 * Müşteri ve randevu parametrelerini dinamik ve değiştirilemez şekilde şablona bağlar.
 */
export class ContractCompilerService {
  /**
   * Compiles the markdown contract template with contextual data.
   */
  public static compile(templateMarkdown: string, context: BookingContextData): string {
    const tenantName = context.tenantName || context.businessName || "BooKi İşletmesi";
    const tenantLegalName = context.tenantLegalName || context.businessLegalName || tenantName;
    const tenantTaxId = context.tenantTaxId || context.businessVkn || "Belirtilmedi";
    const tenantMersis = context.tenantMersis || context.businessMersisNo || "Belirtilmedi";
    const tenantAddress = context.tenantAddress || context.businessAddress || "Belirtilmedi";
    const tenantPhone = context.tenantPhone || context.businessPhone || "Belirtilmedi";

    const customerFullName = context.customerFullName || context.customerName || "Sayın Müşteri";
    const customerMaskedTckn = context.customerNationalId ? this.maskTckn(context.customerNationalId) : "***********";
    const customerPhone = context.customerPhone || "Belirtilmedi";
    const customerEmail = context.customerEmail || "Belirtilmedi";

    const serviceName = context.serviceName || "Hizmet";
    const servicePriceFormatted = this.formatCurrency(context.servicePrice || 0, context.currency || "TRY");
    const depositAmountFormatted = this.formatCurrency(context.depositAmount || 0, context.currency || "TRY");
    const appointmentDateTimeFormatted = this.formatDateTime(context.appointmentDateTime);

    const cancellationDeadline = context.cancellationDeadlineHours ? `${context.cancellationDeadlineHours} saat` : "24 saat";
    const penaltyRate = context.penaltyRatePercent ? `%${context.penaltyRatePercent}` : "%50";

    const complicationsListFormatted = context.complicationsList && context.complicationsList.length > 0
      ? context.complicationsList.map((c: string, i: number) => `${i + 1}. ${c}`).join("\n")
      : "- Standart işlem hassasiyeti ve geçici reaksiyonlar hekim tarafından sözlü açıklanmıştır.";

    const propertyParcelAddress = context.propertyParcelAddress || context.propertyAddress || "Portföy adresi teyit edilmiştir.";
    const vehicleVinPlate = context.vehicleVinPlate || context.vehiclePlate || "34 BK 2026";

    // 1. Primary Master Variables
    const replacementMap: Record<string, string> = {
      "{{TENANT_NAME}}": tenantName,
      "{{TENANT_LEGAL_NAME}}": tenantLegalName,
      "{{TENANT_TAX_ID}}": tenantTaxId,
      "{{TENANT_MERSIS}}": tenantMersis,
      "{{TENANT_ADDRESS}}": tenantAddress,
      "{{TENANT_PHONE}}": tenantPhone,

      "{{CUSTOMER_FULL_NAME}}": customerFullName,
      "{{CUSTOMER_MASKED_TCKN}}": customerMaskedTckn,
      "{{CUSTOMER_PHONE}}": customerPhone,
      "{{CUSTOMER_EMAIL}}": customerEmail,

      "{{SERVICE_NAME}}": serviceName,
      "{{SERVICE_PRICE}}": servicePriceFormatted,
      "{{DEPOSIT_AMOUNT}}": depositAmountFormatted,
      "{{APPOINTMENT_DATE_TIME}}": appointmentDateTimeFormatted,
      "{{CANCELLATION_DEADLINE}}": cancellationDeadline,
      "{{PENALTY_RATE}}": penaltyRate,

      "{{COMPLICATIONS_LIST}}": complicationsListFormatted,
      "{{PROPERTY_PARCEL_ADDRESS}}": propertyParcelAddress,
      "{{VEHICLE_VIN_PLATE}}": vehicleVinPlate,

      // System date / time
      "{{SYSTEM_DATE}}": new Date().toLocaleDateString("tr-TR", { year: "numeric", month: "long", day: "numeric" }),
      "{{SYSTEM_TIME}}": new Date().toLocaleTimeString("tr-TR", { hour: "2-digit", minute: "2-digit" }),
    };

    let result = templateMarkdown;

    // Apply primary replacements
    for (const [placeholder, value] of Object.entries(replacementMap)) {
      result = result.split(placeholder).join(value);
    }

    // 2. Handlebars-style object fallback: {{customer.name}}, {{tenant.legalName}}, etc.
    const nestedData: Record<string, any> = {
      tenant: {
        legalName: tenantLegalName,
        address: tenantAddress,
        phone: tenantPhone,
        vkn: tenantTaxId,
        mersisNo: tenantMersis,
      },
      customer: {
        name: customerFullName,
        nationalIdMasked: customerMaskedTckn,
        phone: customerPhone,
        email: customerEmail,
      },
      booking: {
        id: context.bookingId,
        serviceName,
        priceFormatted: servicePriceFormatted,
        depositFormatted: depositAmountFormatted,
        hasPrepayment: context.hasPrepayment,
        appointmentDateFormatted: appointmentDateTimeFormatted,
        staffName: context.staffName || "Yetkili Uzman",
      },
      health: {
        allergies: context.allergies && context.allergies.length > 0 ? context.allergies.join(", ") : "Bilinen alerji bildirilmemiştir.",
      },
      realEstate: {
        propertyAddress: propertyParcelAddress,
        listingNo: context.propertyListingNo || "PORTFOY-01",
      },
      automotive: {
        plate: vehicleVinPlate,
        brandModel: context.vehicleBrandModel || "Belirtilmedi",
        km: context.vehicleKm ? `${context.vehicleKm.toLocaleString("tr-TR")} KM` : "Belirtilmedi",
      },
      system: {
        currentDateTr: new Date().toLocaleDateString("tr-TR", { year: "numeric", month: "long", day: "numeric" }),
        currentTimeTr: new Date().toLocaleTimeString("tr-TR", { hour: "2-digit", minute: "2-digit" }),
      },
    };

    // Conditional blocks: {{#if condition}}...{{else}}...{{/if}}
    result = result.replace(/\{\{#if\s+([a-zA-Z0-9_.]+)\}\}([\s\S]*?)(?:\{\{else\}\}([\s\S]*?))?\{\{\/if\}\}/g, (_, key, ifContent, elseContent) => {
      const val = key.split(".").reduce((acc: any, part: string) => (acc ? acc[part] : undefined), nestedData);
      return val ? ifContent : (elseContent || "");
    });

    // Interpolate remaining {{path.to.var}}
    result = result.replace(/\{\{([a-zA-Z0-9_.]+)\}\}/g, (_, key) => {
      const val = key.split(".").reduce((acc: any, part: string) => (acc ? acc[part] : undefined), nestedData);
      return val !== undefined && val !== null ? String(val) : "";
    });

    return result.trim();
  }

  public static maskTckn(tckn: string): string {
    const cleaned = tckn.replace(/\D/g, "");
    if (cleaned.length !== 11) return tckn;
    return `${cleaned.slice(0, 3)}*****${cleaned.slice(8)}`;
  }

  public static formatCurrency(amount: number, currency: string = "TRY"): string {
    return new Intl.NumberFormat("tr-TR", {
      style: "currency",
      currency,
      minimumFractionDigits: 2,
    }).format(amount);
  }

  public static formatDateTime(dateStr?: string): string {
    if (!dateStr) return "-";
    try {
      const d = new Date(dateStr);
      return d.toLocaleDateString("tr-TR", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    } catch {
      return dateStr;
    }
  }
}
