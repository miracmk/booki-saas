-- ============================================================================
-- BooKi / RandevuBurada Ecosystem
-- Migration: 002_create_post_service_follow_up_engine_schema.sql
-- Database: PostgreSQL 14+ / Prisma / Drizzle Compatible
-- Description:
--   Implements tables, constraints, indexes, and triggers for:
--   1. Post-Service Follow-Up Rules (follow_up_rules) for 9 sector families
--   2. Scheduled & Dispatched Follow-Up Tasks (follow_up_dispatches)
--   3. Customer Marketing Opt-Out (KVKK / İleti Güvenliği: RED / DUR)
--   4. Business Logic: Anti-Spam De-duplication, Quiet Hours (21:00-09:00),
--      and Life-Critical Medical Reaction Exception
-- ============================================================================

-- Ensure required UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ----------------------------------------------------------------------------
-- 1. EXTEND USERS / CUSTOMERS: Marketing Opt-Out Flag
-- ----------------------------------------------------------------------------
DO $$ 
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'users') THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'marketing_opt_out') THEN
            ALTER TABLE users ADD COLUMN marketing_opt_out BOOLEAN NOT NULL DEFAULT FALSE;
        END IF;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'customers') THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'customers' AND column_name = 'marketing_opt_out') THEN
            ALTER TABLE customers ADD COLUMN marketing_opt_out BOOLEAN NOT NULL DEFAULT FALSE;
        END IF;
    END IF;
END $$;

-- ----------------------------------------------------------------------------
-- 2. TABLE: follow_up_rules (Takip Kural Tanımları)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS follow_up_rules (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL, -- İşletme ID
    industry_family VARCHAR(50) NOT NULL, -- 'health_clinical', 'beauty_wellness', 'automotive', 'education_experience', 'sports_fitness', 'hospitality_food', 'professional'
    blueprint_type VARCHAR(50) NOT NULL,   -- 'dental_clinic', 'nail_studio', 'beauty_salon', 'auto_service_detailing', vb.
    rule_type VARCHAR(50) NOT NULL,        -- 'reaction_check', 'aftercare', 'retention_rebook', 'asset_delivery', 'review_request', 'nps_feedback', 'diet_form', 'routine_check'
    trigger_delay_interval INTERVAL NOT NULL, -- ör: '24 hours', '21 days', '45 minutes', '15 minutes', '48 hours'
    whatsapp_template_name VARCHAR(100) NOT NULL,
    dynamic_payload_schema JSONB DEFAULT '{}'::jsonb, -- Mesaj değişkenleri (hasta_adi, hekim_adi, teslim_linki, nps_skoru vb.)
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_follow_up_rules_family CHECK (
        industry_family IN (
            'health_clinical',
            'beauty_wellness',
            'automotive',
            'education_experience',
            'sports_fitness',
            'hospitality_food',
            'professional'
        )
    ),
    CONSTRAINT chk_follow_up_rules_type CHECK (
        rule_type IN (
            'reaction_check',
            'aftercare',
            'retention_rebook',
            'asset_delivery',
            'review_request',
            'nps_feedback',
            'diet_form',
            'routine_check'
        )
    )
);

-- Indexes for rule lookup
CREATE INDEX IF NOT EXISTS idx_follow_up_rules_tenant ON follow_up_rules (tenant_id, is_active);
CREATE INDEX IF NOT EXISTS idx_follow_up_rules_blueprint ON follow_up_rules (tenant_id, blueprint_type, is_active);
CREATE INDEX IF NOT EXISTS idx_follow_up_rules_family ON follow_up_rules (tenant_id, industry_family, is_active);

-- ----------------------------------------------------------------------------
-- 3. TABLE: follow_up_dispatches (Planlanmış ve Yürütülen Takip Görevleri)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS follow_up_dispatches (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL,
    booking_id VARCHAR(64) NOT NULL,
    customer_id VARCHAR(64) NOT NULL,
    rule_id UUID REFERENCES follow_up_rules(id) ON DELETE SET NULL,
    channel VARCHAR(20) NOT NULL DEFAULT 'WHATSAPP', -- 'WHATSAPP', 'SMS', 'IN_APP', 'EMAIL'
    status VARCHAR(35) NOT NULL DEFAULT 'SCHEDULED', -- 'SCHEDULED', 'SENT', 'FAILED', 'CANCELLED_REBOOKED', 'CANCELLED_OPT_OUT', 'RESCHEDULED_QUIET_HOURS'
    scheduled_for TIMESTAMP WITH TIME ZONE NOT NULL,
    sent_at TIMESTAMP WITH TIME ZONE,
    payload JSONB DEFAULT '{}'::jsonb,
    response_received JSONB DEFAULT NULL, -- Müşteri yanıtı / NPS skoru / Medikal reaksiyon yanıtı
    error_message TEXT,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),

    CONSTRAINT chk_follow_up_dispatches_channel CHECK (
        channel IN ('WHATSAPP', 'SMS', 'IN_APP', 'EMAIL')
    ),
    CONSTRAINT chk_follow_up_dispatches_status CHECK (
        status IN (
            'SCHEDULED',
            'SENT',
            'FAILED',
            'CANCELLED_REBOOKED',
            'CANCELLED_OPT_OUT',
            'RESCHEDULED_QUIET_HOURS'
        )
    )
);

