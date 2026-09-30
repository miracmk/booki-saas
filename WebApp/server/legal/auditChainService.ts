import crypto from "crypto";

/**
 * BooKi AuditChainService
 * Kriptografik SHA-256 Hash ve Değiştirilemez Blok Zincir Denetim İzi Servisi
 * HMK m. 199/202, 5070 E-İmza Kanunu ve RFC 3161 standartlarına uygun özetleme.
 */
export class AuditChainService {
  /**
   * Calculates deterministic SHA-256 hash for strings or Buffers.
   */
  public static computeSha256(data: string | Buffer): string {
    return crypto.createHash("sha256").update(data).digest("hex");
  }

  /**
   * Generates a tamper-proof chained block hash by linking previous block hash and current audit payload.
   */
  public static computeBlockHash(previousHash: string, payload: any): string {
    const serializedPayload = typeof payload === "string" ? payload : JSON.stringify(payload);
    return crypto.createHash("sha256").update(previousHash + ":" + serializedPayload).digest("hex");
  }
}
