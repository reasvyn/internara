# Certification — Certificate Templates, Issuance, Batch Processing & QR Verification

> **Last updated:** 2026-07-22 **Changes:** feat — initial spec covering certificate template management,
> single/batch issuance, PDF rendering with 17 placeholders, QR hash generation, revocation,
> and student certificate view

## Description

Complete specification of the Internara Certification module: admin-managed certificate templates
with placeholder-based content, single and batch certificate issuance for completed PKL
registrations, PDF rendering via DomPDF with dynamic placeholder resolution, QR hash generation
for authenticity verification, revocation workflow, and student-facing certificate download.

---

## 1. Problem Statements

### PS-1 — Standardized Certificate Generation for PKL Completion

Schools issue PKL completion certificates manually (Word templates, handwritten). This produces
inconsistent formatting, makes verification difficult, and doesn't scale for large cohorts. The
system must generate professional PDF certificates from admin-defined templates with dynamic
placeholder resolution.

### PS-2 — Batch Issuance for Large Cohorts

At the end of a PKL period, dozens or hundreds of students need certificates simultaneously.
Issuing certificates one-by-one is impractical. The system must support batch issuance with
per-student error collection and success reporting.

### PS-3 — Authenticity Verification via QR Code

Employers and institutions receiving PKL certificates need to verify their authenticity. Without
a verification mechanism, forged certificates are indistinguishable from genuine ones. The system
must generate unique QR hashes that can be validated against the certificate record.

### PS-4 — Certificate Revocation for Error Correction

Certificates may need to be revoked if issued in error (wrong student, wrong program, data
corrections). Without a formal revocation workflow, schools must manually recall physical copies
or issue replacement certificates without clear audit trail.

### PS-5 — Student Self-Service Certificate Access

Students should be able to view and download their own certificates without contacting administrators.
Without self-service access, every certificate request becomes a support ticket.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide admin UI for creating and managing certificate templates with placeholder syntax |
| G2  | Issue individual certificates with unique certificate numbers and QR hashes |
| G3  | Support batch issuance across multiple registrations with error collection |
| G4  | Render certificates as PDF using DomPDF with 17 dynamic placeholders |
| G5  | Store rendered PDFs on local disk for download |
| G6  | Support certificate revocation (ISSUED → REVOKED transition) |
| G7  | Provide students with a self-service certificate view and download |
| G8  | Lazy-generate PDF on first download if not pre-rendered |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Certificate template versioning or design history |
| NG2  | Digital signatures or cryptographic certificate signing |
| NG3  | Online verification portal (public URL with QR scan) |
| NG4  | Email delivery of certificates |
| NG5  | Certificate re-issuance after revocation (create new certificate instead) |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Creates a Certificate Template

**Actor:** Admin
**Preconditions:** Admin is authenticated with `admin` or `super_admin` role
**Flow:**
1. Admin navigates to `admin/certificates/templates`
2. `CertificateTemplateManager` shows existing templates
3. Admin clicks "New Template", fills name, layout (portrait/landscape), content_template (with placeholders)
4. Calls `CreateCertificateTemplateAction::execute(data)`
**Postconditions:** Template exists and is active

### UC-2 — Admin Issues a Certificate

**Actor:** Admin
**Preconditions:** Active template exists; student has completed registration
**Flow:**
1. Admin navigates to `admin/certificates`
2. `CertificateList` shows existing certificates and active templates
3. Admin selects template and registration, clicks "Issue"
4. `IssueCertificateAction::execute(registration, template)`:
   - Generates unique `certificate_number` and `qr_hash`
   - Creates Certificate record with ISSUED status
   - Calls `CertificateRenderer::resolvePlaceholders()` (17 placeholders)
   - Renders PDF via `CertificateRenderer::renderPdf()`
   - Stores PDF via `CertificateRenderer::storePdf()`
5. Dispatches `CertificateIssued` event
**Postconditions:** Certificate issued with PDF stored; student notified

### UC-3 — Admin Batch Issues Certificates

**Actor:** Admin
**Preconditions:** Active template exists; multiple students have completed registrations
**Flow:**
1. Admin selects template and multiple registrations
2. Calls `BatchIssueCertificateAction::execute(registrationIds, template)`
3. For each registration: attempts `IssueCertificateAction`; collects errors
4. Returns array of issued certificates and error details
**Postconditions:** Certificates issued for eligible registrations; errors reported for ineligible

### UC-4 — Student Downloads Certificate

**Actor:** Student
**Preconditions:** Student has an issued certificate
**Flow:**
1. Student navigates to `/student/certificates`
2. `StudentCertificates` lists certificates with status and issue date
3. Student clicks download
4. `CertificateDownloadController` checks ownership or admin role
5. If PDF not pre-rendered, calls `CertificateRenderer::storePdf()` to generate
6. Returns `StreamedResponse` with PDF
**Postconditions:** Student receives PDF download

