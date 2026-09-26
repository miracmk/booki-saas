import { useEffect } from "react";

interface SEOHeadProps {
  title: string;
  description: string;
  canonicalPath?: string;
  keywords?: string[];
  schemaJson?: Record<string, any>;
}

export function SEOHead({
  title,
  description,
  canonicalPath = "/",
  keywords = [],
  schemaJson
}: SEOHeadProps) {
  // Stabil bağımlılıklar: keywords/schemaJson her render'da yeni referans olduğundan
  // (state değişimi, hashchange) efekt tekrar çalışıp sayfayı en başa atıyordu.
  const keywordsKey = keywords.join("|");
  const schemaKey = schemaJson ? JSON.stringify(schemaJson) : "";

  // Sayfa (rota) değişiminde bir kez en başa kaydır; hash varsa tarayıcıya bırak.
  useEffect(() => {
    if (!window.location.hash) window.scrollTo(0, 0);
  }, [canonicalPath]);

  useEffect(() => {
    // 1. Update Title
    document.title = title;

    // 2. Update Meta Description
    let descMeta = document.querySelector('meta[name="description"]');
    if (!descMeta) {
      descMeta = document.createElement("meta");
      descMeta.setAttribute("name", "description");
      document.head.appendChild(descMeta);
    }
    descMeta.setAttribute("content", description);

    // 3. Update Meta Keywords
    if (keywords.length > 0) {
      let kwMeta = document.querySelector('meta[name="keywords"]');
      if (!kwMeta) {
        kwMeta = document.createElement("meta");
        kwMeta.setAttribute("name", "keywords");
        document.head.appendChild(kwMeta);
      }
      kwMeta.setAttribute("content", keywords.join(", "));
    }

    // 4. Update Canonical
    const fullUrl = `https://booki.kibusiness.co${canonicalPath.startsWith("/") ? canonicalPath : "/" + canonicalPath}`;
    let canonical = document.querySelector('link[rel="canonical"]');
    if (!canonical) {
      canonical = document.createElement("link");
      canonical.setAttribute("rel", "canonical");
      document.head.appendChild(canonical);
    }
    canonical.setAttribute("href", fullUrl);

    // 5. Update OG Tags
    let ogUrl = document.querySelector('meta[property="og:url"]');
    if (ogUrl) ogUrl.setAttribute("content", fullUrl);
    let ogTitle = document.querySelector('meta[property="og:title"]');
    if (ogTitle) ogTitle.setAttribute("content", title);
    let ogDesc = document.querySelector('meta[property="og:description"]');
    if (ogDesc) ogDesc.setAttribute("content", description);

    // 6. Inject / Update Dynamic JSON-LD Schema
    const schemaId = "dynamic-seo-schema";
    let existingScript = document.getElementById(schemaId);
    if (schemaJson) {
      if (!existingScript) {
        existingScript = document.createElement("script");
        existingScript.setAttribute("id", schemaId);
        existingScript.setAttribute("type", "application/ld+json");
        document.head.appendChild(existingScript);
      }
      existingScript.textContent = JSON.stringify(schemaJson);
    } else if (existingScript) {
      existingScript.remove();
    }
  }, [title, description, canonicalPath, keywordsKey, schemaKey]);

  return null;
}
