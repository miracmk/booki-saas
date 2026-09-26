import type { Express } from "express";
import fs from "fs";
import path from "path";
import { ENV } from "./env";

export function registerStorageProxy(app: Express) {
  app.get("/manus-storage/*", async (req, res, next) => {
    const key = (req.params as Record<string, string>)[0];
    if (!key) {
      res.status(400).send("Missing storage key");
      return;
    }

    // 1. Check local static files first (fast & reliable)
    const possiblePaths = [
      path.resolve(import.meta.dirname, "public", "manus-storage", key),
      path.resolve(import.meta.dirname, "../..", "dist", "public", "manus-storage", key),
      path.resolve(import.meta.dirname, "../..", "client", "public", "manus-storage", key),
    ];

    for (const p of possiblePaths) {
      if (fs.existsSync(p)) {
        res.setHeader("Cache-Control", "public, max-age=2592000, stale-while-revalidate=86400");
        res.sendFile(p);
        return;
      }
    }

    // 2. If no local file and forge proxy not configured, return clean 404
    if (!ENV.forgeApiUrl || !ENV.forgeApiKey) {
      res.status(404).send("File not found");
      return;
    }

    // 3. Fallback to forge API presigned URL if configured
    try {
      const forgeUrl = new URL(
        "v1/storage/presign/get",
        ENV.forgeApiUrl.replace(/\/+$/, "") + "/",
      );
      forgeUrl.searchParams.set("path", key);

      const forgeResp = await fetch(forgeUrl, {
        headers: { Authorization: `Bearer ${ENV.forgeApiKey}` },
      });

      if (!forgeResp.ok) {
        const body = await forgeResp.text().catch(() => "");
        console.error(`[StorageProxy] forge error: ${forgeResp.status} ${body}`);
        res.status(502).send("Storage backend error");
        return;
      }

      const { url } = (await forgeResp.json()) as { url: string };
      if (!url) {
        res.status(502).send("Empty signed URL from backend");
        return;
      }

      res.set("Cache-Control", "no-store");
      res.redirect(307, url);
    } catch (err) {
      console.error("[StorageProxy] failed:", err);
      res.status(502).send("Storage proxy error");
    }
  });
}
