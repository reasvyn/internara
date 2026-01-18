# Security Policy & Protocols

This document outlines the security standards and vulnerability reporting procedures for the
Internara project. We follow a **Security-by-Design** approach to ensure the integrity and privacy
of our academic management system.

---

## Support Policy

Internara provides security updates only for the **latest active release**. We do not support legacy
versions or experimental branches. If a vulnerability is identified, remediations will be applied to
the current development line and included in the subsequent release.

---

## Foundational Security Protocols

All development within the Internara project must adhere to these non-negotiable security
principles. These protocols are audited iteratively during every development cycle.

### 1. Identity & Access Control

- **UUID Standardization:** All primary keys and cross-module references MUST use UUIDs. This is our
  primary defense against Insecure Direct Object Reference (IDOR) attacks.
- **Mandatory Policies:** Every Eloquent model must have a corresponding Policy class.
- **Layered Authorization:** Permission checks must be enforced at both the UI (Livewire) and
  Business Logic (Service) layers.
- **Role-Based Access Control (RBAC):** Access is granted based on granular permissions
  (`module.action`) rather than broad roles where possible.

### 2. Data Isolation & Integrity

- **Module Isolation:** Modules must not have physical database-level foreign keys between them.
  Referential integrity is managed at the Application Layer (Services).
- **Strict Scoping:** Operational data (Journals, Attendance) must be scoped to the active Academic
  Year and the specific user/supervisor context.
- **Input Validation:** All data entering the Service layer must be validated against strict DTOs or
  validated arrays.

### 3. Privacy & Data Protection

- **PII Masking:** Personally Identifiable Information (PII) must be masked in application logs.
- **Media Privacy:** Student attachments and sensitive documents are stored on a `private` disk and
  accessed only via signed, temporary URLs.
- **Secret Management:** Hardcoding credentials or API keys is strictly prohibited. All
  configurations must use `config()` and `setting()` helpers.

### 4. Audit & Engineering Stories

- **Analytical Verification:** Every significant release undergoes a security review, with findings
  documented in the **Deep Analytical Narratives** under `docs/versions/`.
- **Iterative QA:** No feature is considered "Done" until it passes the full test suite and a manual
  logic audit.

---

## Reporting a Vulnerability

If you discover a security vulnerability, please do not disclose it publicly. Instead, send a
detailed report to:

**[reasnov.official@gmail.com](mailto:reasnov.official@gmail.com)**

### What to include in your report:

- A clear description of the vulnerability.
- Steps to reproduce the issue (Proof of Concept).
- Potential impact analysis.

We aim to acknowledge all legitimate reports within 24 hours and address critical issues within
48-72 hours.

---

## Public Disclosure

Please do not disclose security-related issues publicly until they have been resolved and an
official release or patch has been made available.
