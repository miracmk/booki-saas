# Compliance — ISO/IEC 27001, SOC 2, and Salon-Industry Standards

Last updated 2026-08-24. This document does two things: (1) separates
which of the standards on the business's radar are actually about *this
software* versus about *the physical salon business*, and (2) gives a
concrete technical gap analysis for the two that are — ISO/IEC 27001 and
SOC 2 — against what BooKi has today.

**This document is engineering input to a certification effort, not a
certification itself.** ISO 27001 and SOC 2 are audited by an accredited
external body against the *organization*, not just its software. No
amount of code closes that gap alone — see §3.

---

## 1. Which standards are actually about the software?

| Standard | About | BooKi relevant? |
|---|---|---|
| **ISO/IEC 27001** | Information Security Management System (ISMS) — how an organization protects data | **Yes — primary target of this document** |
| **SOC 2** (Type I/II) | Trust Services Criteria (security, availability, processing integrity, confidentiality, privacy) for a service organization | **Yes — primary target of this document** |
| ISO 9001 | General Quality Management System | Partially — the software can support consistent process execution (audit log, standardized workflows), but certification is a QMS exercise (documented procedures, management review, internal audits) covering the whole business, not just software |
| ISO 22458 | Service Excellence / customer experience management | No — front-desk/service-delivery process standard, not a software concern |
| MEB Güzellik Uzmanlığı / Ustalık Belgesi, MYK Mesleki Yeterlilik | Individual staff vocational qualifications | **No** — staff training/certification, unrelated to software |
| İşyeri Açma ve Çalışma Ruhsatı | Municipal business operating license | **No** — physical premises licensing |
| CE (device compliance) | Physical equipment (laser, epilation devices) safety marking | **No** — hardware, not software |
| Sağlık Müdürlüğü onayları | Health authority hygiene/facility inspection | **No** — physical premises |
| ISO 45001 | Occupational Health & Safety | **No** — physical workplace safety (chemicals, sharp tools, slip hazards), not IT |
| ISO 31000 | Risk Management (general) | Partially — the *methodology* is reusable for an ISMS risk register (§3), but as applied to "customer allergic reactions" it's a clinical/physical risk, not software |
| ISO 17679 | Wellness & Spa services (facility, hygiene, staff competency) | **No** — physical facility standard |
| ISO 13485 | Medical device quality management | **No**, unless the business itself manufactures/imports medical-grade devices (it uses them, doesn't make them) |
| ISO 22716 | Cosmetics GMP (manufacturing) | **No**, unless the business manufactures its own cosmetic products |
| ISO 14001 | Environmental Management | **No** — physical waste/water/energy management |
| ISO 50001 | Energy Management | **No** — physical facility energy use |

**Bottom line:** of everything on the list, **ISO/IEC 27001 and SOC 2**
are the two where BooKi (the software) is actually part of the
answer. Everything else is a business/facility/staff certification that
Ki Software's engineering work has no bearing on — pursue those directly
with the relevant bodies (MEB, MYK, the local belediye, an ISO-accredited
certification body for the facility standards), independent of this
project.

---

## 2. Gap analysis — ISO/IEC 27001 (Annex A controls)

Status legend: ✅ Done · 🟡 Partial · ❌ Missing (not yet addressed)

| Annex A area | Control | Status | Notes |
|---|---|---|---|
| A.5 Organizational | Information security policies | ❌ | No written ISMS policy set exists yet (this doc + KEY_MANAGEMENT.md are a start, not a complete policy set) |
| A.5 | Roles & responsibilities | 🟡 | RBAC exists in code (admin/secretary/provider); no written organizational responsibility matrix |
| A.5 | Supplier/vendor risk management | 🟡 | SBOM.md documents dependencies; no formal vendor risk assessment process |
| A.6 | Screening / background checks | ❌ | Organizational, not software |
| A.6 | Security awareness training | ❌ | Organizational, not software |
| A.7 | Physical security | ❌ | Depends on hosting provider + office; out of scope for this repo |
| A.8.1–8.3 | Asset inventory, acceptable use, media handling | 🟡 | SBOM.md covers software assets; no formal asset register for infrastructure |
| A.8.5 | Secure authentication | 🟡 | Password policy (min 7 chars — **weak by modern standard**, no complexity/MFA), rate limiting + CAPTCHA on login exist. **Gap: no MFA/2FA.** |
| A.8.9 | Configuration management | 🟡 | Docker-based, version-controlled application source; infra config (docker-compose, .env) is NOT in this repo (correctly, since it holds secrets) but also isn't tracked anywhere else yet |
| A.8.10 | Information deletion | ✅ | Automated + manual right-to-erasure (§KVKK retention, project history) |
| A.8.12 | Data leakage prevention | 🟡 | Encryption at rest for PII; no DLP tooling (egress monitoring, etc.) |
| A.8.13 | Backup | ✅ | Automated nightly backup, **now encrypted at rest** (2026-08-24), 14-day retention. **Gap: backups are not verified by an automated restore test, and are stored on the same host as production — no offsite/geo-redundant copy.** |
| A.8.15 | Logging | ✅ | Application audit log (auth, erasure, payment changes) + PHP error log. **Gap: logs are not shipped to a separate/immutable log store — an attacker with host access could tamper with or delete both.** |
| A.8.16 | Monitoring activities | ❌ | No alerting/SIEM — issues are found by manual log review, not automated detection |
| A.8.20–8.23 | Network security, web filtering | 🟡 | Cloudflare in front (WAF/DDoS mitigation available but not confirmed configured), CSP/security headers now in place (2026-08-24) |
| A.8.24 | Cryptography | ✅ | AES-256-GCM PII encryption, documented key model + rotation procedure (KEY_MANAGEMENT.md). **Gap: no HSM/KMS (documented and accepted for current single-tenant scale, see KEY_MANAGEMENT.md)** |
| A.8.25–8.29 | Secure development lifecycle | 🟡 | Every change this session was syntax-checked, backed up, and regression-tested before deploy — but this is ad hoc practice, not a documented SDLC policy, and there's no automated test suite (SPECS.md §10) |
| A.8.32 | Change management | ❌ | Single-maintainer, no formal review/approval gate on the private repo |
| A.5.24–5.28 | Incident management | ❌ | **No written incident response plan.** Audit log gives detection capability; no documented escalation/containment/notification procedure |
| A.5.29–5.30 | Business continuity, ICT readiness for disruption | 🟡 | Backups exist; no documented/tested disaster recovery plan (RTO/RPO targets, restore drill) |
| A.5.34 | Privacy / PII protection | ✅ | Encryption, retention automation, role-based visibility, consent tracking (KVKK-oriented, needs legal review per SPECS.md) |

### Highest-priority gaps to close next

1. **MFA for admin accounts** — the single biggest authentication gap. No 2FA library is currently a dependency; would need TOTP (e.g. RFC 6238) support added.
2. **Written incident response plan** — a short, concrete runbook (who does what within the first hour of a suspected breach) closes a real Annex A requirement cheaply.
3. **Offsite/immutable backup and log copy** — today, an attacker who compromises the host can alter or delete both the application logs and the backups that would otherwise reveal what they did.
4. **A documented ISMS policy set** (even a lean one) — access control policy, secure development policy, acceptable use policy. Much of the *practice* already exists in this project's history; it needs to be written down as policy, not just inferred from commits.
5. **Formal SDLC / change management** — a lightweight one (e.g., every change requires a second reviewer once there's a second engineer, or at minimum a documented pre-deploy checklist) is enough to close A.8.32 at this company's current size.

---

## 3. Gap analysis — SOC 2 (Trust Services Criteria)

SOC 2 doesn't have a fixed control list like ISO 27001's Annex A — it's
evaluated against the five Trust Services Criteria, of which **Security**
is always in scope and the other four are elected per engagement.

| Criterion | Status | Notes |
|---|---|---|
| **Security** (mandatory) | 🟡 | Same technical gaps as the ISO 27001 table above (MFA, incident response plan, offsite backups being the top three) |
| **Availability** | 🟡 | Single-server deployment, no documented uptime SLA, no redundancy/failover. Automated backups exist but restore has never been drilled end-to-end under time pressure |
| **Processing Integrity** | ✅ | Billing/commission calculations are computed by one server-side function (`compute_effective_billing()`) reused everywhere, so the report can't drift from what was actually charged — a genuine integrity control, though not usually framed that way |
| **Confidentiality** | ✅ | PII encryption, role-based visibility, RBAC |
| **Privacy** | 🟡 | KVKK-oriented consent/retention exists; SOC 2's Privacy criterion additionally expects a published privacy notice, a data subject request handling *process* (not just a button), and documented data flow mapping — none of that documentation exists yet |

**A SOC 2 Type I report** attests controls are suitably designed at a
point in time — achievable once the §2 priority gaps are closed and
written up as policy.
**A SOC 2 Type II report** additionally attests those controls operated
effectively over an observation period (typically 3–12 months) — this
requires the controls to actually be *run* (incident response drilled,
access reviews performed, backups tested) and evidenced, not just
documented. Budget for that timeline before committing to a Type II date
with a customer.

---

## 4. What this repository can and cannot do for certification

**Can:** provide/maintain the technical controls above, keep SBOM.md and
KEY_MANAGEMENT.md current, keep the audit log comprehensive, and (once
written) host the ISMS policy documents alongside the code they describe.

**Cannot:** substitute for an accredited external auditor, cover
non-software Annex A domains (physical security, HR screening, vendor
contracts), or make the certification decision — that's a business
decision balancing cost (auditor fees, consultant time, the operational
overhead of *running* the controls) against what having the certificate
actually unlocks (e.g., a specific enterprise customer requiring it as a
sales prerequisite).

## 5. Suggested next step

Treat §2's "highest-priority gaps" as the next engineering sprint (see
SPECS.md §11 Roadmap — MFA, incident response plan, and offsite backups
are added there as near-term items). In parallel, the business should
scope whether a full ISO 27001/SOC 2 certification is worth pursuing now
versus operating to this document's controls informally until a specific
customer or contract requires the formal certificate.
