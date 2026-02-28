# Blueprint: Hierarchical Account Creation (BP-ID-F203)

**Blueprint ID**: `BP-ID-F203` | **Requirement ID**: `SYRS-F-203` | **Scope**: Identity & Security

---

## 1. Context & Strategic Intent

This blueprint defines the delegated authority model for account management. It ensures that user creation is a hierarchical responsibility where higher-level roles manage the lifecycle of their subordinates.

---

## 2. Technical Implementation

### 2.1 The Delegation Invariant (S1 - Secure)
- **Authority Mapping**: 
    - `SuperAdmin` -> All Roles.
    - `Admin` -> Teacher, Mentor, Student.
    - `Teacher` -> Student (within Department).
- **PEP Enforcement**: `UserService::createWithProfile` MUST invoke `Gate::authorize('create', [User::class, $targetRoles])`.

### 2.2 Secure Onboarding (S2 - Sustain)
- **Credentialing**: New accounts MUST receive a `WelcomeUserNotification` with a cryptographically secure random password.

---

## 3. Verification & Validation - TDD Strategy (3S Aligned)

### 3.1 Secure (S1) - Boundary & Integrity Protection
- **Feature (`Feature/`)**:
    - **Delegation Audit**: Verify that a `Teacher` attempting to create an `Admin` results in a `403`.
    - **Self-Service Lock**: Verify that a `Student` cannot modify their own roles.
- **Unit (`Unit/`)**:
    - **Policy Logic**: Test `UserPolicy::create` with various role combinations.

### 3.2 Sustain (S2) - Maintainability & Semantic Clarity
- **Feature (`Feature/`)**:
    - **Notification Audit**: Verify that `Notification::fake()` catches the dispatch of `WelcomeUserNotification`.
- **Architectural (`arch/`)**:
    - **Enum Standards**: Ensure role checking uses the `Role` Enum, not hard-coded strings.

### 3.3 Scalable (S3) - Structural Modularity & Performance
- **Architectural (`arch/`)**:
    - **Service Isolation**: Ensure `UserPolicy` does not directly query the database but uses the `Permission` service.
- **Feature (`Feature/`)**:
    - **Atomic Creation**: Verify that User and Profile creation is wrapped in a DB Transaction.

---

## 4. Documentation Strategy
- **Admin Guide**: Update `docs/wiki/user-onboarding.md` to document the delegated authority model (who can create whom).
- **Security Guide**: Update `docs/dev/governance.md` to include the hierarchical authorization mapping.
- **API Reference**: Update `modules/User/README.md` to document the `UserService::createWithProfile` method and its authorization requirements.