---

## 4. Functional Requirements

### Template Management

| ID   | Requirement |
| ---- | ----------- |
| FR-TM1 | `CertificateTemplateManager` must be accessible at route `admin/certificates/templates` with `auth` and `role:super_admin\|admin` middleware |
| FR-TM2 | `CertificateTemplate` model must use `#[Fillable]` with `name`, `layout`, `content_template`, `is_active`, `created_by` |
| FR-TM3 | `layout` must support: `portrait` (default), `landscape` |
| FR-TM4 | `content_template` must contain HTML with `{placeholder}` syntax for dynamic values |
| FR-TM5 | `CertificateTemplatePolicy` must restrict all operations to admin roles |
| FR-TM6 | Templates must support `is_active` flag — only active templates available for issuance |

### Certificate Issuance

| ID   | Requirement |
| ---- | ----------- |
| FR-CI1 | `IssueCertificateAction` must accept `Registration` and `CertificateTemplate`, return `Certificate` |
| FR-CI2 | `certificate_number` must be globally unique (generated with random component) |
| FR-CI3 | `qr_hash` must be globally unique (random string for verification) |
| FR-CI4 | Default status must be `ISSUED` |
| FR-CI5 | `template_content` must snapshot the template's `content_template` at issuance time |
| FR-CI6 | `issued_by` must record the admin who issued the certificate |
| FR-CI7 | `issued_at` must be set to issuance timestamp |

### Batch Processing

| ID   | Requirement |
| ---- | ----------- |
| FR-BP1 | `BatchIssueCertificateAction` must extend `BaseProcessAction` |
| FR-BP2 | `execute(array $registrationIds, CertificateTemplate)` must iterate and attempt issuance per registration |
| FR-BP3 | Errors must be collected per registration — batch must not fail entirely on one error |
| FR-BP4 | `executeFiltered(Builder $query, CertificateTemplate)` must accept a query builder for filtered batch issuance |
| FR-BP5 | Batch results must return array of issued certificates and error details |

### PDF Rendering

| ID   | Requirement |
| ---- | ----------- |
| FR-PR1 | `CertificateRenderer` must resolve 17 placeholders: `{student_name}`, `{school_name}`, `{company_name}`, `{score}`, `{score_letter}`, `{certificate_number}`, `{issue_date}`, `{start_date}`, `{end_date}`, `{internship_name}`, `{department_name}`, `{supervisor_name}`, `{teacher_name}`, `{student_number}`, `{student_email}`, `{student_phone}`, `{school_address}` |
| FR-PR2 | `renderHtml(Registration, Certificate)` must return HTML string |
| FR-PR3 | `renderPdf(Registration, Certificate)` must return PDF string via DomPDF |
| FR-PR4 | `storePdf(Registration, Certificate)` must store to `local` disk at `certificates/` directory |
| FR-PR5 | `getDiskPath(string)` must return full filesystem path for a certificate file |

### Revocation

| ID   | Requirement |
| ---- | ----------- |
| FR-RV1 | `RevokeCertificateAction` must transition status from ISSUED to REVOKED |
| FR-RV2 | `CertificateStatus::isTerminal()` must return true for REVOKED |
| FR-RV3 | `CertificatePolicy::revoke()` must require admin role |
| FR-RV4 | Revoked certificates must remain in the system (not deleted) |

### Download & Verification

| ID   | Requirement |
| ---- | ----------- |
| FR-DV1 | `CertificateDownloadController` must check student ownership or admin role |
| FR-DV2 | If PDF not pre-rendered, controller must lazy-generate via `CertificateRenderer::storePdf()` |
| FR-DV3 | `CertificateList` must display certificate number, status, issue date, and student info |
| FR-DV4 | `StudentCertificates` must show only the current student's certificates |
| FR-DV5 | `CertificatePolicy::viewAny` must allow super_admin, admin, and student roles |
| FR-DV6 | `CertificatePolicy::view` must allow admin or the certificate's student |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-S1 | Certificate number uniqueness must be enforced at database level |
| NFR-S2 | QR hash uniqueness must be enforced at database level |
| NFR-S3 | Revoked certificates must not be re-issued (same registration + template → new certificate) |
| NFR-S4 | PDF storage must use local disk (not public URL) to prevent unauthorized access |
| NFR-P1 | Single certificate issuance must complete in < 5s (PDF rendering + storage) |
| NFR-P2 | Batch issuance of 50 certificates must complete in < 60s |
| NFR-P3 | Certificate list page must load in < 500ms |
| NFR-P4 | PDF lazy-generation must complete in < 5s on first download |
| NFR-R1 | Certificate issuance must be wrapped in a database transaction |
| NFR-R2 | Batch issuance must collect errors per-registration without failing the entire batch |
| NFR-U1 | Certificate number and QR hash must be displayed in admin certificate list |
| NFR-U2 | Student certificate view must show status (ISSUED/REVOKED) with visual indicator |
| NFR-M1 | All PHP files must declare `strict_types=1` and follow PSR-12 |
| NFR-L1 | All user-facing strings must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |

