# Application Blueprint: v0.3.0 (Identity)

**Series Code**: `ARC01-USER-01` **Status**: `Archived` (Released)

> **Spec Alignment:** This blueprint implements the **Security** requirements of the
> **[Internara Specs](../../../internara-specs.md)** (Section 10.6), specifically login via
> email/password and role-based access control.

---

## 1. Version Goals and Scopes (Core Problem Statement)

**Purpose**: Implement a flexible and secure identity system that decouples authentication (User)
from role-specific context (Profile).

**Objectives**:

- Decouple user credentials from role-specific attributes.
- Implement a polymorphic profile system to support Students, Teachers, and Staff.
- Enhance system privacy via automated data masking.

**Scope**: A monolithic `User` model leads to architectural rigidity. We need a structure where
identity is managed centrally but context is role-specific. This version addresses the "God Object"
model issue and PII privacy.

---

## 2. Functional Specifications

**Feature Set**:

- **Polymorphic Profiles**: Single `User` can have different profile types (Student, Teacher) with
  specific fields (NISN, NIP).
- **Automated Onboarding**: Automatic initialization of profile data upon account creation or role
  assignment.
- **PII Masking**: Automatic redacting of sensitive data (emails, passwords, IDs) in system logs.

**User Stories**:

- As a **User**, I want to log in with my email and password so that I can access my account safely.
- As a **Student**, I want to provide my Student ID (NISN) during onboarding so that my school can
  identify me.
- As an **Admin**, I want system logs to hide sensitive user data to ensure privacy compliance.

---

## 3. Technical Architecture (Architectural Impact)

**Modules**:

- **Auth/User**: Refactored to handle only credentials and core identity.
- **Profile**: New module for role-specific data using polymorphic relationships.
- **Shared**: Enhanced with privacy utilities (Masker).

**Data Layer**:

- **Polymorphism**: `profiles` table with `profileable_type` and `profileable_id`.
- **Identity**: All user and profile records use UUID v4.

**Security**:

- **Log Masking**: Integration of a custom Monolog processor to intercept and mask PII patterns.

---

## 4. UX/UI Design Specifications (UI/UX Strategy)

**Design Philosophy**: Secure identity and role-specific context.

**User Flow**:

1. User registers or is invited to the system via email.
2. User logs in for the first time.
3. System identifies the user's role and redirects to the "Complete Profile" flow.
4. User provides role-specific details (e.g., Student provides NISN/Major).
5. System verifies the data and unlocks the workspace.

**Mobile-First**:

- Profile management and onboarding forms are designed for responsive mobile usage.
- Inputs for numeric IDs (NISN/NIP) use appropriate mobile keypad types.

**Multi-Language**:

- All profile field labels, validation messages, and privacy notices are localized in **Indonesian**
  and **English**.

---

## 5. Success Metrics (KPIs)

- **Onboarding Success**: 100% of newly created users successfully initialize their polymorphic
  profile record.
- **Privacy Compliance**: 0 instances of unmasked PII found in logs during post-release audit.
- **Architecture**: 100% removal of role-specific columns from the core `users` table.

---

## 6. Quality Assurance (QA) Criteria (Exit Criteria)

**Acceptance Criteria**:

- [x] **Separation of Concerns**: User table contains no role-specific data (God Object remediated).
- [x] **Automated Onboarding**: Profile records are created atomically with User roles.

**Testing Protocols**:

- [x] Feature tests for polymorphic relationship integrity.
- [x] Audit tests for log masking efficiency across different data types.

**Quality Gates**:

- [x] **Spec Verification**: Security protocols align with Section 10.6 of the Internara Specs.
- [x] Static Analysis Clean.

---

## 7. vNext Roadmap (v0.4.0: Institutional Foundation)

- **Institutional Context**: Schools and Departments.
- **Academic Scoping**: Linking users to organizational units.
