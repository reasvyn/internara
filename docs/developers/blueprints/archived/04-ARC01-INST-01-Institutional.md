# Application Blueprint: Institutional Foundation (ARC01-INST-01)

**Series Code**: `ARC01-INST-01` **Status**: `Archived` (Done)

---

## 1. Design Objectives & Scope

**Strategic Purpose**: Establish the structural framework for managing institutional entities,
including school departments and industry placement registries.

**Objectives**:

- Provide a centralized repository for managing academic pathway data.
- Enable the formal registration and classification of industry placements (Internship Locations).
- Establish the baseline for student-to-placement assignment orchestration.

---

## 2. Functional Specification

### 2.1 Capability Set

- **Department Orchestrator**: Management of institutional academic units and their metadata.
- **Placement Registry**: Centralized listing of industry collaborators with contact and capacity
  attributes (previously referred to as "Partners").
- **Placement Logic Baseline**: Core services for linking student profiles to registered placements.

### 2.2 Stakeholder Personas

- **Practical Work Staff**: Utilizes the registry to manage institutional and industry data.
- **Instructor**: Accesses department-specific student lists for supervision planning.

---

## 3. Architectural Impact (Logical View)

### 3.1 Modular Decomposition

- **Department Module**: New domain for academic structural orchestration.
- **School Module**: Domain for managing institutional identity.
- **Internship Module**: Enhanced to handle placement logistics and capacity tracking.

### 3.2 Persistence Logic

- **Identity Invariant**: Mandatory utilization of **UUID v4** for all institutional records.
- **Isolation Protocol**: Domain logic ensures zero concrete coupling between school and placement
  entities at the database layer.
- **Software-Level Integrity (SLRI)**: Implementation of service-layer validation to ensure referential integrity between Departments and Schools without physical constraints.

---

## 4. Documentation Strategy (Knowledge View)

- **Standardization**: Documentation of the institutional hierarchy within the **Architecture Description**.
- **Module Records**: Authoring of the `README.md` files for the `School` and `Department` modules.
- **Branding Integration**: Strategy for providing school logo and name to the global layout and reporting engine.

---

## 5. Audit & Evaluation Report (v0.13.0 Audit)

**Date**: 2026-02-04 | **Auditor**: Gemini

### 5.1 Realized Outcomes
- **UUID Identity**: Successfully adopted across `School`, `Department`, and `InternshipPlacement` models.
- **SLRI Enforcement**: `DepartmentService` refactored to explicitly validate `school_id` via `SchoolService`, adhering to the logic isolation mandate.
- **Terminology Alignment**: Standardized "Partner" into "Internship Placement" to better reflect the system's operational focus.
- **Branding Baseline**: Integrated `COLLECTION_LOGO` in the `School` model via the `Media` module. Institutional branding data now serves as the authoritative source for the **[Operational (ARC01-OPER-01)](05-ARC01-OPER-01-Operational-Layer.md)** dashboards.

### 5.2 Identified Anomalies & Corrections
- **Loose Integrity**: Initial `DepartmentService` relied on model-level validation. **Correction**: Moved validation logic to the Service Layer to satisfy the **Logic Layer** mandate.
- **Naming Inconsistency**: "Partner" terminology was ambiguous. **Correction**: Aligned all documentation and code to use "Placement".

### 5.3 Improvement Plan
- [x] Refactor Department creation/update logic to use SLRI protocols.
- [x] Synchronize institutional metadata with the global UI module.

---

## 6. Exit Criteria & Verification Protocols

A design series is considered done only when it satisfies the following gates:

- **Verification Gate**: 100% pass rate across the institutional verification suites via
  **`composer test`**.
- **Quality Gate**: zero static analysis violations via **`composer lint`**.
- **Acceptance Criteria**:
    - Successful registration and retrieval of department-placement relationships.
    - Verified fulfillment of [SYRS-F-101] requirements.

---

## 7. Improvement Suggestions (Legacy)

- **Daily Monitoring**: Realized via the **[Instructional Execution (ARC01-GAP-02)](11-ARC01-GAP-02-Instructional-Execution.md)** series.
- **Historical Scoping**: Realized via the `HasAcademicYear` concern in the **Shared** module.