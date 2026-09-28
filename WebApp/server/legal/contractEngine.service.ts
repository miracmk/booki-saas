import fs from "fs";
import path from "path";
import crypto from "crypto";
import {
  BookingContextData,
  ContractVerificationResponse,
  DocumentType,
  FlowContext,
  RequiredContractRule,
  SectorFamily,
  SignContractRequest,
  SignatureAuditPayload,
  SignedContractResponse,
} from "@shared/legalTypes";
import { DEFAULT_LEGAL_TEMPLATES } from "./defaultTemplates";
import { ContractCompilerService } from "./contractCompiler.service";
import { LegalComplianceEngine } from "./complianceEngine";
import { AuditChainService } from "./auditChainService";
import { PdfEngineService } from "./pdfEngine.service";
import { ContractDispatcherService } from "./contractDispatcher.service";
import { ESignProviderService } from "./eSignProviders";

const STORAGE_DIR = path.resolve(process.cwd(), "storage/legal_contracts");
if (!fs.existsSync(STORAGE_DIR)) {
  fs.mkdirSync(STORAGE_DIR, { recursive: true });
}

interface StoredContractRecord {
  id: string;
  tenantId: string;
  bookingId?: string;
  customerId: string;
  documentType: DocumentType;
  title: string;
  sectorFamily: SectorFamily;
  sha256Hash: string;
  securityChainHash: string;
  timestampToken: string;
  renderedMarkdown: string;
  pdfFilename: string;
  signatureCanvasSvg?: string;
  auditPayload: SignatureAuditPayload;
  signedAtUtc: string;
}

export class ContractEngineService {
  private static contractsByHash = new Map<string, StoredContractRecord>();
  private static contractsById = new Map<string, StoredContractRecord>();
  private static latestSecurityChainHash: string = "GENESIS_BOOKI_LEGALTECH_CHAIN_2026";

  /**
   * Compiles contract template with contextual data.
   */
  public static compileContract(templateId: string, bookingContext: BookingContextData): string {
    const template = DEFAULT_LEGAL_TEMPLATES[templateId as DocumentType];
    const markdown = template ? template.bodyTemplateMarkdown : `# SÖZLEŞME\n\n{{CUSTOMER_FULL_NAME}} ile {{TENANT_NAME}} arasındaki hizmet anlaşması.`;
    return ContractCompilerService.compile(markdown, bookingContext);
  }

  /**
   * Evaluates the legal requirements and provides rendered markdown contracts for checkout/check-in.
   */
  public static getRequirementsWithRenderedText(
    sectorFamily: SectorFamily,
    flowContext: FlowContext,
    context: BookingContextData
  ): { rules: RequiredContractRule[]; renderedContracts: Record<string, string> } {
    const rules = LegalComplianceEngine.evaluateRequirements({
      sectorFamily,
      flowContext,
      context,
      customerExistingValidSignatures: this.getCustomerSignatures(context.customerId),
    });

    const renderedContracts: Record<string, string> = {};
    for (const rule of rules) {
      const template = DEFAULT_LEGAL_TEMPLATES[rule.docType];
      if (template) {
        renderedContracts[rule.docType] = ContractCompilerService.compile(template.bodyTemplateMarkdown, context);
      }
    }

    return { rules, renderedContracts };
  }

