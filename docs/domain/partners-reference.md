# Partners — API Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Consolidated Company and Partnership references

This reference details the class structures, models, actions, and Livewire components belonging to the **Partners** domain.

---

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Partners/Actions/CreateCompanyAction.php` | `CreateCompanyAction` | `BaseAction` | Registers a new company profile with contact credentials |
| `Partners/Actions/UpdateCompanyAction.php` | `UpdateCompanyAction` | `BaseAction` | Edits company details (name, email, sector, etc.) |
| `Partners/Actions/DeleteCompanyAction.php` | `DeleteCompanyAction` | `BaseAction` | Removes a company profile (aborts if placement records exist) |
| `Partners/Actions/BatchDeleteCompanyAction.php` | `BatchDeleteCompanyAction` | `BaseAction` | Batch deletes multiple company records |
| `Partners/Actions/CreatePartnershipAction.php` | `CreatePartnershipAction` | `BaseAction` | Creates a new MoU agreement for a company |
| `Partners/Actions/UpdatePartnershipAction.php` | `UpdatePartnershipAction` | `BaseAction` | Modifies an existing MoU agreement |
| `Partners/Actions/DeletePartnershipAction.php` | `DeletePartnershipAction` | `BaseAction` | Removes a partnership agreement |
| `Partners/Actions/BatchDeletePartnershipAction.php` | `BatchDeletePartnershipAction` | `BaseAction` | Batch deletes multiple partnership agreements |
| `Partners/Actions/RenewPartnershipAction.php` | `RenewPartnershipAction` | `BaseAction` | Extends partnership span by spawning a sequential agreement |
| `Partners/Actions/TerminatePartnershipAction.php` | `TerminatePartnershipAction` | `BaseAction` | Terminates active agreements and logs the reason |

---

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Partners/Livewire/CompanyManager.php` | `CompanyManager` | `BaseRecordManager` | Company list, search, filters, and CRUD controls |
| `Partners/Livewire/PartnershipManager.php` | `PartnershipManager` | `BaseRecordManager` | Agreement management, status controls, and PDF uploads |

### Livewire Form Objects
| File | Class | Extends | Fields | Used By |
|---|---|---|---|---|
| `Partners/Livewire/Forms/CompanyForm.php` | `CompanyForm` | `Form` | name, industry_sector, email, phone, address, website, contact_person | `CompanyManager` |
| `Partners/Livewire/Forms/PartnershipForm.php` | `PartnershipForm` | `Form` | company_id, agreement_number, start_date, end_date, witness_name, status | `PartnershipManager` |

---

## Models

### Company (`Company.php`)
- **Extends**: `BaseModel`
- **Fields**: name, industry_sector, email, phone, address, website, contact_person
- **Relationships**:
  - `partnerships` → `HasMany` (Partnership)
  - `placements` → `HasMany` (via Enrollment domain)

### Partnership (`Partnership.php`)
- **Extends**: `BaseModel` (implements `HasMedia` for MoU PDF storage)
- **Fields**: company_id, agreement_number, start_date, end_date, witness_name, status (cast to `PartnershipStatus` enum)
- **Relationships**:
  - `company` → `BelongsTo` (Company)

---

## Entities & Enums

### Entities
- `CompanyState`: Read-only DTO for current company properties.
- `PartnershipState`: Read-only DTO for current agreement properties.

### Enums
- `PartnershipStatus` (implements `LabelEnum`, `StatusEnum`):
  - `DRAFT` ('Draft')
  - `ACTIVE` ('Active')
  - `EXPIRED` ('Expired')
  - `TERMINATED` ('Terminated')

---

## Policies

- `CompanyPolicy`: Secures company registration, updates, and deletes. Prevents deletion when placements are linked.
- `PartnershipPolicy`: Secures MoU creations and terminations. Ensures draft agreements can be modified while active ones have locked date fields.
