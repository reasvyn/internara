# Blueprint: Initialization & Technical Baseline (BP-SYS-01)

**Blueprint ID**: `BP-SYS-01` | **Scope**: System Core | **Compliance**: `ISO/IEC 12207`

---

## 1. Context & Strategic Intent

This blueprint defines the foundational orchestration required to transition the system from a raw repository to a functional, secure baseline. It establishes the "Engine Room" protocols that govern environment health, metadata sovereignty, and runtime configuration.

---

## 2. System Lifecycle Orchestration (S3 - Scalable)

### 2.1 Technical Installation (`Setup` Module)
The `Setup` module acts as the authoritative **Process Manager** for first-boot orchestration.
- **Installer Service**: Extends `BaseService` to coordinate cross-module initialization (Migrations, Seeding, Key Generation).
- **System Auditor**: Performs pre-flight environment checks (ISO/IEC 25010 Reliability).
- **Perimeter Defense (S1)**: All setup forms MUST implement **Cloudflare Turnstile** and **Laravel Honeypot** to prevent automated infrastructure probing (OWASP A04).
- **Security Gate**: The `RequireSetupAccess` middleware enforces **Signed URLs** for wizard steps and locks routes via the `app_installed` flag upon completion.

### 2.2 Global State Control: The Phase Invariant
The system enforces a global `system_phase` setting (SSoT) to restrict domain operations.
- **Enforcement**: Validated at the **PEP (Policy Enforcement Point)** within Service Layer mutations.

---

## 3. Metadata & Configuration (S2 - Sustain)

### 3.1 `MetadataService` (SSoT)
- **Attribution Protection**: Enforces the `AUTHOR_IDENTITY` constant check.
- **i18n Compliance**: All system-level responses and installer feedback MUST resolve via translation keys (`__('setup::messages.key')`).

### 3.2 Configuration Hygiene (Twelve-Factor App)
- **Prohibition**: Direct `env()` calls are **Strictly Forbidden** in business logic.
- **Resolution**: Use the cached `setting()` helper for runtime-dynamic values and `config()` for environment-static parameters.

---

## 4. Technical Construction Standards

- **PHP 8.4 Excellence**: 
    - Mandatory `declare(strict_types=1);` in all setup artifacts.
    - Use **Property Hooks** for computed environment metadata.
- **src-Omission**: Namespaces MUST omit the `src/` segment while the filesystem retains it.
- **Observability (S1)**: All initialization errors logged via the `Log` module MUST pass through the `PiiMaskingProcessor` to prevent credential leakage.

---

## 5. Verification & Validation (V&V) - TDD Strategy

Internara mandates a **RED-GREEN-REFACTOR** cycle for all initialization logic.

### 5.1 Architecture Testing (`tests/Arch/SetupTest.php`)
- **Isolation**: Verify `Setup` module DOES NOT depend on any domain modules (Internship, Journal, etc.).
- **Standards**: Enforce `BaseService` usage for `InstallerService` and `final` keyword for support utilities.

### 5.2 Functional Testing (`tests/Feature/Setup/`)
- **Pre-flight Audit**: Test `SystemAuditor` with simulated failures (e.g., missing PHP extensions, read-only directories) to ensure the system fails safely.
- **Installer Idempotency**: Verify that running the `install` command multiple times does not duplicate data or corrupt settings.
- **Security Gate (S1)**: 
    - Verify that setup routes return `403 Forbidden` once the `app_installed` flag is true.
    - Test signature tampering on setup URLs to ensure they are rejected.
- **Perimeter Defense**: Use **Dusk** or Feature tests to verify that `Turnstile` and `Honeypot` tokens are required for setup submission.

### 5.3 Quantitative & Performance
- **Complexity**: Ensure `InstallerService` maintains a Cyclomatic Complexity < 10.
- **Coverage**: 100% code coverage for the `SystemAuditor` logic to prevent installation deadlocks.

_This blueprint records the current technical baseline. Evolution of core protocols must be reflected here to maintain systemic integrity._
