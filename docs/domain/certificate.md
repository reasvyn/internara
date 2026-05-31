# Certificate Domain

## Purpose

Certificate manages credentialing — template-based certificate issuance, revocation,
and serial number tracking.

---

## Design Principles

### 1. Unique Serial Numbers

Certificates use strictly sequential, unique serial numbers. Revoked serials are
permanently retired — never reused.

### 2. Batch Issuance

Cohort batch issuance is supported. One failure does not block the entire batch.

---

## Domain Boundary

The Certificate domain owns credentialing — the full lifecycle of certificates that validate a student's completion of their placement program. It manages certificate templates with configurable layouts, branding elements, data field mappings, and version tracking. Certificates can be issued individually with a unique serial number or in batch for an entire cohort (with individual failures not blocking the rest of the batch). Serial numbers follow a strict sequential scheme — each is unique, permanently retired after revocation, and never reused. Certificates follow a two-state lifecycle from issued to revoked, where revocation is terminal and irreversible — a revoked certificate cannot be reissued. Students can view and download their own certificates.

Certificate does not own student identity data (User/Mentee), program definitions (Internship), assessment scores or rubrics (Assessment), document templates or rendering (Document), or any operational domain data. It manages the credential record and its template definition, but the data fields that populate certificates (student name, program name, grades, dates) are owned by other domains. Certificate owns the question "has this student earned a credential?" while other domains own the data that answers it.

The domain depends on Internship for program context and completion status, on Assessment for competency and grade data used in certificate content, on User for student identity, and on Document for the rendering engine that produces the final certificate file. It consumes data from those domains but does not own it.

---

## Key Features

- Create and manage certificate templates with customizable layouts, branding elements, data field mappings, and versioning.
- Issue a single certificate with a unique, strictly sequential serial number to an individual student.
- Issue certificates in batch for an entire cohort, with individual failures not blocking the rest of the batch.
- Revoke a certificate with a reason category, permanently retiring its serial number.
- Enforce strictly sequential and unique serial numbers, permanently retiring revoked serial numbers with no reuse.
- Apply a two-state certificate lifecycle where issued certificates can be revoked and revocation is terminal and irreversible.
- Allow students to view and download their own certificates.
- Preview a certificate PDF in the browser before issuing it to verify layout, data fields, and branding.
- Display the serial number prominently with a copy-to-clipboard button for easy sharing.
- Track batch issuance progress with a status bar showing successful, pending, and failed certificate counts.
- View issued certificates in a student-facing list with download buttons and status badges.
- Select multiple students for batch certificate issuance with a bulk-issue confirmation dialog.
