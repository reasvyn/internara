# Application Blueprint: Identity & Security (ARC01-USER-01)

**Series Code**: `ARC01-USER-01` **Status**: `Archived` (Done)

---

## 1. Design Objectives & Scope

**Strategic Purpose**: Establish the foundational identity baseline, implementing secure
authentication and the initial RBAC (Role-Based Access Control) framework.

**Objectives**:

- Implement secure subject identification via email and cryptographic passwords.
- Establish the formal User Role taxonomy (Instructor, Student, etc.) mandated by the SyRS.
- Provide self-service profile management for system subjects.

---

## 2. Functional Specification

### 2.1 Capability Set

- **Authentication Orchestrator**: Secure login/logout flows with session integrity.
- **RBAC Baseline**: Implementation of roles and granular permissions according to the stakeholder
  requirements.
- **Profile Subsystem**: Logic for subjects to update personal metadata and security credentials.
- **Environment Redirection**: Intelligent routing of users to their authorized dashboards post-login.

### 2.2 Stakeholder Personas

- **Universal Subject**: Any identified system user requiring secure access to role-specific
  capabilities.

---

## 3. Architectural Impact (Logical View)

### 3.1 Modular Decomposition

- **Auth Module**: New domain for identity orchestration and session management.
- **User Module**: Domain for managing user credentials and account status.
- **Profile Module**: Dedicated domain for managing personal data and role-based affiliations.
- **Permission Module**: Core domain for RBAC state and synchronization.

### 3.2 Security Architecture

- **Encryption Invariant**: Passwords must be hashed using the **BCrypt** algorithm (Standard MVP Baseline).
- **Access Control**: Mandatory authorization check at every system boundary via Policies and Gates.

---

## 4. Documentation Strategy (Knowledge View)

- **Security Standards**: Formalization of the **[Access Control Standards](../access-control.md)**.
- **Identity Record**: Authoring of the `README.md` files for the `Auth`, `User`, and `Permission` modules.
- **Implementation Guide**: Documentation of the post-login redirection logic and session protection.

---

## 5. Audit & Evaluation Report (v0.13.0 Audit)

**Date**: 2026-02-04 | **Auditor**: Gemini

### 5.1 Realized Outcomes
- **RBAC Foundation**: Successfully implemented 5 core roles via `RoleSeeder`.
- **Credential Security**: BCrypt adopted as the lightweight MVP hashing standard.
- **Domain Isolation**: Verified separation of `User` (Account) and `Profile` (Biodata) modules.
- **Post-Login Routing**: `RedirectService` implemented to manage role-based environment transitions.

### 5.2 Identified Anomalies & Corrections
- **Hashing Drift**: Initial design suggested Argon2id, but implementation used BCrypt. **Resolution**: Adjusted blueprint to favor BCrypt for MVP portability while recommending Argon2id for high-security production.
- **Identity Sprawl**: Found redundant profile-like fields in early User model migrations. **Resolution**: Standardized delegating all biodata to the `Profile` module.
- **Academic Coupling**: User profiles now depend on the `Department` structure formalized in the **[Institutional (ARC01-INST-01)](04-ARC01-INST-01-Institutional.md)** series.

### 5.3 Improvement Plan
- [x] Synchronize all identity-related blueprints with the finalized SyRS.
- [x] Ensure `AliasServiceProvider` covers all new identity models.

---

## 6. Exit Criteria & Verification Protocols

A design series is considered done only when it satisfies the following gates:

- **Verification Gate**: 100% pass rate across the security verification suites via
  **`composer test`**.
- **Quality Gate**: zero static analysis violations via **`composer lint`**.
- **Acceptance Criteria**:
    - Demonstrated enforcement of role-based route protection.
    - Verified security of subject credentials at rest.

---

## 7. Improvement Suggestions (Legacy)

- **Institutional Structure**: Realized via the **[Institutional (ARC01-INST-01)](04-ARC01-INST-01-Institutional.md)** series.
- **Resource Placement**: Potential for tracking internship capacity and placement limits.