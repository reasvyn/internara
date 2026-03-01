# Incident Response Procedure (IRP): Operational Resilience

This document formalizes the **Incident Response Procedure (IRP)** for the Internara system, standardized according to **ISO/IEC 27035** (Information security incident management).

---

## 1. Incident Classification

| Severity | Description | Target Response |
| :--- | :--- | :--- |
| **CRITICAL** | Total system outage, massive data breach, or unauthorized admin access. | Immediate (Within 1 hour). |
| **HIGH** | Functional outage affecting a major module (e.g., Attendance blocked). | Within 4 hours. |
| **MEDIUM** | Performance degradation or non-critical functional bug. | Within 24 hours. |
| **LOW** | Minor UI defect or documentation inconsistency. | Next release cycle. |

---

## 2. Response Lifecycle

### 2.1 Detection & Reporting
Incidents are detected via:
- Automated monitoring alerts (e.g., Sentry, New Relic).
- User reports via the official support channels (`SUPPORT.md`).
- Integrity violations triggered by the bootstrapping guard.

### 2.2 Analysis & Triage
- **Initial Audit**: Review system logs (`storage/logs/laravel.log`) and activity logs.
- **Scope Identification**: Determine the impacted modules and user cohorts.

### 2.3 Containment & Eradication
- **Containment**: Temporary lockdown of affected routes or modules if necessary.
- **Fix Development**: Develop a hotfix baseline targeting the current release tag.
- **V&V Pass**: Ensure the hotfix passes all existing and new regression tests.

### 2.4 Recovery & Baseline Promotion
- **Promotion**: Deploy the hotfix following the **[Release Protocols](releases.md)**.
- **Communication**: Notify impacted institutional stakeholders of the resolution.

---

## 3. Post-Incident Review (PIR)

For every CRITICAL or HIGH incident, a formal PIR must be conducted:
- **Root Cause Analysis (RCA)**: Identify the technical or procedural failure.
- **Corrective Action**: Update documentation, tests, or architecture to prevent recurrence.
- **Traceability**: Link the RCA record to the corresponding issue in the `problem-resolution-log.md`.
