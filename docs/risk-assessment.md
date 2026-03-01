# Risk Assessment: Internara Strategic Engineering

This document provides a formal **Risk Assessment** for the Internara system, standardized according to **ISO/IEC 27005** (Information security risk management) and **ISO/IEC 31000** (Risk management).

---

## 1. Risk Identification & Scope

Internara identifies risks across several domains:
- **Technological**: Complexity of modular monolithic architecture.
- **Security**: Potential data breaches or unauthorized access.
- **Operational**: System downtime or data loss during migration.
- **Governance**: Non-compliance with academic or institutional standards.

---

## 2. Risk Matrix (Probability vs. Impact)

| Risk ID | Risk Description | Probability | Impact | Score |
| :--- | :--- | :--- | :--- | :--- |
| **R01** | Data Breach (PII Exposure) | Low | Critical | High |
| **R02** | Broken Access Control (IDOR) | Medium | High | High |
| **R03** | System Downtime (Infrastructure Failure) | Low | Medium | Medium |
| **R04** | Modular Coupling Drift (Architectural Decay) | Medium | Medium | Medium |
| **R05** | Unauthorized Grade/Competency Change | Low | High | Medium |

---

## 3. High-Priority Risk Treatment

### 3.1 R01 & R02: Security & Identity
- **Treatment**: Mitigation through the **3S Doctrine**.
- **Controls**: UUID v4 identity, PII encryption at rest, and mandatory RBAC policy enforcement at the Service boundary.

### 3.2 R04: Modular Coupling Drift
- **Treatment**: Mitigation through automated **Architecture Tests** (`tests/Arch`).
- **Controls**: CI pipeline blocking cross-module physical foreign keys and direct model instantiation.

---

## 4. Risk Monitoring & Review

Risks are reviewed during:
- **Major Release Cycles**: Architectural re-assessment for breaking changes.
- **Security Audits**: Continuous 3S Audit protocol during PR reviews.
- **Environment Migrations**: Infrastructure stability and data integrity checks.

---

## 5. Residual Risk Acceptance

Certain risks (e.g., zero-day vulnerabilities in the underlying Laravel framework) are accepted as part of the technological stack. These are managed through continuous updates and maintaining an active **Software Bill of Materials (SBOM)**.
