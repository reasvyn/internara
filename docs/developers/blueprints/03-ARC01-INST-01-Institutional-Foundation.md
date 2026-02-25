# Application Blueprint: Institutional Foundation (ARC01-INST-01)

**Series Code**: `ARC01-INST` | **Scope**: `Phase 2: The WHERE` | **Compliance**: `ISO/IEC 12207`

---

## 1. Context & Strategic Intent

This blueprint authorizes the construction of the **Institutional Hierarchy**. It defines the 
physical and logical boundaries where internships occur, ensuring that data is correctly scoped 
to schools and specializations.

- **SyRS Traceability**:
    - **[SYRS-F-101]**: School Identity Management.
    - **[SYRS-F-102]**: Departmental (Specialization) Orchestration.
    - **[SYRS-C-004]**: Institutional Branding Invariant.
    - **[SYRS-NF-601]**: Multi-Institutional Isolation.

---

## 2. User Roles & Stakeholders

- **System Administrator**: Global management of multiple school instances.
- **School Administrator**: Management of departments, teachers, and school-specific settings.
- **Instructors/Students**: Association with their respective departments.

---

## 3. Modular Impact Assessment

1.  **School**: Owners of the institutional identity and branding.
2.  **Department**: Owners of the academic specializations and student grouping.
3.  **Setting**: Orchestrates the active branding and institutional parameters.

---

## 4. Logic & Architecture (The Foundation Engine)

### 4.1 The School-Department Relationship
- **One-to-Many**: A School can have multiple Departments.
- **Strict Scoping**: Every operational entity (Student, Teacher, Internship) MUST belong to 
  at least one Department to ensure correct supervision routing.

### 4.2 Branding Orchestration (Product vs Instance)
- **Metadata SSoT**: The `MetadataService` from `Core` provides the `app_name` (Internara).
- **Branding Logic**: The `School` module provides the `brand_name` and `logo`.
- **Fallback Rule**: UI components must utilize the `brand_name` if present, otherwise 
  revert to `app_name`.

---

## 5. Contract & Interface Definition

### 5.1 `Modules\School\Services\Contracts\SchoolService`
- `active(): ?School`: Retrieves the authoritative school record for the current instance.
- `updateBranding(array $data): void`: Atomic update of institutional identity.

### 5.2 `Modules\Department\Services\Contracts\DepartmentService`
- `allBySchool(string $schoolUuid): Collection`: Retrieves isolated departments.
- `assignUser(string $userUuid, string $departmentUuid): void`: Anchors a persona to a domain.

---

## 6. Data Persistence Strategy

### 6.1 Schema Invariants
- **`schools`**: `uuid`, `name`, `legal_id`, `address`, `branding_config` (JSON).
- **`departments`**: `uuid`, `school_uuid` (Reference), `name`, `slug`.
- **SLRI**: Physical foreign keys are allowed *within* the module or between `School` and 
  `Department` if they share a common service boundary. Cross-module references to `User` 
  remain UUID-only.

### 6.2 The Academic Year Hook
- All institutional data must support the `HasAcademicYear` concern to facilitate 
  year-over-year reporting without data collisions.

---

## 7. Verification Plan (V&V View)

### 7.1 Integrity Testing
- **Test 1**: Verify that deleting a School triggers a `SchoolDeleted` event for 
  departmental cleanup.
- **Test 2**: Verify that branding settings correctly override global app metadata in the UI.

### 7.2 Architecture Police
- Enforce that `DepartmentService` uses `SchoolService` contract to verify existence.

---

_This blueprint constitutes the authoritative engineering record for the Institutional Foundation. 
Any violation of institutional isolation is considered an architectural defect._
