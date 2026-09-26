import express, { type Express, type Request, type Response, type NextFunction } from "express";
import fs from "fs";
import { type Server } from "http";
import { nanoid } from "nanoid";
import path from "path";
import { createServer as createViteServer } from "vite";
import viteConfig from "../../vite.config";

// ════════════════════════════════════════════════════════════════════
// SECURITY + SEO HEADERS
// Applied to every response. Dramatically improves:
//  • Security: HSTS, XSS prevention, clickjacking protection
//  • SEO: proper CORS for crawlers, cache headers
//  • GEO: AI crawlers can access content without restrictions
// ════════════════════════════════════════════════════════════════════
function applySecurityHeaders(_req: Request, res: Response, next: NextFunction) {
  // Enforce HTTPS for 2 years, include subdomains
  res.setHeader("Strict-Transport-Security", "max-age=63072000; includeSubDomains; preload");
  // Prevent MIME-type sniffing
  res.setHeader("X-Content-Type-Options", "nosniff");
  // Prevent clickjacking
  res.setHeader("X-Frame-Options", "SAMEORIGIN");
  // Control referrer info sent to external sites
  res.setHeader("Referrer-Policy", "strict-origin-when-cross-origin");
  // Modern permissions policy (camera, mic, geolocation off by default)
  res.setHeader("Permissions-Policy", "camera=(), microphone=(), geolocation=(), payment=()");
  // Content Security Policy — restrictive but functional for this SPA
  res.setHeader(
    "Content-Security-Policy",
    [
      "default-src 'self'",
      "script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.google-analytics.com",
      "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
      "font-src 'self' https://fonts.gstatic.com",
      "img-src 'self' data: https: blob:",
      "connect-src 'self' https://www.google-analytics.com https://region1.google-analytics.com https://analytics.umami.is",
      "frame-ancestors 'none'",
      "base-uri 'self'",
      "form-action 'self'",
    ].join("; ")
  );
  // Allow AI crawlers and general crawlers — critical for GEO
  res.setHeader("X-Robots-Tag", "index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1");
  next();
}

// ════════════════════════════════════════════════════════════════════
// CACHE CONTROL — Static assets get long cache; HTML always fresh
// ════════════════════════════════════════════════════════════════════
function staticCacheHeaders(req: Request, res: Response, next: NextFunction) {
  const url = req.url;
  // Hashed assets (JS, CSS, images with content hash in filename) — cache 1 year
  if (/\.(js|css|woff2?|ttf|otf)(\?.*)?$/.test(url) && /[.-][a-f0-9]{8,}\./.test(url)) {
    res.setHeader("Cache-Control", "public, max-age=31536000, immutable");
  }
  // Images without hashes — 30 days
  else if (/\.(png|jpg|jpeg|webp|avif|svg|ico|gif)(\?.*)?$/.test(url)) {
    res.setHeader("Cache-Control", "public, max-age=2592000, stale-while-revalidate=86400");
  }
  // HTML, robots.txt, sitemap — always validate freshness
  else if (/\.(html|txt|xml)(\?.*)?$/.test(url) || url === "/") {
    res.setHeader("Cache-Control", "public, max-age=0, must-revalidate");
  }
  next();
}

export async function setupVite(app: Express, server: Server) {
  app.use(applySecurityHeaders);

  const serverOptions = {
    middlewareMode: true,
    hmr: { server },
    allowedHosts: true as const,
  };

  const vite = await createViteServer({
    ...viteConfig,
    configFile: false,
    server: serverOptions,
    appType: "custom",
  });

  app.use(vite.middlewares);
  app.use("*", async (req, res, next) => {
    const url = req.originalUrl;

    try {
      const clientTemplate = path.resolve(
        import.meta.dirname,
        "../..",
        "client",
        "index.html"
      );

      // always reload the index.html file from disk incase it changes
      let template = await fs.promises.readFile(clientTemplate, "utf-8");
      template = template.replace(
        `src="/src/main.tsx"`,
        `src="/src/main.tsx?v=${nanoid()}"`
      );
      const page = await vite.transformIndexHtml(url, template);
      res.status(200).set({ "Content-Type": "text/html" }).end(page);
    } catch (e) {
      vite.ssrFixStacktrace(e as Error);
      next(e);
    }
  });
}

