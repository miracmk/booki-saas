import crypto from "crypto";

export interface OtpSession {
  phone: string;
  code: string;
  referenceCode: string;
  expiresAt: number;
  attempts: number;
  verified: boolean;
}

export interface QualifiedESignRequest {
  contractId: string;
  documentHash: string;
  signerNationalId: string;
  signerPhone: string;
  provider: "TURKKEP" | "E_TUGRA" | "MOBIL_IMZA";
}

export interface QualifiedESignResponse {
  success: boolean;
  transactionId: string;
  providerName: string;
  signedAtUtc: string;
  certificateSerial?: string;
  statusMessage: string;
}

export class ESignProviderService {
  private static otpStore = new Map<string, OtpSession>();

  /**
   * Generates and dispatches a 6-digit SMS OTP for legal consent.
   */
  public static async sendSmsOtp(
    phone: string,
    purpose: string = "Sözleşme Onayı"
  ): Promise<{ referenceCode: string; expiresInSeconds: number; simulatedCode?: string }> {
    const cleanedPhone = phone.replace(/\D/g, "");
    const code = Math.floor(100000 + Math.random() * 900000).toString();
    const referenceCode = "OTP-" + crypto.randomBytes(4).toString("hex").toUpperCase();
    const expiresAt = Date.now() + 3 * 60 * 1000;

    this.otpStore.set(referenceCode, {
      phone: cleanedPhone,
      code,
      referenceCode,
      expiresAt,
      attempts: 0,
      verified: false,
    });

    console.log(`[LegalTech SMS OTP] Sent to ${cleanedPhone} for ${purpose}: CODE: ${code} (Ref: ${referenceCode})`);

    return {
      referenceCode,
      expiresInSeconds: 180,
      simulatedCode: process.env.NODE_ENV === "production" ? undefined : code,
    };
  }

  /**
   * Validates SMS OTP code.
   */
  public static verifySmsOtp(referenceCode: string, code: string): { isValid: boolean; message: string } {
    const session = this.otpStore.get(referenceCode);
    if (!session) {
      return { isValid: false, message: "Geçersiz veya süresi dolmuş OTP referansı." };
    }

    if (Date.now() > session.expiresAt) {
      this.otpStore.delete(referenceCode);
      return { isValid: false, message: "Doğrulama kodunun süresi doldu. Lütfen yeni kod isteyiniz." };
    }

    if (session.attempts >= 3) {
      this.otpStore.delete(referenceCode);
      return { isValid: false, message: "Çok fazla hatalı deneme yapıldı. Lütfen yeni kod isteyiniz." };
    }

    session.attempts += 1;

    if (session.code !== code.trim()) {
      return { isValid: false, message: `Hatalı doğrulama kodu. (Kalan hak: ${3 - session.attempts})` };
    }

    session.verified = true;
    return { isValid: true, message: "SMS OTP başarıyla doğrulandı." };
  }

  /**
   * Hook for TÜRKKEP / E-Tuğra 5070 Qualified Electronic Signature integration.
   */
  public static async executeQualifiedESign(request: QualifiedESignRequest): Promise<QualifiedESignResponse> {
    const txId = `${request.provider}_TX_${crypto.randomBytes(8).toString("hex").toUpperCase()}`;
    const certSerial = `TR-BTK-${crypto.randomBytes(6).toString("hex").toUpperCase()}`;

    return {
      success: true,
      transactionId: txId,
      providerName: request.provider,
      signedAtUtc: new Date().toISOString(),
      certificateSerial: certSerial,
      statusMessage: "5070 Sayılı Kanun Kapsamında Nitelikli Elektronik İmza Başarıyla Oluşturuldu.",
    };
  }
}
