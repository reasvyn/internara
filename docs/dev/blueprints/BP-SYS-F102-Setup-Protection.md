# Blueprint: Setup Protection (BP-SYS-F102)

**Blueprint ID**: `BP-SYS-F102` | **Requirement ID**: `SYRS-F-102` | **Scope**: System Core

---

## 1. Context & Strategic Intent

This blueprint defines the "Single-Install" invariant, locking setup routes once the `app_installed` state is achieved. It prevents unauthorized re-initialization of the system which could lead to data loss or security breaches.

---

## 2. Technical Implementation

### 2.1 The Security Gate (S1 - Secure)
- **Middleware Guard**: The `RequireSetupAccess` middleware MUST check the `app_installed` setting.
- **Route Locking**: If `app_installed` is true, all setup routes MUST return a `403 Forbidden`.
- **Signed URLs**: All multi-step setup transitions MUST use Laravel's Signed URLs to prevent URL tampering.

---

## 3. Verification & Validation - TDD Strategy (3S Aligned)

### 3.1 Secure (S1) - Boundary & Integrity Protection
- **Feature (`Feature/`)**:
    - **Setup Locking**: Verify that accessing any setup route returns `403 Forbidden` once `app_installed` is set to true.
    - **Signature Audit**: Test that modifying any parameter in a setup Signed URL results in a `403` or `401` error.
- **Architectural (`arch/`)**:
    - **Middleware Sovereignty**: Ensure the `Setup` module defines and applies its own security middleware without leaking to global routes prematurely.

### 3.2 Sustain (S2) - Maintainability & Semantic Clarity
- **Unit (`Unit/`)**:
    - **Setting Resolution**: Verify that the `RequireSetupAccess` middleware correctly resolves the `app_installed` key from the `SettingService`.

### 3.3 Scalable (S3) - Structural Modularity & Performance
- **Architectural (`arch/`)**:
    - **Global Isolation**: Verify that domain routes (e.g., `/dashboard`) are inaccessible until `app_installed` is true.

---

## 4. Documentation Strategy
- **Security Policy**: Update `docs/dev/policy.md` to document the setup-locking invariant as a core system security protocol.
- **Troubleshooting**: Update `docs/wiki/installation.md` with instructions on how to manually reset the installation state via CLI.
