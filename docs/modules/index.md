# Module Index — Module Dependency Graph & Navigation

> **Last updated:** 2026-07-11 **Changes:** sync — add metadata, verify module count

Complete index of module documentation for the Internara internship management system. Each module
manages a vertical slice of the application with colocated Actions, Models, Policies, and Livewire
components.

## Documentation Structure

Each module has two files:

- **`{module}.md`** — Business overview, principles, context, and rules
- **`{module}-reference.md`** — Technical API reference, file organization, and implementation
  details

---

## Core Modules

### Core — Foundation & Infrastructure

Foundational base classes, interfaces, contracts, exception structures, middleware, and
cross-cutting implementations (DTOs, enums, exceptions, support utilities, global helpers).

- Overview: [core.md](core.md)
- Reference: [core-reference.md](core-reference.md)

**Key Concepts:** BaseModel, BaseAction, BasePolicy, BaseRecordManager, SmartLogger

**Dependencies:** None

**Used By:** All modules

---

### Auth — Authentication & Authorization

Login, password management, account activation, recovery, RBAC, and super admin integrity.

- Overview: [auth.md](auth.md)
- Reference: [auth-reference.md](auth-reference.md)

**Key Concepts:** Login, Password Reset, Account Activation, Recovery Codes, RBAC, Super Admin

**Dependencies:** Core, User

**Used By:** All modules

---

### User — Identity & Profiles

User profiles, notifications, account status, and dashboards.

- Overview: [user.md](user.md)
- Reference: [user-reference.md](user-reference.md)

**Key Concepts:** Profiles, Notifications, Account Status, Dashboards

**Dependencies:** Core, SysAdmin

**Used By:** All modules

---

### SysAdmin — System Administration

User administration, announcements, compliance, audit logging, and health monitoring.

- Overview: [sysadmin.md](sysadmin.md)
- Reference: [sysadmin-reference.md](sysadmin-reference.md)

**Key Concepts:** Account Lifecycle, GDPR Compliance, Announcements, Pulse Monitoring

**Dependencies:** User, Academics, Core

**Used By:** User

---

### Setup — Installation & Provisioning

One-time technical installation, environment check, database provisioning, and setup token
lifecycle.

- Overview: [setup.md](setup.md)
- Reference: [setup-reference.md](setup-reference.md)

**Key Concepts:** SetupWizard, SetupEntity, EnvironmentAuditor, SystemProvisioner

**Dependencies:** Core, Academics

**Used By:** None (one-time initialization)

---

### Settings — System Configuration & Branding

System-wide configuration management — brand identity, color schemes, localization, mail services,
and global feature toggles.

- Overview: [settings.md](settings.md)
- Reference: [settings-reference.md](settings-reference.md)

**Key Concepts:** Setting key-value store, dynamic branding, color presets, cached resolution chain

**Dependencies:** Core, Academics

**Used By:** All modules (via `setting()`, `brand()`, `app_info()` helpers)

---

## Academic Modules

### Academics — Educational Structure

Departments and academic calendar management.

- Overview: [academics.md](academics.md)
- Reference: [academics-reference.md](academics-reference.md)

**Key Concepts:** Department, AcademicYear

**Dependencies:** Core

**Used By:** Program, Enrollment, Assessment

---

### Program — Internship Programs

Internship/practicum programs, timelines, and cohort student groupings (groups).

- Overview: [program.md](program.md)
- Reference: [program-reference.md](program-reference.md)

**Key Concepts:** Internship, InternshipGroup

**Dependencies:** Academics, Partners, Core

**Used By:** Enrollment, Journals, Evaluation

---

### Enrollment — Student Placement

Student registration, placement slot assignment, and change requests.

- Overview: [enrollment.md](enrollment.md)
- Reference: [enrollment-reference.md](enrollment-reference.md)

**Key Concepts:** Registration, Placement, AccountApplication, PlacementChangeRequest

**Dependencies:** User, Program, Academics, Core

**Used By:** Journals, Assessment, Evaluation

---

## Evaluation & Assessment Modules

### Assessment — Evaluation Framework

Rubrics, assessments, and scoring frameworks.

- Overview: [assessment.md](assessment.md)
- Reference: [assessment-reference.md](assessment-reference.md)

**Key Concepts:** Rubric, Assessment

**Dependencies:** Core

**Used By:** Evaluation

---

### Evaluation — Generic Feedback Forms

