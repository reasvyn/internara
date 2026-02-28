# Blueprint: Installation Wizard (BP-SYS-F101)

**Blueprint ID**: `BP-SYS-F101` | **Requirement ID**: `SYRS-F-101` | **Scope**: System Core

---

## 1. Context & Strategic Intent

This blueprint defines the sequential 8-step wizard required to orchestrate environment auditing, school identity creation, and super-admin initialization. It ensures the system is bootstrapped correctly before any domain operations can occur.

---

## 2. Technical Implementation

### 2.1 The Setup Process Manager (`Setup` Module)
- **Installer Service**: Coordinates cross-module initialization including database migrations, system seeding, and application key generation.
- **System Auditor**: Performs pre-flight environment checks (PHP version, mandatory extensions like BCmath, and directory write permissions).
- **Perimeter Defense (S1)**: All setup forms MUST implement **Cloudflare Turnstile** and **Laravel Honeypot** to prevent automated infrastructure probing.

### 2.2 Metadata & Onboarding
- **Onboarding Orchestration**: Mandatory sequential steps for School, SuperAdmin, Department, and initial Internship creation.
- **i18n Compliance**: All installer feedback MUST resolve via translation keys (`setup::messages`).

---

## 3. Verification & Validation - TDD Strategy (3S Aligned)

### 3.1 Secure (S1) - Boundary & Integrity Protection
- **Feature (`Feature/`)**:
    - **Perimeter Defense**: Verify that `Turnstile` and `Honeypot` tokens are required for step submission.
    - **Step Gating**: Verify that users cannot skip steps in the wizard (sequence enforcement).
- **Unit (`Unit/`)**:
    - **Pre-flight Audit**: Test `SystemAuditor` with simulated failures (e.g., missing PHP extensions) to ensure it returns `false` and prevents progression.

### 3.2 Sustain (S2) - Maintainability & Semantic Clarity
- **Architectural (`arch/`)**:
    - **Contract Compliance**: Ensure `InstallerService` implements `Initializable` contract.
    - **Service Standards**: Verify all Setup services extend `BaseService`.
- **Feature (`Feature/`)**:
    - **i18n Audit**: Verify that all validation errors and success messages return correct localized strings for `ID` and `EN`.

### 3.3 Scalable (S3) - Structural Modularity & Performance
- **Architectural (`arch/`)**:
    - **Module Isolation**: Verify `Setup` module DOES NOT depend on domain modules (Internship, Journal, etc.).
- **Unit (`Unit/`)**:
    - **Idempotency**: Test that calling `InstallerService::install()` multiple times does not corrupt existing settings or duplicate records.
- **Quantitative**:
    - **Coverage**: 100% code coverage for `SystemAuditor` logic.

---

## 4. Documentation Strategy
- **User Guide**: Update `docs/wiki/installation.md` to include a step-by-step walkthrough of the 8-step wizard.
- **Technical Reference**: Update `modules/Setup/README.md` to document the `InstallerService` logic and environmental requirements.
- **Registry**: Update `docs/wiki/modules.md` to reflect the functional role of the `Setup` module in the system lifecycle.
