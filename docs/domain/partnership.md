# Partnership Domain

## Purpose

Partnership manages external relationships — companies and formal agreements that define each
partnership's terms for hosting students.

---

## Design Principles

### 1. Company as Core Entity

Companies are the primary entity. Partnerships (MoU agreements) are secondary — a company
can have multiple partnerships over time.

### 2. Partnership Lifecycle

Partnerships flow through ACTIVE → EXPIRED → TERMINATED. Expiry detection warns at 30 days.

---

## Models

| Model | Key Fields |
|---|---|
| `Company` | name, address, industry_sector, phone, email |
| `Partnership` | agreement_number, start_date, end_date, status, company_id |

## Actions

| Action | Type |
|---|---|
| `CreateCompanyAction` | Command |
| `UpdateCompanyAction` | Command |
| `DeleteCompanyAction` | Command |
| `CreatePartnershipAction` | Command |
| `UpdatePartnershipAction` | Command |
| `RenewPartnershipAction` | Command |
| `TerminatePartnershipAction` | Command |
| `DeletePartnershipAction` | Command |

## Where to Find It

- `app/Domain/Partnership/Models/`
- `app/Domain/Partnership/Actions/`
