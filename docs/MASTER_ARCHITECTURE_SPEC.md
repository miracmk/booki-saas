# Unified BK-RD-BKA Master Technical Specification
**Version:** 1.0.0-PROD-SPEC  
**Last Updated:** 2026-10-01  
**Architecture:** Monorepo (BooKi SaaS + RandevuBurada + BooKi Admin)  
**Target Codebase:** `Backend/` (CodeIgniter 3 Core), `booki-wa` (Node.js Baileys), `booki-mcp` (Node.js Gemini 3.8), `RandevuBurada/` (Next.js/React Frontend), `WebApp/` (Vue/React Tenant UI).

---

## 1. Architectural Philosophy & Scope
This specification governs the unified implementation of the **Unified BK-RD-BKA** platform. The system operates as a cohesive monorepo where three distinct roles interact over a local Redis Event Bus (IPC) and partitioned relational schemas:

1. **BooKi SaaS (BK):** Multi-tenant operating system for appointment and service-based verticals (Beauty, Clinic, Restaurant/Bistro, Sports, Auto Detailing, Experience). Features NutrixPOS adisyon, multi-branch, dynamic commission, legaltech e-signature, and service-level follow-ups.
2. **RandevuBurada (RB):** High-traffic B2C marketplace and directory. Serves 3 business personas (`Unclaimed`, `RB-Only`, and `RB-BooKi`), utilizing Tosla 7-day card provisions and a T+3 business day escrow model with Bayesian sponsored rank boosting.
3. **BooKi Admin (BKA):** Superadmin ERP, automated lead harvester, self-evolving sales AI, unified payout reconciler, YapıKredi dynamic fee scraper, and 14-day review dispute arbitrator.

---

## 2. Financial & Escrow Engine Specification

### 2.1 Deduction Models
Customer payments never incur hidden surcharges. All platform and processing fees are deducted from the merchant payout:

* **Model A - RB Organic Marketplace Booking:**
  $$\text{Net Payout} = \text{Gross} - 5\% (\text{RB Platform}) - 5\% (\text{Tosla POS}) - 1\% (\text{Bank EFT/FAST}) = 89\%$$
* **Model B - RB-Only Manual Booking (BooKi POS Link):**
  $$\text{Net Payout} = \text{Gross} - 20\% (\text{Comprehensive Platform/POS Fee}) - 1\% (\text{Bank Fee}) = 79\%$$
* **Model C - BooKi SaaS Active Subscriber (BooKi POS Link):**
  $$\text{Net Payout} = \text{Gross} - \text{Standard POS Fee} - 1\% (\text{Bank Fee})$$

### 2.2 Escrow & Provision Flow
1. **Reservation Phase:** Customer selects service and card is authorized via Tosla for a 7-day pre-authorization hold (`provision_status = 'authorized'`).
2. **Show-Up Phase:** Business marks booking as completed; Tosla pre-auth is captured (`provision_status = 'captured'`).
3. **T+3 Business Days Countdown:** 3 Turkish banking business days must elapse to mitigate dispute and chargeback liabilities (`release_date = NOW() + INTERVAL 3 BUSINESS_DAY`).
4. **Payout Gatekeeper:** Once released, balance moves into `bka_unified_current_accounts`. The merchant **MUST** upload an official tax invoice or freelance receipt (e-Fatura / e-SMM). Payout status remains `PENDING_INVOICE` until verified.
5. **Dynamic Bank Fee (YapıKredi Crawler):** Real-time wire transfer fees are crawled from `https://www.yapikredi.com.tr/...` and deducted before FAST dispatch.

---

## 3. Core Operational Engines

### 3.1 Universal Availability & Distributed Locking
To prevent double bookings across external channels and internal staff allocations:
* **Slot Resolution:** Evaluates `service_duration + buffer_cleanup_time` against both the staff shift schedule and the business station/capacity quota.
* **Distributed Lock:** A Redis atomic lock key `lock:slot:{tenant_id}:{date}:{start_time}` is established with a 120-second TTL during checkout.

### 3.2 NutrixPOS Adisyon & Universal Split Payment
* Supports item-level modifiers, discounts, and split billing:
  * Cash (physical cash drawer)
  * External physical POS (slip logging)
  * BooKi Online POS link (SMS/WA card checkout)
  * RandevuBurada pre-authorized escrow deduction
  * Customer prepaid package deduction
