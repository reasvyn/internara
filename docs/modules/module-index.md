# Module Documentation Index

> Last updated: 2026-06-10
> Changes: sync — fix migrations (41→40), models (38+→37), add BaseAuthenticatable to base classes, policy count detail

Complete index of module documentation for the Internara internship management system. Each module
manages a vertical slice of the application with colocated Actions, Models, Policies, and Livewire
components.

---

## Documentation Structure

Each module has two files:

- **`{module}.md`** — Business overview, principles, context, and rules
- **`{module}-reference.md`** — Technical API reference, file organization, and implementation
  details

---

## Core Modules

### 1. Core — Foundation & Infrastructure

**Purpose:** Foundational base classes, interfaces, contracts, abstract exception structures,
request-level middleware, plus cross-cutting concrete implementations (DTOs, enums, exceptions,
support utilities, global helpers, policy concerns, Livewire concerns)

- Overview: [core.md](core.md)
- Reference: [core-reference.md](core-reference.md)

**Key Concepts:** BaseModel, BaseAction, BasePolicy, BaseRecordManager, SmartLogger, AppIntegrity,
CsvHandler, concrete Exceptions, global enums

---

### 2. Auth — Authentication & Authorization

**Purpose:** Login, password management, account activation, recovery, RBAC, and super admin
integrity

- Overview: [auth.md](auth.md)
- Reference: [auth-reference.md](auth-reference.md)

**Key Concepts:** Login, Password Reset, Account Activation, Recovery Codes, RBAC, Super Admin

**Dependencies:** Core, User

---

### 3. User — Identity & Profiles

**Purpose:** User profiles, notifications, account status, and dashboards

- Overview: [user.md](user.md)
- Reference: [user-reference.md](user-reference.md)

**Key Concepts:** Profiles, Notifications, Account Status, Dashboards

**Dependencies:** Core, SysAdmin

---

### 4. SysAdmin — System Administration

**Purpose:** User administration, announcements, compliance, audit logging, and health monitoring

- Overview: [sysadmin.md](sysadmin.md)
- Reference: [sysadmin-reference.md](sysadmin-reference.md)

**Key Concepts:** Account Lifecycle, GDPR Compliance, Announcements, Pulse Monitoring

**Dependencies:** User, Academics, Core

---

### 5. Setup — Installation & Provisioning

**Purpose:** One-time technical installation, environment check, database provisioning, and setup
token lifecycle management

- Overview: [setup.md](setup.md)
- Reference: [setup-reference.md](setup-reference.md)

**Key Concepts:** SetupWizard, SetupEntity, EnvironmentAuditor, SystemProvisioner

**Dependencies:** Core, Academics

**Used By:** None (one-time initialization)

---

### 6. Settings — System Configuration & Branding

**Purpose:** System-wide configuration management — brand identity, color schemes, localization,
mail services, and global feature toggles

- Overview: [settings.md](settings.md)
- Reference: [settings-reference.md](settings-reference.md)

**Key Concepts:** Setting key-value store, dynamic branding, color presets, cached resolution chain

**Dependencies:** Core, Academics

**Used By:** All modules (via `setting()`, `brand()`, `app_info()` helpers)

---

## Academic Modules

### 7. Academics — Educational Structure

**Purpose:** Departments and academic calendar management

- Overview: [academics.md](academics.md)
- Reference: [academics-reference.md](academics-reference.md)

**Key Concepts:** Department, AcademicYear

**Dependencies:** Core

**Used By:** Program, Enrollment, Assessment

---

### 8. Program — Internship Programs

**Purpose:** Internship/practicum programs, timelines, and cohort student groupings (groups)

- Overview: [program.md](program.md)
- Reference: [program-reference.md](program-reference.md)

**Key Concepts:** Internship, InternshipGroup

**Dependencies:** Academics, Partners, Core

**Used By:** Enrollment, Journals, Evaluation

---

### 9. Enrollment — Student Placement

**Purpose:** Student registration, placement slot assignment, and change requests

- Overview: [enrollment.md](enrollment.md)
- Reference: [enrollment-reference.md](enrollment-reference.md)

**Key Concepts:** Registration, Placement, AccountApplication, PlacementChangeRequest

**Dependencies:** User, Program, Academics, Core

**Used By:** Journals, Assessment, Evaluation

---

## Evaluation & Assessment Modules

### 10. Assessment — Evaluation Framework

**Purpose:** Rubrics, assessments, and scoring frameworks

- Overview: [assessment.md](assessment.md)
- Reference: [assessment-reference.md](assessment-reference.md)

**Key Concepts:** Rubric, Assessment

**Dependencies:** Core

**Used By:** Evaluation

---

### 11. Evaluation — Performance Feedback

**Purpose:** Supervisor and teacher evaluations of students

- Overview: [evaluation.md](evaluation.md)
- Reference: [evaluation-reference.md](evaluation-reference.md)

**Key Concepts:** Evaluation, Scoring, Feedback

**Dependencies:** User, Assessment, Program, Core

**Used By:** Certification

---

### 12. Assignment — Course Work

**Purpose:** Assignment management and submission tracking

- Overview: [assignment.md](assignment.md)
- Reference: [assignment-reference.md](assignment-reference.md)

**Key Concepts:** Assignment, Submission, Grading

**Dependencies:** User, Program, Core

---

## Tracking & Activity Modules

