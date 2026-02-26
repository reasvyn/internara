# Blueprint: Institutional & Departmental Sovereignty (BP-ORG-01)

**Blueprint ID**: `BP-ORG-01` | **Scope**: Institutional Architecture | **Compliance**: `ISO/IEC 12207`

---

## 1. Context & Strategic Intent

This blueprint defines the structural boundaries of the Internara ecosystem. It establishes how multiple educational institutions and their respective departments are isolated and branded, ensuring that data is correctly scoped and attributed.

---

## 2. Institutional Hierarchy (S3 - Scalable)

The system operates under a hierarchical structure that governs data visibility and supervision.

### 2.1 `School` (Institutional Anchor)
- **Authority**: The primary entity representing the institution.
- **Branding Orchestration (S2)**: 
    - `app_name`: Product identity ("Internara") from `app_info.json`.
    - `brand_name`: Instance identity from runtime settings.
    - **Fallback**: UI MUST use `brand_name` if present, falling back to `app_name`.

### 2.2 `Department` (Academic Scope)
- **Authority**: Manages specific fields of study.
- **Scoping**: All students and instructors MUST be anchored to a department to ensure correct supervision routing.
- **Isolation**: Interaction with schools MUST occur via the `SchoolService` contract.

---

## 3. Temporal Scoping: The Academic Year (S2 - Sustain)

To prevent data collisions across cycles, the system implements a strict temporal isolation mechanism.

### 3.1 `HasAcademicYear` Protocol
- **Mechanism**: A global Eloquent scope filtering by `active_academic_year`.
- **Invariant**: Every operational record (Internship, Journal, Attendance) is stamped with an academic year UUID.
- **Auditability**: All academic cycle transitions MUST be recorded in the `ActivityLog`.

---

## 4. Cross-Module Sovereignty (SLRI)

- **Prohibition**: Physical FKs between `School`/`Department` and `User` are forbidden.
- **Verification**: `DepartmentService` MUST verify the `school_id` via `SchoolService` before creation.
- **Soft-Delete (S2)**: When a School is soft-deleted, a `SchoolArchived` event MUST be dispatched for cross-module cleanup.

---

## 5. Technical Contracts & Logic

### 5.1 Service Dualism
- **`SchoolService`**: Extends `EloquentQuery` for administrative CRUD.
- **`BrandingOrchestrator`**: Extends `BaseService` for complex asset/metadata logic.

---

## 6. Verification & Validation (V&V) - TDD Strategy

### 6.1 Temporal Scoping Tests (S2 - Sustain)
- **Academic Year Leak Audit**: 
    - **Scenario**: Set `active_academic_year` to Year A. Create records in Year B.
    - **Assertion**: Standard queries MUST NOT return Year B records.
    - **Scenario**: Use `withoutAcademicYear()` scope.
    - **Assertion**: Both Year A and B records must be returned.

### 6.2 Branding Orchestration Tests
- **Identity Fallback Audit**: 
    - Verify that UI displays `brand_name` when set.
    - Verify that UI displays `app_name` (from `app_info.json`) when `brand_name` is null.
- **A11y Audit**: Use automated tools to verify that brand colors injected into the CSS maintain a 4.5:1 contrast ratio (WCAG 2.1 AA).

### 6.3 Architectural Testing (`tests/Arch/InstitutionalTest.php`)
- **Isolation**: Verify `Department` models do not hold direct relationships to `Internship` models (use indexed UUIDs).
- **Service-Only**: Ensure all external data access to School info is handled via `SchoolService` contract.

### 6.4 Quantitative
- **Cognitive Load**: Maintain Cognitive Complexity < 15 for the `HasAcademicYear` scope implementation.
- **Coverage**: 100% coverage for the branding fallback logic.

_This blueprint records the current state of institutional architecture. Evolution of the organizational scoping must be reflected here._
