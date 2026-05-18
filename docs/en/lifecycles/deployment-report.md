# Deployment Official Report («Berita Acara Penerjunan»)

**Event:** Generating the official handover document when students are deployed to their company placement.

**Phase:** 3 — Registration & Placement

**Previous Event:** [Internship Briefing](internship-briefing.md)

**Next Event:** [Logbook Workflow](logbook-workflow.md), [Attendance Tracking](attendance-tracking.md)

---

## Overview

The Deployment Official Report formalizes the handover of students from the school to the host company. One document per company, listing all active students placed there. Signed by the principal, supervising teacher, and industry supervisor.

## Trigger

- Students have completed registration and briefing
- Students are ready to begin their internship at the company

## Pre-conditions

- Registration status is `active`
- Placement is assigned with a verified industry supervisor
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
- Signatures: principal, supervising teacher, industry supervisor

## Flow

```
Admin → Companies → Select Company → "Deployment Report"
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

After deployment, students proceed to operational activities:

- **[Logbook Workflow](logbook-workflow.md)** — record daily activities
- **[Attendance Tracking](attendance-tracking.md)** — daily presence records