### 13. Journals — Student Activity Tracking

**Purpose:** Logbooks, attendance, and absence requests

- Overview: [journals.md](journals.md)
- Reference: [journals-reference.md](journals-reference.md)

**Key Concepts:** Logbook, Attendance, AbsenceRequest

**Dependencies:** Enrollment, Program, Core

**Used By:** Evaluation

---

### 14. Guidance — Mentoring & Supervision

**Purpose:** Mentor relationships coordination and private field supervision logs

- Overview: [guidance.md](guidance.md)
- Reference: [guidance-reference.md](guidance-reference.md)

**Key Concepts:** SupervisionLog, Mentoring Assignments

**Dependencies:** User, Program, Core

---

### 15. Incident — Issue Tracking

**Purpose:** Incident reports and workplace concern documentation

- Overview: [incident.md](incident.md)
- Reference: [incident-reference.md](incident-reference.md)

**Key Concepts:** IncidentReport, Severity, Resolution

**Dependencies:** User, Program, Core

---

## Supporting Modules

### 16. Partners — Industrial Partners

**Purpose:** Company management and partnership agreements

- Overview: [partners.md](partners.md)
- Reference: [partners-reference.md](partners-reference.md)

**Key Concepts:** Company, Partnership

**Dependencies:** Core

**Used By:** Program, Guidance

---

### 17. Certification — Credentials

**Purpose:** Certificate generation and credential management

- Overview: [certification.md](certification.md)
- Reference: [certification-reference.md](certification-reference.md)

**Key Concepts:** Certificate, CertificateTemplate

**Dependencies:** User, Evaluation, Program, Core

---

### 18. Reports — Student Final Grade Card

**Purpose:** Final student grade compilation, score aggregation, and coordinator sign-off

- Overview: [reports.md](reports.md)
- Reference: [reports-reference.md](reports-reference.md)

**Key Concepts:** Grade Card, Final Grade Card, Score Aggregation

**Dependencies:** User, Program, Assessment, Enrollment, Core

---

### 19. Document — Templates & Handbooks

**Purpose:** Official document templates, correspondence generation, policy
handbooks, and compliance acknowledgements

- Overview: [document.md](document.md)
- Reference: [document-reference.md](document-reference.md)

**Key Concepts:** OfficialDocument, Handbook, DocumentRenderer

**Dependencies:** Core, User

---

## Quick Access Guide

### By Business Function

**User Management**: [user.md](user.md) → [sysadmin.md](sysadmin.md)

**Academic Setup**: [academics.md](academics.md) → [program.md](program.md) →
[enrollment.md](enrollment.md)

**Evaluation**: [assignment.md](assignment.md) → [assessment.md](assessment.md) →
[evaluation.md](evaluation.md) → [certification.md](certification.md)

**Activity Tracking**: [enrollment.md](enrollment.md) → [journals.md](journals.md) →
[guidance.md](guidance.md)

**Reporting**: [reports.md](reports.md)

**Official Correspondence**: [document.md](document.md)

### By Role

**Super Admin**: [sysadmin.md](sysadmin.md), [user.md](user.md)

**Academics**: [academics.md](academics.md), [program.md](program.md),
[assignment.md](assignment.md)

**Supervisor**: [program.md](program.md), [enrollment.md](enrollment.md),
[journals.md](journals.md), [guidance.md](guidance.md)

**Teacher**: [user.md](user.md), [evaluation.md](evaluation.md), [incident.md](incident.md)

**Student**: [enrollment.md](enrollment.md), [journals.md](journals.md),
[assignment.md](assignment.md), [guidance.md](guidance.md)

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

All 19 modules are vertical slices cross-cutting the 12-layer architecture defined in
[architecture.md](../architecture.md):

| Layer | Name              | Description                                                                                      |
| ----- | ----------------- | ------------------------------------------------------------------------------------------------ |
| 1     | Infrastructure    | PHP 8.4, Laravel 13, Composer/Spatie packages, npm assets                                        |
| 2     | Persistence       | Database (SQLite/MySQL), 39 migrations, config, media library, cache, queue                      |
| 3     | Core Contracts    | LabelEnum, StatusEnum, ColorableEnum, exception hierarchy                                        |
| 4     | Core Base Classes | BaseModel, BaseAuthenticatable, BaseAction, BaseEntity, BasePolicy, BaseRecordManager, BaseController, BaseFormRequest, BaseData, BaseEvent |
| 5     | Module Models     | Eloquent models (37), UUID PKs, factories, seeders                                                |
| 6     | Module Rules      | Enums, Entities (final readonly), Data DTOs                                                      |
| 7     | Business Ops      | Command Actions (mutations), Read Actions (queries), Process Actions (orchestration)             |
| 8     | Authorization     | Policies (29 domain + 1 Base), RBAC (5 roles + 2 functional), spatie/permission                  |
| 9     | Communication     | Events, Listeners, Notifications, Console Commands                                               |
| 10    | HTTP Layer        | Controllers, Middleware, 18 module route files                                                   |
| 11    | UI / Presentation | Livewire 4 components, Blade templates, maryUI + DaisyUI + Tailwind CSS v4                       |
| 12    | Business Modules  | Each module is a vertical slice of layers 1–11                                                   |

Each module is a vertical slice cross-cutting all layers.

---

_Last synchronized with architecture at 2026-06-10_
