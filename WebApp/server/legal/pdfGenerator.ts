import { PdfEngineService, GeneratePdfOptions } from "./pdfEngine.service";
import crypto from "crypto";

export class LegalPdfGenerator {
  public static async generateContractPdf(options: any): Promise<Buffer> {
    const timestampToken = `RFC3161-${crypto.randomBytes(8).toString("hex").toUpperCase()}-${new Date().toISOString()}`;
    return PdfEngineService.generate({
      contractId: options.auditPayload?.contractId || "CNT-DOC",
      title: options.title,
      docType: options.docType,
      tenantName: options.businessName || "BooKi SaaS",
      customerName: options.customerName || "Müşteri",
      renderedMarkdown: options.renderedMarkdown,
      signatureCanvasBase64: options.signatureCanvasBase64,
      auditPayload: options.auditPayload,
      verificationUrl: options.verificationUrl,
      securityChainHash: options.blockHash || "GENESIS_HASH",
      timestampToken,
    });
  }
}
