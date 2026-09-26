import type { Express } from "express";
import { buildAuthorizationUrl, exchangeCodeForTokens, isZohoAuthorized, isZohoConfigured } from "../zohoCrm";

// Bir kerelik kurulum akışı - kalıcı endpoint listesine dahil değil (ana navigasyonda linklenmiyor),
// sadece admin'in tarayıcıdan bir kez ziyaret edip Zoho hesabına izin vermesi için.
export function registerZohoCrmRoutes(app: Express) {
  app.get("/api/zoho/oauth/status", (_req, res) => {
    res.json({ configured: isZohoConfigured(), authorized: isZohoAuthorized() });
  });

  app.get("/api/zoho/oauth/start", (_req, res) => {
    if (!isZohoConfigured()) {
      res.status(500).send("ZOHO_CLIENT_ID / ZOHO_CLIENT_SECRET tanımlı değil.");
      return;
    }
    res.redirect(buildAuthorizationUrl());
  });

  app.get("/api/zoho/oauth/callback", async (req, res) => {
    const code = typeof req.query.code === "string" ? req.query.code : undefined;
    const error = typeof req.query.error === "string" ? req.query.error : undefined;

    if (error) {
      res.status(400).send(`Zoho yetkilendirmesi reddedildi: ${error}`);
      return;
    }
    if (!code) {
      res.status(400).send("Eksik 'code' parametresi.");
      return;
    }

    try {
      await exchangeCodeForTokens(code);
      res.send("Zoho CRM bağlantısı başarıyla kuruldu. Bu sekmeyi kapatabilirsiniz.");
    } catch (err) {
      res.status(500).send(`Zoho token değişimi başarısız: ${(err as Error).message}`);
    }
  });
}
