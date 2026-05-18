# MoU & Partnership Management

**Event:** Managing formal partnership agreements between the school and host companies.

**Phase:** 2 — Internship Planning

**Previous Event:** [Company & Placement Management](company-placement.md)

**Next Event:** [Internship Briefing](internship-briefing.md)

---

## Overview

Partnerships formalise the cooperation between the school and host companies. Each partnership is a time-bounded agreement with a contact person and validity period. A company may have multiple partnerships across different academic years.

Partnerships are informational and organisational — they are **never a hard prerequisite** for placing students. Schools can place students at companies without an active MoU if needed.

## Trigger

- New industry partnership formalised (signing of MoU/Perjanjian Kerja Sama)
- Existing partnership renewal for a new academic period
- Partnership termination (expired or manually ended)

## Pre-conditions

- User is logged in as Super Admin or Admin
- Company record exists (see [Company & Placement Management](company-placement.md))

## Actors

| Actor | Role | Can create | Can terminate |
|---|---|---|---|
| Super Admin | System administrator | Yes | Yes |
| Admin | School administrator | Yes | Yes |

---

## Event A: Creating a Partnership

### Flow

```
Admin → Companies → Partnerships → Create → Fill Details → Save
```

Navigate to **Admin → Companies → Partnerships** and click **Create**.

| Field | Validation | Description |
|---|---|---|
| **Company** | Required, exists | Which company this agreement is with |
| **Agreement Number** | Required, max 100, unique | Nomor Perjanjian Kerja Sama (PKS) |
| **Title** | Required, max 255 | e.g., "PKL Partnership 2025/2026" |
| **Start Date** | Required, date | When agreement takes effect |
| **End Date** | Required, date, after start date | When agreement expires |
| **Scope** | Optional, max 5000 | Scope of cooperation |
| **Contact Person Name** | Optional, max 255 | Company PIC |
| **Contact Person Phone** | Optional, max 30 | |
| **Contact Person Email** | Optional, valid email | |
| **Signed by School** | Optional, max 255 | School representative name |
| **Signed by Company** | Optional, max 255 | Company representative name |
| **Signed at** | Optional, date | Signing date |
| **Notes** | Optional, text | Internal notes |
| **MoU Document** | Optional, file upload | Scanned PDF/Image of signed MoU (max 5MB, via Spatie Media Library) |

The `CreatePartnershipAction` (`app/Actions/Internship/CreatePartnershipAction.php`) creates the record with audit logging. The default status is `active`.

The MoU document is uploaded via `Livewire\WithFileUploads` in `PartnershipManager` and stored in the `mou_document` Spatie Media Library collection.

---

## Event B: Terminating a Partnership

### Flow

```
Admin → Partnerships → Select → Terminate → Confirm
```

`TerminatePartnershipAction` (`app/Actions/Internship/TerminatePartnershipAction.php`):
1. Validates the partnership is currently `ACTIVE`
2. Sets status to `TERMINATED`
3. Logs audit

Termination does **not** affect existing active registrations at that company. Students already placed continue their internships normally.

---

## Renewing a Partnership

Renewal is handled manually by the admin: terminate the old partnership and create a new one with updated details. The `RenewPartnershipAction` (`app/Actions/Internship/RenewPartnershipAction.php`) exists as a convenience action that marks the old partnership as `EXPIRED` and creates a new record carrying forward company and contact information.

---

## Partnership Status Lifecycle

```
ACTIVE ──► EXPIRED (end date reached or manual via renew)
ACTIVE ──► TERMINATED (manual, before expiry)
```

| Status | Meaning |
|---|---|
| `ACTIVE` | Agreement is valid and in effect |
| `EXPIRED` | Agreement end date has passed |
| `TERMINATED` | Agreement ended early by admin decision |

Defined in `App\Enums\Shared\PartnershipStatus`.

## Business Rules

| Rule | Enforced by |
|---|---|
| **Agreement number unique** | Database unique constraint |
| **Partnership links to existing company** | Foreign key constraint |
| **Not required for placement** | Placement does not require a partnership |
| **Historical preservation** | Expired/terminated partnerships remain searchable |
| **MoU document is optional** | No file required; uploaded via Spatie Media Library when provided |
| **Only active can be terminated** | `PartnershipState::isActive()` check in `TerminatePartnershipAction` |
| **Only expired/terminated can be deleted** | `PartnershipState::canBeDeleted()` |

## Expiry Warning

The entity method `PartnershipState::isExpiringSoon(int $thresholdDays = 30)` checks whether a partnership's end date is within the threshold. The PartnershipManager displays a stats widget showing the count of partnerships expiring within 30 days.

---

## Entity: PartnershipState

`App\Entities\Partnership\PartnershipState` provides business rules:

| Method | Purpose |
|---|---|
| `isActive()` | Status is ACTIVE |
| `isExpired()` | Status is EXPIRED |
| `isTerminated()` | Status is TERMINATED |
| `isExpiringSoon(int $thresholdDays = 30)` | Active with end date within threshold |
| `canBeDeleted()` | Expired or terminated |

## Livewire Component

| Component | Route | View |
|---|---|---|
| `App\Livewire\Internship\PartnershipManager` | `admin/companies/partnerships` (name: `admin.partnerships`) | `livewire.internship.partnership-manager` |

## State Changes

| Component | Before | After |
|---|---|---|
| Partnerships table | No record | Partnership created with status ACTIVE |
| MoU document | — | Attached to partnership via `mou_document` media library collection |
| Company record | Unchanged | Referenced by partnership |
| Activity log | — | Audit entry for create/terminate |

## Seamless Connection

Partnership data is purely informational and does not gate any operational feature.
