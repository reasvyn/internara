# Application Blueprint: System Initialization (ARC01-BOOT-01)

**Series Code**: `ARC01-BOOT-01` | **Status**: `Done`

---

## 1. Strategic Context

- **Spec Alignment**: This configuration baseline implements the **Administrative Orchestration** ([SYRS-F-101]) and **Architecture & Maintainability** ([SYRS-NF-601], [SYRS-NF-602]) requirements of the authoritative **[Specs](../specs.md)**.

---

## 2. Logic & Architecture (Systemic View)

### 2.1 Capabilities
- **Automated Installer CLI**: A unified `app:install` command to bootstrap the environment baseline (Env, Key, Migrations, Seeders, Symlinks).
- **Pre-flight System Auditor**: Automated validation of PHP extensions, directory permissions, and database connectivity.
- **Setup Security Baseline**: CLI-generated authorization tokens and signed URLs to prevent unauthorized web-based orchestration.

### 2.2 Service Contracts
- **InstallerService**: Handles low-level technical installation tasks.
- **SystemAuditor**: Performs deep environmental validation.
- **SetupService**: Orchestrates the multi-step installation wizard state.

### 2.3 Data Architecture
- **Identity Invariant**: All initialization records and system settings utilize **UUID v4**.
- **State Persistence**: Setup progress is persisted to the `settings` table to allow session resumption.

---

## 3. Presentation Strategy (User Experience View)

### 3.1 UX Workflow
- **Graphical Setup Wizard**: An interactive 8-step flow: Welcome -> Environment -> School -> Account -> Department -> Internship -> System -> Complete.
- **Security Middleware**: `ProtectSetupRoute` and `RequireSetupAccess` ensure the wizard is only accessible via signed tokens and locked post-installation.

### 3.2 Interface Design
- **Onboarding Experience**: Minimalist, high-trust interface using MaryUI components.
- **Visual Feedback**: Real-time audit results and task progress indicators.

### 3.3 Invariants
- **Accessibility**: Full localization in **ID** and **EN** across all onboarding interfaces.
- **Responsiveness**: Mobile-first design for IT staff using portable devices.

---

## 4. Documentation Strategy (Knowledge View)

### 4.1 Engineering Record
- **Package Integration**: Formalization of the **[Laravel Modules](../packages/laravel-modules.md)** standards.
- **Tooling Guide**: Documentation of the `app:install` command in the **[Automated Tooling](../tooling.md)** reference.

### 4.2 Stakeholder Manuals
- **Installation Guide**: Authoring of the **[System Installation](../../wiki/installation.md)** guide for IT administrators.

---

## 5. Audit & Evaluation Report (v0.13.0 Audit)

### 5.1 Realized Outcomes
- **Resilient Installer**: `app:install` successfully automates the full technical baseline.
- **Hardened Security**: Verified that `URL::temporarySignedRoute` correctly protects the Wizard from unauthorized probing.
- **Environmental Awareness**: System Auditor successfully detects missing extensions and permission issues.

### 5.2 Identified Anomalies & Corrections
- **Port-Awareness**: Initial URL generation sometimes missed the port in non-standard environments. **Correction**: Added port detection logic and manual warnings in the CLI output.
- **Audit Output Redundancy**: Found potential crashes when audit results were not scalar strings. **Correction**: Refactored `AppInstallCommand` to safely handle array-based audit logs.


---

## 6. Exit Criteria & Verification Protocols

- **Verification Gate**: 100% pass rate across the setup verification suite via **`composer test`**.
- **Quality Gate**: zero static analysis violations via **`composer lint`**.
- **Acceptance Criteria**:
    - `app:install` successfully bootstraps the authoritative baseline.
    - Institutional metadata correctly persists to the `setting()` registry.
    - Setup module becomes inaccessible post-installation.

---

## 7. Improvement Suggestions (Legacy)

- **Competency Mapping**: Realized via the **[Reporting (ARC01-INTEL-01)](08-ARC01-INTEL-01-Reporting-Intelligence.md)** series.
- **Mentoring Dialogue**: Suggestions for formalizing feedback logs.