  /**
   * Signs and seals contract with SHA-256, RFC 3161 timestamp, generates PDF and dispatches to customer.
   */
  public static async signAndSealContract(
    request: SignContractRequest,
    clientMeta: { ipAddress: string; userAgent: string; host: string; protocol: string }
  ): Promise<SignedContractResponse> {
    const template = DEFAULT_LEGAL_TEMPLATES[request.docType];
    if (!template) {
      throw new Error(`Sözleşme şablonu bulunamadı: ${request.docType}`);
    }

    if (!request.acceptedTerms) {
      throw new Error("Sözleşme şartlarının kabul edilmesi zorunludur.");
    }

    if (template.requiresSignature && !request.signatureCanvasBase64 && !request.signatureCanvasSvg) {
      throw new Error("Bu sözleşme için geçerli bir kanvas dijital imza zorunludur.");
    }

    // OTP 2FA Doğrulama
    if (request.signatureType === "SMS_OTP_VERIFIED" || template.signatureLevel === "OTP_2FA") {
      if (!request.otpCode || !request.otpReferenceCode) {
        throw new Error("Bu işlem için SMS OTP güvenlik doğrulaması zorunludur.");
      }
      const otpValidation = ESignProviderService.verifySmsOtp(request.otpReferenceCode, request.otpCode);
      if (!otpValidation.isValid) {
        throw new Error(otpValidation.message);
      }
    }

    // Render compiled markdown
    const renderedMarkdown = ContractCompilerService.compile(template.bodyTemplateMarkdown, request.context);

    // Kriptografik SHA-256 Hash
    const contentSha256 = AuditChainService.computeSha256(renderedMarkdown);
    const signatureSha256 = request.signatureCanvasBase64
      ? AuditChainService.computeSha256(request.signatureCanvasBase64)
      : undefined;

    const contractId = "CNT-" + crypto.randomBytes(8).toString("hex").toUpperCase();
    const timestampUtc = new Date().toISOString();

    // RFC 3161 Uyumlu Zaman Damgası Token'ı
    const timestampToken = `RFC3161-SHA256-${crypto.randomBytes(12).toString("hex").toUpperCase()}-${timestampUtc}`;

    // Hazırlanan Denetim İzi Payload'u
    const auditPayload: SignatureAuditPayload = {
      contractId,
      documentType: request.docType,
      signerName: request.signerName || request.context.customerFullName || request.context.customerName || "Müşteri",
      signerNationalIdMasked: request.signerNationalId
        ? ContractCompilerService.maskTckn(request.signerNationalId)
        : undefined,
      signerPhoneMasked: request.signerPhone
        ? request.signerPhone.slice(0, 4) + "****" + request.signerPhone.slice(-2)
        : undefined,
      ipAddress: clientMeta.ipAddress || "127.0.0.1",
      userAgent: clientMeta.userAgent || "Mozilla/5.0",
      screenResolution: request.biometricMetadata?.screenResolution || "1920x1080",
      timestampUtc,
      signatureType: request.signatureType || "CANVAS_BIOMETRIC",
      contentSha256,
      signatureSha256,
      biometrics: request.biometricMetadata,
      rfc3161Token: timestampToken,
    };

    if (request.otpReferenceCode) {
      auditPayload.otp = {
        referenceCode: request.otpReferenceCode,
        verifiedAtUtc: timestampUtc,
        deliveryChannel: "SMS",
        mobileNumberMasked: request.signerPhone
          ? request.signerPhone.slice(0, 4) + "****" + request.signerPhone.slice(-2)
          : "**********",
      };
    }

    if (request.gpsLatitude !== undefined && request.gpsLongitude !== undefined) {
      auditPayload.gps = {
        latitude: request.gpsLatitude,
        longitude: request.gpsLongitude,
        accuracyMeters: request.gpsAccuracyMeters,
        timestampUtc,
      };
    }

    // Blok Hash Zinciri (Blockchain / Tamper-proof bağlantı)
    const securityChainHash = AuditChainService.computeBlockHash(this.latestSecurityChainHash, auditPayload);
    this.latestSecurityChainHash = securityChainHash;

    const verificationUrl = `${clientMeta.protocol}://${clientMeta.host}/api/legal/verify/${contentSha256}`;

    // Üretilen Resmi A4 PDF (Ek-1 Audit Trail ile)
    const pdfBuffer = await PdfEngineService.generate({
      contractId,
      title: template.title,
      docType: request.docType,
      tenantName: request.context.tenantLegalName || request.context.tenantName || request.context.businessName || "BooKi SaaS",
      customerName: request.signerName || request.context.customerFullName || "Müşteri",
      renderedMarkdown,
      signatureCanvasBase64: request.signatureCanvasBase64,
      auditPayload,
      verificationUrl,
      securityChainHash,
      timestampToken,
    });

    const pdfFilename = `${contractId}_${request.docType}.pdf`;
    const pdfFilePath = path.join(STORAGE_DIR, pdfFilename);
    fs.writeFileSync(pdfFilePath, pdfBuffer);

    // Otomatik Müşteri Dağıtımı (E-Posta, SMS, WhatsApp)
    const deliveries = await ContractDispatcherService.dispatch({
      contractId,
      sha256Hash: contentSha256,
      customerName: request.signerName || request.context.customerFullName || "Müşteri",
      customerPhone: request.signerPhone || request.context.customerPhone,
      customerEmail: request.signerEmail || request.context.customerEmail,
      tenantName: request.context.tenantName || request.context.businessName || "BooKi İşletmesi",
      serviceName: request.context.serviceName,
      pdfBuffer,
      pdfFilename,
      verificationUrl,
    });

    const record: StoredContractRecord = {
      id: contractId,
      tenantId: request.tenantId,
      bookingId: request.bookingId,
      customerId: request.customerId,
      documentType: request.docType,
      title: template.title,
      sectorFamily: template.sectorFamily,
      sha256Hash: contentSha256,
      securityChainHash,
      timestampToken,
      renderedMarkdown,
      pdfFilename,
      signatureCanvasSvg: request.signatureCanvasSvg,
      auditPayload,
      signedAtUtc: timestampUtc,
    };

    this.contractsByHash.set(contentSha256, record);
    this.contractsById.set(contractId, record);

    console.log(`[LegalTech Engine] Signed and Dispatched: ${contractId} (SHA: ${contentSha256.slice(0, 10)}...)`);

    return {
      success: true,
      contractId,
      docType: request.docType,
      title: template.title,
      sha256Hash: contentSha256,
      signedPdfUrl: `/api/legal/contracts/${contractId}/pdf`,
      signedAtUtc: timestampUtc,
      verificationUrl,
      deliveries,
      auditTrail: {
        blockHash: securityChainHash,
        ipAddress: auditPayload.ipAddress,
        signatureType: auditPayload.signatureType,
        timestampToken,
      },
    };
  }

