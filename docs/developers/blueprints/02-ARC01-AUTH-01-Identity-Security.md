# Application Blueprint: Identity & Security (ARC01-AUTH-01)

**Series Code**: `ARC01-AUTH` | **Scope**: `Security & Access Control` | **Compliance**: `ISO/IEC 27001`

---

## 1. Strategic Context

- **Spec Alignment**: This blueprint authorizes the construction of the secure entry and authorization subsystem required to satisfy **[SYRS-NF-501]** (Authentication) and **[SYRS-NF-502]** (RBAC).
- **Objective**: Establish a centralized, hardened, and modular identity provider that governs access across all Internara domains.
- **Rationale**: Security is a non-negotiable baseline. By decoupling authentication from domain logic, we ensure that identity verification remains consistent and resilient to architectural changes.

---

## 2. Logic & Architecture (The Identity Engine)

### 2.1 Multi-Identifier Authentication
- **Flexibility**: Implementation of an intelligent login system in `AuthService` that accepts both **Email** and **Username** as identifiers.
- **Escalation Protection**: Strict sanitization of role inputs during registration to prevent unauthorized privilege escalation via user-supplied data.

### 2.2 Role-Based Orchestration
- **Redirect Sovereignty**: Implementation of `RedirectService` to handle post-authentication routing based on the institutional role context (Student, Teacher, Mentor, Admin).
- **Verification Loop**: Integration of mandatory email verification gates [SYRS-NF-505] within the redirection flow to satisfy institutional trust requirements.

### 2.3 Service Contract Specifications
- **`Modules\Auth\Services\Contracts\AuthService`**: Authoritative manager for session lifecycle, credential verification, and security telemetry.
- **`Modules\Permission\Services\Contracts\PermissionManager`**: Specialized engine for the technical orchestration of Roles and Permissions.

---

## 3. Data Architecture (Security Invariants)

### 3.1 Hardened Identity
- **UUID v4 Persistence**: Mandatory usage of **UUID v4** for `User`, `Role`, and `Permission` models to prevent sequential ID leakage and enumeration attacks.
- **Argon2id Hashing**: Standardization of the **Argon2id** algorithm for password persistence, ensuring resistance against GPU-based brute force attacks.

### 3.2 Authorization Governance
- **RBAC Engine**: Powered by `spatie/laravel-permission`, customized with UUID support and module-specific scoping.
- **Explicit PEP (Policy Enforcement Point)**: Every domain action must be guarded by a **Laravel Policy** that verifies both functional capability (`can`) and contextual ownership.

---

## 4. Presentation & UX (Secure Entry)

### 4.1 Thin UI Pattern
- **Component Delegation**: The `LoginForm` (Livewire) acts as a minimalist orchestrator, delegating 100% of authentication logic to the `AuthService`.
- **Feedback Sanitization**: Generic error messages for failed login attempts to prevent account enumeration, while maintaining internal forensic logs.

### 4.2 Bot Protection
- **Perimeter Defense**: Integration of **Cloudflare Turnstile** on all entry points (Login, Register, Forgot Password) to mitigate automated brute-force attempts.

---

## 5. Verification Strategy (V&V View)

### 5.1 Security Unit Verification
- **Credential Integrity**: Tests verifying the correct matching of hashed credentials and token lifecycle.
- **Redirection Logic**: Exhaustive testing of the `RedirectService` matrix across all institutional roles.

### 5.2 Forensic Audit
- **Masking Verification**: Ensuring that sensitive identifiers are correctly redacted in audit logs via the `PiiMaskingProcessor`.

---

## 6. Exit Criteria & Quality Gates

- **Acceptance**: Secure multi-identifier login active; RBAC engine operational; PII masking verified in logs.
- **Quality Gate**: 100% pass rate in security-related test suites and zero critical vulnerabilities identified in authentication logic.

---

_This blueprint prevents architectural decay and ensures continuous alignment with the foundational security specifications._
