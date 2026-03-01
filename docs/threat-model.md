# Threat Model: Internara Security Architecture

This document formalizes the **Threat Model** for the Internara system, standardized according to **ISO/IEC 27005** (Information security risk management) and **OWASP** (Open Web Application Security Project) principles.

---

## 1. Threat Scenery & Attack Surface

Internara's modular monolith architecture presents several potential attack surfaces:
- **Presentation Layer**: Public-facing Livewire components (XSS, Injection).
- **Authentication**: Login and session management (Brute force, Session hijacking).
- **Domain Logic**: Unauthorized data access or mutation (Broken access control, IDOR).
- **Persistence**: Sensitive data exposure (PII leakage, database compromise).

---

## 2. Threat Actor Profile

| Actor | Capability | Motivation |
| :--- | :--- | :--- |
| **External Attacker** | Network-level exploitation, SQLi, XSS. | Financial gain, system disruption, PII theft. |
| **Malicious Student** | Domain-level logic abuse, IDOR attempts. | Grade manipulation, attendance fraud. |
| **Insider Threat** | Unauthorized data access by staff/instructors. | Data exfiltration, personal gain. |

---

## 3. High-Priority Threats & Mitigations

### 3.1 T01: Broken Access Control (IDOR)
- **Threat**: A student attempts to access or modify another student's journal entry or attendance log by manipulating the UUID in the URL.
- **Mitigation**: Every Service method and Policy must verify **ownership** and **supervisory context** before executing domain logic.

### 3.2 T02: PII Data Exposure
- **Threat**: Personal Identifiable Information (email, phone, NIK) is leaked via logs or insecure database backups.
- **Mitigation**: PII must be **encrypted at rest** and **masked in all logging sinks**.

### 3.3 T03: Cross-Site Scripting (XSS)
- **Threat**: A malicious user injects scripts into journal activity descriptions or activity logs.
- **Mitigation**: All user-generated content must be **automatically escaped** by the templating engine.

### 3.4 T04: SQL Injection
- **Threat**: An attacker manipulates search filters to execute unauthorized SQL queries.
- **Mitigation**: Use only **parameterized queries** via Eloquent and strict input validation at the service boundary.

---

## 4. Resilience & Defensive Posture

Internara employs the following defensive strategies:
- **Defense in Depth**: Security is enforced at the UI, Service, and Persistence layers.
- **Least Privilege**: Users are granted only the minimum necessary permissions via RBAC.
- **Identity Obfuscation**: Universal usage of **UUID v4** to prevent enumeration attacks.
- **Zero-Trust Logic**: All cross-module interactions must be authenticated via Service Contracts.

---

## 5. Continuous Verification

The threat model is validated through:
- **SAST (Static Analysis)**: Automated security scans in the CI/CD pipeline.
- **DAST (Dynamic Analysis)**: Periodic vulnerability assessment on the staging environment.
- **Manual Security Audits**: 3S Audit protocol during PR reviews.
