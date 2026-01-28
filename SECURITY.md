# Security Policy: Vulnerability Management

This document formalizes the security standards and vulnerability disclosure protocols for the
Internara project. We follow a **Security-by-Design** approach to ensure the confidentiality,
integrity, and availability of institutional and student data.

---

## 🛠 Support Policy

Security updates are provided exclusively for the **latest stable release**. For more details on the
support status of specific versions, refer to the
**[Release Notes](docs/versions/versions-overview.md)**.

| Version    | Readiness | Security Support |
| :--------- | :-------- | :--------------- |
| **v0.9.x** | Alpha     | Snapshot Only    |
| **v0.8.x** | Alpha     | Snapshot Only    |
| **< v0.8** | Archived  | EOL              |

---

## 🔐 Foundational Security Protocols

Our engineering process incorporates the following protections as mandated by the authoritative
**[System Requirements Specification](docs/internal/system-requirements-specification.md)**:

### 1. Identity & Access Management (IAM)

- **UUID Identity**: All entities utilize UUID v4 to prevent ID enumeration and data scraping.
- **Authorization Baselines**: Every domain resource is protected by mandatory **Policies**
  enforcing role-based access control (RBAC).

### 2. Data Isolation & Privacy

- **Modular Sovereignty**: Strict isolation between modules—no physical foreign keys across
  boundaries.
- **Privacy Masking**: Automated masking of Personally Identifiable Information (PII) in all system
  logs.
- **Secure Media**: Sensitive documents are stored on private storage baselines and accessed via
  cryptographic signed URLs.

### 3. Systematic Verification

- **Continuous Audit**: Every system state transition is verified via **`composer test`**.
- **Static Analysis**: Mandatory vulnerability scanning and path analysis via **`composer lint`**.

---

## 🚨 Reporting a Vulnerability

If you discover a potential security vulnerability, please do not disclose it publicly. We encourage
responsible disclosure to protect the system's stakeholders.

Please report your findings directly to the project maintainer:
**[reasnov.official@gmail.com](mailto:reasnov.official@gmail.com)**

---

_Security is a core value of Internara, ensuring the academic legitimacy and privacy of the
vocational internship process._
