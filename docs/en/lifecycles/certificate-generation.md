# Certificate Generation («Sertifikat PKL»)

**Event:** Issuing internship completion certificates to students.

**Phase:** 6 — Period Closing

**Previous Event:** [Assessment & Scoring](assessment-scoring.md)

**Next Event:** [Period Closing](period-closing.md)

---

## Overview

After completing all internship requirements, students receive a completion certificate (Sertifikat PKL). The system generates certificates as PDF files using configurable templates and issues them with unique serial numbers.

Certificate issuance is **optional** — the system does not require certificates to be issued before closing an internship period.

## Trigger

- Student has completed all internship requirements
- Admin initiates certificate issuance
- Batch issuance at the end of the internship period

## Pre-conditions

- Student's registration is `ACTIVE` or `COMPLETED`
- At least one certificate template exists
- User is logged in as Super Admin or Admin

## Actors

| Actor | Role | Can create templates | Can issue certificates | Can revoke |
|---|---|---|---|---|
| Super Admin | System administrator | Yes | Yes | Yes |
| Admin | School administrator | Yes | Yes | Yes |

> Supervisor is not involved in certificate issuance.

---

## Event A: Managing Certificate Templates

### Flow

```
Admin → Certificates → Templates → Create/Edit → Save
```

Navigate to **Admin → Certificates → Templates**.

| Field | Description |
|---|---|
| **Name** | e.g., "Sertifikat PKL Standar" |
| **Layout** | `PORTRAIT` or `LANDSCAPE` |
| **Content Template (HTML)** | PDF template with placeholders |
| **Active** | Whether template is available for issuance |

### Placeholders

| Placeholder | Resolves to |
|---|---|
| `{student_name}` | User's full name |
| `{student_nis}` | Registration number from profile |
| `{company_name}` | Placement company name |
| `{internship_name}` | Internship program name |
| `{start_date}` | Date formatted |
| `{end_date}` | Date formatted |
| `{duration}` | Duration in months |
| `{score}` | Final assessment score (or "—") |
| `{score_letter}` | Letter grade A-E (or "—") |
| `{certificate_number}` | Auto-generated serial number |
| `{issued_date}` | Issuance date |
| `{supervisor_name}` | Industry supervisor name (or "—") |

The template is rendered via `Blade::render()` after placeholder substitution (see `App\Support\CertificateRenderer`).

---

## Event B: Issuing a Certificate

### Flow

```
Admin → Certificates → Issue → Select Student → Select Template → Issue
```

> **Batch Issuance (Pending):** The current UI issues one certificate at a time. A batch mode that applies a template to all filtered registrations has not yet been built. The `IssueCertificateAction` can be invoked programmatically per registration.

`IssueCertificateAction` (`app/Actions/Certificate/IssueCertificateAction.php`):

1. Generates a unique certificate number: `{PREFIX}/{YEAR}/{SEQUENTIAL}`
   - Prefix is derived from internship name (first 6 uppercase chars)
   - Sequential is auto-incrementing per year (4 digits, zero-padded)
2. Snapshots metadata (student name, company, dates, score) into the `metadata` JSON field
3. Renders the PDF via `CertificateRenderer::storePdf()` using dompdf
4. Stores the PDF to `storage/certificates/`
5. Records the file path in `metadata.pdf_path`
6. Creates the Certificate record with status `issued`

### Certificate Renderer

`App\Support\CertificateRenderer`:
- `resolvePlaceholders()` — maps placeholders to registration/certificate data
- `renderHtml()` — substitutes placeholders and runs through Blade
- `renderPdf()` — converts HTML to PDF via dompdf
- `storePdf()` — saves PDF to `storage/certificates/`

---

## Event C: Student Download

### Flow

```
Student → My Certificates → Download
```

The download is handled by `CertificateDownloadController` (`app/Http/Controllers/CertificateDownloadController.php`):

1. Authorizes the student (own certificate) or admin
2. If no PDF exists yet, generates it on-the-fly
3. Streams the PDF file as download response

Route: `GET /certificates/{certificate}/download` (name: `certificates.download`)

---

## Event D: Revoking a Certificate

`RevokeCertificateAction` (`app/Actions/Certificate/RevokeCertificateAction.php`):
1. Validates certificate is not already revoked
2. Sets status to `REVOKED`
3. Records `revoked_at` and `revoked_by`
4. Revoked certificates are hidden from the student's portal

---

## Certificate Status Lifecycle

```
ISSUED ──► REVOKED (terminal)
```

Defined in `App\Enums\Certificate\CertificateStatus`.

---

## Models

| Model | Table |
|---|---|
| `App\Models\CertificateTemplate` | `certificate_templates` |
| `App\Models\Certificate` | `certificates` |

## Actions

| Action | Purpose |
|---|---|
| `CreateCertificateTemplateAction` | Creates a template |
| `IssueCertificateAction` | Issues certificate with PDF generation |
| `RevokeCertificateAction` | Revokes an issued certificate |

## Livewire Components

| Component | Route | View |
|---|---|---|
| `App\Livewire\Document\CertificateTemplateManager` | `admin/certificates/templates` | `livewire.document.certificate-template-manager` |
| `App\Livewire\Certificate\CertificateList` | `admin/certificates` | `livewire.certificate.certificate-list` |
| `App\Livewire\Certificate\StudentCertificates` | `student/certificates` | `livewire.certificate.student-certificates` |

## Key Rules

| Rule | Enforcement |
|---|---|
| **Unique certificate number** | Database unique constraint with auto-generated format |
| **Placeholders resolved at issuance** | Metadata snapshot prevents data drift |
| **One certificate per registration** | Single issued certificate per registration |
| **Score is optional** | Certificate can be issued without finalized assessment score |
| **Batch issuance available** | Via admin panel (template + registration select) |
| **Certificate issuance not required for closure** | Optional |
| **No supervisor dependency** | Fully admin-managed |

## Seamless Connection

Certificates are the final output of the internship and can be issued before or after period closure.
