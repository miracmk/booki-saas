import matrixData from "./sectorPricingMatrix.json";

export interface TierLimit {
  resource_limit: number | string | null;
  staff_limit: number | string | null;
  appointment_limit: number | string | null;
  resource_label: string;
  staff_label: string;
  appointment_label: string;
  raw?: string;
}

export interface SubsectorData {
  name: string;
  slug?: string;
  main_sector: string;
  vertical_group: string;
  metric_types?: {
    resource: string;
    staff: string;
    booking: string;
  };
  metric_raw?: string;
  resource_type_label: string;
  tiers: {
    free: TierLimit;
    basic: TierLimit;
    pro: TierLimit;
    premium: TierLimit;
    custom: TierLimit;
    Free: TierLimit;
    Basic: TierLimit;
    Pro: TierLimit;
    Premium: TierLimit;
    Custom: TierLimit;
  };
}

export interface MainSectorInfo {
  name: string;
  vertical_group: string;
  icon: string;
}

export const MAIN_SECTORS: Record<string, MainSectorInfo> = matrixData.mainSectors;

// Raw matrix with 168 subsectors
const rawMatrix = matrixData.matrix as Record<string, any>;

export const SUBSECTOR_NAMES: string[] = Object.keys(rawMatrix);

function normalizeTier(t: any, fallbackLabel = ""): TierLimit {
  if (!t) {
    return {
      resource_limit: "Sınırsız",
      staff_limit: "Sınırsız",
      appointment_limit: "Sınırsız",
      resource_label: fallbackLabel || "Sınırsız",
      staff_label: "Sınırsız",
      appointment_label: "Sınırsız"
    };
  }
  return {
    resource_limit: t.resource_limit !== null && t.resource_limit !== undefined ? t.resource_limit : "Sınırsız",
    staff_limit: t.staff_limit !== null && t.staff_limit !== undefined ? t.staff_limit : "Sınırsız",
    appointment_limit: t.appointment_limit !== null && t.appointment_limit !== undefined ? t.appointment_limit : "Sınırsız",
    resource_label: t.resource_label || String(t.resource_limit ?? "Sınırsız"),
    staff_label: t.staff_label || String(t.staff_limit ?? "Sınırsız"),
    appointment_label: t.appointment_label || String(t.appointment_limit ?? "Sınırsız"),
    raw: t.raw
  };
}

// Normalized map supporting both lower and upper case tier access
export const SECTOR_MATRIX: Record<string, SubsectorData> = {};

for (const [key, item] of Object.entries(rawMatrix)) {
  const rawTiers = item.tiers || {};
  const freeTier = normalizeTier(rawTiers.Free || rawTiers.free);
  const basicTier = normalizeTier(rawTiers.Basic || rawTiers.basic);
  const proTier = normalizeTier(rawTiers.Pro || rawTiers.pro);
  const premiumTier = normalizeTier(rawTiers.Premium || rawTiers.premium);
  const customTier = normalizeTier(rawTiers.Custom || rawTiers.custom);

  const resourceLabel = item.metric_types?.resource || "Koltuk / Masa / Kaynak";

  SECTOR_MATRIX[key] = {
    name: item.name || key,
    slug: item.slug,
    main_sector: item.main_sector,
    vertical_group: item.vertical_group,
    metric_types: item.metric_types,
    metric_raw: item.metric_raw,
    resource_type_label: resourceLabel,
    tiers: {
      free: freeTier,
      basic: basicTier,
      pro: proTier,
      premium: premiumTier,
      custom: customTier,
      Free: freeTier,
      Basic: basicTier,
      Pro: proTier,
      Premium: premiumTier,
      Custom: customTier,
    }
  };
}

export function getSubsectorData(name?: string): SubsectorData {
  if (name && SECTOR_MATRIX[name]) {
    return SECTOR_MATRIX[name];
  }
  const fallbackKey = SUBSECTOR_NAMES[0] || "Kuaför";
  return SECTOR_MATRIX[fallbackKey] || {
    name: "Genel İşletme",
    main_sector: "Genel",
    vertical_group: "general",
    resource_type_label: "Kaynak",
    tiers: {
      free: normalizeTier(null),
      basic: normalizeTier(null),
      pro: normalizeTier(null),
      premium: normalizeTier(null),
      custom: normalizeTier(null),
      Free: normalizeTier(null),
      Basic: normalizeTier(null),
      Pro: normalizeTier(null),
      Premium: normalizeTier(null),
      Custom: normalizeTier(null),
    }
  };
}

export const PLAN_PRICES = {
  free: { monthly: "0 ₺", annual: "0 ₺", rawMonthly: 0, rawAnnual: 0, name: "Ücretsiz", key: "free" },
  basic: { monthly: "1.250 ₺", annual: "1.000 ₺", rawMonthly: 1250, rawAnnual: 1000, name: "Başlangıç", key: "basic" },
  pro: { monthly: "2.450 ₺", annual: "1.950 ₺", rawMonthly: 2450, rawAnnual: 1950, name: "Orta (Pro)", key: "pro", popular: true },
  premium: { monthly: "4.750 ₺", annual: "3.800 ₺", rawMonthly: 4750, rawAnnual: 3800, name: "Premium", key: "premium" },
  custom: { monthly: "Özel Teklif", annual: "Özel Teklif", rawMonthly: 0, rawAnnual: 0, name: "Özel", key: "custom" },
};
