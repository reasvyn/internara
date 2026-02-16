# Application Blueprint: Institutional Foundation (ARC01-INST-01)

**Series Code**: `ARC01-INST` | **Scope**: `Administrative Hierarchy`

---

## 1. Strategic Context

- **Spec Alignment**: This blueprint authorizes the construction of the institutional master data
  subsystem required to satisfy **[SYRS-F-101]** (Record profiles and locations).
- **Objective**: Establish the structural hierarchy of the educational organization, enabling
  multi-school and multi-department orchestration.
- **Rationale**: Internara is designed for institutional scalability. A robust foundation allows the
  system to manage complex vocational structures and accurately scope student placements and
  analytics by department.

---

## 2. Logic & Architecture (Systemic View)

### 2.1 Capabilities

- **Master Data Orchestration**: Authoritative management of school and department profiles,
  including Indonesian NPSN integration.
- **Hierarchical Governance**: Logical linking of academic specializations to their respective
  parent institutions.

### 2.2 Service Contract Specifications

- **`Modules\School\Services\Contracts\SchoolService`**: Managing school identities, branding
  (logos), and NPSN-based validation.
- **`Modules\Department\Services\Contracts\DepartmentService`**: Managing academic specializations
  and their relationship with institutional parents.

### 2.3 Data Architecture

- **Identity Protocol**: Mandatory use of **UUID v4** for all institutional records.
- **Natural Key Integrity**: NPSN utilized as a secondary natural key with strict 8-digit
  validation.
- **SLRI Constraint**: Referential integrity between modules handled at the service layer using
  indexed UUID columns.

---

## 3. Presentation Strategy (User Experience View)

### 3.1 UX Workflow

- **Administrative Control**: Unified management interface for administrators to configure school
  details and department quotas.
- **Contextual Selection**: Implementation of the `DepartmentSelector` for use in student and
  teacher profile initialization.

### 3.2 Interface Design

- **Management Components**: High-frequency administrative UIs designed for clarity and rapid data
  entry using MaryUI.
- **Institutional Branding**: Strategy for surfacing the `brand_name` and `brand_logo` consistently
  across the system.

---

## 4. Verification Strategy (V&V View)

### 4.1 Unit Verification

- **Identifier Validation**: Mathematical verification of NPSN format and institutional quota
  calculations.
- **Hierarchy Integrity**: Unit tests ensuring that child entities (Departments) cannot exist
  without valid parent identities.

### 4.2 Feature Validation

- **Institutional Management**: Integration tests for creating, updating, and verifying
  institutional profiles.
- **Logo Orchestration**: Verification of successful asset persistence and URL generation for
  institutional branding.

### 4.3 Architecture Verification

- **Modular Isolation**: Ensuring that `School` and `Department` modules remain decoupled from
  functional domains like `Internship`.

---

## 5. Compliance & Standardization (Integrity View)

### 5.1 i18n & Localization

- **Localized Master Data**: Ensuring that department names and program descriptions support full
  ID/EN translation.

### 5.2 Zero-Coupling

- **Referential Isolation**: Verification that no physical foreign keys exist between the `School`
  and `Department` tables across module boundaries.

### 5.3 a11y (Accessibility)

- **Accessible Selectors**: Ensuring that dynamic institutional dropdowns and searchable selectors
  are keyboard-navigable.

---

## 6. Documentation Strategy (Knowledge View)

### 6.1 Engineering Record

- **Organizational Schema**: Documentation of the institutional hierarchy in `architecture.md`.

### 6.2 Stakeholder Manuals

- **Institutional Setup**: Guide for administrators on how to register their school and majors in
  the Wiki.

### 6.3 Release Narration

- **Structure Milestone**: Highlighting the establishment of the structural bedrock for vocational
  management.

### 6.4 Strategic GitHub Integration

- **Issue #Inst1**: Implementation of the `School` module and master data services.
- **Issue #Inst2**: Development of the `Department` module and hierarchical linking.
- **Issue #Inst3**: Construction of institutional management UI and dynamic selectors.
- **Milestone**: ARC01-INST (Institutional Baseline).

---

## 7. Exit Criteria & Quality Gates

- **Acceptance Criteria**: Institutional profiles persistent; hierarchy verified; branding active.
- **Verification Protocols**: 100% pass rate in the institutional test segments.
- **Quality Gate**: Adherence to the **Modular DDD** folder hierarchy and `src` omission mandate.

---

_Application Blueprints prevent architectural decay and ensure continuous alignment with the
foundational specifications._
