# Blueprint: Slot Atomic Integrity (BP-PLC-F302)

**Blueprint ID**: `BP-PLC-F302` | **Requirement ID**: `SYRS-F-302` | **Scope**: Internship Lifecycle Management

---

## 1. Context & Strategic Intent

This blueprint defines the atomic industry placement availability and quota management. It ensures that industrial slots are never over-allocated, even under concurrent registration attempts.

---

## 2. Technical Implementation

### 2.1 Transactional Quota (S1 - Secure)
- **Atomic Deduction**: The `RegistrationService` MUST use `DB::transaction()` with a "Shared Lock" or atomic decrement to ensure quota integrity.
- **One-Placement Law**: Systemic invariant preventing a student from holding two active placements in the same cycle.

### 2.2 Dynamic Calculation (S3 - Scalable)
- **Property Hooks**: The `remaining_slots` attribute in `InternshipPlacement` MUST be computed dynamically based on current `active` registration statuses.

---

## 3. Verification & Validation - TDD Strategy (3S Aligned)

### 3.1 Secure (S1) - Boundary & Integrity Protection
- **Feature (`Feature/`)**:
    - **Concurrency Audit**: Simulate 10 simultaneous registration attempts for a placement with only 1 slot (Only 1 must succeed).
    - **Atomic Rollback**: Verify that if a registration fails halfway, the slot count remains unchanged.

### 3.2 Sustain (S2) - Maintainability & Semantic Clarity
- **Unit (`Unit/`)**:
    - **Calculation Logic**: Test the `remainingSlots` property hook with various registration status combinations (pending, active, rejected).
- **Architectural (`arch/`)**:
    - **Service Standards**: Verify `InternshipPlacementService` implements the corresponding Contract.

### 3.3 Scalable (S3) - Structural Modularity & Performance
- **Architectural (`arch/`)**:
    - **Isolation**: Ensure `Placement` models are never instantiated outside the `Internship` module.
- **Unit (`Unit/`)**:
    - **Query Efficiency**: Verify that the slot calculation query is optimized and indexed.

---

## 4. Documentation Strategy
- **Admin Guide**: Update `docs/wiki/enrollment-matching.md` to explain industry placement quota management.
- **Developer Guide**: Update `modules/Internship/README.md` to document the transactional quota deduction logic and concurrency guards.
- **Architecture**: Document the "One-Placement Law" invariant in `docs/dev/architecture.md`.
