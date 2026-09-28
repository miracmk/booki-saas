-- ============================================================================
-- BooKi / RandevuBurada Ecosystem
-- Migration: 001_create_preauth_deposit_and_split_payment_schema.sql
-- Database: PostgreSQL 14+
-- Description:
--   Implements tables, constraints, and audit logs for:
--   1. Hybrid Gateway Models (Platform Gateway, Custom Gateway, Disabled)
--   2. Pre-Authorization Hold & Max 7-Day Window Constraints
--   3. Legal Proportionality (Turkish Obligations Code - TBK Art. 178: Max 25% Deposit)
--   4. Global Deposit Ban for Doctors / Medical Clinics
--   5. Merchant Credit Ledger & Balance Deduction Tracking
-- ============================================================================

-- Ensure required extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ----------------------------------------------------------------------------
-- 1. TABLE: merchants
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS merchants (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID DEFAULT uuid_generate_v4() UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    subdomain VARCHAR(100) UNIQUE NOT NULL,
    merchant_type VARCHAR(50) NOT NULL DEFAULT 'salon' 
        CHECK (merchant_type IN ('salon', 'gym', 'clinic', 'doctor', 'beauty_center', 'spa', 'other')),
    
    -- Hybrid Payment Gateway Modes:
    -- 'platform_gateway': Platform collects via Tosla/Iyzico/PayTR, takes commission, routes net to merchant
    -- 'custom_gateway': Merchant uses own Sanal POS credentials; Platform deducts 200 TL fee from credit_balance
    -- 'disabled': No online deposit/payment
    payment_gateway_mode VARCHAR(30) NOT NULL DEFAULT 'disabled'
        CHECK (payment_gateway_mode IN ('platform_gateway', 'custom_gateway', 'disabled')),
    
    custom_gateway_provider VARCHAR(50) NULL, -- 'tosla', 'iyzico', 'paytr', etc.
    custom_gateway_credentials JSONB NULL,    -- Encrypted API user, pass, client_id, store_key
    
    -- Credit Balance for Custom Gateway Fee Deductions (allows negative balances)
    credit_balance NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    min_credit_balance_alert NUMERIC(12, 2) NOT NULL DEFAULT 200.00,
    
    -- Marketplace Split & Settlement
    sub_merchant_id VARCHAR(100) NULL,        -- Payment gateway sub-merchant ID for direct split
    payout_iban VARCHAR(34) NULL,
    
    -- Deposit Configuration
    deposit_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    -- TBK Art. 178: Deposit cannot exceed 25% of service price
    deposit_percentage NUMERIC(5, 2) NOT NULL DEFAULT 20.00 
        CHECK (deposit_percentage >= 0.00 AND deposit_percentage <= 25.00),
    
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Rule: Medical exception - Doctors and Clinics are legally forbidden from taking reservation deposits
    CONSTRAINT chk_merchant_doctor_no_deposit 
        CHECK (merchant_type NOT IN ('doctor', 'clinic') OR deposit_enabled = FALSE)
);

-- ----------------------------------------------------------------------------
-- 2. TABLE: appointments
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS appointments (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID DEFAULT uuid_generate_v4() UNIQUE NOT NULL,
    merchant_id BIGINT NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    customer_id BIGINT NULL,
    service_id BIGINT NULL,
    service_name VARCHAR(255) NOT NULL,
    
    -- Financials & TBK Art. 178 Proportionality Rule
    service_price NUMERIC(12, 2) NOT NULL CHECK (service_price >= 0),
    deposit_amount NUMERIC(12, 2) NOT NULL DEFAULT 0.00 CHECK (deposit_amount >= 0),
    
    start_datetime TIMESTAMPTZ NOT NULL,
    end_datetime TIMESTAMPTZ NOT NULL,
    
    status VARCHAR(30) NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'confirmed', 'customer_arrived', 'completed', 'no_show', 'cancelled')),
    
    -- Pre-authorization Lifecycle
    -- 'none': No hold
    -- 'held': Pre-authorization hold active on customer card
    -- 'voided': Pre-authorization voided (Scenario A: Customer Arrived)
    -- 'captured': Pre-authorization partially or fully captured (Scenario B: No-Show)
    -- 'failed': Pre-auth failed
    -- 'refunded': Captured funds refunded
    preauth_status VARCHAR(30) NOT NULL DEFAULT 'none'
        CHECK (preauth_status IN ('none', 'held', 'voided', 'captured', 'failed', 'refunded')),
    
    payment_gateway_used VARCHAR(50) NULL,
    preauth_transaction_id VARCHAR(150) NULL, -- Gateway authorization code / order ID
    preauth_held_at TIMESTAMPTZ NULL,
    preauth_expires_at TIMESTAMPTZ NULL,      -- Bank holds expire after max 7 days
    
    -- Split Amounts upon capture (Scenario B)
    captured_amount NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    platform_commission_amount NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    merchant_payout_amount NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    
    -- Legal Terms of Service Consent (TBK Art. 178)
    legal_terms_accepted BOOLEAN NOT NULL DEFAULT FALSE,
    legal_terms_text TEXT NULL,
    legal_terms_accepted_at TIMESTAMPTZ NULL,
    legal_terms_ip VARCHAR(45) NULL,
    
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Legal Proportionality Constraint (TBK Art. 178: Deposit cannot exceed 25% of total service price)
    CONSTRAINT chk_deposit_max_25 
        CHECK (deposit_amount <= ROUND(service_price * 0.25, 2)),
    
    -- Temporal Rule: Pre-authorization hold window cannot exceed 7 days
    CONSTRAINT chk_preauth_duration_max_7_days 
        CHECK (preauth_expires_at IS NULL OR preauth_held_at IS NULL OR preauth_expires_at <= preauth_held_at + INTERVAL '7 days')
);

