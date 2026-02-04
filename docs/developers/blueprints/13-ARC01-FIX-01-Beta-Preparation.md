# Architectural Blueprint: Beta Preparation & Stabilization

**Document ID:** 13-ARC01-FIX-01 | **Status:** In Progress | **Author:** Gemini

---

## 1. Strategic Context

- **Series Identification**: ARC01-FIX-01 (Beta Preparation & Stabilization)
- **Spec Alignment**: This blueprint authorizes technical corrections and systemic security hardening required to satisfy **[SYRS-NF-601]** (Isolation), **[SYRS-NF-502]** (Access Control), **[SYRS-NF-503]** (Data Privacy), and **[SYRS-NF-603]** (Data Integrity).

---

## 2. Functional Specification

### 2.1 Capability Set (Technical Corrections)
- Identity Visibility: Integration of the `Brand` component into the `Setup` module navbar.
- Namespaced Layouts: Systematic transition to `auth::components.layouts.auth` for identity flows.
- Robust Installation: Port-aware URL generation in `app:install` and sanitization of audit outputs to prevent runtime crashes.
- Schema Optimization: Consolidation of database migrations to maintain an atomic, table-centric history.
- Metadata Standardization: Universal adoption of the `view()->layout(..., ['title' => ...])` pattern in Livewire components.
- **Memory-Efficient Testing**: Implementation of the `app:test` command to orchestrate modular sequential test execution, preventing memory exhaustion in constrained environments.

### 2.2 Security Hardening Set
- **Bot Defense**: Integration of **Cloudflare Turnstile** on all sensitive entry points (Login, Register, Setup).
- **Spam Mitigation**: Implementation of **Spatie/Laravel-Honeypot** to silently trap automated form submissions.
- **Access Integrity**: Mandatory use of **Signed URLs** for the Setup Wizard and high-privilege administrative actions.
- **Traffic Governance**: Implementation of granular **Rate Limiting** for authentication, media uploads, and setup routes.
- **Privacy at Rest**: Application of **Database Encryption** for PII fields (Phone, Address, NISN, NIP, Bio) across the `User`, `Student`, and `Teacher` domains.
- **Information Protection**: Implementation of **Data Masking** for sensitive identifiers in logs and administrative UI views (role-dependent).
- **Adaptive Password Policy**: Introduction of a project-wide `Password` rule class with environment-aware complexity logic.

---

## 3. Architectural Impact

### 3.1 Module Decomposition
- Modified: `Setup`, `Auth`, `UI`, `Shared`, `User`, `Student`, `Teacher`, `Log`.
- Infrastructure: New security middleware, validation rules, and encryption casts in the `Shared` and `Core` modules.

### 3.2 Data Architecture
- **Encryption Casts**: Models containing PII must utilize Eloquent encryption casts for fields identified in the audit.
- **Migration Policy**: Ensure migration fields for encrypted data have sufficient length to handle ciphertext.

### 3.3 Security Contracts
- **Modules\Shared\Rules\Password**: A centralized rule enforcing complexity tiers:
    - `low()`: min 8 characters (Development/Testing).
    - `medium()`: min 8 chars + alphanumeric.
    - `high()`: min 12 chars + mixed case + symbols (Production).
    - `auto()`: Dynamic resolution based on `app.env`.

---

## 4. Presentation Strategy

### 4.1 UI Security Components
- Integration of the Turnstile widget within the `UI` module's shared form components.
- Global registration of the Honeypot component in the `Auth` and `Setup` layout stacks.

### 4.2 Masking Standards
- Implementation of helper methods in the `Support` module for masking sensitive strings (e.g., `ma**@email.com`, `32**********01`).

### 4.3 Documentation Strategy (Knowledge View)
- **Structural Transformation**: Consolidation of redundant technical documents into authoritative guides (`architecture.md`, `conventions.md`, `patterns.md`).
- **Standardization**: Transition of all index files to `README.md` for better platform compatibility.
- **Relocation**: Migration of release-specific documentation to `docs/pubs/releases/` to distinguish public records from internal engineering standards.
- **Protocol Definition**: Formalization of Release Notes authoring standards in `releases.md`.

---

## 5. Exit Criteria & Quality Gates

### 5.1 Acceptance Criteria
- [ ] **Functional**: `app:install` generates an expired, **Signed URL** with the correct port.
- [ ] **Security**: Identity forms are protected by Turnstile and Honeypot; rate limiting is active on `/login`.
- [ ] **Privacy**: Targeted PII fields are encrypted in the database; logs contain masked sensitive data.
- [ ] **Validation**: Passwords meet the `auto()` complexity requirements for the current environment.
- [ ] **Stability**: Zero "Array to string conversion" errors; migrations are clean and consolidated.

### 5.2 Verification Protocols
- **Full Verification Suite**: `composer test` (100% Pass).
- **Static Analysis**: `composer lint` (Zero violations).
- **Security Audit**: Manual verification of signed URL expiration and encryption transparency.

---

## 6. Forward Outlook: Improvement Suggestions

- **Identity**: Consider WebAuthn/Passkey support for the Beta 2.0 milestone.
- **Logs**: Transition to encrypted log storage for high-compliance environments.

---

_This Application Blueprint establishes the authoritative security and stabilization design, serving as the work contract for the v0.13.0-alpha baseline._