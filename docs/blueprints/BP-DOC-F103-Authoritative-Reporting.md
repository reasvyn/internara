# Application Blueprint: Authoritative Reporting (BP-DOC-F103)

**Blueprint ID**: `BP-DOC-F103` | **Requirement ID**: `SYRS-F-103` | **Scope**:
`Intelligence & Delivery`

---

## 1. Strategic Context

- **Spec Alignment**: This blueprint authorizes the generation of QR-signed institutional records
  and competency reports required to satisfy **[SYRS-F-103]** (Authoritative Reporting).
- **Objective**: Establish a secure, verifiable, and professionally branded document synthesis
  engine that converts student vocational data into authoritative PDF/Excel certificates.
- **Rationale**: Institutional reports (certificates, competency records) are the final output of
  the internship program. By implementing cryptographic signatures (via QR codes) and standardized
  branding, we ensure these documents are tamper-proof and reflect the prestige of the institution.

---

## 2. Logic & Architecture (Systemic View)

### 2.1 The Synthesis Engine

- **Asynchronous Pipeline**: Report generation MUST be handled via queued jobs
  (`ReportGeneratorJob`) to prevent UI blocking during complex PDF rendering.
- **Template System**: Utilization of a Blade-based PDF template engine that automatically injects
  institutional metadata (Logo, Brand Colors, Signatories).

### 2.2 QR Verification Loop

Every generated PDF MUST contain a unique **Verification QR Code**:

1.  **Generation**: The system creates a Signed URL pointing to the `Public/Verification` portal.
2.  **Encoding**: The URL is hashed and encoded into a QR image embedded in the document footer.
3.  **Validation**: External parties (employers, universities) scan the code to verify the
    document's authenticity against the system's live database.

### 2.3 Service Contract Specifications

- **`Modules\Report\Services\Contracts\ReportService`**: The master orchestrator for initiating
  generation, managing document storage, and verifying QR signatures.
- **`Modules\Report\Contracts\ReportProvider`**: An interface that domain modules MUST implement to
  provide data for specific report types (e.g., `CompetencyReportProvider`).

---

## 3. Presentation Strategy (User Experience View)

### 3.1 UX Workflow

- **Background Generation**: When a user clicks "Download", the UI displays a "Preparing Document"
  state while the job executes in the background.
- **Verification Portal**: A clean, public-facing portal where stakeholders can upload or scan
  documents to see a "Green/Red" authenticity status.

### 3.2 Interface Design

- **Branded Layout**: The PDF layout MUST strictly follow the institutional style guide, including
  the mandated **Instrument Sans** typography and 1px border invariants.

---

## 4. Verification Strategy (V&V View)

### 4.1 Unit Verification

- **Signature Hash Integrity**: Unit tests verifying that changing a single character in the
  report's UUID results in a "Signature Invalid" state.
- **Metadata Audit**: Verification that generated PDFs contain correct accessibility tags (Title,
  Author, Lang) matching the system locale.

### 4.2 Feature Validation

- **Private Disk Sovereignty**: Integration tests ensuring that generated reports are stored on the
  `private` disk and return `404` when accessed via direct public URLs.
- **Queue Invariant**: Tests verifying that the `ReportService` correctly dispatches a job to the
  `default` queue.

### 4.3 Architecture Verification

- **Module Isolation**: Pest Arch tests ensuring that the `Report` module remains independent of
  UI-specific Livewire state during the generation process.

---

## 5. Compliance & Standardization (Integrity View)

### 5.1 Document Accessibility

- **WCAG 2.1 AA**: All generated PDF documents MUST be optimized for screen readers, including
  proper tagging of headings and alt-text for images.

---

### 5.2 Mandatory 3S Audit Alignment

To guarantee architectural integrity and prevent systemic entropy, this implementation MUST strictly
adhere to the project's 3S Protocol:

- **S1 (Secure)**: Every state-altering method within the Service Layer MUST explicitly invoke
  `Gate::authorize()` prior to execution to prevent IDOR and Broken Access Control. Sensitive PII
  fields MUST utilize the `encrypted` cast.
- **S2 (Sustain)**: All files MUST declare `strict_types=1`. Virtual attributes MUST be implemented
  using explicit typing and standard methods. All user-facing strings and exceptions MUST be localized via
  `__('key')`. Every public method MUST contain professional PHPDoc explaining its intent.
- **S3 (Scalable)**: Cross-module interactions MUST use **Contract-First** dependency injection
  (Interfaces). All domain models MUST implement `HasUuid` (and `HasStatus`, `HasAcademicYear` where
  applicable). Asynchronous side-effects MUST utilize Domain Events with lightweight, UUID-only
  payloads.

## 6. Documentation Strategy (Knowledge View)

### 6.1 Engineering Record

- **Developer Guide**: Update `modules/Report/README.md` to document the PDF template system and the
  QR signature hashing algorithm.

### 6.2 Stakeholder Manuals

- **Admin Guide**: Update `docs/wiki/reporting.md` to document the types of authoritative reports
  available and the QR verification process.

---

## 7. Exit Criteria & Quality Gates

- **Acceptance Criteria**: PDF generation operational; QR verification loop verified; Private
  storage enforced; Accessibility metadata confirmed.
- **Verification Protocols**: 100% pass rate in the reporting and synthesis test suite.
- **Quality Gate**: Physical scan test of 10 sample QR codes on varied devices confirms 100%
  verification success rate.

---

_Application Blueprints prevent architectural decay and ensure continuous alignment with the
foundational specifications._
