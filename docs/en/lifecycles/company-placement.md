# Company & Placement Management

**Event:** Registering partner companies and defining placement slots.

**Phase:** 2 — Internship Planning

**Previous Event:** [Internship Creation](internship-creation.md)

**Next Event:** [Student Registration](student-registration.md)

---

## Overview

Companies are the industry partners that host students during their internship. Placement slots define how many students each company can accommodate, along with period and requirements.

## Trigger

- New industry partnership established
- Existing partnership needs updated quota for new period
- Internship program requires placement options before publishing

## Pre-conditions

- User is logged in as Super Admin, Admin, or Teacher
- At least one internship exists (placements are linked to internships)

## Actors

| Actor | Role | Can create | Can update | Can delete |
|---|---|---|---|---|
| Super Admin | System administrator | Yes | Yes | Yes |
| Admin | School administrator | Yes | Yes | Yes |
| Teacher | Academic supervisor | Yes | Yes | Yes |

---

## Event A: Registering a Company

### Flow

```
Admin → Companies → Create → Fill Details → Save
```

Navigate to **Admin → Companies** and click **Create**.

| Field | Validation | Description |
|---|---|---|
| **Name** | Required, max 255 | Company legal name |
| **Address** | Optional, max 500 | Physical address |
| **Contact Person** | Optional, max 255 | HR or PIC name |
| **Phone** | Optional, max 30 | Contact number |
| **Email** | Optional, valid email | Contact email |
| **Website** | Optional, valid URL | Company website |
| **Industry Type** | Optional | Sector (technology, manufacturing, services, etc.) |
| **Description** | Optional | Company profile or notes |

The `CreateCompanyAction` creates the record with audit logging. Companies are reusable across multiple internship periods.

### Updating a Company

`UpdateCompanyAction` handles edits. Changes are audit-logged.

### Deleting a Company

`DeleteCompanyAction` — blocked if the company has active placements or registrations.

---

## Event B: Defining Placement Slots

### Flow

```
Admin → Internships → Placements → Create → Set Quota → Save
```

Navigate to **Admin → Internships → Placements** and click **Create**.

| Field | Validation | Description |
|---|---|---|
| **Company** | Required, exists | Which company hosts the students |
| **Internship** | Required, exists | Which internship period |
| **Quota** | Required, integer, min 1 | Maximum number of students |
| **Start Date** | Required, date | When placement begins (may differ from internship) |
| **End Date** | Required, date, after start | When placement ends |
| **Requirements** | Optional | Skills or prerequisites |

### Capacity Management

The `PlacementCapacity` entity enforces:

- `isFull()` — returns true when `filled_quota >= quota`
- `availableSlots()` — difference between quota and filled count
- `hasAvailableSlots()` — convenience check for registration

When a student is placed, `filled_quota` is incremented. It is decremented if the registration is removed.

### Updating Placements

`UpdatePlacementAction` handles edits. Quota can be increased or decreased, but cannot go below the current `filled_quota`.

### Deleting Placements

`DeletePlacementAction` — blocked if the placement has active student registrations.

---

## Event C: Capacity Lifecycle

```
Placement Created (quota empty)
    │
    ▼
Accepting Registrations (slots available)
    │
    ├── Student placed → filled_quota +1
    │       │
    │       ├── Registration active (slot consumed)
    │       └── Registration removed → filled_quota -1
    │
    ├── Quota reached → Placement Full
    │       └── No more registrations accepted
    │
    └── Internship ends → Placement becomes historical
```

## Key Rules

| Rule | Enforcement |
|---|---|
| **Capacity enforced** | Cannot place more students than quota |
| **Quota cannot be reduced below filled** | Update validation |
| **Delete blocked if students placed** | Dependency check |

## State Changes

| Component | Before | After |
|---|---|---|
| Companies table | No company | Company record created |
| Placements | No slot | Placement with quota, linked to company and internship |
| filled_quota | 0 | Incremented when student placed |

## Seamless Connection

Once companies and placements are defined:

- **[Student Registration](student-registration.md)** — students can select companies and placements during registration
- Internship can be published (at least one placement with slots available is recommended)