* **Closed Bill Guard:** Modifying closed bills is strictly prohibited unless escalated to an authorized `REOPEN` status with audit trail logging.

### 3.3 Legaltech E-Signature (Chargeback Defense)
* Generates tamper-proof medical, aesthetic, or service intake consent forms.
* Captures client telemetry: `IP, GPS coordinates, User-Agent, Screen resolution, Timestamp`.
* Calculates SHA-256 cryptographic hash and appends TSA timestamp. Stored in encrypted storage for instant chargeback defense.

---

## 4. Platform Lifecycle & State Machine

### 4.1 Subscription, Grace Period & Downgrade
* **Billing Day:** Automated monthly card charge.
* **Failure Handling:**
  * Checks tenant wallet for available BooKi POS funds; offsets automatically if sufficient.
  * If insufficient, triggers a **5-Day Grace Period** with daily alerts.
  * If unpaid after 5 days, executes **Downgrade to RB-Only**:
    * BooKi SaaS core features lock (`saas_status = 'locked'`).
    * RandevuBurada profile remains live (`membership_type = 'rb_only'`).
    * Sponsored rank boost score (+50) is stripped.
    * Bookings switch to manual tabular management on RB.

### 4.2 Prorated Capacity Upgrades
When adding staff/stations beyond the subscribed plan:
$$\text{Charge} = \left(\frac{\text{Station Monthly Rate}}{\text{Days in Month}}\right) \times \text{Remaining Days}$$
The difference is charged immediately, and plan quotas are incremented in real time.

---

## 5. Directory & Sponsored Ad Engine

### 5.1 Bayesian Ranking Formula
$$\text{Final Score} = \text{Bayesian\_Rating} (0-10) + \text{Profile\_Completeness} (0-5) + \text{Distance\_Factor} (0-5) + \text{Sponsored\_Boost} + \text{BooKi\_Bonus}$$
* Organic ceiling: 20.0 points.
* Sponsored campaign active: **+50.0 points**.
* Active BooKi SaaS subscriber: **+10.0 points**.

---

## 6. Omnichannel AI & Microservices

### 6.1 WhatsApp Dual-Mode Bridge (`booki-wa`)
* **Unclaimed Outbound:** Utilizes Baileys QR Bridge for zero marginal cost cold notifications.
* **Inbound / Response:** When customer replies, opens 24-hour window, transitioning to Meta Cloud API where approved.
* **Human Handoff:** On explicit escalation or sentiment degradation, AI mutes itself and dispatches `conversation.handoff_requested` over Redis.

### 6.2 Model Context Protocol (`booki-mcp`)
Exposes standardized JSON-RPC tools to Gemini 3.8:
* `check_availability(service_id, date)`
* `create_booking(customer_data, slot_id, service_id)`
* `cancel_booking(booking_id, reason)`
* `get_service_menu(category_id)`
* `get_customer_package_balance(customer_id)`
* `trigger_human_handoff(conversation_id)`

---

## 7. Redis Event Bus (IPC Specification)

| Channel | Publisher | Subscriber | Purpose |
|---|---|---|---|
| `lead.discovered` | `booki-app` (Scraper) | `booki-wa` | Initiate lazy enrichment & unclaimed showcase creation |
| `booking.created` | `booki-app` (Tenant/RB) | `booki-wa` | Dispatch confirmation notifications & PDF contracts |
| `booking.show_up` | `booki-app` (Tenant) | `booki-app` (Admin) | Convert Tosla pre-auth to capture & start T+3 timer |
| `wa.send_template` | `booki-app` | `booki-wa` | Trigger template message delivery |
| `wa.incoming_message` | `booki-wa` | `booki-mcp` | Route message to Gemini 3.8 reasoning engine |
| `escrow.release_t3` | Cron / Scheduler | `booki-app` (Admin) | Shift captured funds to `UnifiedCurrentAccount` |
| `conversation.handoff`| `booki-mcp` | `booki-app` | Alert admin dashboard for manual human takeover |

---

## 8. PlantUML Visual Master References
The architectural diagrams corresponding to this specification are organized in `docs/architecture/puml/`:
* `01_tenant_core_operations.puml`
* `02_booki_platform_lifecycle.puml`
* `03_booki_admin_erp.puml`
* `04_randevuburada_marketplace.puml`
* `05_system_topology_and_erd.puml`
