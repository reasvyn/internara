# Application Blueprint: Identity & Security (ARC01-AUTH-01)

**Series Code**: `ARC01-AUTH` | **Scope**: `Phase 1: The WHO` | **Compliance**: `ISO/IEC 27001`

---

## 1. Context & Strategic Intent

This blueprint authorizes the construction of the **Identity Provider (IdP)** and **Access Control 
Subsystem**. It establishes the "Identity Anchor" — the most critical dependency for all 
subsequent domain modules.

- **SyRS Traceability**:
    - **[SYRS-NF-501]**: Secure Authentication (Multi-Identifier: Email/Username).
    - **[SYRS-NF-502]**: Granular Role-Based Access Control (RBAC).
    - **[SYRS-NF-504]**: UUID-Based Identity (Anonymization Baseline).
    - **[SYRS-NF-505]**: Trust Loop (Mandatory Email Verification).

---

## 2. Logic & Architecture (The Identity Engine)

### 2.1 The Dual-Entity Identity Pattern
To satisfy data portability and separation of concerns, identity is split into two models:
1.  **`User` (The Account)**: Minimalist data for authentication (UUID, credentials, timestamp).
2.  **`Profile` (The Persona)**: PII and institutional metadata (`full_name`, `national_id`).
- **Invariant**: No module other than `Profile` may store student names or phone numbers. All modules must link to the `User` UUID.

### 2.2 Account Lifecycle States
Identity state is governed by the `Status` module with following transitions:
- `pending_verification`: Initial state after registration.
- `active`: Fully verified and authorized.
- `suspended`: Temporarily blocked due to security or administrative reasons.
- `deactivated`: Graceful exit from the system.

### 2.3 Contract Specifications
- **`AuthService`**: Orchestrates sessions and credential validation.
    - `register(UserRegistrationDTO $data): User`
    - `validateCredentials(string $identifier, string $password): bool`
- **`PermissionManager`**: Specialized engine for UUID-aware RBAC.
    - `initializeDefaultRoles(): void` (Student, Teacher, Mentor, Admin, SuperAdmin).

---

## 3. Data Architecture (Security Invariants)

### 3.1 Persistence Hardening
- **Primary Keys**: Strictly **UUID v4**.
- **Password Hashing**: **Argon2id** with a cost factor aligned with high-security environments.
- **Strict Isolation**: The `Auth` database schema must not contain business-level columns.

### 3.2 SLRI (Software-Level Referential Integrity)
- Modul `Auth` adalah pemilik tabel `users`.
- Modul `Profile` adalah pemilik tabel `profiles`.
- Integritas dipastikan di **Service Layer** `ProfileService` yang mendengarkan event `UserCreated`.

---

## 4. Presentation & UX (Secure Entry)

### 4.1 UI Reactivity (Thin Component)
- **`LoginForm`**: Delegates credential verification to `AuthService`.
- **`RegisterForm`**: Sanitize role inputs to prevent self-elevation to 'Admin'.
- **Turnstile Integration**: Mandatory Cloudflare Turnstile on all public entry points.

### 4.2 Post-Auth Redirection Matrix
The `RedirectService` determines the landing zone based on the dominant role:
- `SUPER_ADMIN` -> `/admin/dashboard` (System Health)
- `ADMIN` -> `/school/dashboard` (Institutional Management)
- `TEACHER` -> `/supervision/dashboard` (Daily Guidance)
- `STUDENT` -> `/journal` (Active Program)

---

## 5. Verification Plan (V&V View)

### 5.1 Security TDD (RED Phase Targets)
- **Test 1**: Verify that registering with an 'admin' role in the request body fails or resets to 'student'.
- **Test 2**: Verify that PII data in `Profile` is encrypted if configured.
- **Test 3**: Verify that a `suspended` user cannot create a session.

### 5.2 Architecture Police (Arch Pass)
- Ensure `AuthService` extends `BaseService`.
- Ensure no module (except `Auth` and `Profile`) accesses `users` or `profiles` tables directly.

---

_This blueprint constitutes the authoritative engineering record for the Identity & Security phase. 
Any deviation is considered an architectural defect._
