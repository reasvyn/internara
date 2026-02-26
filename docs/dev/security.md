# Security and Privacy Protocols: System Hardening Standards

This document codifies the **Security and Privacy Protocols** for the Internara project,
standardized according to **ISO/IEC 27001** (Information Security Management) and **ISO/IEC 27034**
(Application Security). It defines the multi-layered defense strategy and privacy-enhancing
technologies (PET) used to protect system integrity and user data. Furthermore, this document explicitly aligns with the **OWASP Top 10** to mitigate critical web application security risks.

> **Governance Mandate:** All security implementations must satisfy the requirements for Access
> Control **[SYRS-NF-502]**, Data Privacy **[SYRS-NF-503]**, and Data Integrity **[SYRS-NF-603]**
> defined in the authoritative **[System Requirements Specification](specs.md)**.

---

## 1. Perimeter Defense & Automated Threat Protection (OWASP A04, A07)

Internara utilizes automated challenge and entrapment mechanisms to prevent non-human infiltration, brute-force attacks, and credential stuffing at sensitive system boundaries.

### 1.1 Cloudflare Turnstile Integration

- **Objective**: Prevent automated registration, login attempts, and brute-force attacks (OWASP A07: Identification and Authentication Failures).
- **Implementation**: Non-intrusive CAPTCHA alternative rendered via the `UI::turnstile` component.
- **Verification**: Backend validation via the `Modules\Shared\Rules\Turnstile` rule.
- **Usage**: Mandatory on all public-facing forms (Auth, Setup, Password Reset).

### 1.2 Spatie Laravel Honeypot

- **Objective**: Silently trap and reject automated form submissions from bots that bypass
  client-side scripts.
- **Implementation**: Global inclusion of the `<x-honeypot />` component in foundational layouts
  (`Auth`, `Setup`).
- **Policy**: Any request triggering the honeypot is immediately discarded without technical
  feedback, preventing reconnaissance.

---

## 2. Traffic & Access Governance (OWASP A01, A05)

Proactive management of request volume, link integrity, and strict access controls to protect system resources and prevent unauthorized escalation.

### 2.1 Multi-Tiered Rate Limiting

- **Auth Limiter**: Restricted to 5 attempts per minute per IP to prevent credential stuffing.
- **Setup Limiter**: Restricted to 10 attempts per minute per IP to protect infrastructure routes.
- **Global Limiter**: Standard Laravel application limits applied via the `Shared` module to prevent Denial of Service (DoS).

### 2.2 Cryptographic Link Integrity (Signed URLs) & Zero-Trust

- **Objective**: Ensure that high-privilege administrative links cannot be guessed, manipulated, or
  shared beyond their intended scope (OWASP A01: Broken Access Control).
- **Implementation**: Use of `URL::temporarySignedRoute()` for the **Setup Wizard** and sensitive file downloads (e.g., Certificates, Reports).
- **Enforcement**: Mandatory signature verification via the `ProtectSetupRoute` middleware.
- **Zero-Trust Validation**: Every request is treated as untrusted. Session state and digital signatures are verified on every invocation, regardless of the network origin.

---

## 3. Data Privacy: Protection at Rest & In-Transit (OWASP A02)

Systemic isolation and encryption of Personally Identifiable Information (PII) to prevent cryptographic failures and data exposure.

### 3.1 Distributed Database Encryption

- **Policy**: All fields identified as PII must be encrypted at the database level (OWASP A02: Cryptographic Failures).
- **Implementation**: Utilization of Eloquent's `encrypted` cast in the relevant domain models. AES-256-CBC encryption is utilized via the Laravel application key.
- **PII Distribution**:
    - **`Profile`**: `phone`, `address`, `bio`, `national_identifier` (NISN), and
      `registration_number` (NIP/NIS).
- **Rationale**: Ensures that even in the event of a raw database breach, sensitive user data
  remains unreadable ciphertext.

### 3.2 Information Masking (Data Redaction)

- **Objective**: Prevent accidental exposure of PII in logs, console outputs, or lower-privileged UI
  views (OWASP A09: Security Logging and Monitoring Failures).
- **Logic**: Use of the `Modules\Shared\Support\Masker` utility to obscure data (e.g., `j***@example.com`).
- **Logging**: Integration of `PiiMaskingProcessor` in the `Log` module to automatically redact
  sensitive keys from Monolog records before they are written to disk.

---

## 4. Identity Integrity: Adaptive Password Policy (OWASP A07)

Standardized enforcement of credential complexity tailored to the operational environment to prevent weak password exploitation.

### 4.1 The `Password` Rule Class

A centralized validation rule (`Modules\Shared\Rules\Password`) providing environment-aware
complexity tiers:

- **`low()`**: 8 characters minimum (Local/Testing).
- **`medium()`**: 8 characters + Alpha-numeric.
- **`high()`**: 12 characters + Mixed case + Numbers + Symbols (Production standard).
- **`auto()`**: Automatically escalates to `high()` in production and defaults to `low()` in
  development.
- **Compromised Password Verification**: In production, the system SHOULD verify passwords against known data breaches (e.g., via `uncompromised()` rule).

---

## 5. Implementation & Compliance Protocols

### 5.1 New Feature Security Checklist (The Zero-Trust Standard)

Every new feature involving user input, file uploads, or sensitive data must demonstrate compliance with the following Zero-Trust principles:

1. **Form Protection**: Turnstile and Honeypot presence (A04).
2. **PII Classification**: Encryption casts applied to new models containing SPI/PII (A02).
3. **Link Security**: Signed routes for administrative actions or file access (A01).
4. **Validation (OWASP A03)**: 
    - Use of `Password::auto()` for all credential inputs.
    - **Strict Validation**: All external input (HTTP requests, CLI arguments, uploaded files) must be strictly validated before processing. Blacklisting is insufficient; use explicit whitelisting via Laravel Form Requests or Livewire `rules()`.
5. **Sanitization (OWASP A03)**: Output rendered to Blade/Livewire MUST be escaped to prevent Cross-Site Scripting (XSS). Avoid `{!! !!}` unless strictly necessary and accompanied by an HTML Purifier.
6. **Logic Isolation (OWASP A05)**: Strict prohibition of `env()` calls in business logic; rely entirely on `config()` and `setting()` to prevent environment configuration injection or leakage.
7. **Authorization PEP**: Every controller, Livewire component, or Service layer mutation MUST enforce a Laravel Policy (`Gate::authorize()`) to prevent Insecure Direct Object References (IDOR/A01).

### 5.2 Verification Gate

The **Security Audit** phase of the Implementation Process verifies that these protocols are active
and correctly configured prior to any baseline promotion. Static Application Security Testing (SAST) and automated architectural tests (`tests/Arch/`) are utilized to enforce these invariants continuously.

---

_By strictly adhering to these protocols, Internara ensures a high-fidelity security posture that
respects user privacy and system resilience._
