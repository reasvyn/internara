# Module Documentation Index

> Last updated: 2026-06-05
> Changes: Added Setup module to module index; updated total module count to 18; synchronized with SysAdmin restructuring

Complete index of module documentation for the Internara internship management system. Each module manages a vertical slice of the application with colocated Actions, Models, Policies, and Livewire components.

---

## Documentation Structure

Each module has two files:
- **`{module}.md`** — Business overview, principles, context, and rules
- **`{module}-reference.md`** — Technical API reference, file organization, and implementation details

---

## Core Modules

### 1. Core — Foundation & Infrastructure
**Purpose:** Foundational base classes, interfaces, abstract exception structures, and request-level middleware

- Overview: [core.md](core.md)
- Reference: [core-reference.md](core-reference.md)

**Key Concepts:** BaseModel, BaseAction, BasePolicy, BaseRecordManager, SmartLogger

---

### 1b. Shared — Cross-Cutting Components
**Purpose:** Reusable helper utilities, concrete exceptions, common DTOs, enums, global UI components, and helper traits

- Overview: [shared.md](shared.md)
- Reference: [shared-reference.md](shared-reference.md)

**Key Concepts:** CacheKeys, CsvHandler, concrete Exceptions, global enums, LangSwitcher, ThemeSwitcher

---

### 2. User — Authentication & Identity
**Purpose:** User management, authentication, profiles, notifications, and account recovery

- Overview: [user.md](user.md)
- Reference: [user-reference.md](user-reference.md)

**Key Concepts:** Login, Recovery Codes, Profiles, Notifications, Activation

**Dependencies:** Core, SysAdmin

---

### 3. SysAdmin — System Administration
**Purpose:** User administration, announcements, compliance, system configuration, audit logging, and health monitoring

- Overview: [sysadmin.md](sysadmin.md)
- Reference: [sysadmin-reference.md](sysadmin-reference.md)

**Key Concepts:** Account Lifecycle, GDPR Compliance, Announcements, Settings, Pulse Monitoring

**Dependencies:** User, Academics, Core

**Used By:** All modules (via Settings)

---

### 3b. Setup — Installation & Provisioning
**Purpose:** One-time technical installation, environment check, database provisioning, and setup token lifecycle management

- Overview: [setup.md](setup.md)
- Reference: [setup-reference.md](setup-reference.md)

**Key Concepts:** SetupWizard, SetupState, EnvironmentAuditor, SystemProvisioner

**Dependencies:** Core, Academics

**Used By:** None (one-time initialization)

---

## Academic Modules

### 4. Academics — Educational Structure
**Purpose:** Departments and academic calendar management

- Overview: [academics.md](academics.md)
- Reference: [academics-reference.md](academics-reference.md)

**Key Concepts:** Department, AcademicYear

**Dependencies:** Core

**Used By:** Program, Enrollment, Assessment

---

### 5. Program — Internship Programs
**Purpose:** Internship/practicum programs, timelines, and cohort student groupings (groups)

- Overview: [program.md](program.md)
- Reference: [program-reference.md](program-reference.md)

**Key Concepts:** Internship, InternshipGroup

**Dependencies:** Academics, Partners, Core

**Used By:** Enrollment, Journals, Evaluation

---

### 6. Enrollment — Student Placement
**Purpose:** Student registration, placement slot assignment, and change requests

- Overview: [enrollment.md](enrollment.md)
- Reference: [enrollment-reference.md](enrollment-reference.md)

**Key Concepts:** Registration, Placement, AccountApplication, PlacementChangeRequest

**Dependencies:** User, Program, Academics, Core

**Used By:** Journals, Assessment, Evaluation

---

## Evaluation & Assessment Modules

### 7. Assessment — Evaluation Framework
**Purpose:** Rubrics, assessments, and scoring frameworks

- Overview: [assessment.md](assessment.md)
- Reference: [assessment-reference.md](assessment-reference.md)

**Key Concepts:** Rubric, Assessment

**Dependencies:** Core

**Used By:** Evaluation

---

### 8. Evaluation — Performance Feedback
**Purpose:** Supervisor and teacher evaluations of students

- Overview: [evaluation.md](evaluation.md)
- Reference: [evaluation-reference.md](evaluation-reference.md)

**Key Concepts:** Evaluation, Scoring, Feedback

**Dependencies:** User, Assessment, Program, Core

**Used By:** Certification

---

### 9. Assignment — Course Work
**Purpose:** Assignment management and submission tracking

- Overview: [assignment.md](assignment.md)
- Reference: [assignment-reference.md](assignment-reference.md)

**Key Concepts:** Assignment, Submission, Grading

**Dependencies:** User, Program, Core

---

## Tracking & Activity Modules

### 10. Journals — Student Activity Tracking
**Purpose:** Logbooks, attendance, schedules, and industry assessments

- Overview: [journals.md](journals.md)
- Reference: [journals-reference.md](journals-reference.md)

**Key Concepts:** Logbook, Attendance, Schedule, IndustryAssessment

**Dependencies:** Enrollment, Program, Core

**Used By:** Evaluation

---

### 11. Guidance — Mentoring & Supervision
**Purpose:** Mentor relationships coordination and private field supervision logs

