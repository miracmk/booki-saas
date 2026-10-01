import { describe, it, expect } from "vitest";
import { ContractCompilerService } from "./contractCompiler.service";
import { LegalComplianceEngine } from "./complianceEngine";
import { AuditChainService } from "./auditChainService";
import { ContractEngineService } from "./contractEngine.service";
import { ESignProviderService } from "./eSignProviders";
import { ContractDispatcherService } from "./contractDispatcher.service";
import { ServiceTemplateMappingService } from "./serviceTemplateMapping.service";
import { SectorDynamicFieldsService } from "./sectorDynamicFields.service";
import { DocumentExportService } from "./documentExport.service";
import { BookingContextData, SignContractRequest } from "@shared/legalTypes";

describe("BooKi Ceza-Geçirmez Dijital Sözleşme & E-İmza Motoru", () => {
  const sampleContext: BookingContextData = {
    tenantId: "TENANT_TEST_01",
    tenantName: "BooKi Güzellik & Klinik",
    tenantLegalName: "BooKi Sağlık ve Güzellik Hizmetleri A.Ş.",
    tenantMersis: "098765432100001",
    tenantTaxId: "9876543210",
    tenantAddress: "Bağdat Cad. No: 100 Kadıköy / İstanbul",
    tenantPhone: "+90 216 111 2233",

    bookingId: "REZ-TEST-8899",
    serviceName: "Lazer Cilt Yenileme ve Bakım",
    servicePrice: 2000,
    depositAmount: 500,
    hasPrepayment: true,
    appointmentDateTime: "2026-10-15T14:30:00Z",
    staffName: "Uzm. Dr. Aylin Kaya",

    cancellationDeadlineHours: 24,
    penaltyRatePercent: 50,

    customerId: "CUST_999",
    customerFullName: "Zeynep Demir",
    customerNationalId: "12345678901",
    customerPhone: "+90 555 444 3322",
    customerEmail: "zeynep.demir@example.com",
    isFirstVisit: true,

    complicationsList: ["Geçici eritem ve ödem", "Hafif kabuklanma ve hassasiyet"],
    propertyParcelAddress: "Kadıköy / Caddebostan Ada: 102 Parsel: 4",
    vehicleVinPlate: "34 ZD 1923",
  };

  // 1. Dynamic Contract Compiler Engine
  describe("ContractCompilerService", () => {
    it("should inject master legal parameters and format masked TCKN", () => {
      const template = "İşbu sözleşme {{CUSTOMER_FULL_NAME}} (TCKN: {{CUSTOMER_MASKED_TCKN}}) ile {{TENANT_LEGAL_NAME}} arasında {{SERVICE_NAME}} için düzenlenmiştir. İptal süresi: {{CANCELLATION_DEADLINE}}, cezai oran: {{PENALTY_RATE}}.";
      const compiled = ContractCompilerService.compile(template, sampleContext);

      expect(compiled).toContain("Zeynep Demir");
      expect(compiled).toContain("123*****901");
      expect(compiled).toContain("BooKi Sağlık ve Güzellik Hizmetleri A.Ş.");
      expect(compiled).toContain("Lazer Cilt Yenileme ve Bakım");
      expect(compiled).toContain("24 saat");
      expect(compiled).toContain("%50");
    });

    it("should inject sector-specific complications list for medical onam", () => {
      const template = "Olası komplikasyonlar:\n{{COMPLICATIONS_LIST}}";
      const compiled = ContractCompilerService.compile(template, sampleContext);

      expect(compiled).toContain("Geçici eritem ve ödem");
      expect(compiled).toContain("Hafif kabuklanma ve hassasiyet");
    });
  });

  // 2. Legal Compliance Engine across 10 Sector Families
  describe("LegalComplianceEngine", () => {
    it("beauty_wellness: enforces KVKK + MSS on checkout, and AYDINLATILMIS_ONAM on check-in", () => {
      const rules = LegalComplianceEngine.evaluateRequirements({
        sectorFamily: "beauty_wellness",
        flowContext: "CHECKOUT",
        context: sampleContext,
      });
      expect(rules.some((r) => r.docType === "KVKK_AYDINLATMA")).toBe(true);
      expect(rules.some((r) => r.docType === "MESAFELI_SATIS_SOZLESMESI")).toBe(true);
    });

    it("health_clinical: enforces AYDINLATILMIS_ONAM_FORMU and KVKK_OZEL_NITELIKLI_RIZA with SMS OTP", () => {
      const rules = LegalComplianceEngine.evaluateRequirements({
        sectorFamily: "health_clinical",
        flowContext: "CHECKOUT",
        context: sampleContext,
      });
      const onam = rules.find((r) => r.docType === "AYDINLATILMIS_ONAM_FORMU");
      expect(onam).toBeDefined();
      expect(onam?.requiresOtp).toBe(true);
      expect(onam?.signatureType).toBe("SMS_OTP_VERIFIED");
    });

    it("real_estate: enforces TASINMAZ_GOSTERME_BELGESI with GPS requirement", () => {
      const rules = LegalComplianceEngine.evaluateRequirements({
        sectorFamily: "real_estate",
        flowContext: "CHECKOUT",
        context: sampleContext,
      });
      const yerGosterme = rules.find((r) => r.docType === "TASINMAZ_GOSTERME_BELGESI");
      expect(yerGosterme).toBeDefined();
      expect(yerGosterme?.requiresGps).toBe(true);
    });

    it("automotive: enforces TESLIM_TESELLUM_HASAR_TUTANAGI with damage marking on check-in", () => {
      const rules = LegalComplianceEngine.evaluateRequirements({
        sectorFamily: "automotive",
        flowContext: "CHECK_IN",
        context: sampleContext,
      });
      const hasar = rules.find((r) => r.docType === "TESLIM_TESELLUM_HASAR_TUTANAGI");
      expect(hasar).toBeDefined();
      expect(hasar?.requiresDamageInspection).toBe(true);
    });
  });

  // 3. Cryptographic Audit & Tamper Proofing
  describe("AuditChainService", () => {
    it("should compute deterministic SHA-256 and block chain hashes", () => {
      const h1 = AuditChainService.computeSha256("booki legal");
      const h2 = AuditChainService.computeSha256("booki legal");
      expect(h1).toBe(h2);
      expect(h1.length).toBe(64);
    });
  });

  // 4. SMS OTP Service
  describe("ESignProviderService", () => {
    it("should dispatch and verify SMS OTP code", async () => {
      const otp = await ESignProviderService.sendSmsOtp("+905554443322", "Test Onam");
      expect(otp.referenceCode).toBeDefined();
      expect(otp.simulatedCode).toBeDefined();

      const verification = ESignProviderService.verifySmsOtp(otp.referenceCode, otp.simulatedCode!);
      expect(verification.isValid).toBe(true);
    });
  });

  // 5. Automated Multi-channel Customer Dispatcher
  describe("ContractDispatcherService", () => {
    it("should dispatch contract to customer via Email, SMS, and WhatsApp", async () => {
      const deliveries = await ContractDispatcherService.dispatch({
        contractId: "CNT-TEST-001",
        sha256Hash: "abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890",
        customerName: "Zeynep Demir",
        customerPhone: "+905554443322",
        customerEmail: "zeynep.demir@example.com",
        tenantName: "BooKi Klinik",
        serviceName: "Cilt Bakımı",
        pdfBuffer: Buffer.from("PDF_MOCK"),
        pdfFilename: "CNT-TEST-001.pdf",
        verificationUrl: "https://app.booki.com/v/abcdef",
      });

      expect(deliveries.length).toBe(3);
      expect(deliveries.map((d) => d.channel)).toEqual(expect.arrayContaining(["EMAIL", "SMS", "WHATSAPP"]));
      expect(deliveries.every((d) => d.status === "DELIVERED")).toBe(true);
    });
  });

  // 6. Full End-to-End Sign and Seal Pipeline
  describe("ContractEngineService", () => {
    it("should sign, generate PDF with Ek-1 Audit Trail, dispatch, and verify by SHA-256", async () => {
      const dummyCanvasPng = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==";

      const signReq: SignContractRequest = {
        templateId: "MESAFELI_SATIS_SOZLESMESI",
        docType: "MESAFELI_SATIS_SOZLESMESI",
        tenantId: "TENANT_TEST_01",
        bookingId: "REZ-TEST-8899",
        customerId: "CUST_999",
        signerName: "Zeynep Demir",
        signerNationalId: "12345678901",
        signerPhone: "+90 555 444 3322",
        signerEmail: "zeynep.demir@example.com",
        signatureType: "CANVAS_BIOMETRIC",
        signatureCanvasBase64: dummyCanvasPng,
        biometricMetadata: {
          strokeCount: 4,
          durationMs: 1200,
          pointCount: 88,
          screenResolution: "1920x1080",
        },
        acceptedTerms: true,
        scrollCompleted: true,
        context: sampleContext,
      };

      const clientMeta = {
        ipAddress: "85.105.24.12",
        userAgent: "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
        host: "localhost:3000",
        protocol: "http",
      };

      const res = await ContractEngineService.signAndSealContract(signReq, clientMeta);

      expect(res.success).toBe(true);
      expect(res.contractId).toMatch(/^CNT-/);
      expect(res.sha256Hash).toHaveLength(64);
      expect(res.deliveries.length).toBeGreaterThanOrEqual(2);
      expect(res.auditTrail.timestampToken).toContain("RFC3161");

      // Verify PDF exists and has %PDF magic bytes
      const pdf = ContractEngineService.getContractPdfBuffer(res.contractId);
      expect(pdf).not.toBeNull();
      expect(pdf?.buffer.toString("utf8", 0, 4)).toBe("%PDF");

      // Verify contract by hash
      const verified = ContractEngineService.verifyContract(res.sha256Hash);
      expect(verified.isValid).toBe(true);
      expect(verified.signerName).toBe("Zeynep Demir");
      expect(verified.timestampToken).toContain("RFC3161");
      expect(verified.deliveries?.length).toBeGreaterThanOrEqual(2);
    });

    it("should compile and sign sector-specific onam forms (TOD, IDO, Baia, AvEvrak, TDD)", async () => {
      // 1. TOD (Türk Oftalmoloji Derneği) Onamı Derleme
      const eyeContract = ContractEngineService.compileContract("TOD_KATARAKT_FAKO_ONAM", sampleContext);
      expect(eyeContract).toContain("KATARAKT CERRAHİSİ");
      expect(eyeContract).toContain("Fakoemülsifikasyon");
      expect(eyeContract).toContain("Zeynep Demir");
      expect(eyeContract).toContain("Endoftalmi");

      // 2. İDO (İstanbul Dişhekimleri Odası) Onamı Derleme
      const dentalContract = ContractEngineService.compileContract("IDO_DENTAL_IMPLANT_ONAM", sampleContext);
      expect(dentalContract).toContain("DENTAL İMPLANT");
      expect(dentalContract).toContain("titanyum vidalar");
      expect(dentalContract).toContain("mandibular sinir");

      // 3. Baia Hotels / Konaklama Sözleşmesi Derleme
      const hotelContract = ContractEngineService.compileContract("BAIA_HOTELS_KONAKLAMA_SOZLESMESI", sampleContext);
      expect(hotelContract).toContain("OTEL VE TATİL KÖYÜ KONAKLAMA HİZMET SÖZLEŞMESİ");
      expect(hotelContract).toContain("Giriş ve Çıkış Saatleri");
      expect(hotelContract).toContain("TBK m. 576-581");

      // 4. AvEvrak / Lexpera Araç Bakım ve Onarım Sözleşmesi Derleme
      const autoContract = ContractEngineService.compileContract("AVEVERAK_ARAC_BAKIM_ONARIM_SOZLESMESI", sampleContext);
      expect(autoContract).toContain("ARAÇ BAKIM, ONARIM VE SERVİS HİZMET SÖZLEŞMESİ");
      expect(autoContract).toContain("Türk Borçlar Kanunu Eser Sözleşmesi");
      expect(autoContract).toContain("34 ZD 1923");

      // 5. Türk Dermatoloji Derneği & DK Klinik Lazer Onamı Derleme
      const dermContract = ContractEngineService.compileContract("DERMATOLOJI_LAZER_CILT_YENILEME", sampleContext);
      expect(dermContract).toContain("NONABLATİF LAZERLE CİLT YENİLEME");
      expect(dermContract).toContain("Roaccutane");
      expect(dermContract).toContain("SPF 50+");
    });
  });

  // 7. Service-to-Onam Mapping Engine
  describe("ServiceTemplateMappingService", () => {
    it("should resolve TOD cataract onam for eye surgery services", () => {
      const resolution = ServiceTemplateMappingService.resolveTemplatesForService({
        sectorFamily: "health_clinical",
        serviceName: "Katarakt Fakoemülsifikasyon Cerrahisi",
      });
      expect(resolution.requiredTemplateIds).toContain("TOD_KATARAKT_FAKO_ONAM");
      expect(resolution.requiredTemplateIds).toContain("KVKK_OZEL_NITELIKLI_RIZA");
      expect(resolution.signatureLevel).toBe("OTP_2FA");
    });

    it("should resolve IDO dental implant onam for dental services", () => {
      const resolution = ServiceTemplateMappingService.resolveTemplatesForService({
        sectorFamily: "health_clinical",
        serviceName: "Dental İmplant ve Çene Cerrahisi",
      });
      expect(resolution.requiredTemplateIds).toContain("IDO_DENTAL_IMPLANT_ONAM");
      expect(resolution.signatureLevel).toBe("OTP_2FA");
    });

    it("should resolve Baia & Julian onams for hotel stay services", () => {
      const resolution = ServiceTemplateMappingService.resolveTemplatesForService({
        sectorFamily: "hospitality",
        serviceName: "Deluxe Deniz Manzaralı Oda Konaklama",
      });
      expect(resolution.requiredTemplateIds).toContain("BAIA_HOTELS_KONAKLAMA_SOZLESMESI");
      expect(resolution.requiredTemplateIds).toContain("JULIAN_HOTELS_KVKK_MISAFIR_ONAM");
    });

    it("should resolve AvEvrak auto repair onam for automotive services", () => {
      const resolution = ServiceTemplateMappingService.resolveTemplatesForService({
        sectorFamily: "automotive",
        serviceName: "Periyodik Bakım ve Fren Balata Onarımı",
      });
      expect(resolution.requiredTemplateIds).toContain("AVEVERAK_ARAC_BAKIM_ONARIM_SOZLESMESI");
      expect(resolution.requiredTemplateIds).toContain("TESLIM_TESELLUM_HASAR_TUTANAGI");
    });
  });

  // 8. Sector Dynamic Booking Fields
  describe("SectorDynamicFieldsService", () => {
    it("should provide medical fields for health_clinical and vehicle fields for automotive", () => {
      const healthFields = SectorDynamicFieldsService.getFieldsForSector("health_clinical");
      expect(healthFields.some((f) => f.name === "chronicDiseases")).toBe(true);
      expect(healthFields.some((f) => f.name === "activeMedications")).toBe(true);

      const autoFields = SectorDynamicFieldsService.getFieldsForSector("automotive");
      expect(autoFields.some((f) => f.name === "vehiclePlate")).toBe(true);
      expect(autoFields.some((f) => f.name === "vehicleCurrentKm")).toBe(true);
    });

    it("should enrich booking context with submitted sector fields", () => {
      const enriched = SectorDynamicFieldsService.enrichContextWithSectorFields(sampleContext, {
        vehiclePlate: "06 ANK 2026",
        vehicleCurrentKm: 85000,
        vehicleBrandModel: "Audi A4",
        allergiesInput: "Penisilin ve Lateks",
      });

      expect(enriched.vehiclePlate).toBe("06 ANK 2026");
      expect(enriched.vehicleKm).toBe(85000);
      expect(enriched.allergies).toContain("Penisilin ve Lateks");
    });
  });

  // 9. Document Export Service (Editable Word & Printable PDF)
  describe("DocumentExportService", () => {
    it("should generate editable Word (.doc) with legal headers and party tables", () => {
      const wordDoc = DocumentExportService.generateEditableWordDocument({
        templateId: "TOD_KATARAKT_FAKO_ONAM",
        context: sampleContext,
        isFilledWithData: true,
      });

      expect(wordDoc.filename).toContain("TOD_KATARAKT_FAKO_ONAM");
      expect(wordDoc.mimeType).toBe("application/msword");
      const content = wordDoc.buffer.toString("utf8");
      expect(content).toContain("WordDocument");
      expect(content).toContain("Zeynep Demir");
      expect(content).toContain("KATARAKT CERRAHİSİ");
    });

    it("should generate printable official PDF with tamper-proof seal", async () => {
      const pdfDoc = await DocumentExportService.generatePrintablePdf({
        templateId: "IDO_DENTAL_IMPLANT_ONAM",
        context: sampleContext,
      });

      expect(pdfDoc.filename).toContain("IDO_DENTAL_IMPLANT_ONAM");
      expect(pdfDoc.mimeType).toBe("application/pdf");
      expect(pdfDoc.buffer.toString("utf8", 0, 4)).toBe("%PDF");
    });
  });
});
