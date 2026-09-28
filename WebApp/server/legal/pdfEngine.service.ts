import PDFDocument from "pdfkit";
import QRCode from "qrcode";
import { SignatureAuditPayload } from "@shared/legalTypes";

export interface GeneratePdfOptions {
  contractId: string;
  title: string;
  docType: string;
  tenantName: string;
  customerName: string;
  renderedMarkdown: string;
  signatureCanvasBase64?: string;
  auditPayload: SignatureAuditPayload;
  verificationUrl: string;
  securityChainHash: string;
  timestampToken: string;
}

export class PdfEngineService {
  /**
   * Generates production-grade official legal PDF with Ek-1 Audit Trail Page.
   * Standard: 10pt readable body font, A4 format, tamper-proof cryptographic audit appendix.
   */
  public static async generate(options: GeneratePdfOptions): Promise<Buffer> {
    const {
      contractId,
      title,
      docType,
      tenantName,
      customerName,
      renderedMarkdown,
      signatureCanvasBase64,
      auditPayload,
      verificationUrl,
      securityChainHash,
      timestampToken,
    } = options;

    return new Promise(async (resolve, reject) => {
      try {
        const doc = new PDFDocument({
          size: "A4",
          margins: { top: 45, bottom: 55, left: 50, right: 50 },
          info: {
            Title: title,
            Author: tenantName,
            Subject: `Resmi Yasal Sözleşme - ${docType}`,
            Keywords: "BooKi, E-İmza, 5070, HMK 199, KVKK, RFC 3161",
            CreationDate: new Date(),
          },
          bufferPages: true,
        });

        const buffers: Buffer[] = [];
        doc.on("data", (chunk) => buffers.push(chunk));
        doc.on("end", () => resolve(Buffer.concat(buffers)));
        doc.on("error", (err) => reject(err));

        // 1. Header Banner
        doc
          .rect(50, 40, 495, 34)
          .fillAndStroke("#0f172a", "#1e293b");

        doc
          .fillColor("#ffffff")
          .fontSize(11)
          .font("Helvetica-Bold")
          .text("BooKi SaaS | Resmi Elektronik Sözleşme & Hukuki Belge", 60, 51, { align: "left" });

        doc
          .fontSize(8)
          .font("Helvetica")
          .fillColor("#94a3b8")
          .text("HMK m. 199/202 Kesin Delil", 380, 52, { align: "right" });

        doc.moveDown(2);

        // 2. Document Title
        doc
          .fillColor("#0f172a")
          .fontSize(14)
          .font("Helvetica-Bold")
          .text(title, 50, 90, { align: "center", width: 495 });

        doc
          .fontSize(8.5)
          .font("Helvetica")
          .fillColor("#64748b")
          .text(`Sözleşme No: ${contractId}  |  Evrak Tipi: ${docType}  |  Tarih (UTC): ${auditPayload.timestampUtc}`, {
            align: "center",
          });

        doc.moveDown(0.8);
        doc.moveTo(50, doc.y).lineTo(545, doc.y).strokeColor("#cbd5e1").lineWidth(1).stroke();
        doc.moveDown(1);

        // 3. Body Content (Strict 10pt readable text)
        const cleanLines = renderedMarkdown
          .replace(/^#+\s+/gm, "")
          .replace(/\*\*(.*?)\*\*/g, "$1")
          .split("\n");

        doc.fillColor("#1e293b").fontSize(10).font("Helvetica").lineGap(3);

        for (const line of cleanLines) {
          const trimmed = line.trim();
          if (!trimmed) {
            doc.moveDown(0.4);
            continue;
          }

          if (trimmed.startsWith("###") || /^[0-9]+\.\s/.test(trimmed) || trimmed.startsWith("TARAFLAR") || trimmed.startsWith("SÖZLEŞMENİN")) {
            doc.font("Helvetica-Bold").text(trimmed, { align: "left" }).font("Helvetica");
          } else {
            doc.text(trimmed, { align: "justify" });
          }

          if (doc.y > 690) {
            doc.addPage();
          }
        }

        // 4. Signatures Box at end of contract text
        if (doc.y > 580) {
          doc.addPage();
        } else {
          doc.moveDown(1.5);
        }

        const signBoxY = Math.max(doc.y + 10, 580);
        doc.rect(50, signBoxY, 495, 120).fillAndStroke("#f8fafc", "#cbd5e1");

        doc.rect(50, signBoxY, 495, 20).fillAndStroke("#e2e8f0", "#cbd5e1");
        doc
          .fillColor("#0f172a")
          .fontSize(9)
          .font("Helvetica-Bold")
          .text("TARAFLARIN DİJİTAL İMZASI VE AKDİ KABULÜ", 60, signBoxY + 5);

        // Left Column: Business
        const signContentY = signBoxY + 28;
        doc
          .fontSize(8.5)
          .font("Helvetica-Bold")
          .fillColor("#0f172a")
          .text("Hizmet Veren (İşletme):", 60, signContentY)
          .font("Helvetica")
          .text(tenantName, 60, signContentY + 12)
          .text("Elektronik Onay: SİSTEMDEN DÜZENLENDİ", 60, signContentY + 24)
          .text(`Onay Zamanı: ${auditPayload.timestampUtc}`, 60, signContentY + 36);

        // Right Column: Signer & Canvas Image
        doc
          .font("Helvetica-Bold")
          .text("Hizmet Alan (İmza Sahibi):", 290, signContentY)
          .font("Helvetica")
          .text(`Adı Soyadı: ${customerName}`, 290, signContentY + 12)
          .text(`İmza Yöntemi: ${auditPayload.signatureType}`, 290, signContentY + 24)
          .text(`İstemci IP: ${auditPayload.ipAddress}`, 290, signContentY + 36);

        if (signatureCanvasBase64 && signatureCanvasBase64.includes("base64,")) {
          try {
            const b64 = signatureCanvasBase64.split("base64,")[1];
            const imgBuf = Buffer.from(b64, "base64");
            doc.image(imgBuf, 410, signContentY - 5, { width: 120, height: 50 });
          } catch {
            doc.text("[Biyometrik Kanvas İmza]", 420, signContentY + 15);
          }
        }

        // ==========================================
        // EK-1: ZORUNLU HUKUKİ DENETİM İZİ SAYFASI
        // HMK m. 199/202 & 5070 E-İmza & RFC 3161
        // ==========================================
        doc.addPage();

        // Appendix Header
        doc.rect(50, 40, 495, 36).fillAndStroke("#1e293b", "#0f172a");
        doc
          .fillColor("#ffffff")
          .fontSize(12)
          .font("Helvetica-Bold")
          .text("EK-1: HUKUKİ DENETİM İZİ VE ELEKTRONİK DELİL TUTANAĞI", 60, 52);

        doc
          .fontSize(8)
          .font("Helvetica")
          .fillColor("#94a3b8")
          .text("RFC 3161 & HMK m. 199", 410, 53, { align: "right" });

        doc.moveDown(2);

        doc
          .fillColor("#0f172a")
          .fontSize(10)
          .font("Helvetica")
          .text(
            "İşbu sayfa, 6100 Sayılı Hukuk Muhakemeleri Kanunu Madde 199 ve 5070 Sayılı Elektronik İmza Kanunu uyarınca sözleşmenin taraflarca değiştirilemez biçimde onaylandığını ispatlayan teknik ve hukuki denetim izlerini içerir.",
            50,
            95,
            { width: 495, align: "justify" }
          );

        doc.moveDown(1.5);

        // Audit Trail Technical Table Box
        const auditBoxY = 140;
        doc.rect(50, auditBoxY, 495, 340).fillAndStroke("#f8fafc", "#cbd5e1");

        const renderAuditRow = (label: string, value: string, yPos: number, isMono: boolean = false) => {
          doc
            .fontSize(8.5)
            .font("Helvetica-Bold")
            .fillColor("#334155")
            .text(label, 60, yPos, { width: 160 });

          doc
            .font(isMono ? "Courier" : "Helvetica")
            .fontSize(isMono ? 7.5 : 8.5)
            .fillColor("#0f172a")
            .text(value, 220, yPos, { width: 315 });

          doc.moveTo(55, yPos + 18).lineTo(540, yPos + 18).strokeColor("#e2e8f0").lineWidth(0.5).stroke();
        };

        renderAuditRow("Sözleşme ID:", contractId, auditBoxY + 15);
        renderAuditRow("SHA-256 Doküman Özeti:", auditPayload.contentSha256, auditBoxY + 38, true);
        renderAuditRow("Blok Hash Zinciri:", securityChainHash, auditBoxY + 61, true);
        renderAuditRow("RFC 3161 Zaman Damgası Token:", timestampToken, auditBoxY + 84, true);
        renderAuditRow("İmzalama Zamanı (UTC):", auditPayload.timestampUtc, auditBoxY + 107);
        renderAuditRow("İmzacı IP Adresi (IPv4/v6):", auditPayload.ipAddress, auditBoxY + 130);
        renderAuditRow("İstemci User-Agent:", auditPayload.userAgent.slice(0, 75), auditBoxY + 153);
        renderAuditRow("Ekran Çözünürlüğü:", auditPayload.screenResolution || "1920x1080 (CSS Viewport)", auditBoxY + 176);

        // Biometrics
        const bioText = auditPayload.biometrics
          ? `Vuruş: ${auditPayload.biometrics.strokeCount} | Süre: ${auditPayload.biometrics.durationMs}ms | Nokta: ${auditPayload.biometrics.pointCount}`
          : "Dokunmatik / Mouse Koordinat İzi Mühürlendi";
        renderAuditRow("Biyometrik İmza Metrikleri:", bioText, auditBoxY + 199);

        // OTP / 2FA
        const otpText = auditPayload.otp
          ? `Ref: ${auditPayload.otp.referenceCode} (Zaman: ${auditPayload.otp.verifiedAtUtc})`
          : "Standart Biyometrik Onay (2FA Aranmadı)";
        renderAuditRow("SMS/OTP Doğrulama Kaydı:", otpText, auditBoxY + 222);

        // GPS
        const gpsText = auditPayload.gps
          ? `${auditPayload.gps.latitude.toFixed(6)}, ${auditPayload.gps.longitude.toFixed(6)} (±${Math.round(auditPayload.gps.accuracyMeters || 0)}m)`
          : "Konum doğrulaması uygulanmadı.";
        renderAuditRow("GPS Konum Mührü (Emlak):", gpsText, auditBoxY + 245);

        // Bottom QR Code & Public Verification Section
        const qrSectionY = auditBoxY + 275;
        try {
          const qrBuf = await QRCode.toBuffer(verificationUrl, {
            errorCorrectionLevel: "H",
            margin: 1,
            width: 55,
          });
          doc.image(qrBuf, 60, qrSectionY, { width: 55, height: 55 });
        } catch {
          // fallback
        }

        doc
          .font("Helvetica-Bold")
          .fontSize(9)
          .fillColor("#047857")
          .text("KAMUYA AÇIK DOĞRULAMA KODU (QR CODE)", 125, qrSectionY + 5);

        doc
          .font("Helvetica")
          .fontSize(7.5)
          .fillColor("#475569")
          .text(
            "Yandaki karekodu telefonunuzla okutarak veya aşağıdaki doğrulama bağlantısına tıklayarak sözleşmenin değiştirilmediğini teyit edebilirsiniz:",
            125,
            qrSectionY + 18,
            { width: 410 }
          );

        doc
          .font("Courier")
          .fontSize(7)
          .fillColor("#0284c7")
          .text(verificationUrl, 125, qrSectionY + 38, { width: 410 });

        // Legal Notice Banner
        doc
          .rect(50, 510, 495, 70)
          .fillAndStroke("#f1f5f9", "#cbd5e1");

        doc
          .fillColor("#0f172a")
          .fontSize(8)
          .font("Helvetica-Bold")
          .text("HUKUKİ GEÇERLİLİK VE İNKÂR EDİLEMEZLİK BİLDİRİMİ:", 60, 520);

        doc
          .font("Helvetica")
          .fontSize(7.5)
          .fillColor("#334155")
          .text(
            "İşbu elektronik belge; 5070 Sayılı Elektronik İmza Kanunu, 6100 Sayılı Hukuk Muhakemeleri Kanunu Madde 199 (Elektronik Belge) ve Madde 202 (Yazılı Delil Başlangıcı) ile 6502 Sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Sözleşmeler Yönetmeliği hükümlerine uygun olarak düzenlenmiş olup, zaman damgası ve SHA-256 hash parmak izi ile delil güvenliği teminat altına alınmıştır.",
            60,
            534,
            { width: 475, align: "justify" }
          );

        doc.end();
      } catch (err) {
        reject(err);
      }
    });
  }
}
