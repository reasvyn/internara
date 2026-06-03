# Certification Domain

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Consolidated Certificates and Generated Documents

## Purpose

The **Certification** domain manages credentials issuance and formal document generation for the internship program. This includes template designing, PDF rendering (using Blade + DomPDF engines), batch issuing of certificates to completed students, revocation workflows, and general system report generation (e.g., student completion lists, placement analytics).

It is the final milestone in the internship program. Verified students who meet the required assessment scores receive a formal, system-signed certificate validating their internship participation.

---

## Design Principles

### 1. Unified Template Management
- Documents and certificates share a template design architecture.
- Administrators write HTML/CSS templates using Blade templates. Custom variables (e.g., student name, dates, company name, grades) are injected dynamically.
- System templates are version-controlled (`template_version` counter) to ensure that re-rendering historical documents preserves the exact styling and content they had at the time of issuance.

### 2. Secure and Scalable Rendering (DomPDF)
- All certificates and system documents are compiled into portable, read-only PDF files.
- PDF generation is isolated using support classes (`CertificateRenderer` and `DocumentRenderer`) that translate Blade layouts to PDF formats via DomPDF.
- Generated PDFs are stored permanently using Spatie Media Library, saving compute time and guaranteeing consistent files during subsequent downloads.

### 3. Credential Lifecycle Control
Certificates follow a strict verification lifecycle managed by `CertificateStatus`:
`DRAFT` ➔ `ISSUED` or `REVOKED`
- **Issued Certificates** cannot be modified. They are signed with a unique cryptographic verification hash.
- **Revocation**: If a certificate needs to be canceled (due to grading adjustments or behavior issues), admins can revoke it. Revoked certificates are flagged and blocked from student downloads.

---

## Domain Boundary

### Technical Ownership
- **Certificate Issuances**: Triggering individual or batch issuances, tracking credentials, and signing verification hashes.
- **Document Generations**: Rendering reports, compiling statistics sheets, and printing formats.
- **PDF Streaming & Downloads**: Controllers streaming PDF files to browser downloads.
- **Template CRUDs**: Storing HTML layouts and managing versions.
- **Revocation Workflows**: Invalidating issued credentials with audit logs.

### Dependencies
- **Core**: Relies on base actions, models, policies, and SmartLogger auditing.
- **User**: Connects certificates to student and administrator user records.
- **Enrollment**: Accesses registration records to confirm completion and active status.
- **Assessment**: Queries student final grading results to ensure they meet the certificate issuance threshold.

---

## Domain Rules & Invariants

- **R1 — Issuance Threshold**: A certificate cannot be issued to a student if their registration status is not verified or if their final assessment grade is below the pass threshold.
- **R2 — Verification Code Uniqueness**: Every issued certificate is stamped with a unique, cryptographically random verification code (`hash_equals` checked) to prevent forgery.
- **R3 — Template Version Preservation**: Updating a certificate template does not apply retroactively to already issued certificates unless explicitly requested via an admin "re-render" tool. Historical issuances keep their original `template_version`.
- **R4 — Download Availability**: Students can only view and download certificates set to `ISSUED` status. `DRAFT` or `REVOKED` certificates are hidden or blocked in student panels.

---

## Key Features

- **Certificate Template Builder**: Admin editor with variables support (placeholders like `{{ student_name }}`) to customize printouts.
- **Batch Credentials Issuer**: Batch action scanning completed student cohorts, generating PDFs, and dispatching download notifications.
- **Revocation Tool**: Restricts certificate downloads, transitioning records to revoked states while capturing reasons.
- **Secure Download Controller**: streams PDF downloads, validating user authentication and policy ownership before releasing streams.
- **Report Document Engine**: Renders program-level summaries, attendance aggregates, and grading books into print-ready PDF formats.
- **Verification Portal**: Unauthenticated portal where external employers can input a certificate hash to verify its authenticity.