-- ----------------------------------------------------------------------------
-- 3. TABLE: merchant_credit_logs
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS merchant_credit_logs (
    id BIGSERIAL PRIMARY KEY,
    merchant_id BIGINT NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    appointment_id BIGINT NULL REFERENCES appointments(id) ON DELETE SET NULL,
    amount NUMERIC(12, 2) NOT NULL, -- e.g. -200.00 when platform fee is deducted
    balance_before NUMERIC(12, 2) NOT NULL,
    balance_after NUMERIC(12, 2) NOT NULL,
    transaction_type VARCHAR(50) NOT NULL 
        CHECK (transaction_type IN ('service_fee_deduction', 'deposit_split_credit', 'manual_topup', 'payout_settlement', 'chargeback_reversal')),
    description TEXT NOT NULL,
    is_negative_alert BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------------------
-- 4. PERFORMANCE & LOOKUP INDEXES
-- ----------------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_merchants_type ON merchants(merchant_type);
CREATE INDEX IF NOT EXISTS idx_merchants_gateway_mode ON merchants(payment_gateway_mode);
CREATE INDEX IF NOT EXISTS idx_appointments_merchant_id ON appointments(merchant_id);
CREATE INDEX IF NOT EXISTS idx_appointments_status ON appointments(status);
CREATE INDEX IF NOT EXISTS idx_appointments_preauth_status ON appointments(preauth_status);
CREATE INDEX IF NOT EXISTS idx_appointments_start_datetime ON appointments(start_datetime);
CREATE INDEX IF NOT EXISTS idx_appointments_preauth_expires ON appointments(preauth_expires_at);
CREATE INDEX IF NOT EXISTS idx_credit_logs_merchant ON merchant_credit_logs(merchant_id, created_at DESC);

-- ----------------------------------------------------------------------------
-- 5. TRIGGERS & BUSINESS LOGIC ENFORCEMENT
-- ----------------------------------------------------------------------------

-- Trigger: Forcibly disallow deposits for Doctors and Clinics
CREATE OR REPLACE FUNCTION fn_enforce_medical_deposit_ban()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.merchant_type IN ('doctor', 'clinic') THEN
        NEW.deposit_enabled := FALSE;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_enforce_medical_deposit_ban ON merchants;
CREATE TRIGGER trg_enforce_medical_deposit_ban
    BEFORE INSERT OR UPDATE OF merchant_type, deposit_enabled ON merchants
    FOR EACH ROW
    EXECUTE FUNCTION fn_enforce_medical_deposit_ban();

-- Trigger: Automatically update updated_at timestamp
CREATE OR REPLACE FUNCTION fn_set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at := CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_merchants_updated_at ON merchants;
CREATE TRIGGER trg_merchants_updated_at
    BEFORE UPDATE ON merchants
    FOR EACH ROW
    EXECUTE FUNCTION fn_set_updated_at();

DROP TRIGGER IF EXISTS trg_appointments_updated_at ON appointments;
CREATE TRIGGER trg_appointments_updated_at
    BEFORE UPDATE ON appointments
    FOR EACH ROW
    EXECUTE FUNCTION fn_set_updated_at();
