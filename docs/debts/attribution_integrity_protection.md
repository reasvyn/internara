# Technical Debt: Attribution & Integrity Protection

This document formalizes the **Attribution & Integrity Protection** strategy implemented in the
Internara project. While not "debt" in the traditional sense of neglected code quality, this
represents an intentional **Architectural Constraint** and **Governance Debt** that must be
maintained to ensure the original author's rights and system integrity.

> **Governance Mandate:** The protection layers described herein are non-negotiable. Any attempt to
> bypass these checks without explicit authorization from the original author is considered a
> violation of the project's engineering ethics and license.

---

## 1. Context & Strategic Intent

**Objective:** To prevent unauthorized modifications of core metadata and ensure that the project's
original author (**Reas Vyn**) is properly attributed in every installation of the Internara
ecosystem.

**Rationale:** Protecting the intellectual effort invested in the modular monolith architecture and
ensuring that the system's "Source of Truth" (`app_info.json`) remains un-tampered with.

---

## 2. Multi-Layered Protection Strategy

The system implements a "Defense in Depth" approach, verifying integrity at different stages of the
application lifecycle.

### 2.1 Layer 1: The Root (Bootstrap Verification)

- **Location**: `bootstrap/app.php`
- **Mechanism**: A self-invoking anonymous function executed at the very beginning of the boot
  process.
- **Behavior**: Verifies the existence of `app_info.json` and the `author.name` value.
- **Impact**: **Immediate Halt**. If validation fails, the application dies with an `HTTP 403`
  status before any Laravel components are loaded. This is the most difficult layer to bypass as it
  is the entry point for both Web and CLI.

### 2.2 Layer 2: The Logic (Service Verification)

- **Location**: `Modules\Setting\Services\SettingService`
- **Mechanism**: Integrity check within the constructor.
- **Behavior**: Re-validates the metadata integrity during the initialization of the Setting
  service.
- **Impact**: **Application Exception**. Throws a `Modules\Exception\AppException`, ensuring that
  even if Layer 1 is bypassed, the core configuration engine will block operation.

### 2.3 Layer 3: The Helper (Utility Fallback)

- **Location**: `Modules\Core\Functions\setting.php`
- **Mechanism**: Static variable-based check within the global `setting()` helper.
- **Behavior**: Ensures that even if the `Setting` module is disabled, the fallback mechanism still
  enforces author attribution before resolving any values.
- **Impact**: **Runtime Exception**. Blocks access to any configuration values if metadata is
  compromised.

---

## 3. Maintenance & Evolution (The "Debt")

This strategy introduces a maintenance overhead that future developers must respect:

1.  **Metadata SSoT**: The file `app_info.json` is the **Single Source of Truth**. Any update to the
    application's metadata (version, Blueprint ID, etc.) must be done within this file.2. **Constant
    Integrity**: The constant `AUTHOR_IDENTITY` within the code is the authoritative benchmark for
    verification.
2.  **Future Hardening**:
    - **Digital Signing**: Implementing cryptographic signatures for `app_info.json`.
    - **Code Obfuscation**: Utilizing tools to hide the location of these checks to prevent simple
      "comment-out" bypasses.
    - **Spread Verification**: Distributing subtle integrity checks into other foundational traits
      (e.g., `HasUuid`).

---

## 4. Remediation of Violations

If an "Integrity Violation" or "Attribution Error" is triggered:

1.  Verify that `app_info.json` exists in the project root.
2.  Ensure that `author.name` in `app_info.json` is set to `Reas Vyn`.
3.  Check for accidental deletions of the protection code in the mentioned files.

---

_This engineering record ensures that the attribution protection remains a core part of the
Internara architecture, preserving the relationship between the system and its creator._