---

## 6. API / Data Contracts

### Certificate Model

```
App\Certification\Certificate\Models\Certificate
  Table: certificates (UUID PK)
  Fillable: registration_id, certificate_number, qr_hash, status, template_content, issued_by, issued_at
  Casts: status → CertificateStatus, issued_at → datetime
  Default: status = CertificateStatus::ISSUED
  Relations: registration() BelongsTo Registration, issuer() BelongsTo User
  Factory: CertificateFactory
```

### CertificateTemplate Model

```
App\Certification\Certificate\Models\CertificateTemplate
  Table: certificate_templates (UUID PK)
  Fillable: name, layout, content_template, is_active, created_by
  Casts: is_active → boolean
  Relations: createdBy() BelongsTo User
  Factory: CertificateTemplateFactory
```

### CertificateStatus Enum

```
App\Certification\Certificate\Enums\CertificateStatus: string
  Implements: StatusEnum
  Cases: ISSUED='issued', REVOKED='revoked'
  Methods: label(): string, isTerminal(): bool (true for REVOKED),
           validTransitions(): array, canTransitionTo(StatusEnum): bool
  Transitions: ISSUED→[REVOKED], REVOKED→[]
```

### CertificateRenderer Service

```
App\Certification\Certificate\Services\CertificateRenderer (final readonly)
  Methods:
    resolvePlaceholders(Registration, Certificate): array — 17 key-value pairs
    renderHtml(Registration, Certificate): string
    renderPdf(Registration, Certificate): string
    storePdf(Registration, Certificate): string — returns stored file path
    getDiskPath(string): string — full filesystem path
```

### Actions

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| `IssueCertificateAction` | `BaseCommandAction` | `Registration, CertificateTemplate` | `Certificate` |
| `BatchIssueCertificateAction` | `BaseProcessAction` | `array $registrationIds, CertificateTemplate` | `array` |
| `CreateCertificateTemplateAction` | `BaseCommandAction` | `array $data` | `CertificateTemplate` |
| `RevokeCertificateAction` | `BaseCommandAction` | `Certificate` | `Certificate` |

### Event

| Event | Dispatched By |
| ----- | ------------- |
| `CertificateIssued` | `IssueCertificateAction` |

### Policies

| Policy | Abilities |
| ------ | --------- |
| `CertificatePolicy` | viewAny: super_admin/admin/student, view: admin/student's own, create: admin, update: false, delete: false, revoke: admin |
| `CertificateTemplatePolicy` | viewAny/create/update/delete: admin only |

### Routes

| Route | Component | Name | Middleware |
| ----- | --------- | ---- | ---------- |
| `GET /certificates/{certificate}/download` | `CertificateDownloadController` | `certificates.download` | `auth` |
| `GET /student/certificates` | `StudentCertificates` | `student.certificates` | `auth`, `role:student` |
| `GET /admin/certificates/templates` | `CertificateTemplateManager` | `sysadmin.certificates.templates` | `auth`, `role:super_admin\|admin` |
| `GET /admin/certificates` | `CertificateList` | `sysadmin.certificates` | `auth`, `role:super_admin\|admin` |

### Database Schema

```
certificates:
  id: uuid (PK)
  registration_id: foreignUuid → registrations.id (cascadeOnDelete, indexed)
  certificate_number: string (unique)
  qr_hash: string (unique)
  status: string (default 'issued', indexed)
  template_content: text (nullable)
  issued_by: foreignUuid → users.id (nullOnDelete, nullable)
  issued_at: dateTime
  timestamps

certificate_templates:
  id: uuid (PK)
  name: string
  layout: string(20) (default 'portrait')
  content_template: text
  is_active: boolean (default true, indexed)
  created_by: foreignUuid → users.id (nullOnDelete, nullable)
  timestamps
  Indexes: created_at
```

---

## 7. Design Decisions

### DD-1 — Template Content as Text With Placeholder Syntax (Not Blade)

**Decision:** Certificate templates store HTML content with `{placeholder}` syntax, rendered by `CertificateRenderer` via string replacement rather than Blade rendering.
**Rationale:** Blade templates require PHP compilation, which introduces security risks (arbitrary code execution if templates are user-editable). Placeholder syntax is safe, predictable, and easy for admins to understand. The 17 known placeholders are enumerated and validated.
**Trade-off:** Less flexible than Blade (no conditionals, loops). Rejected alternative: Blade templates (security risk for user-editable content); Twig (adds dependency).

