# Application Blueprint: v0.4.0 (Institutional Foundation)

**Series Code**: `ARC01-INST-01` **Status**: `Archived` (Released)

> **Spec Alignment:** This blueprint implements the **Administrative Management** requirements
> defined in the **[Internara Specs](../../../internara-specs.md)** (Section 1). It establishes the
> "Centralized data storage" for institutions.

---

## 1. Version Goals and Scopes (Core Problem Statement)

**Purpose**: Establish the Academic Hierarchy and enforce the "Zero Physical Foreign Key" rule to
ensure modular portability and institutional context.

**Objectives**:

- Decouple Schools and Departments into self-contained modules.
- Ensure that users belong to specific academic units.
- Implement strictly isolated database relations using UUIDs.

**Scope**: The system lacks academic context (Schools, Departments). Users exist in a vacuum. Tight
database coupling threatens the modular goal of the project. This version establishes the academic
core.

---

## 2. Functional Specifications

**Feature Set**:

- **School Management**: CRUD for institutional identity (Name, Logo).
- **Department Management**: Academic unit organization and staff/student assignment.
- **Internship Placements**: Management of industry partner slots and programs.

**User Stories**:

- As an **Admin**, I want to create a new Department so that I can organize students by their major.
- As a **Staff**, I want to define how many students can be placed at a specific industry partner.
- As a **User**, I want my profile to reflect my institutional affiliation.

---

## 3. Technical Architecture (Architectural Impact)

**Modules**:

- **School**: New module for high-level institutional data.
- **Department**: New module for academic organizational units.
- **Internship**: Enhanced to handle placement slot management.

**Data Layer**:

- **Isolation**: Removal of all physical foreign keys across modules. Use of indexed UUID columns.
- **Identity**: Migration of institutional entities to UUID primary keys.

---

## 4. UX/UI Design Specifications (UI/UX Strategy)

**Design Philosophy**: Institutional structure and modular isolation.

**User Flow**:

1. Admin creates a School record.
2. Admin defines Departments within that School.
3. Staff assigns Teachers and Students to their respective Departments.
4. Staff defines internship programs and available slots within the Internship module.
5. System validates placement capacity before allowing student assignments.

**Mobile-First**:

- Administrative tables for managing academic units are designed for responsive stacking.
- Management forms are optimized for high-density data entry on desktop but remain usable on tablet.

**Multi-Language**:

- Institutional names, department descriptions, and labels are localized in **Indonesian** and
  **English**.

---

## 5. Success Metrics (KPIs)

- **Modular Portability**: 100% of modules can be deleted without causing database-level cascade
  errors.
- **Data Integrity**: 0 cases of "orphaned" departments without school context due to service-layer
  validation.
- **Capacity Accuracy**: 100% enforcement of placement slot limits.

---

## 6. Quality Assurance (QA) Criteria (Exit Criteria)

**Acceptance Criteria**:

- [x] **Logical Integrity**: Deleting a master record does not crash related modules (graceful
      degradation via service logic).
- [x] **Capacity Logic**: Placement slots are correctly restricted by department capacity.

**Testing Protocols**:

- [x] Unit tests for cross-module relationship lookup services.
- [x] Schema verification for absence of physical FK constraints.

**Quality Gates**:

- [x] **Spec Verification**: Fulfills administrative data structure requirements in Section 1.
- [x] Static Analysis Clean.

---

## 7. vNext Roadmap (v0.5.0: Operational Layer)

- **Activity Tracking**: Journals and Attendance logs.
- **Academic Scoping**: Multi-year cohort management.
