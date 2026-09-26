// BooKi platform-level Zoho CRM entegrasyonu (2026-09-16) - kiracı/tenant CRM'inden AYRI,
// Ki Software'in kendi satış hunisi için: booki.kibusiness.co'daki demo/deneme formundan gelen
// her talep otomatik olarak Zoho CRM'de bir Lead kaydına dönüşür.
//
// Bilinçli tasarım kararı: resmi @zohocrm/nodejs-sdk yerine doğrudan REST API kullanıldı - SDK
// kendi dosya/DB tabanlı token store'unu, initializer boilerplate'ini ve logger yapılandırmasını
// gerektiriyor; bu küçük marketing sitesi için gereksiz ağırlık. Aynı OAuth2 akışını (authorization
// code -> refresh token -> access token) doğrudan fetch() ile uyguluyoruz.
//
// Zoho'nun sunucu-taraflı (self-client) OAuth akışı insan onayı gerektirir - client id/secret tek
// başına API çağrısı yapmaya yetmez. Bir kerelik kurulum: GET /api/zoho/oauth/start ziyaret edilip
// Zoho hesabıyla giriş yapılıp izin verilmeli; bu /api/zoho/oauth/callback'e bir "code" ile döner,
// o da kalıcı bir refresh token'a çevrilip diske yazılır (ZOHO_TOKEN_STORE_PATH). Refresh token
// bir kez alındıktan sonra süresi dolmaz (kullanıcı elle iptal etmediği sürece), access token'lar
// otomatik yenilenir.
import fs from "fs";
import path from "path";

type TokenStore = {
  refreshToken: string;
  accessToken?: string;
  accessTokenExpiresAt?: number;
};

const DATA_CENTER_HOSTS: Record<string, { accounts: string; api: string }> = {
  US: { accounts: "https://accounts.zoho.com", api: "https://www.zohoapis.com" },
  EU: { accounts: "https://accounts.zoho.eu", api: "https://www.zohoapis.eu" },
  IN: { accounts: "https://accounts.zoho.in", api: "https://www.zohoapis.in" },
  AU: { accounts: "https://accounts.zoho.com.au", api: "https://www.zohoapis.com.au" },
  JP: { accounts: "https://accounts.zoho.jp", api: "https://www.zohoapis.jp" },
};

function getHosts() {
  const dc = (process.env.ZOHO_DATA_CENTER || "US").toUpperCase();
  return DATA_CENTER_HOSTS[dc] || DATA_CENTER_HOSTS.US;
}

function getTokenStorePath() {
  return process.env.ZOHO_TOKEN_STORE_PATH || path.resolve(process.cwd(), "data", "zoho-token.json");
}

function readTokenStore(): TokenStore | null {
  try {
    const raw = fs.readFileSync(getTokenStorePath(), "utf8");
    return JSON.parse(raw) as TokenStore;
  } catch {
    return null;
  }
}

function writeTokenStore(store: TokenStore) {
  const file = getTokenStorePath();
  fs.mkdirSync(path.dirname(file), { recursive: true });
  fs.writeFileSync(file, JSON.stringify(store, null, 2), { mode: 0o600 });
}

export function isZohoConfigured(): boolean {
  return Boolean(process.env.ZOHO_CLIENT_ID && process.env.ZOHO_CLIENT_SECRET);
}

export function isZohoAuthorized(): boolean {
  return Boolean(readTokenStore()?.refreshToken);
}

export function buildAuthorizationUrl(): string {
  const { accounts } = getHosts();
  const params = new URLSearchParams({
    scope: "ZohoCRM.modules.leads.CREATE,ZohoCRM.modules.leads.UPDATE,ZohoCRM.modules.leads.READ",
    client_id: process.env.ZOHO_CLIENT_ID || "",
    response_type: "code",
    access_type: "offline",
    redirect_uri: process.env.ZOHO_REDIRECT_URI || "",
    prompt: "consent",
  });
  return `${accounts}/oauth/v2/auth?${params.toString()}`;
}

