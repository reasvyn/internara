# Withdrawal Official Report («Berita Acara Penarikan»)

**Event:** Generating the official handover document when students complete their internship and are withdrawn from the company.

**Phase:** 6 — Period Closing

**Previous Event:** [Assessment & Scoring](assessment-scoring.md)

**Next Event:** [Certificate Generation](certificate-generation.md)

---

## Overview

The Withdrawal Official Report formalizes the return of students from the host company back to the school upon completion of their internship. One document per company, listing all students who have finished their placement. Signed by the principal, supervising teacher, and industry supervisor.

## Trigger

- Students have completed their internship period
- Assessment is finalized (recommended but not required)

## Pre-conditions

- Registration status is `active`
- Internship period has ended or is ending
- User is logged in as Admin or Super Admin

## Actors

| Actor | Can generate |
|---|---|
| Admin | Yes |
| Super Admin | Yes |

---

## Data

- School letterhead (name, address, principal)
- Student table: name, NIS/NISN, class/department
- Company details (name, address)
- Internship period (start to end dates)
- Signatures: principal, supervising teacher, industry supervisor

## Flow

```
Admin → Companies → Select Company → "Withdrawal Report"
  → Load all active registrations across company's placements
  → Render Blade template → DomPDF → Download
```

---

## Models

| Model | Table |
|---|---|
| `App\Models\Document` | `documents` |
| `App\Models\Company` | `internship_companies` |
| `App\Models\Registration` | `internship_registrations` |

## Key Rules

| Rule | Enforcement |
|---|---|
| **One document per company** | Groups all active registrations by company |
| **Only active registrations** | Filtered by registration status `active` |
| **Template stored in DB** | Editable via admin panel |

## Seamless Connection

After withdrawal:

- **[Certificate Generation](certificate-generation.md)** — issue completion certificates
- **[Period Closing](period-closing.md)** — close the internship period