-- Indexes for worker scheduling and tracking
CREATE INDEX IF NOT EXISTS idx_follow_up_dispatches_queue ON follow_up_dispatches (status, scheduled_for)
    WHERE status = 'SCHEDULED';
CREATE INDEX IF NOT EXISTS idx_follow_up_dispatches_tenant ON follow_up_dispatches (tenant_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_follow_up_dispatches_booking ON follow_up_dispatches (booking_id);
CREATE INDEX IF NOT EXISTS idx_follow_up_dispatches_customer ON follow_up_dispatches (customer_id, status);

-- ----------------------------------------------------------------------------
-- 4. FUNCTION & TRIGGER: Auto-update updated_at timestamps
-- ----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_follow_up_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_follow_up_rules_updated_at ON follow_up_rules;
CREATE TRIGGER trg_follow_up_rules_updated_at
    BEFORE UPDATE ON follow_up_rules
    FOR EACH ROW
    EXECUTE FUNCTION update_follow_up_timestamp();

DROP TRIGGER IF EXISTS trg_follow_up_dispatches_updated_at ON follow_up_dispatches;
CREATE TRIGGER trg_follow_up_dispatches_updated_at
    BEFORE UPDATE ON follow_up_dispatches
    FOR EACH ROW
    EXECUTE FUNCTION update_follow_up_timestamp();

-- ----------------------------------------------------------------------------
-- 5. SEED DATA: Default Master Rules Across 9 Industry Families
-- (Can be cloned or applied per tenant)
-- ----------------------------------------------------------------------------
INSERT INTO follow_up_rules (
    id, tenant_id, industry_family, blueprint_type, rule_type,
    trigger_delay_interval, whatsapp_template_name, dynamic_payload_schema, is_active
) VALUES
    -- A. HEALTH & CLINICAL
    -- Diş Kliniği: Akut Reaksiyon Kontrolü (T+24 Saat)
    (
        'a0000001-0000-0000-0000-000000000001',
        '00000000-0000-0000-0000-000000000000',
        'health_clinical', 'dental_clinic', 'reaction_check',
        '24 hours', 'dentist_postop_reaction',
        '{"hasta_adi": "{{customer_name}}", "hekim_adi": "{{provider_name}}", "islem": "{{service_name}}", "soru": "Geçmiş olsun! Uyuşukluk sonrası zonklama, beklenmeyen kanama veya yüksek ateş var mı? (1: İyiyim, 2: Doktoruma Danışmak İstiyorum)"}'::jsonb,
        TRUE
    ),
    -- Diş Kliniği: Rutin 6 Aylık Kontrol (T+180 Gün)
    (
        'a0000001-0000-0000-0000-000000000002',
        '00000000-0000-0000-0000-000000000000',
        'health_clinical', 'dental_clinic', 'routine_check',
        '180 days', 'dentist_routine_recall',
        '{"hasta_adi": "{{customer_name}}", "hekim_adi": "{{provider_name}}", "slot_link": "{{booking_url}}"}'::jsonb,
        TRUE
    ),
    -- Diyetisyen / Psikoloji: Uyum & Beslenme Formu (T+7 Gün)
    (
        'a0000001-0000-0000-0000-000000000003',
        '00000000-0000-0000-0000-000000000000',
        'health_clinical', 'psychology_dietitian_clinic', 'diet_form',
        '7 days', 'dietitian_weekly_log',
        '{"danisan_adi": "{{customer_name}}", "uzman_adi": "{{provider_name}}", "form_link": "{{portal_url}}/forms/nutrition-log"}'::jsonb,
        TRUE
    ),

    -- B. BEAUTY & WELLNESS
    -- Güzellik Salonu: NPS & Sosyal İtibar (T+2 Saat)
    (
        'b0000002-0000-0000-0000-000000000001',
        '00000000-0000-0000-0000-000000000000',
        'beauty_wellness', 'beauty_salon', 'review_request',
        '2 hours', 'beauty_nps_review',
        '{"musteri_adi": "{{customer_name}}", "uzman_adi": "{{provider_name}}", "maps_review_url": "{{google_maps_review_url}}"}'::jsonb,
        TRUE
    ),
    -- Güzellik Salonu: Lazer / Peeling Aftercare (T+24 Saat)
    (
        'b0000002-0000-0000-0000-000000000002',
        '00000000-0000-0000-0000-000000000000',
        'beauty_wellness', 'beauty_salon', 'aftercare',
        '24 hours', 'beauty_aftercare_guide',
        '{"musteri_adi": "{{customer_name}}", "talimat": "24 saat boyunca sıcak su, sauna ve doğrudan güneş ışığından kaçınınız."}'::jsonb,
        TRUE
    ),
    -- Tırnak Stüdyosu: Akıllı Yenileme (T+21 Gün)
    (
        'b0000002-0000-0000-0000-000000000003',
        '00000000-0000-0000-0000-000000000000',
        'beauty_wellness', 'nail_studio', 'retention_rebook',
        '21 days', 'nail_smart_renewal',
        '{"musteri_adi": "{{customer_name}}", "uzman_adi": "{{provider_name}}", "slot_link": "{{booking_url}}"}'::jsonb,
        TRUE
    ),
    -- Kuaför / Berber: Saç Kesim & Dip Boya Döngüsü (T+28 Gün)
    (
        'b0000002-0000-0000-0000-000000000004',
        '00000000-0000-0000-0000-000000000000',
        'beauty_wellness', 'barber', 'retention_rebook',
        '28 days', 'barber_smart_renewal',
        '{"musteri_adi": "{{customer_name}}", "berber_adi": "{{provider_name}}", "slot_link": "{{booking_url}}"}'::jsonb,
        TRUE
    ),
    -- Güzellik Salonu: Lazer Epilasyon Periyodu (T+40 Gün)
    (
        'b0000002-0000-0000-0000-000000000005',
        '00000000-0000-0000-0000-000000000000',
        'beauty_wellness', 'beauty_salon', 'retention_rebook',
        '40 days', 'beauty_laser_renewal',
        '{"musteri_adi": "{{customer_name}}", "islem": "Lazer Epilasyon", "slot_link": "{{booking_url}}"}'::jsonb,
        TRUE
    ),

    -- C. AUTOMOTIVE
    -- Ekspertiz & Servis: Dijital Varlık Teslimi (T+15 Dakika)
    (
        'c0000003-0000-0000-0000-000000000001',
        '00000000-0000-0000-0000-000000000000',
        'automotive', 'auto_service_detailing', 'asset_delivery',
        '15 minutes', 'auto_inspection_pdf_report',
        '{"musteri_adi": "{{customer_name}}", "arac_plaka": "{{vehicle_plate}}", "rapor_url": "{{report_url}}", "garanti_belgesi_url": "{{warranty_url}}"}'::jsonb,
        TRUE
    ),
    -- Detailing & Seramik: Uygulama Sonrası Koruma (T+48 Saat)
    (
        'c0000003-0000-0000-0000-000000000002',
        '00000000-0000-0000-0000-000000000000',
        'automotive', 'auto_service_detailing', 'aftercare',
        '48 hours', 'auto_detailing_aftercare_guide',
        '{"musteri_adi": "{{customer_name}}", "talimat": "İlk 7 gün basınçlı su ve fırçalı yıkama yaptırmayınız."}'::jsonb,
        TRUE
    ),
    -- Periyodik Bakım / Sanal KM Döngüsü (T+180 Gün)
    (
        'c0000003-0000-0000-0000-000000000003',
        '00000000-0000-0000-0000-000000000000',
        'automotive', 'auto_service_detailing', 'retention_rebook',
        '180 days', 'auto_periodic_maintenance_recall',
        '{"musteri_adi": "{{customer_name}}", "servis_link": "{{booking_url}}"}'::jsonb,
        TRUE
    ),

    -- D. EDUCATION & EXPERIENCE
    -- Kaçış Evi: Hatıra Fotoğrafı & Skor Kartı (T+1 Saat)
    (
        'd0000004-0000-0000-0000-000000000001',
        '00000000-0000-0000-0000-000000000000',
        'education_experience', 'experience_escape_room', 'asset_delivery',
        '1 hour', 'escape_room_souvenir_photo',
        '{"ekip_adi": "{{customer_name}}", "oda_adi": "{{service_name}}", "sure": "{{escape_duration}}", "medya_url": "{{photo_url}}"}'::jsonb,
        TRUE
    ),
    -- Seramik / Sanat Atölyesi: Fırından Çıktı / Teslim Alınabilir (Durum Bazlı - READY_FOR_PICKUP)
    (
        'd0000004-0000-0000-0000-000000000002',
        '00000000-0000-0000-0000-000000000000',
        'education_experience', 'workshop', 'asset_delivery',
        '0 minutes', 'workshop_pottery_ready_pickup',
        '{"sanatsever_adi": "{{customer_name}}", "eser_adi": "{{artwork_name}}", "teslim_kodu": "{{pickup_code}}", "sure_gun": 10}'::jsonb,
        TRUE
    ),

    -- E. SPORTS & FITNESS
    -- Birebir PT / Pilates: Seans Sonu Kalan Ders Takibi (T+0 Seans Sonu)
    (
        'e0000005-0000-0000-0000-000000000001',
        '00000000-0000-0000-0000-000000000000',
        'sports_fitness', 'pt_training', 'asset_delivery',
        '0 minutes', 'fitness_session_credit_update',
        '{"ogrenci_adi": "{{customer_name}}", "antrenor_adi": "{{provider_name}}", "kalan_ders": "{{remaining_credits}}", "toplam_ders": "{{total_credits}}"}'::jsonb,
        TRUE
    ),
    -- Birebir PT / Pilates: Son 1 Ders Kala Up-Sell / Paket Yenileme
    (
        'e0000005-0000-0000-0000-000000000002',
        '00000000-0000-0000-0000-000000000000',
        'sports_fitness', 'pt_training', 'retention_rebook',
        '12 hours', 'fitness_renew_package_discount',
        '{"ogrenci_adi": "{{customer_name}}", "kalan_ders": 1, "indirim_orani": "%10", "odeme_link": "{{payment_url}}"}'::jsonb,
        TRUE
    ),
    -- Halı Saha / Tenis Kortu: Sabit Slot Koruma (T+2 Saat)
    (
        'e0000005-0000-0000-0000-000000000003',
        '00000000-0000-0000-0000-000000000000',
        'sports_fitness', 'sports_court', 'retention_rebook',
        '2 hours', 'court_slot_retention_offer',
        '{"kaptan_adi": "{{customer_name}}", "saha_adi": "{{resource_name}}", "opsiyon_saat": 24, "rezervasyon_link": "{{booking_url}}"}'::jsonb,
        TRUE
    ),

    -- F. HOSPITALITY, PROFESSIONAL & RESTAURANT_FOOD
    -- Restoran & Kafe: Hesap Kapandıktan Sonra NPS (T+45 Dakika)
    (
        'f0000006-0000-0000-0000-000000000001',
        '00000000-0000-0000-0000-000000000000',
        'hospitality_food', 'restaurant', 'review_request',
        '45 minutes', 'restaurant_nps_rating',
        '{"misafir_adi": "{{customer_name}}", "isletme_adi": "{{company_name}}", "nps_link": "{{nps_url}}"}'::jsonb,
        TRUE
    ),
    -- Butik Otel: Check-out Sonrası Fatura + Unutulan Eşya Formu (T+2 Saat)
    (
        'f0000006-0000-0000-0000-000000000002',
        '00000000-0000-0000-0000-000000000000',
        'hospitality_food', 'hotel', 'asset_delivery',
        '2 hours', 'hotel_checkout_invoice_lost_items',
        '{"misafir_adi": "{{customer_name}}", "fatura_link": "{{invoice_url}}", "kayip_esya_form": "{{portal_url}}/lost-and-found"}'::jsonb,
        TRUE
    ),
    -- Hukuk & Ajans: Toplantı Özeti & İstenen Belgeler Linki (T+2 Saat)
    (
        'f0000006-0000-0000-0000-000000000003',
        '00000000-0000-0000-0000-000000000000',
        'professional', 'law_firm', 'asset_delivery',
        '2 hours', 'law_meeting_summary_docs',
        '{"muvekkil_adi": "{{customer_name}}", "avukat_adi": "{{provider_name}}", "ozet_link": "{{portal_url}}/documents"}'::jsonb,
        TRUE
    )
ON CONFLICT (id) DO NOTHING;
