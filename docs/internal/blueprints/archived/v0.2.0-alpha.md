# Application Blueprint: v0.2.0 (Core Engine)

**Series Code**: `ARC01-CORE-01` **Status**: `Archived` (Released)

> **Spec Alignment:** This blueprint lays the foundation for the **Platform and Technology**
> standards (Specs Section 5), including centralized database architecture and role-based access.

---

## 1. Version Goals and Scopes (Core Problem Statement)

**Purpose**: Establish the foundational infrastructure (Core & Shared modules) and implement the
Role-Based Access Control (RBAC) system.

**Objectives**:

- Unify utility logic (UUIDs, Statuses) across the modular system.
- Standardize CRUD operations via a base service layer.
- Implement the system roles mandated by the project specs.

**Scope**: The project lacks a unified infrastructure, leading to duplication of utility logic and
inconsistent database patterns. This version establishes the "Engine Room" of the application.

---

## 2. Functional Specifications

**Feature Set**:

- **Shared Utilities**: Universal concerns for UUIDs, status management, and common traits.
- **RBAC Infrastructure**: Modular implementation of role-based permissions for Instructor, Staff,
  and Student.
- **Base Service Layer**: Standardized `EloquentQuery` service for consistent data orchestration.

**User Stories**:

- As a **Developer**, I want a base service for CRUD so that I can implement new domains rapidly and
  consistently.
- As an **Admin**, I want to define specific permissions for different roles to ensure system
  security.
- As a **User**, I want my identity to be uniquely identified by a secure UUID.

---

## 3. Technical Architecture (Architectural Impact)

**Modules**:

- **Shared**: New module for universal utility logic and traits.
- **Permission**: New module for managing RBAC infrastructure.
- **Core**: Infrastructure logic for the overall application lifecycle.

**Data Layer**:

- **Identity**: Enforcement of UUID v4 as the global standard for primary keys.
- **Infrastructure**: Spatie Permission tables integrated within a modular context.

---

## 4. UX/UI Design Specifications (UI/UX Strategy)

**Design Philosophy**: Architectural consistency and foundational security.

**User Flow**:

- Since this is a core engine version, the primary flow is developer-centric:

1. Developer extends `BaseModel` and `HasUuid`.
2. Developer extends `EloquentQuery` service for a new domain.
3. Developer assigns roles to users via the `PermissionService`.
4. System automatically enforces access control based on assigned roles.

**Multi-Language**:

- All permission labels and core system messages support **Indonesian** and **English**
  localization.

---

## 5. Success Metrics (KPIs)

- **Code Consistency**: 100% of domain models utilize the `HasUuid` concern.
- **Security Coverage**: 100% of routes protected by role-aware middleware.
- **Performance**: CRUD operations using the base service show consistent execution times.

---

## 6. Quality Assurance (QA) Criteria (Exit Criteria)

**Acceptance Criteria**:

- [x] **Universal UUIDs**: All models and migrations strictly follow the UUID standard.
- [x] **Base Service**: Domain services successfully inherit and utilize standard CRUD features.

**Testing Protocols**:

- [x] Unit tests for UUID generation and status management.
- [x] Integration tests for role-based permission inheritance.

**Quality Gates**:

- [x] **Spec Verification**: RBAC architecture supports all required user roles from Section 4.
- [x] Static Analysis Clean.

---

## 7. vNext Roadmap (v0.3.0: Identity)

- **Identity System**: Refactoring User and Profile modules.
- **Polymorphic Profiles**: Decoupling credentials from role context.
