# Blueprint: Partner Registry & Quota Management (BP-PLC-01)

**Blueprint ID**: `BP-PLC-01` | **Scope**: Internship Lifecycle | **Compliance**: `ISO/IEC 12207`

---

## 1. Context & Strategic Intent

This blueprint defines the management of industry partners and the allocation of physical internship opportunities (Slots). It ensures that the system provides a reliable registry of companies and strictly manages their capacity to prevent over-allocation during the enrollment phase.

---

## 2. Industry Partner Ecosystem (S3 - Scalable)

### 2.1 `Company` (The Partner)
- **Responsibility**: Authoritative record for industrial partners.
- **Identity**: Permanent **UUID v4**.
- **PII Protection (S1)**: Contact person details MUST be encrypted at rest.

### 2.2 `Placement` (The Slot)
- **Invariants**: Links a `Company` to an `InternshipProgram` within a specific batch.
- **Isolation**: Companies and Placements MUST be managed via `CompanyService` contract.

---

## 3. Capacity & Quota Orchestration (S1 - Secure)

The system implements a real-time tracking engine to ensure administrative integrity.

### 3.1 Slot Availability Check (PEP)
- **Verification**: `PlacementService::hasAvailableSlots(id)` is a hard prerequisite for registration.
- **Atomicity**: Quota deductions MUST be encapsulated within a **Database Transaction** (`DB::transaction`) to prevent race conditions (ISO/IEC 25010 Reliability).

### 3.2 Event-Driven Updates (S3)
- **Side-Effects**: When a placement is finalized or cancelled, a `PlacementUpdated` event SHOULD be dispatched to update analytical caches.

---

## 4. Technical Construction & Governance

### 4.1 Service Layer Dualism
- **`CompanyService`**: Extends `EloquentQuery` for partner master data.
- **`PlacementManager`**: Extends `BaseService` for complex slot allocation logic and quota telemetry.

### 4.2 Authorization (PEP)
- **Rule**: Every state-changing method (e.g., `createSlot`) MUST invoke `Gate::authorize()`.
- **Policy**: Only users with the `Administrator` or `Staff` roles are authorized to manage industry quotas.

---

## 5. Verification & Validation (V&V) - TDD Strategy

### 5.1 Transactional Integrity Tests (S1 - Secure)
- **Concurrent Enrollment Audit**: 
    - **Scenario**: Use parallel processing or rapid sequential requests to apply for the last available slot in a `Placement`.
    - **Assertion**: Only one registration must succeed; others must return a `422 (No slots available)` error.
- **Atomic Rollback Audit**: Verify that if a registration fails halfway (after the slot is "deducted" in memory), the transaction correctly rolls back and the slot remains available.

### 5.2 Functional Testing (`tests/Feature/Internship/`)
- **Quota Release Audit**: Verify that cancelling an active registration immediately increments the `available_slots` count.
- **Audit Trail Accuracy**: Assert that `ActivityLog` contains the exact UUID of the placement and the metadata explaining the quota change.

### 5.3 Architectural Testing (`tests/Arch/PlacementTest.php`)
- **Dualism Enforcement**: Ensure `PlacementManager` extends `BaseService` and `CompanyService` extends `EloquentQuery`.
- **Domain Isolation**: Ensure `Placement` models are never instantiated outside the `Internship` module.

### 5.4 Quantitative
- **Cyclomatic Complexity**: Ensure the `hasAvailableSlots()` algorithm remains < 10.
- **Coverage**: Minimum 90% behavioral coverage for all quota-deduction logic.

_This blueprint records the current state of partner and slot management. Evolution of the industry capacity engine must be reflected here._
