# Application Blueprint: Identity & Security (ARC01-USER-01)

**Series Code**: `ARC01-USER-01` | **Status**: `Archived` (Done)

---

## 1. Strategic Context

- **Spec Alignment**: This configuration baseline implements the **Security & Integrity** ([SYRS-NF-501], [SYRS-NF-502]) requirements of the authoritative **[Specs](../specs.md)**.

---

## 2. Logic & Architecture (Systemic View)

### 2.1 Capabilities
- **Authentication Orchestrator**: Secure login/logout flows with session integrity.
- **RBAC Baseline**: Implementation of roles and granular permissions according to the stakeholder requirements.
- **Profile Subsystem**: Logic for subjects to update personal metadata and security credentials.

### 2.2 Service Contracts
- **AuthService**: Manages the technical lifecycle of an authentication session.
- **RedirectService**: Orchestrates role-based environment redirection post-login.

### 2.3 Data Architecture
- **Identity Isolation**: Verification of the separation between `User` (Account) and `Profile` (Biodata) modules.
- **Encryption Invariant**: Passwords must be hashed using the **BCrypt** algorithm (Standard MVP Baseline).

---

## 3. Presentation Strategy (User Experience View)

### 3.1 UX Workflow
- **Environment Redirection**: Intelligent routing of users to their authorized dashboards (Student, Teacher, Mentor, Admin).
- **Self-Service**: Dedicated interfaces for subjects to manage their personal security baseline.

### 3.2 Invariants
- **Access Control**: Mandatory authorization check at every system boundary via Policies and Gates.
- **Mobile-First**: Priority given to high-frequency identity actions on touch viewports.

---

## 4. Documentation Strategy (Knowledge View)

### 4.1 Security Standards
- **Standardization**: Formalization of the **[Access Control Standards](../access-control.md)**.

### 4.2 Module Standards
- **Identity Record**: Authoring of the `README.md` files for the `Auth`, `User`, and `Permission` modules.
- **Implementation Guide**: Documentation of the post-login redirection logic and session protection.

---

## 5. Audit & Evaluation Report (v0.13.0 Audit)

### 5.1 Realized Outcomes
- **RBAC Foundation**: Successfully implemented 5 core roles via `RoleSeeder`.
- **Credential Security**: BCrypt adopted as the lightweight MVP hashing standard.
- **Domain Isolation**: Verified separation of `User` (Account) and `Profile` (Biodata) modules.
- **Post-Login Routing**: `RedirectService` implemented to manage role-based environment transitions.

### 5.2 Identified Anomalies & Corrections
- **Hashing Drift**: Initial design suggested Argon2id, but implementation used BCrypt. **Resolution**: Adjusted blueprint to favor BCrypt for MVP portability.
- **Identity Sprawl**: Found redundant profile-like fields in early User model migrations. **Resolution**: Standardized delegating all biodata to the `Profile` module.
- **Academic Coupling**: User profiles now depend on the `Department` structure formalized in the **[Institutional (ARC01-INST-01)](04-ARC01-INST-01-Institutional.md)** series.


---

## 6. Exit Criteria & Verification Protocols

- **Verification Gate**: 100% pass rate across the security verification suites via **`composer test`**.
- **Quality Gate**: zero static analysis violations via **`composer lint`**.
- **Acceptance Criteria**:
    - Demonstrated enforcement of role-based route protection.
    - Verified security of subject credentials at rest.

---

## 7. Improvement Suggestions (Legacy)

- **Institutional Structure**: Realized via the **[Institutional (ARC01-INST-01)](04-ARC01-INST-01-Institutional.md)** series.
- **Resource Placement**: Potential for tracking internship capacity and placement limits.
