# Blueprint: Assignment Fulfillment Logic (BP-OPR-03)

**Blueprint ID**: `BP-OPR-03` | **Scope**: Operational Telemetry | **Compliance**: `ISO/IEC 25010`

---

## 1. Context & Strategic Intent

This blueprint defines the task management and fulfillment infrastructure. It establishes the rules for how specific assignments (e.g., "Final Report", "Skills Log") are linked to the internship lifecycle and how their completion acts as a mandatory technical lock for program finalization.

---

## 2. Dynamic Task Orchestration (S3 - Scalable)

The `Assignment` module provides a flexible template-based system for tasks.

### 2.1 Assignment Templates
- **`AssignmentType`**: Defines the "blueprint" for a task.
- **Mandatory Flag**: Designates types required for `InternshipRegistration` completion.

### 2.2 Instance Generation
- **Automatic Allocation**: When an `InternshipRegistered` event is received, the `AssignmentService` MUST automatically instantiate tasks for the student.
- **Isolation**: Handled via the `AssignmentService` contract, never direct model creation from the `Internship` module.

---

## 3. The Completion Invariant (S1 - Secure)

To ensure institutional and academic standards, the system enforces a strict fulfillment gate.

### 3.1 Fulfillment Guard (The PEP Check)
- **Rule**: `Registration Status -> COMPLETED` is prohibited if any mandatory assignment is not in the `verified` status.
- **Verification**: `AssignmentService::isFulfillmentComplete()` is the authoritative PEP check.

### 3.2 Authorization (PEP)
- **Standard**: Every state-altering method (e.g., `verifySubmission`) MUST explicitly invoke **`Gate::authorize()`**.
- **Ownership**: Policies MUST verify that only assigned supervisors can verify student tasks.

---

## 4. Submission Lifecycle & Events (S2 - Sustain)

Submissions follow a strictly audited state machine:
- `draft` -> `submitted` -> `verified` -> `rejected`.
- **Domain Event**: Upon task verification, an `AssignmentVerified` event SHOULD be dispatched to trigger progress updates in the `Assessment` module.
- **i18n**: All rejection reasons and feedback MUST be localized.

---

## 5. Verification & Validation (V&V) - TDD Strategy

### 5.1 Fulfillment Lock Tests (S1 - Secure)
- **Completion Guard Audit**: 
    - **Scenario**: Create an `InternshipRegistration` with 3 mandatory assignments. Set 2 to `verified` and 1 to `submitted`. Attempt to `complete()` the registration.
    - **Assertion**: Must throw `AppException` with code `422` and specific task completion message.
- **Negative Rejection Audit**: Verify that if a supervisor `rejects` a previously `verified` assignment, the registration completion status is immediately blocked.

### 5.2 Event-Driven Allocation Tests (S3 - Scalable)
- **Automatic Generation Audit**: 
    - **Scenario**: Fire an `InternshipRegistered` event.
    - **Assertion**: Assert that `assignments` table contains the correct rows mapped to the student's program requirements.
- **Async Verification Side-Effects**: Verify that `AssignmentVerified` event triggers a re-calculation of the student's compliance score in the `Assessment` module.

### 5.3 Architectural Testing (`tests/Arch/AssignmentTest.php`)
- **Isolation**: Ensure `Assignment` module DOES NOT import models from `Internship` (Interface only).
- **Standards**: Enforce usage of `HasStatus` concern for all submission models.

### 5.4 Quantitative & UI
- **Coverage**: 100% coverage for the `isFulfillmentComplete()` logic.
- **A11y**: Test the file upload component for `aria-label` presence and focus visibility during drag-and-drop.

_This blueprint records the current state of assignment fulfillment. Evolution of the task orchestration engine must be reflected here._
