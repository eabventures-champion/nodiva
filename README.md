# NODIVA — The Network Behind Diagnostics

**NODIVA connects hospitals and corporate organizations to diagnostic capacity across qualified laboratories.**

NODIVA coordinates the entire diagnostic journey: referral demand, laboratory matching, specimen collection, chain of custody, analytical fulfillment, dual-control result release, and multi-party financial reconciliation.

---

## Architecture Overview

NODIVA is architected as an institutional healthcare technology platform in Laravel 12 / PHP 8.2 with strict domain boundaries and multi-tenancy controls:

- **Modular Monolith (`app/Domain`)**:
  - `Organizations`: Context resolution, role-based access control, tenant isolation.
  - `Network`: Diagnostic test catalogue, laboratory profiles, accreditation and qualification verification, and rules-based matching.
  - `Referrals`: End-to-end referral order state machine with auditable transition history.
  - `Specimens`: Barcoded chain-of-custody tracking, dispatch management, temperature and custody milestones.
  - `Results`: Dual-control result review and release, encrypted observation storage, and private document management.
  - `Finance`: Decimal-safe (`Money` value object) ledger, configurable platform fee rules (12% baseline), invoice creation, payment allocations, and reconciliation.
  - `Audit`: Cryptographically hash-chained (SHA-256) append-only audit trail.

---

## Workspaces Implemented

1. **Public Marketing Website**:
   - Institutional branding, network operational loop, problem/solution matrix, and accreditation transparency.
2. **Hospital Referral Portal (`/hospital/dashboard`)**:
   - Referral order tracking, status metrics, and diagnostic test referral management.
3. **Laboratory Portal (`/lab/dashboard`)**:
   - Order intake queue, specimen reception, and pathologist result release queue.
4. **Corporate Screening Portal (`/corporate/dashboard`)**:
   - Workplace screening programmes, employee roster management, and privacy-shielded aggregate reporting.
5. **NODIVA Operations Console (`/ops/dashboard`)**:
   - Network control plane, partner verification, pseudonymized order oversight, and audit trail.

---

## Credentials (Local Development & Demo)

All demo accounts use password: `NodivaSecure2026!`

| Portal | User / Email | Role | Organization |
|---|---|---|---|
| **NODIVA Operations** | `admin@nodiva.com` | Platform Admin | NODIVA Operations Network |
| **Hospital Portal** | `doctor@ridgespecialist.com` | Clinician | Ridge Specialist Hospital |
| **Laboratory Portal** | `scientist@synlab-gh.example` | Lab Scientist | SynLab Diagnostics West Africa |
| **Laboratory Portal** | `pathologist@synlab-gh.example` | Pathologist | SynLab Diagnostics West Africa |
| **Corporate Portal** | `hr@tullow.example` | Corporate Admin | Tullow Oil Ghana Ltd |

---

## Setup & Testing

### Database & Migrations
```bash
php artisan migrate
php artisan db:seed
```

### Build Assets
```bash
npm run build
```

### Run Automated Acceptance Test
```bash
php artisan test
```

The feature test (`EndToEndReferralWorkflowTest`) validates the complete Milestone 1 vertical slice: from patient consent, test referral creation, rules-based lab matching, laboratory acceptance, barcoded specimen custody dispatch & receipt, dual-control pathologist result release, invoicing, payment allocation, and financial reconciliation.
