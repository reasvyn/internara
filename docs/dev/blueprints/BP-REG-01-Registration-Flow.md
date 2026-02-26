# Blueprint: Registration Gating & Placement Flow (BP-REG-01)

**Blueprint ID**: `BP-REG-01` | **Scope**: Internship Lifecycle | **Compliance**: `ISO/IEC 12207`

---

## 1. Context & Strategic Intent

This blueprint defines the orchestration of the student enrollment lifecycle. It establishes the rules for how a student moves from an initial application to a verified placement within an industry partner, ensuring that all administrative and temporal invariants are satisfied.

---

## 2. The Enrollment Lifecycle (S3 - Scalable)

Registration is an atomic process orchestrated by the `Internship` module, involving state transitions and cross-module validations.

### 2.1 The Registration Sequence
1. **Initiation**: Student applies via a **Thin Livewire Component**.
2. **Zero-Trust Validation (S1)**: Component MUST enforce `rules()` for all inputs (Period dates, Placement UUID) before Service Layer invocation (OWASP A03).
3. **Phase Check**: `RegistrationService` verifies `system_phase`.
4. **Capacity Check**: Verification of slot availability via `PlacementService` contract.
5. **Requirement Verification**: Student MUST clear all `InternshipRequirements` stored in the `Media` module.
6. **Verification (PEP)**: Mutations MUST invoke `Gate::authorize()`.

---

## 3. Implementation Invariants (S1 - Secure)

The `RegistrationService` (extending `EloquentQuery`) enforces high-fidelity rules:

### 3.1 Transactional Orchestration
- **Standard**: Operations involving registration and side-effects (Quota update, Notification) MUST be wrapped in **`DB::transaction()`**.

### 3.2 Requirement Gating (The Approval Lock)
- **Rule**: A registration cannot transition to `active` if mandatory requirements are missing.
- **Contract**: Verified via `InternshipRequirementService::hasClearedMandatory()`.

### 3.3 Side-Effect Governance (Events)
- **Standard**: Upon successful enrollment, an `InternshipRegistered` event is dispatched.
- **Payload**: Event MUST only carry the **UUID** of the registration (S3 Payload Standard).

---

## 4. State Machine & Traceability (S2 - Sustain)

Registrations utilize the `Status` module for auditability:
- `draft` -> `submitted` -> `active` -> `completed` -> `inactive`.
- **History**: Every transition is recorded in the `ActivityLog` with the subject UUID.

---

## 5. Technical Contracts

### 5.1 `RegistrationService` Responsibilities
- `register(array $data)`: Atomically validates phase, capacity, and period.
- `approve(string $id)`: Validates mandatory requirements before state transition.
- `complete(string $id)`: Validates **Assignment Fulfillment** (BP-OPR-03) before finalization.

---

## 6. Verification & Validation (V&V) - TDD Strategy

### 6.1 State Machine & Gating Tests (S1 - Secure)
- **Requirement Guard Audit**: 
    - **Scenario**: Attempt to `approve()` a registration where `InternshipRequirement` status is `pending`.
    - **Assertion**: Must throw `AppException` with code `422`.
- **Fulfillment Lock Audit**: 
    - **Scenario**: Attempt to `complete()` a registration where mandatory assignments are not `verified`.
    - **Assertion**: Must return `422 Unprocessable Entity`.
- **Zero-Trust Validation**: Test Livewire component with invalid UUIDs or incorrect date formats to ensure they are caught by `rules()`.

### 6.2 Event Governance Tests (S3 - Scalable)
- **Payload Integrity**: Use `Event::fake()` to verify that `InternshipRegistered` carries only the UUID of the registration.
- **Asynchronous Side-Effects**: Verify that the `AssignmentService` listener correctly reacts to the registration event without direct coupling.

### 6.3 Architectural Testing (`tests/Arch/RegistrationTest.php`)
- **Isolation**: Verify `RegistrationService` interacts with `Media` and `Assignment` modules strictly via Service Contracts.
- **Performance Audit**: Test the registration index with 100+ records to ensure zero N+1 queries using Pest's query counting tools.

### 6.4 Quantitative
- **Coverage**: 100% code coverage for the state transition logic (`approve`, `complete`, `reject`).
- **i18n**: Verify that all `AppException` messages correctly resolve via translation keys in `ID` and `EN`.

_This blueprint records the current state of registration orchestration. Evolution of the placement logic must be reflected here._
