# Official Internship Documents («Dokumen PKL»)

**Event:** Generating official administrative documents throughout the internship lifecycle.

**Phase:** Multiple — see per-document phase below.

---

## Overview

Official documents formalize administrative events across the internship lifecycle. Each document is generated per company, listing all relevant students. Templates are stored in the database as Blade HTML, rendered via DomPDF.

### Document Catalog

| Phase | Document | Status | Doc Link |
|---|---|---|---|
| 2 — Planning | Placement Request Letter | Pending | — |
| 3 — Registration | Student Assignment Letter | Pending | — |
| 3 — Registration | Deployment Official Report | **Next** | [deployment-report](deployment-report.md) |
| 4 — Operations | Site Visit Report | Pending | — |
| 5 — Assessment | Score Sheet | Pending | — |
| 5 — Assessment | Grade Transcript | Pending | — |
| 6 — Closing | Withdrawal Official Report | **Next** | [withdrawal-report](withdrawal-report.md) |
| 6 — Closing | Completion Certificate | Implemented | [certificate-generation](certificate-generation.md) |
| 6 — Closing | Completion Statement | Pending | — |

## Generation Mechanism

```
Admin → Company row → Select document type
  → Query active registrations across company's placements
  → Blade::render(template, [company, students, school])
  → DomPDF → Preview (stream) or Store (persist + audit)
```

| Mode | Route | Behavior |
|---|---|---|
| Preview | `GET /admin/companies/{company}/documents/{type}/preview` | Stream PDF, no storage |
| Store | `POST /admin/companies/{company}/documents/{type}/generate` | Persist PDF, log audit |

## Key Rules

| Rule | Enforcement |
|---|---|
| **One document per company** | Groups all active registrations by company |
| **Template customizable** | Stored in DB as Blade HTML, editable via admin panel |
| **Extensible by design** | New types added via `DocumentCategory` enum + `Document` record |
