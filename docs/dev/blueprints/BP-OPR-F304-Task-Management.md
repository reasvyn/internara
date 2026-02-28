# Blueprint: Task Management (BP-OPR-F304)

**Blueprint ID**: `BP-OPR-F304` | **Requirement ID**: `SYRS-F-304` | **Scope**: Internship Lifecycle Management

---

## 1. Context & Strategic Intent

This blueprint defines the dynamic assignment engine. It ensures that students fulfill all mandatory institutional tasks (e.g., periodic reports, feedback forms) throughout their internship period.

---

## 2. Technical Implementation

### 2.1 Dynamic Allocation (S3 - Scalable)
- **Automatic Generation**: When a registration is approved, the `AssignmentService` automatically populates the `submissions` table based on the program's assignment types.
- **Fulfillment Invariant**: A registration cannot be marked as `completed` if mandatory assignments remain in `pending` or `submitted` status.

---

## 3. Verification & Validation - TDD Strategy (3S Aligned)

### 3.1 Secure (S1) - Boundary & Integrity Protection
- **Feature (`Feature/`)**:
    - **Completion Guard**: Attempt to `complete()` a registration with incomplete assignments (Must throw 422).
    - **IDOR Audit**: Test that a student can only upload files to assignments belonging to their own UUID.

### 3.2 Sustain (S2) - Maintainability & Semantic Clarity
- **Unit (`Unit/`)**:
    - **Status Transitions**: Verify that `Submission::verify()` correctly updates the model status and logs the action.
- **Architectural (`arch/`)**:
    - **Namespace Compliance**: Ensure all submission models use the `HasStatus` concern.

### 3.3 Scalable (S3) - Structural Modularity & Performance
- **Architectural (`arch/`)**:
    - **Event-Driven**: Verify that assignment generation is triggered via the `InternshipRegistered` event listener.
- **Unit (`Unit/`)**:
    - **Performance**: Ensure the completion check query uses efficient Eloquent aggregation (`whereAllVerified()`).

---

## 4. Documentation Strategy
- **Student Guide**: Update `docs/wiki/getting-started.md` to include instructions on managing and submitting internship assignments.
- **Staff Guide**: Update `docs/wiki/assessment-and-evaluation.md` on how to verify student submissions.
- **Developer Guide**: Update `modules/Assignment/README.md` to document the dynamic allocation engine and fulfillment invariants.