export function serveStatic(app: Express) {
  const distPath =
    process.env.NODE_ENV === "development"
      ? path.resolve(import.meta.dirname, "../..", "dist", "public")
      : path.resolve(import.meta.dirname, "public");
  if (!fs.existsSync(distPath)) {
    console.error(
      `Could not find the build directory: ${distPath}, make sure to build the client first`
    );
  }

  // Apply security headers globally
  app.use(applySecurityHeaders);
  // Apply smart cache headers
  app.use(staticCacheHeaders);

  // Explicit routes for Privacy Policy & Terms of Service (Google OAuth verification & compliance crawlers)
  app.get(["/privacy", "/privacy/", "/privacy.html", "/gizlilik", "/gizlilik/", "/gizlilik.html"], (_req, res) => {
    res.setHeader("Content-Type", "text/html; charset=utf-8");
    res.setHeader("Cache-Control", "public, max-age=3600, must-revalidate");
    const privacyPath = path.resolve(distPath, "privacy.html");
    if (fs.existsSync(privacyPath)) {
      return res.sendFile(privacyPath);
    }
    const privacyIndexPath = path.resolve(distPath, "privacy", "index.html");
    if (fs.existsSync(privacyIndexPath)) {
      return res.sendFile(privacyIndexPath);
    }
    res.sendFile(path.resolve(distPath, "index.html"));
  });

  app.get(["/terms", "/terms/", "/terms.html", "/kullanim", "/kullanim/", "/kullanim.html"], (_req, res) => {
    res.setHeader("Content-Type", "text/html; charset=utf-8");
    res.setHeader("Cache-Control", "public, max-age=3600, must-revalidate");
    const termsPath = path.resolve(distPath, "terms.html");
    if (fs.existsSync(termsPath)) {
      return res.sendFile(termsPath);
    }
    const termsIndexPath = path.resolve(distPath, "terms", "index.html");
    if (fs.existsSync(termsIndexPath)) {
      return res.sendFile(termsIndexPath);
    }
    res.sendFile(path.resolve(distPath, "index.html"));
  });

  // Company & legal compliance pages (iyzico review / compliance crawlers)
  app.get(["/hakkimizda", "/hakkimizda/", "/hakkimizda.html"], (_req, res) => {
    res.setHeader("Content-Type", "text/html; charset=utf-8");
    res.setHeader("Cache-Control", "public, max-age=3600, must-revalidate");
    const pagePath = path.resolve(distPath, "hakkimizda.html");
    if (fs.existsSync(pagePath)) {
      return res.sendFile(pagePath);
    }
    const pageIndexPath = path.resolve(distPath, "hakkimizda", "index.html");
    if (fs.existsSync(pageIndexPath)) {
      return res.sendFile(pageIndexPath);
    }
    res.sendFile(path.resolve(distPath, "index.html"));
  });

  app.get(["/mesafeli-satis", "/mesafeli-satis/", "/mesafeli-satis.html"], (_req, res) => {
    res.setHeader("Content-Type", "text/html; charset=utf-8");
    res.setHeader("Cache-Control", "public, max-age=3600, must-revalidate");
    const pagePath = path.resolve(distPath, "mesafeli-satis.html");
    if (fs.existsSync(pagePath)) {
      return res.sendFile(pagePath);
    }
    const pageIndexPath = path.resolve(distPath, "mesafeli-satis", "index.html");
    if (fs.existsSync(pageIndexPath)) {
      return res.sendFile(pageIndexPath);
    }
    res.sendFile(path.resolve(distPath, "index.html"));
  });

  app.get(["/teslimat-iade", "/teslimat-iade/", "/teslimat-iade.html"], (_req, res) => {
    res.setHeader("Content-Type", "text/html; charset=utf-8");
    res.setHeader("Cache-Control", "public, max-age=3600, must-revalidate");
    const pagePath = path.resolve(distPath, "teslimat-iade.html");
    if (fs.existsSync(pagePath)) {
      return res.sendFile(pagePath);
    }
    const pageIndexPath = path.resolve(distPath, "teslimat-iade", "index.html");
    if (fs.existsSync(pageIndexPath)) {
      return res.sendFile(pageIndexPath);
    }
    res.sendFile(path.resolve(distPath, "index.html"));
  });

  // Meta User Data Deletion Instructions & Status
  app.get(
    [
      "/data-deletion",
      "/data-deletion/",
      "/data-deletion.html",
      "/data-deletion-instructions",
      "/veri-silme",
      "/veri-silme/",
    ],
    (_req, res) => {
      res.setHeader("Content-Type", "text/html; charset=utf-8");
      res.setHeader("Cache-Control", "public, max-age=3600, must-revalidate");
      const deletionPath = path.resolve(distPath, "data-deletion.html");
      if (fs.existsSync(deletionPath)) {
        return res.sendFile(deletionPath);
      }
      const deletionIndexPath = path.resolve(distPath, "data-deletion", "index.html");
      if (fs.existsSync(deletionIndexPath)) {
        return res.sendFile(deletionIndexPath);
      }
      res.sendFile(path.resolve(distPath, "index.html"));
    }
  );

  // Meta User Data Deletion Callback (POST endpoint required if choosing "Data Deletion Callback URL" in Meta App Dashboard)
  app.all(["/api/meta/data-deletion", "/api/meta/delete"], (req, res) => {
    const confirmationCode =
      "DEL-" +
      Date.now().toString(36).toUpperCase() +
      "-" +
      Math.random().toString(36).substring(2, 7).toUpperCase();
    const statusUrl = `https://booki.kibusiness.co/data-deletion?code=${confirmationCode}`;

    res.setHeader("Content-Type", "application/json");
    return res.status(200).json({
      url: statusUrl,
      confirmation_code: confirmationCode,
    });
  });

  app.use(express.static(distPath, {
    // Don't set cache on index.html — let staticCacheHeaders handle it
    setHeaders: (res, filePath) => {
      // Already handled by staticCacheHeaders middleware above
      // This is intentionally left minimal
      if (filePath.endsWith("robots.txt") || filePath.endsWith("sitemap.xml") || filePath.endsWith("llms.txt")) {
        res.setHeader("Cache-Control", "public, max-age=3600, must-revalidate");
      }
    }
  }));

  // fall through to index.html if the file doesn't exist
  app.use("*", (_req, res) => {
    res.setHeader("Cache-Control", "public, max-age=0, must-revalidate");
    res.sendFile(path.resolve(distPath, "index.html"));
  });
}
