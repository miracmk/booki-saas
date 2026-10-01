import { BookingContextData } from "@shared/legalTypes";
import { ContractCompilerService } from "./contractCompiler.service";
import { DEFAULT_LEGAL_TEMPLATES } from "./defaultTemplates";
import { SECTOR_ONAM_CATALOG } from "./sectorOnamCatalog";
import { PdfEngineService } from "./pdfEngine.service";
import crypto from "crypto";

export interface ExportDocumentOptions {
  templateId: string;
  context?: BookingContextData;
  isFilledWithData?: boolean;
}

export class DocumentExportService {
  /**
   * Generates an editable Microsoft Word compatible document (.doc / Word XML/HTML format)
   * formatted with legal headers, party details, dynamic tables, and signature blocks.
   */
  public static generateEditableWordDocument(options: ExportDocumentOptions): { filename: string; buffer: Buffer; mimeType: string } {
    const { templateId, context, isFilledWithData = true } = options;

    const template =
      SECTOR_ONAM_CATALOG[templateId] ||
      (DEFAULT_LEGAL_TEMPLATES as any)[templateId] ||
      Object.values(SECTOR_ONAM_CATALOG).find((t) => t.docType === templateId) ||
      (DEFAULT_LEGAL_TEMPLATES as any).AYDINLATILMIS_ONAM_FORMU;

    const title = template?.title || "Yasal Onam ve Hizmet Sözleşmesi";
    let bodyContent = template?.bodyTemplateMarkdown || "";

    if (isFilledWithData && context) {
      bodyContent = ContractCompilerService.compile(bodyContent, context);
    } else {
      // Blank placeholders for manual paper filling
      bodyContent = bodyContent
        .replace(/\{\{CUSTOMER_FULL_NAME\}\}/g, "...........................................................")
        .replace(/\{\{CUSTOMER_MASKED_TCKN\}\}/g, "...........................")
        .replace(/\{\{CUSTOMER_PHONE\}\}/g, "...........................")
        .replace(/\{\{CUSTOMER_EMAIL\}\}/g, "...........................")
        .replace(/\{\{TENANT_NAME\}\}/g, context?.tenantName || "...................................................")
        .replace(/\{\{TENANT_LEGAL_NAME\}\}/g, context?.tenantLegalName || "...................................................")
        .replace(/\{\{SERVICE_NAME\}\}/g, context?.serviceName || "...................................................")
        .replace(/\{\{APPOINTMENT_DATE_TIME\}\}/g, "....../....../202... Saat: ......:......")
        .replace(/\{\{SYSTEM_DATE\}\}/g, new Date().toLocaleDateString("tr-TR"))
        .replace(/\{\{SYSTEM_TIME\}\}/g, new Date().toLocaleTimeString("tr-TR"))
        .replace(/\{\{COMPLICATIONS_LIST\}\}/g, "- Standart risk ve komplikasyonlar hekim/uzman tarafından sözlü izah edilmiştir.");
    }

    // Convert Markdown headers & lists to clean Word HTML formatting
    const htmlFormattedBody = bodyContent
      .replace(/^# (.*$)/gim, '<h1 style="color:#0f172a; font-size:16pt; font-family:Calibri, Arial; border-bottom:2px solid #0f172a; padding-bottom:4px; margin-top:12pt;">$1</h1>')
      .replace(/^## (.*$)/gim, '<h2 style="color:#1e293b; font-size:13pt; font-family:Calibri, Arial; margin-top:10pt;">$1</h2>')
      .replace(/^### (.*$)/gim, '<h3 style="color:#334155; font-size:11pt; font-family:Calibri, Arial; margin-top:8pt;">$1</h3>')
      .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
      .replace(/\*(.*?)\*/g, '<em>$1</em>')
      .replace(/^- (.*$)/gim, '<li style="margin-bottom:3pt; font-family:Calibri, Arial; font-size:10pt;">$1</li>')
      .replace(/\n\n/g, '</p><p style="margin-bottom:6pt; font-family:Calibri, Arial; font-size:10pt; line-height:1.4;">');

    // Official Microsoft Word HTML Document Structure
    const wordDocumentContent = `
<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
  <meta charset="utf-8">
  <title>${title}</title>
  <!--[if gte mso 9]>
  <xml>
    <w:WordDocument>
      <w:View>Print</w:View>
      <w:Zoom>100</w:Zoom>
      <w:DoNotOptimizeForBrowser/>
    </w:WordDocument>
  </xml>
  <![endif]-->
  <style>
    @page Section1 {
      size: 595.3pt 841.9pt; /* A4 */
      margin: 54.0pt 54.0pt 54.0pt 54.0pt;
      mso-header-margin: 36.0pt;
      mso-footer-margin: 36.0pt;
    }
    div.Section1 { page: Section1; }
    body { font-family: 'Calibri', 'Arial', sans-serif; font-size: 10pt; color: #1e293b; }
    table.legal-meta { width: 100%; border-collapse: collapse; margin-bottom: 15pt; }
    table.legal-meta td { border: 1px solid #cbd5e1; padding: 6pt; font-size: 9pt; }
    table.signatures { width: 100%; margin-top: 30pt; border-collapse: collapse; }
    table.signatures td { width: 50%; vertical-align: top; padding: 10pt; font-size: 9.5pt; }
  </style>
</head>
<body>
  <div class="Section1">
    <!-- Header -->
    <div style="background-color:#0f172a; color:#ffffff; padding:10pt; text-align:center; font-family:Calibri, Arial; font-weight:bold; font-size:12pt; margin-bottom:12pt;">
      RESMİ BİLGİLENDİRİLMİŞ ONAM VE HİZMET SÖZLEŞMESİ METNİ
      <div style="font-size:8pt; font-weight:normal; color:#94a3b8; margin-top:2pt;">
        HMK m. 199/202 Kesin Delil • 1219 Sayılı Tababet Kanunu • TBK m. 20-25 Genel İşlem Koşulları
      </div>
    </div>

    <!-- Metadata Table -->
    <table class="legal-meta">
      <tr style="background-color:#f8fafc;">
        <td><strong>Doküman Başlığı:</strong> ${title}</td>
        <td><strong>Tarih:</strong> ${new Date().toLocaleDateString("tr-TR")}</td>
      </tr>
      <tr>
        <td><strong>Hizmet Sağlayıcı / Tesis:</strong> ${context?.tenantLegalName || context?.tenantName || "İşletme"}</td>
        <td><strong>Müşteri / Hasta:</strong> ${context?.customerFullName || "Müşteri / Hasta"}</td>
      </tr>
      <tr>
        <td><strong>Hizmet / Operasyon:</strong> ${context?.serviceName || "Hizmet"}</td>
        <td><strong>Randevu / İşlem Zamanı:</strong> ${context?.appointmentDateTime || "-"}</td>
      </tr>
    </table>

    <!-- Main Content -->
    <div>
      <p style="margin-bottom:6pt; font-family:Calibri, Arial; font-size:10pt; line-height:1.4;">
        ${htmlFormattedBody}
      </p>
    </div>

    <!-- Signatures -->
    <table class="signatures">
      <tr>
        <td style="border-right: 1px dashed #94a3b8;">
          <strong>HİZMET SAĞLAYICI / HEKİM / YETKİLİ:</strong><br><br>
          Unvan: ${context?.tenantLegalName || context?.tenantName || "İşletme"}<br>
          Yetkili Adı: ${context?.staffName || "Yetkili Uzman"}<br><br>
          İmza / Kaşe: ....................................................<br><br>
          Tarih: ...... / ...... / 202...
        </td>
        <td>
          <strong>MÜŞTERİ / HASTA / DANIŞAN:</strong><br><br>
          Adı Soyadı: ${context?.customerFullName || "...................................................."}<br>
          T.C. Kimlik No: ${context?.customerNationalId ? ContractCompilerService.maskTckn(context.customerNationalId) : "..................................."}<br><br>
          İmza: ................................................................<br>
          <em>(Kendi el yazısıyla "Okudum, anladım, kabul ediyorum" yazılarak imzalanacaktır)</em><br><br>
          Tarih: ...... / ...... / 202...
        </td>
      </tr>
    </table>
  </div>
</body>
</html>`.trim();

    const safeFilename = `${templateId}_${Date.now()}.doc`;
    return {
      filename: safeFilename,
      buffer: Buffer.from(wordDocumentContent, "utf8"),
      mimeType: "application/msword",
    };
  }

  /**
   * Generates a printable PDF using PdfEngineService.
   */
  public static async generatePrintablePdf(options: ExportDocumentOptions): Promise<{ filename: string; buffer: Buffer; mimeType: string }> {
    const { templateId, context } = options;
    const template =
      SECTOR_ONAM_CATALOG[templateId] ||
      (DEFAULT_LEGAL_TEMPLATES as any)[templateId] ||
      (DEFAULT_LEGAL_TEMPLATES as any).AYDINLATILMIS_ONAM_FORMU;

    const dummyContext: BookingContextData = context || {
      tenantId: "TENANT_SAMPLE",
      tenantName: "BooKi Sağlık & Hizmet Merkezi",
      tenantLegalName: "BooKi Sağlık ve Hizmet Hizmetleri A.Ş.",
      bookingId: "REZ-SAMPLE-01",
      serviceName: "Hizmet / Uygulama",
      servicePrice: 1000,
      hasPrepayment: false,
      appointmentDateTime: new Date().toISOString(),
      customerId: "CUST-001",
      customerFullName: "Sayın Müşteri",
      customerPhone: "05550000000",
      customerEmail: "info@example.com",
    };

    const renderedMarkdown = ContractCompilerService.compile(template.bodyTemplateMarkdown, dummyContext);
    const contractId = "CNT-EXPORT-" + crypto.randomBytes(4).toString("hex").toUpperCase();
    const timestampUtc = new Date().toISOString();

    const pdfBuffer = await PdfEngineService.generate({
      contractId,
      title: template.title,
      docType: template.docType,
      tenantName: dummyContext.tenantLegalName || dummyContext.tenantName || "BooKi",
      customerName: dummyContext.customerFullName || "Müşteri",
      renderedMarkdown,
      auditPayload: {
        contractId,
        documentType: template.docType,
        signerName: dummyContext.customerFullName || "Müşteri",
        ipAddress: "127.0.0.1",
        userAgent: "BooKi Document Export Engine",
        timestampUtc,
        signatureType: "CANVAS_BIOMETRIC",
        contentSha256: crypto.createHash("sha256").update(renderedMarkdown).digest("hex"),
      },
      verificationUrl: `https://app.booki.com/v/${contractId}`,
      securityChainHash: "GENESIS_EXPORT_SEAL",
      timestampToken: `RFC3161-${timestampUtc}`,
    });

    return {
      filename: `${templateId}_${Date.now()}.pdf`,
      buffer: pdfBuffer,
      mimeType: "application/pdf",
    };
  }
}