### DD-2 — QR Hash as Random String (Not URL)

**Decision:** `qr_hash` is a random unique string stored on the certificate record, not a URL-encoded QR code.
**Rationale:** The hash serves as a proof-of-existence token — anyone with the hash can verify the certificate exists in the system. A URL would couple the hash to a specific endpoint, which may change. The hash is format-agnostic and can be embedded in a QR code image at display time.
**Trade-off:** No built-in QR image generation. Rejected alternative: store a URL (couples to deployment environment).

### DD-3 — PDF Lazy-Generation on First Download

**Decision:** Certificate issuance creates the record and generates the hash, but the PDF is only rendered and stored on first download (or pre-generated during issuance).
**Rationale:** Batch issuance of 50+ certificates would be slow if each required PDF rendering during issuance. Lazy generation allows fast issuance with on-demand rendering. The `CertificateRenderer::storePdf()` is idempotent — re-rendering overwrites the same file path.
**Trade-off:** First download has latency. Rejected alternative: always pre-render (slows batch issuance); never store (regenerates every download, wasteful).

### DD-4 — Template Content Snapshot at Issuance Time

**Decision:** `Certificate.template_content` stores a snapshot of the template's `content_template` at the time of issuance.
**Rationale:** Templates may be edited after certificates are issued. Without snapshots, editing a template would retroactively change the appearance of all previously issued certificates. The snapshot ensures each certificate's content is immutable after issuance.
**Trade-off:** Storage overhead for duplicated template content. Rejected alternative: always reference template by ID (changes retroactively affect all certificates).

---

## 8. Success Metrics

### 8.1 Data Integrity

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Duplicate certificate numbers | 0 | Unique constraint on `certificate_number` |
| Duplicate QR hashes | 0 | Unique constraint on `qr_hash` |
| Revoked certificates re-issued | 0 | Status enum terminal state enforcement |
| Template change affecting old certificates | 0 | Content snapshot at issuance time |

### 8.2 Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Single issuance | < 5s | PDF rendering + storage |
| Batch issuance (50 certs) | < 60s | Sequential processing with error collection |
| Certificate list load | < 500ms | Admin list page |
| PDF lazy-generation | < 5s | First download rendering |

### 8.3 User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Batch error reporting | Per-registration errors | Clear success/failure counts |
| Student self-service | No admin intervention required | Student downloads directly |
| Certificate status visibility | Color-coded ISSUED/REVOKED | Visual status indicator |

### 8.4 Architecture Compliance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| No model mutations in Livewire (C1) | 0 violations | All mutations via Actions |
| No service locator (C2) | 0 violations | CertificateRenderer injected via constructor |
| Strict types (D1) | 100% files | `declare(strict_types=1)` in all PHP files |

---

## Quick References

- `app/Certification/Certificate/Models/Certificate.php` — Certificate model with status and QR hash
- `app/Certification/Certificate/Models/CertificateTemplate.php` — Template model with placeholder content
- `app/Certification/Certificate/Enums/CertificateStatus.php` — ISSUED/REVOKED status enum
- `app/Certification/Certificate/Services/CertificateRenderer.php` — PDF rendering with 17 placeholders
- `app/Certification/Certificate/Actions/IssueCertificateAction.php` — Single certificate issuance
- `app/Certification/Certificate/Actions/BatchIssueCertificateAction.php` — Batch issuance with error collection
- `app/Certification/Certificate/Actions/CreateCertificateTemplateAction.php` — Template creation
- `app/Certification/Certificate/Actions/RevokeCertificateAction.php` — Certificate revocation
- `app/Certification/Certificate/Http/Controllers/CertificateDownloadController.php` — PDF download with lazy-generation
- `app/Certification/Certificate/Livewire/CertificateList.php` — Admin certificate management
- `app/Certification/Certificate/Livewire/CertificateTemplateManager.php` — Template CRUD
- `app/Certification/Certificate/Livewire/StudentCertificates.php` — Student certificate view
- `app/Certification/Certificate/Policies/CertificatePolicy.php` — Authorization
- `app/Certification/Certificate/Policies/CertificateTemplatePolicy.php` — Template authorization
- `app/Certification/Certificate/Events/CertificateIssued.php` — Issuance event
- `database/migrations/2026_01_04_000012_create_certificates_table.php` — Certificates schema
- `database/migrations/2026_01_04_000013_create_certificate_templates_table.php` — Templates schema
- `routes/web/certification.php` — Route definitions
- `docs/modules/certification.md` — Module conceptual documentation