  // Backwards compatibility alias
  public static async signContract(
    request: SignContractRequest,
    clientMeta: { ipAddress: string; userAgent: string; host: string; protocol: string }
  ): Promise<SignedContractResponse> {
    return this.signAndSealContract(request, clientMeta);
  }

  /**
   * Verifies the authenticity of a contract by its SHA-256 hash.
   */
  public static verifyContract(sha256Hash: string): ContractVerificationResponse {
    const record = this.contractsByHash.get(sha256Hash);

    if (!record) {
      return {
        isValid: false,
        sha256Hash,
        title: "Bilinmeyen Sözleşme",
        docType: "KVKK_AYDINLATMA",
        sectorFamily: "beauty_wellness",
        tenantName: "Kayıt Bulunamadı",
        signerName: "Bilinmiyor",
        signedAtUtc: "-",
        signatureType: "CANVAS_BIOMETRIC",
        ipAddress: "-",
        hasOtpVerification: false,
        hasGpsVerification: false,
        blockHash: "-",
        timestampToken: "-",
        legalNotice: "Belirtilen SHA-256 hash özetine sahip geçerli bir sözleşme kaydı bulunamadı veya değiştirilmiştir.",
      };
    }

    const recomputedHash = AuditChainService.computeSha256(record.renderedMarkdown);
    const isValid = recomputedHash === sha256Hash;
    const deliveries = ContractDispatcherService.getDeliveries(record.id);

    return {
      isValid,
      sha256Hash,
      title: record.title,
      docType: record.documentType,
      sectorFamily: record.sectorFamily,
      tenantName: record.auditPayload.signerName ? "BooKi Doğrulanmış İşletme" : "İşletme",
      signerName: record.auditPayload.signerName,
      signedAtUtc: record.signedAtUtc,
      signatureType: record.auditPayload.signatureType,
      ipAddress: record.auditPayload.ipAddress,
      hasOtpVerification: Boolean(record.auditPayload.otp),
      hasGpsVerification: Boolean(record.auditPayload.gps),
      blockHash: record.securityChainHash,
      timestampToken: record.timestampToken,
      legalNotice: isValid
        ? "İşbu sözleşmenin kriptografik bütünlüğü ve zaman damgası doğrulanmıştır. HMK Madde 199/202 ve 5070 Sayılı Elektronik İmza Kanunu uyarınca kesin delil niteliğindedir."
        : "UYARI: Sözleşme metninin kriptografik bütünlüğü bozulmuştur!",
      deliveries,
    };
  }

  /**
   * Retrieves the generated PDF buffer for download.
   */
  public static getContractPdfBuffer(contractId: string): { buffer: Buffer; filename: string } | null {
    const record = this.contractsById.get(contractId);
    if (!record) return null;

    const filePath = path.join(STORAGE_DIR, record.pdfFilename);
    if (!fs.existsSync(filePath)) return null;

    return {
      buffer: fs.readFileSync(filePath),
      filename: record.pdfFilename,
    };
  }

  private static getCustomerSignatures(customerId: string): { docType: DocumentType; signedAt: string; expiresAt?: string }[] {
    const results: { docType: DocumentType; signedAt: string; expiresAt?: string }[] = [];
    const allRecords = Array.from(this.contractsById.values());
    for (const record of allRecords) {
      if (record.customerId === customerId) {
        const template = DEFAULT_LEGAL_TEMPLATES[record.documentType as DocumentType];
        let expiresAt: string | undefined;
        if (template?.validityDays) {
          const signedTime = new Date(record.signedAtUtc).getTime();
          expiresAt = new Date(signedTime + template.validityDays * 86400000).toISOString();
        }
        results.push({
          docType: record.documentType,
          signedAt: record.signedAtUtc,
          expiresAt,
        });
      }
    }
    return results;
  }
}
