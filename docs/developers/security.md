# Security and Privacy Protocols: System Hardening Standards

This document codifies the **Security and Privacy Protocols** for the Internara project,
standardized according to **ISO/IEC 27001** (Information Security Management) and **ISO/IEC 27034**
(Application Security). It defines the multi-layered defense strategy and privacy-enhancing
technologies (PET) used to protect system integrity and user data.

> **Governance Mandate:** All security implementations must satisfy the requirements for Access
> Control **[SYRS-NF-502]**, Data Privacy **[SYRS-NF-503]**, and Data Integrity **[SYRS-NF-603]**
> defined in the authoritative **[System Requirements Specification](system-requirements-specification.md)**.

---

## 1. Perimeter Defense: Bot & Spam Protection

Internara utilizes automated challenge and entrapment mechanisms to prevent non-human infiltration
at sensitive system boundaries.

### 1.1 Cloudflare Turnstile Integration
- **Objective**: Prevent automated registration, login attempts, and brute-force attacks.
- **Implementation**: Non-intrusive CAPTCHA alternative rendered via the `UI::turnstile` component.
- **Verification**: Backend validation via the `Modules\Shared\Rules\Turnstile` rule.
- **Usage**: Mandatory on all public-facing forms (Auth, Setup).

### 1.2 Spatie Laravel Honeypot
- **Objective**: Silently trap and reject automated form submissions from bots that bypass
  client-side scripts.
- **Implementation**: Global inclusion of the `<x-honeypot />` component in foundational layouts
  (`Auth`, `Setup`).
- **Policy**: Any request triggering the honeypot is immediately discarded without technical feedback.

---

## 2. Traffic & Access Governance

Proactive management of request volume and link integrity to protect system resources.

### 2.1 Multi-Tiered Rate Limiting
- **Auth Limiter**: Restricted to 5 attempts per minute per IP to prevent credential stuffing.
- **Setup Limiter**: Restricted to 10 attempts per minute per IP to protect infrastructure routes.
- **Global Limiter**: Standard Laravel application limits applied via the `Shared` module.

### 2.2 Cryptographic Link Integrity (Signed URLs)
- **Objective**: Ensure that high-privilege administrative links cannot be guessed, manipulated, or
  shared beyond their intended scope.
- **Implementation**: Use of `URL::temporarySignedRoute()` for the **Setup Wizard**.
- **Enforcement**: Mandatory signature verification via the `ProtectSetupRoute` middleware.

---

## 3. Data Privacy: Protection at Rest & In-Transit

Systemic isolation and encryption of Personally Identifiable Information (PII).

### 3.1 Database Encryption
- **Policy**: All fields identified as PII must be encrypted at the database level.
- **Target Fields**: `phone`, `address`, `nisn`, `nip`, `nik`, and `bio`.
- **Implementation**: Utilization of Eloquent's `encrypted` cast in the `Profile`, `Student`, and
  `Teacher` models.
- **Rationale**: Ensures that even in the event of a raw database breach, sensitive user data
  remains unreadable ciphertext.

### 3.2 Information Masking (Data Redaction)
- **Objective**: Prevent accidental exposure of PII in logs, console outputs, or lower-privileged
  UI views.
- **Logic**: Use of the `Modules\Shared\Support\Masker` utility.
- **Logging**: Integration of `PiiMaskingProcessor` in the `Log` module to automatically redact
  sensitive keys from Monolog records.

---

## 4. Identity Integrity: Adaptive Password Policy

Standardized enforcement of credential complexity tailored to the operational environment.

### 4.1 The `Password` Rule Class
A centralized validation rule (`Modules\Shared\Rules\Password`) providing environment-aware
complexity tiers:

- **`low()`**: 8 characters minimum (Local/Testing).
- **`medium()`**: 8 characters + Alpha-numeric.
- **`high()`**: 12 characters + Mixed case + Numbers + Symbols (Production standard).
- **`auto()`**: Automatically escalates to `high()` in production and defaults to `low()` in
  development.

---

## 5. Implementation & Compliance Protocols

### 5.1 New Feature Checklist
Every new feature involving user input or sensitive data must demonstrate compliance with:
1. **Form Protection**: Turnstile and Honeypot presence.
2. **PII Classification**: Encryption casts applied to new models.
3. **Link Security**: Signed routes for administrative actions.
4. **Validation**: Use of `Password::auto()` for all credential inputs.

### 5.2 Verification Gate
The **Security Audit** phase of the Implementation Process verifies that these protocols are active
and correctly configured prior to any baseline promotion.

---

_By strictly adhering to these protocols, Internara ensures a high-fidelity security posture that
respects user privacy and system resilience._