export async function exchangeCodeForTokens(code: string): Promise<void> {
  const { accounts } = getHosts();
  const params = new URLSearchParams({
    grant_type: "authorization_code",
    client_id: process.env.ZOHO_CLIENT_ID || "",
    client_secret: process.env.ZOHO_CLIENT_SECRET || "",
    redirect_uri: process.env.ZOHO_REDIRECT_URI || "",
    code,
  });

  const response = await fetch(`${accounts}/oauth/v2/token`, { method: "POST", body: params });
  const data = (await response.json()) as {
    refresh_token?: string;
    access_token?: string;
    expires_in?: number;
    error?: string;
  };

  if (!data.refresh_token) {
    throw new Error(`Zoho token exchange failed: ${data.error || JSON.stringify(data)}`);
  }

  writeTokenStore({
    refreshToken: data.refresh_token,
    accessToken: data.access_token,
    accessTokenExpiresAt: data.access_token ? Date.now() + (data.expires_in || 3600) * 1000 : undefined,
  });
}

async function getAccessToken(): Promise<string> {
  const store = readTokenStore();
  if (!store?.refreshToken) {
    throw new Error("Zoho CRM not authorized yet - visit /api/zoho/oauth/start once to grant access.");
  }

  // 60 saniyelik güvenlik payıyla önbellekteki access token'ı kullan.
  if (store.accessToken && store.accessTokenExpiresAt && store.accessTokenExpiresAt - 60_000 > Date.now()) {
    return store.accessToken;
  }

  const { accounts } = getHosts();
  const params = new URLSearchParams({
    grant_type: "refresh_token",
    client_id: process.env.ZOHO_CLIENT_ID || "",
    client_secret: process.env.ZOHO_CLIENT_SECRET || "",
    refresh_token: store.refreshToken,
  });

  const response = await fetch(`${accounts}/oauth/v2/token`, { method: "POST", body: params });
  const data = (await response.json()) as { access_token?: string; expires_in?: number; error?: string };

  if (!data.access_token) {
    throw new Error(`Zoho access token refresh failed: ${data.error || JSON.stringify(data)}`);
  }

  writeTokenStore({
    ...store,
    accessToken: data.access_token,
    accessTokenExpiresAt: Date.now() + (data.expires_in || 3600) * 1000,
  });

  return data.access_token;
}

export type ZohoLeadInput = {
  name: string;
  email: string;
  phone: string;
  businessType: string;
  plan?: string;
  preferredContactTime?: string;
  source: "Demo Talebi" | "Ücretsiz Deneme";
};

function splitName(fullName: string): { firstName?: string; lastName: string } {
  const parts = fullName.trim().split(/\s+/);
  if (parts.length === 1) return { lastName: parts[0] };
  return { firstName: parts.slice(0, -1).join(" "), lastName: parts[parts.length - 1] };
}

// Best-effort: e-posta bildirimi zaten birincil kanal (demoEmail.ts), bu yüzden Zoho çağrısı
// başarısız olursa kullanıcıya gösterilen forma hata YANSITILMAZ, sadece sunucu logunda uyarı verir.
export async function createZohoLead(input: ZohoLeadInput): Promise<void> {
  if (!isZohoConfigured() || !isZohoAuthorized()) {
    return;
  }

  const { api } = getHosts();
  const accessToken = await getAccessToken();
  const { firstName, lastName } = splitName(input.name);

  const description = [
    `Kaynak: BooKi web sitesi (${input.source})`,
    input.plan ? `Seçilen paket: ${input.plan}` : null,
    input.preferredContactTime ? `Tercih edilen iletişim zamanı: ${input.preferredContactTime}` : null,
  ]
    .filter(Boolean)
    .join("\n");

  const response = await fetch(`${api}/crm/v8/Leads`, {
    method: "POST",
    headers: {
      Authorization: `Zoho-oauthtoken ${accessToken}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      data: [
        {
          Last_Name: lastName,
          First_Name: firstName,
          Email: input.email,
          Phone: input.phone,
          Company: input.businessType,
          Lead_Source: "BooKi Web Sitesi",
          Description: description,
        },
      ],
      trigger: ["approval", "workflow"],
    }),
  });

  const result = await response.json().catch(() => null);
  const status = result?.data?.[0]?.status;
  if (status !== "success") {
    throw new Error(`Zoho lead creation failed: ${JSON.stringify(result)}`);
  }
}
