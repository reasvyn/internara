# Assignment Module

The `Assignment` module manages the lifecycle of student tasks and submissions, decoupled from the
core internship program to provide flexible institutional governance.

> **Governance Mandate:** This module implements the requirements defined in the authoritative
> **[System Requirements Specification](../../docs/dev/specs.md)**. All implementation must adhere
> to the **[Coding Conventions](../../docs/dev/conventions.md)**.

---

## 1. Architectural Role

The `Assignment` module acts as a specialized task management system. It provides the infrastructure
for defining task categories, task instances, and student submissions. It is utilized by the
`Internship` module to verify program fulfillment.

---

## 2. Core Components

### 2.1 Service Layer

- **`AssignmentTypeService`**: Manages dynamic assignment categories (e.g., "Final Report",
  "Industry Certificate").
    - _Contract_: `Modules\Assignment\Services\Contracts\AssignmentTypeService`.
- **`AssignmentService`**: Orchestrates the creation and fulfillment verification of specific tasks.
    - _Features_: Automated default task generation, fulfillment status calculation.
    - _Contract_: `Modules\Assignment\Services\Contracts\AssignmentService`.
- **`SubmissionService`**: Handles student file uploads and supervisor verification.
    - _Contract_: `Modules\Assignment\Services\Contracts\SubmissionService`.

### 2.2 Domain Models

- **`AssignmentType`**: Defines the template for a task (Group, Slug, Handler).
- **`Assignment`**: A specific task instance linked to an internship program.
- **`Submission`**: A student's response to an assignment, including file attachments and status.

### 2.3 Presentation Layer (Livewire)

- **`AssignmentTypeManager`**: Administrator UI for defining custom task categories.
- **`AssignmentManager`**: Administrator UI for managing specific task instances and policies.
- **`AssignmentSubmission`**: Student UI for uploading and tracking their assignments.

---

## 3. Engineering Standards

- **Isolation Invariant**: Interacts with the `Internship` module exclusively via Service Contracts.
- **Guidance Gating**: Student submissions are gated by the completion of mandatory guidelines
  defined in the `Guidance` module.
- **Fulfillment Invariant**: Student program completion is gated by the completion of all mandatory
  assignments defined in this module.
- **Status Lifecycle**: Submissions track their state (Draft, Submitted, Verified, Rejected) using
  the `HasStatus` concern.

---

## 4. Verification & Validation (V&V)

Quality is enforced through **Pest v4**:

- **Feature Tests**: Validates the submission lifecycle and fulfillment logic.
- **Integration Tests**: Ensures correct interaction with the `Internship` module during program
  completion.
- **Command**: `php artisan test modules/Assignment`

---

_The Assignment module provides the flexibility required to adapt Internara to diverse academic
requirements._
