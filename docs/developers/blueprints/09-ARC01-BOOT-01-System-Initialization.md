# Application Blueprint: System Initialization (ARC01-BOOT-01)

**Series Code**: `ARC01-BOOT-01` **Status**: `Done`

> **System Requirements Specification Alignment:** This configuration baseline implements the
> **Administrative Orchestration** ([SYRS-F-101]) and **Architecture & Maintainability**
> ([SYRS-NF-601], [SYRS-NF-602]) requirements of the authoritative
> **[System Requirements Specification](../specs.md)**.

---

## 1. Design Objectives & Scope

**Strategic Purpose**: Automate the system initialization process to ensure a resilient, secure, and
repeatable deployment experience.

**Objectives**:

- Eliminate configuration drift and manual orchestration errors during deployment.
- Provide a professional, high-trust onboarding experience for institutional stakeholders.
- Execute deep environmental validation to ensure host compatibility.

---

## 2. Functional Specification

### 2.1 Capability Set

- **Automated Installer CLI**: A unified `app:install` command to bootstrap the environment
  baseline.
- **Graphical Setup Wizard**: An 8-step interactive interface for institutional identity
  configuration and state-persistent onboarding.
- **Pre-flight System Auditor**: Automated validation of PHP extensions, directory permissions, and
  database connectivity.
- **Setup Security Baseline**: CLI-generated authorization tokens to prevent unauthorized web-based
  orchestration.

### 2.2 Stakeholder Personas

- **IT Staff**: Utilizes the auditor and CLI tools to verify environment health and bootstrap the
  platform.
- **Principal/Admin**: Utilizes the web wizard to establish institutional identity (`brand_name`,
  `brand_logo`) without technical overhead.

---

## 3. Architectural Impact (Logical View)

### 3.1 Modular Decomposition

- **Setup Module**: Dedicated domain for installation logic and initialization state persistence.

### 3.2 Security Architecture

- **Verification Middleware**: `ProtectSetupRoute` and `RequireSetupAccess` for lifecycle control.
- **Authentication**: Mandatory one-time token verification for initial web access.
- **Self-Lockdown Invariant**: Automated 404 lockdown of setup routes once installation is
  certified.

### 3.3 Persistence Logic

- **Identity Invariant**: All initialization records utilize **UUID v4**.
- **State Persistence**: Setup progress is persisted to the `settings` table to allow for
  interrupted session resumption.

---

## 4. Presentation Strategy (User Experience View)

### 4.1 Design Invariants

- **Flow Control**: Guided 8-step flow: Welcome -> Environment -> School -> Account -> Department ->
  Internship -> System -> Complete.
- **Accessibility**: Full localization in **ID** and **EN** across all onboarding interfaces.
- **Responsiveness**: Mobile-first design for all setup components.

---

## 5. Success Metrics (KPIs)

- **Initialization Velocity**: Reduced bootstrapping duration from >15 minutes to < 120 seconds.
- **Configuration Integrity**: Zero manual `.env` modifications required for a standard baseline.
- **Access Control**: 100% of web setup requests verified via authorized tokens.

---

## 6. Exit Criteria & Verification Protocols

A design series is considered done only when it satisfies the following gates:

- **Verification Gate**: 100% pass rate across the 23-test verification suite via
  **`composer test`**.
- **Quality Gate**: Clean compliance with static analysis via **`composer lint`**.
- **Acceptance Criteria**:
    - `app:install` successfully bootstraps the authoritative baseline.
    - Institutional metadata correctly persists to the `setting()` registry.
    - Setup module becomes inaccessible post-release.

---

## 7. Realization Summary

The initialization framework has been successfully implemented with a focus on systemic resilience
and security.

- **System Auditor**: Deep validation of environmental dependencies.
- **Guided Onboarding**: Optimized 8-step flow ensuring data context (School -> Account).
- **Hardened Security**: Multi-layer token verification and automated route destruction.

---

## 8. Improvement Suggestions

- **Competency Mapping**: Potential linkage between journals and curriculum rubrics for deeper
  instructional alignment.
- **Mentoring Dialogue**: Suggestions for formalizing feedback logs for Instructors and Mentors.