- Overview: [guidance.md](guidance.md)
- Reference: [guidance-reference.md](guidance-reference.md)

**Key Concepts:** SupervisionLog, Mentoring Assignments

**Dependencies:** User, Program, Core

---

### 12. Incident — Issue Tracking
**Purpose:** Incident reports and workplace concern documentation

- Overview: [incident.md](incident.md)
- Reference: [incident-reference.md](incident-reference.md)

**Key Concepts:** IncidentReport, Severity, Resolution

**Dependencies:** User, Program, Core

---

## Supporting Modules

### 13. Partners — Industrial Partners
**Purpose:** Company management and partnership agreements

- Overview: [partners.md](partners.md)
- Reference: [partners-reference.md](partners-reference.md)

**Key Concepts:** Company, Partnership

**Dependencies:** Core

**Used By:** Program, Guidance

---

### 14. Certification — Credentials
**Purpose:** Certificate generation and credential management

- Overview: [certification.md](certification.md)
- Reference: [certification-reference.md](certification-reference.md)

**Key Concepts:** Certificate, CertificateTemplate

**Dependencies:** User, Evaluation, Program, Core

---

### 15. Reports — Student Grade Card (Rapor PKL)
**Purpose:** Final student grade compilation, score aggregation, and coordinator sign-off

- Overview: [reports.md](reports.md)
- Reference: [reports-reference.md](reports-reference.md)

**Key Concepts:** Grade Card, Rapor PKL, Score Aggregation

**Dependencies:** User, Program, Assessment, Enrollment, Core

---

### 16. Document — Templates & Handbooks
**Purpose:** Official document templates, correspondence generation (surat menyurat), policy handbooks, and compliance acknowledgements

- Overview: [document.md](document.md)
- Reference: [document-reference.md](document-reference.md)

**Key Concepts:** OfficialDocument, Handbook, DocumentAcknowledgement, DocumentRenderer

**Dependencies:** Core, User

---

## Quick Access Guide

### By Business Function

**User Management**: [user.md](user.md) → [sysadmin.md](sysadmin.md)

**Academic Setup**: [academics.md](academics.md) → [program.md](program.md) → [enrollment.md](enrollment.md)

**Evaluation**: [assignment.md](assignment.md) → [assessment.md](assessment.md) → [evaluation.md](evaluation.md) → [certification.md](certification.md)

**Activity Tracking**: [enrollment.md](enrollment.md) → [journals.md](journals.md) → [guidance.md](guidance.md)

**Reporting**: [reports.md](reports.md)

**Official Correspondence**: [document.md](document.md)

### By Role

**Super Admin**: [sysadmin.md](sysadmin.md), [user.md](user.md)

**Academics**: [academics.md](academics.md), [program.md](program.md), [assignment.md](assignment.md)

**Supervisor**: [program.md](program.md), [enrollment.md](enrollment.md), [journals.md](journals.md), [guidance.md](guidance.md)

**Teacher**: [user.md](user.md), [evaluation.md](evaluation.md), [incident.md](incident.md)

**Student**: [enrollment.md](enrollment.md), [journals.md](journals.md), [assignment.md](assignment.md), [guidance.md](guidance.md)

---

## Implementation Guidelines

### Creating New Features
1. Identify which module(s) own the feature
2. Review `{module}.md` for business rules
3. Check `{module}-reference.md` for API structure
4. Follow submodule-based organization under `app/{Module}/`
5. Create Action, Model, Policy, and tests

### Extending Modules
1. Understand current submodule boundaries
2. Add features to existing submodules when possible
3. Create new submodules only for distinct business concepts
4. Update documentation after changes

### Cross-Module Communication
1. One module depends on another
2. Use dependency injection in Actions
3. Emit events for loose coupling
4. Avoid circular dependencies

---

## Architecture Overview

All 18 modules are vertical slices cross-cutting the 12-layer architecture defined in [architecture.md](../architecture.md):

| Layer | Name | Description |
|---|---|---|
| 1 | Infrastructure | PHP 8.4, Laravel 13, Composer/Spatie packages, npm assets |
| 2 | Persistence | Database (SQLite/MySQL), 60 migrations, config, media library, cache, queue |
| 3 | Core Contracts | LabelEnum, StatusEnum, ColorableEnum, exception hierarchy |
| 4 | Core Base Classes | BaseModel, BaseAction, BaseEntity, BasePolicy, BaseRecordManager, FormRequest, Data, SmartLogger |
| 5 | Module Models | Eloquent models (50+), UUID PKs, factories, seeders |
| 6 | Module Rules | Enums, Entities (final readonly), Data DTOs |
| 7 | Business Ops | Command Actions (mutations), Read Actions (queries), Process Actions (orchestration) |
| 8 | Authorization | Policies (36), RBAC (5 roles + 2 functional), spatie/permission |
| 9 | Communication | Events, Listeners, Notifications, Console Commands |
| 10 | HTTP Layer | Controllers, Middleware, 18 module route files |
| 11 | UI / Presentation | Livewire 4 components, Blade templates, maryUI + DaisyUI + Tailwind CSS v4 |
| 12 | Business Modules | Each module is a vertical slice of layers 1–11 |

Each module is a vertical slice cross-cutting all layers.

---

*Last synchronized with architecture at 2026-06-05*
