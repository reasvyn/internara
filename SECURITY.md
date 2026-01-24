# Security Policy & Protocols

This document outlines the security standards for Internara. We follow a **Security-by-Design**
approach to protect institutional and student data.

---

## Support Policy

Security updates are provided for the **latest active release**. Refer to the
**[Versions Overview](docs/versions/versions-overview.md)** for current support status.

---

## Foundational Security Protocols

### 1. Identity & Access Control

- **UUID Standardization:** All entities use UUID v4 to prevent ID enumeration.
- **Mandatory Policies:** Every model MUST have a Policy class enforcing **Role-Based Access
  Control**.
- **User Roles:** Access is restricted to the roles mandated by the specs (Instructor, Staff,
  Student, Industry Supervisor).

### 2. Data Isolation & Integrity

- **Module Isolation:** **No physical foreign keys** between modules. Integrity managed via
  Services.
- **Strict Scoping:** Operational data is scoped to the active cycle via `HasAcademicYear`.

### 3. Privacy & Data Protection

- **PII Masking:** PII is masked in logs.
- **Secure Media:** Attachments stored on `private` disks; accessed via signed URLs.
- **No Hard-coding:** All settings must use `setting()` or `config()`. No secrets in source code.

### 4. Continuous Audit

- **Verification:** Features are validated against Specs during Phase 4 of the SDLC.
- **Testing:** Comprehensive **Pest** suite covering authorization gates and IDOR vectors.

---

## Reporting a Vulnerability

If you discover a security vulnerability, please do not disclose it publicly. Email us at:

**[reasnov.official@gmail.com](mailto:reasnov.official@gmail.com)**

---

_Security is a core value of Internara, ensuring the academic legitimacy of the internship process._
