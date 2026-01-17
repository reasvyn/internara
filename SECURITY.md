# Security Policy

This document outlines the security policy and procedures for the Internara project. We take the
security of our academic management system seriously and appreciate the efforts of those who help us
identify and resolve vulnerabilities.

---

## Supported Versions

During the current development phase (**Alpha**), we only provide security updates for the latest
active branch.

| Version     | Supported          | Notes                               |
| ----------- | ------------------ | ----------------------------------- |
| `0.x-alpha` | :white_check_mark: | Active development and prototyping. |
| < `0.1.0`   | :x:                | Legacy/Initial experimental builds. |

---

## Security Audit Protocol

As part of our commitment to security, every significant release cycle (identified by a **Series
Code**) undergoes a security audit. Findings and remediations are documented in the respective
version notes under `docs/versions/`.

We adhere to the following principles:

- **UUID Migration:** All primary and foreign keys for sensitive entities use UUIDs to prevent IDOR.
- **Strict RBAC:** Access control is enforced at both the route and policy levels.
- **Application-Level Integrity:** Data integrity across modules is managed via Services to maintain
  low coupling.

---

## Reporting a Vulnerability

If you discover a security vulnerability within Internara, please do not disclose it publicly.
Instead, send a detailed report to Reas Vyn at:

**[reasnov.official@gmail.com](mailto:reasnov.official@gmail.com)**

### What to include in your report:

- A description of the vulnerability.
- Steps to reproduce the issue (PoC).
- Potential impact.

All legitimate reports will be acknowledged, and we aim to address critical vulnerabilities within
48-72 hours of confirmation.

---

## Public Disclosure

Please do not disclose security-related issues publicly until they have been resolved and an
official release or patch has been made available.
