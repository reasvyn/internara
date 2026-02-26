# Blueprint: Dual-Entity Identity & RBAC (BP-ID-01)

**Blueprint ID**: `BP-ID-01` | **Scope**: Identity & Security | **Compliance**: `ISO/IEC 27034`

---

## 1. Context & Strategic Intent

This blueprint defines the architecture for stakeholder identification and authorization. It establishes a secure perimeter where authentication is decoupled from personal data, and permissions are enforced via a granular, context-aware engine.

---

## 2. Identity Architecture: The Dual-Entity Pattern (S1 - Secure)

To maintain data portability and privacy (PII isolation), identity is split into two distinct models with a strict **one-to-one** relationship.

### 2.1 `User` (The Account)
- **Responsibility**: Authentication and systemic identity.
- **Attributes**: UUID (PK), email/username, password (**Argon2id**), active session metadata.
- **Isolation**: Contains zero business-level metadata.

### 2.2 `Profile` (The Persona)
- **Responsibility**: PII storage and academic anchoring.
- **PII Protection**: Fields such as `phone`, `address`, `national_identifier`, and `registration_number` MUST be encrypted at rest using Eloquent's **`encrypted` cast** (OWASP A02).
- **Masking**: Display of PII in common views or logs MUST be masked via the `Masker` utility.

---

## 3. Identification & Integrity Invariants (S3 - Scalable)

### 3.1 Universal UUID v4
- **Rule**: Every entity MUST utilize **UUID v4** (via `Shared\Models\Concerns\HasUuid`). 
- **Prohibition**: Auto-increment integers MUST NOT be exposed in APIs or URIs.

### 3.2 SLRI Persistence (The Link Protocol)
- **No Physical FKs**: Cross-module relationships are stored as indexed UUID columns.
- **Integrity Verification**: Service Layer methods (e.g., `createWithProfile`) MUST verify the existence of foreign entities via their respective **Service Contracts** before persistence.

---

## 4. Authorization Engine (RBAC & PEP)

### 4.1 Granular Permissions
- **Convention**: Permissions follow the `{module}.{action}` pattern (e.g., `user.create`).
- **PEP Enforcement**: Every state-changing method in `UserService` MUST explicitly invoke **`Gate::authorize()`**.

### 4.2 Policy Patterns (ISO/IEC 29146)
- **Explicit Deny**: Policies must return `false` by default.
- **IDOR Mitigation**: Policies MUST verify both functional capability AND resource ownership (e.g., `$user->id === $profile->user_id`).

---

## 5. Construction & Observability (S2 - Sustain)

- **PHP 8.4 Excellence**: Use **Property Hooks** for virtual attributes (e.g., `full_name`).
- **Audit Trail**: All identity state changes (status toggles, role assignments) MUST be recorded in the `ActivityLog` via the `HandlesStatus` or `InteractsWithActivityLog` concerns.
- **i18n**: All authorization failure messages MUST be localized (`__('auth::exceptions.unauthorized')`).

---

## 6. Verification & Validation (V&V) - TDD Strategy

Every identity change must be preceded by a verification suite.

### 6.1 Security Testing (S1 - Secure)
- **PII Encryption Audit**: Test that `Profile` model fields (phone, address) are stored as ciphertext in the database and automatically decrypted during hydration.
- **IDOR Mitigation**: 
    - **Negative Test**: Attempt to update a `Profile` using a user ID that does not own the record (Must return `403`).
    - **Positive Test**: SuperAdmin can update any profile regardless of ownership.
- **Service Authorization**: Verify that `UserService::createWithProfile()` fails if `Gate::authorize` is mocked to return false.

### 6.2 Architectural Testing (`tests/Arch/IdentityTest.php`)
- **UUID Invariant**: Ensure ALL models in `User` and `Profile` modules use the `HasUuid` trait.
- **Isolation**: Ensure no module other than `User` or `Profile` contains a `User` or `Profile` Eloquent Model (Modular Sovereignty).

### 6.3 Functional & i18n (`tests/Feature/User/`)
- **One-to-One Lifecycle**: Verify that creating a `User` correctly triggers the atomic creation of its corresponding `Profile`.
- **i18n**: Verify that authorization error messages are returned in the user's active locale.
- **Audit Trail**: Assert that `ActivityLog` contains a record for every role change or status toggle.

### 6.4 Quantitative
- **Coverage**: Minimum 95% behavioral coverage for authentication and profile management logic.

_This blueprint reflects the current state of identity management. All identity-related evolutions must be recorded here to preserve the system's security posture._
