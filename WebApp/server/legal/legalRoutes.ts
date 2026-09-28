import { Express, Request, Response } from "express";
import { ContractEngineService } from "./contractEngine.service";
import { ESignProviderService } from "./eSignProviders";
import { DEFAULT_LEGAL_TEMPLATES } from "./defaultTemplates";
import { FlowContext, SectorFamily, SignContractRequest } from "@shared/legalTypes";

export function registerLegalRoutes(app: Express) {
  /**
   * GET /api/legal/templates
   */
  app.get("/api/legal/templates", (_req: Request, res: Response) => {
    res.json({
      success: true,
      templates: Object.values(DEFAULT_LEGAL_TEMPLATES),
    });
  });

  /**
   * POST /api/legal/requirements
   */
  app.post("/api/legal/requirements", (req: Request, res: Response) => {
    try {
      const { sectorFamily, flowContext, context } = req.body as {
        sectorFamily: SectorFamily;
        flowContext: FlowContext;
        context: any;
      };

      if (!sectorFamily || !flowContext || !context) {
        return res.status(400).json({ error: "Eksik parametreler (sectorFamily, flowContext, context gereklidir)" });
      }

      const result = ContractEngineService.getRequirementsWithRenderedText(
        sectorFamily,
        flowContext,
        context
      );

      res.json({
        success: true,
        rules: result.rules,
        renderedContracts: result.renderedContracts,
      });
    } catch (err: any) {
      console.error("[LegalTech API] requirements error:", err);
      res.status(500).json({ error: err.message || "Sözleşme gereksinimleri hesaplanamadı" });
    }
  });

  /**
   * POST /api/legal/otp/send
   */
  app.post("/api/legal/otp/send", async (req: Request, res: Response) => {
    try {
      const { phone, purpose } = req.body;
      if (!phone) {
        return res.status(400).json({ error: "Telefon numarası zorunludur" });
      }

      const result = await ESignProviderService.sendSmsOtp(phone, purpose || "Sözleşme İmzası");
      res.json({ success: true, ...result });
    } catch (err: any) {
      console.error("[LegalTech API] OTP Send error:", err);
      res.status(500).json({ error: err.message || "OTP gönderilemedi" });
    }
  });

  /**
   * POST /api/legal/otp/verify
   */
  app.post("/api/legal/otp/verify", (req: Request, res: Response) => {
    try {
      const { referenceCode, code } = req.body;
      if (!referenceCode || !code) {
        return res.status(400).json({ error: "Referans kodu ve doğrulama kodu zorunludur" });
      }

      const result = ESignProviderService.verifySmsOtp(referenceCode, code);
      if (!result.isValid) {
        return res.status(400).json({ success: false, message: result.message });
      }

      res.json({ success: true, message: result.message });
    } catch (err: any) {
      res.status(500).json({ error: err.message || "OTP doğrulanamadı" });
    }
  });

  /**
   * POST /api/legal/sign
   */
  app.post("/api/legal/sign", async (req: Request, res: Response) => {
    try {
      const signRequest: SignContractRequest = req.body;

      const ipAddress =
        (req.headers["x-forwarded-for"] as string)?.split(",")[0]?.trim() ||
        req.socket.remoteAddress ||
        "127.0.0.1";
      const userAgent = req.headers["user-agent"] || "Unknown";
      const host = req.get("host") || "localhost:3000";
      const protocol = req.protocol || "http";

      const signedResult = await ContractEngineService.signAndSealContract(signRequest, {
        ipAddress,
        userAgent,
        host,
        protocol,
      });

      res.status(201).json(signedResult);
    } catch (err: any) {
      console.error("[LegalTech API] Sign error:", err);
      res.status(400).json({ error: err.message || "Sözleşme imzalanamadı" });
    }
  });

  /**
   * GET /api/legal/verify/:hash
   */
  app.get("/api/legal/verify/:hash", (req: Request, res: Response) => {
    try {
      const { hash } = req.params;
      if (!hash || hash.length !== 64) {
        return res.status(400).json({
          isValid: false,
          message: "Geçersiz SHA-256 hash formatı. 64 karakterli onaltılık dize beklenmektedir.",
        });
      }

      const verification = ContractEngineService.verifyContract(hash);
      res.json(verification);
    } catch (err: any) {
      res.status(500).json({ error: err.message || "Doğrulama yapılamadı" });
    }
  });

  /**
   * GET /api/legal/contracts/:id/pdf
   */
  app.get("/api/legal/contracts/:id/pdf", (req: Request, res: Response) => {
    try {
      const { id } = req.params;
      const pdf = ContractEngineService.getContractPdfBuffer(id);

      if (!pdf) {
        return res.status(404).json({ error: "Sözleşme PDF dosyası bulunamadı." });
      }

      res.setHeader("Content-Type", "application/pdf");
      res.setHeader("Content-Disposition", `inline; filename="${pdf.filename}"`);
      res.send(pdf.buffer);
    } catch (err: any) {
      res.status(500).json({ error: err.message || "PDF yüklenemedi" });
    }
  });
}