Google Forms-like feedback collection across all PKL aspects (mentor, program, company, overall).

- Overview: [evaluation.md](evaluation.md)
- Reference: [evaluation-reference.md](evaluation-reference.md)

**Key Concepts:** Evaluation Forms, Sections, Weighted Questions, Polymorphic Targeting,
Auto-Scoring

**Dependencies:** User, Assessment, Program, Core

**Used By:** Certification

---

### Assignment — Course Work

Assignment management and submission tracking.

- Overview: [assignment.md](assignment.md)
- Reference: [assignment-reference.md](assignment-reference.md)

**Key Concepts:** Assignment, Submission, Grading

**Dependencies:** User, Program, Core

**Used By:** None

---

## Tracking & Activity Modules

### Journals — Student Activity Tracking

Logbooks, attendance, and absence requests.

- Overview: [journals.md](journals.md)
- Reference: [journals-reference.md](journals-reference.md)

**Key Concepts:** Logbook, Attendance, AbsenceRequest

**Dependencies:** Enrollment, Program, Core

**Used By:** Evaluation

---

### Guidance — Mentoring & Supervision

Mentor relationships coordination and private field supervision logs.

- Overview: [guidance.md](guidance.md)
- Reference: [guidance-reference.md](guidance-reference.md)

**Key Concepts:** SupervisionLog, Mentoring Assignments

**Dependencies:** User, Program, Core

**Used By:** None

---

### Incident — Issue Tracking

Incident reports and workplace concern documentation.

- Overview: [incident.md](incident.md)
- Reference: [incident-reference.md](incident-reference.md)

**Key Concepts:** IncidentReport, Severity, Resolution

**Dependencies:** User, Program, Core

**Used By:** None

---

## Supporting Modules

### Partners — Industrial Partners

Company management and partnership agreements.

- Overview: [partners.md](partners.md)
- Reference: [partners-reference.md](partners-reference.md)

**Key Concepts:** Company, Partnership

**Dependencies:** Core

**Used By:** Program, Guidance

---

### Certification — Credentials

Certificate generation and credential management.

- Overview: [certification.md](certification.md)
- Reference: [certification-reference.md](certification-reference.md)

**Key Concepts:** Certificate, CertificateTemplate

**Dependencies:** User, Evaluation, Program, Core

**Used By:** None

---

### Reports — Student Final Grade Card

Final student grade compilation, score aggregation, and coordinator sign-off.

- Overview: [reports.md](reports.md)
- Reference: [reports-reference.md](reports-reference.md)

**Key Concepts:** Grade Card, Final Grade Card, Score Aggregation

**Dependencies:** User, Program, Assessment, Enrollment, Core

**Used By:** Certification

---

### Document — Templates & Handbooks

Official document templates, correspondence generation, policy handbooks, and compliance
acknowledgements.

- Overview: [document.md](document.md)
- Reference: [document-reference.md](document-reference.md)

**Key Concepts:** OfficialDocument, Handbook, DocumentRenderer

**Dependencies:** Core, User

**Used By:** None

---

## Quick Access Guide

### By Business Function

- **User Management**: User → SysAdmin
- **Academic Setup**: Academics → Program → Enrollment
- **Evaluation**: Assignment → Assessment → Evaluation → Certification
- **Activity Tracking**: Enrollment → Journals → Guidance
- **Reporting**: Reports
- **Official Correspondence**: Document

### By Role

- **Super Admin**: SysAdmin, User
- **Academics**: Academics, Program, Assignment
- **Supervisor**: Program, Enrollment, Journals, Guidance
- **Teacher**: User, Evaluation, Incident
- **Student**: Enrollment, Journals, Assignment, Guidance

---

## Architecture Overview

All 19 modules are vertical slices cross-cutting the 4-layer architecture defined in
[`docs/architecture.md`](../architecture.md):

- **Layer 4 — Presentation/UI** — Livewire, Blade, maryUI/DaisyUI, Controllers, Policies, Routes,
  Console
- **Layer 3 — Business/Domain Ops** — Command/Read/Process Actions, Events, Listeners, Notifications
- **Layer 2 — Data/Persistent** — Eloquent models, Entities (final readonly), DTOs, Enums, Database,
  Config
- **Layer 1 — Framework/Infra** — PHP 8.4, Laravel 13, Core base classes, Contracts, Exceptions,
  Services
