# Domain Documentation Index

> Last updated: 2026-06-03
> **Status:** ✅ **Complete** — All 16 domains fully documented with consistent structure.

Complete index of domain documentation for the Internara internship management system. Each domain manages a vertical slice of the application with colocated Actions, Models, Policies, and Livewire components.

---

## Documentation Structure

Each domain has two files:
- **`{domain}.md`** — Business overview, principles, context, and rules
- **`{domain}-reference.md`** — Technical API reference, file organization, and implementation details

---

## Core Domains

### 1. Core — Foundation & Infrastructure
**Purpose:** Foundational utilities, base classes, and application-wide contracts

- Overview: [core.md](core.md)
- Reference: [core-reference.md](core-reference.md)

**Key Concepts:** BaseModel, BaseAction, BasePolicy, Contracts, Exceptions

---

### 2. User — Authentication & Identity
**Purpose:** User management, authentication, profiles, notifications, and account recovery

- Overview: [user.md](user.md)
- Reference: [user-reference.md](user-reference.md)

**Key Concepts:** Login, Recovery Codes, Profiles, Notifications, Activation

**Dependencies:** Core, Admin

---

### 3. Admin — System Administration
**Purpose:** System setup, user administration, announcements, and compliance

- Overview: [admin.md](admin.md)
- Reference: [admin-reference.md](admin-reference.md)

**Key Concepts:** Setup Wizard, Account Lifecycle, GDPR Compliance, Announcements

**Dependencies:** User, Academics, Core

---

## Academic Domains

### 4. Academics — Educational Structure
**Purpose:** Schools, departments, and academic calendar management

- Overview: [academics.md](academics.md)
- Reference: [academics-reference.md](academics-reference.md)

**Key Concepts:** School, Department, AcademicYear

**Dependencies:** Core

**Used By:** Program, Enrollment, Assessment

---

### 5. Program — Internship Programs
**Purpose:** Internship/practicum programs, phases, groups, and schedules

- Overview: [program.md](program.md)
- Reference: [program-reference.md](program-reference.md)

**Key Concepts:** Internship, Phase, InternshipGroup, Schedule, DocumentRequirement

**Dependencies:** Academics, Partners, Core

**Used By:** Enrollment, Journals, Evaluation

---

### 6. Enrollment — Student Placement
**Purpose:** Student registration and phase progression tracking

- Overview: [enrollment.md](enrollment.md)
- Reference: [enrollment-reference.md](enrollment-reference.md)

**Key Concepts:** Registration, Placement, Phase Progression

**Dependencies:** User, Program, Academics, Core

**Used By:** Journals, Assessment, Evaluation

---

## Evaluation & Assessment Domains

### 7. Assessment — Evaluation Framework
**Purpose:** Rubrics, assessments, and scoring frameworks

- Overview: [assessment.md](assessment.md)
- Reference: [assessment-reference.md](assessment-reference.md)

**Key Concepts:** Rubric, Assessment, Presentation

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

## Tracking & Activity Domains

### 10. Journals — Student Activity Tracking
**Purpose:** Logbooks, attendance, schedules, and industry assessments

- Overview: [journals.md](journals.md)
- Reference: [journals-reference.md](journals-reference.md)

**Key Concepts:** Logbook, Attendance, Schedule, IndustryAssessment

**Dependencies:** Enrollment, Program, Core

**Used By:** Evaluation

---

### 11. Guidance — Mentoring & Supervision
**Purpose:** Mentor relationships, guidance, handbooks, and supervision logs

- Overview: [guidance.md](guidance.md)
- Reference: [guidance-reference.md](guidance-reference.md)

**Key Concepts:** Mentor, Supervisor, Handbook, SupervisionLog

**Dependencies:** User, Program, Core

---

### 12. Incident — Issue Tracking
**Purpose:** Incident reports and workplace concern documentation

- Overview: [incident.md](incident.md)
- Reference: [incident-reference.md](incident-reference.md)

**Key Concepts:** IncidentReport, Severity, Resolution

**Dependencies:** User, Program, Core

---

## Supporting Domains

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

**Key Concepts:** Certificate, Document, CertificateTemplate

**Dependencies:** User, Evaluation, Program, Core

---

### 15. Reports — Business Intelligence
**Purpose:** Report generation and data export

- Overview: [reports.md](reports.md)
- Reference: [reports-reference.md](reports-reference.md)

**Key Concepts:** Report, Export, Analytics

**Dependencies:** User, Program, Evaluation, Enrollment, Core

---

### 16. Settings — System Configuration
**Purpose:** System-wide settings and preferences

- Overview: [settings.md](settings.md)
- Reference: [settings-reference.md](settings-reference.md)

**Key Concepts:** Setting, Configuration, Preferences

**Dependencies:** Core

**Used By:** All domains

---

## Quick Access Guide

### By Business Function

**User Management**: [user.md](user.md) → [admin.md](admin.md)

**Academic Setup**: [academics.md](academics.md) → [program.md](program.md) → [enrollment.md](enrollment.md)

**Evaluation**: [assignment.md](assignment.md) → [assessment.md](assessment.md) → [evaluation.md](evaluation.md) → [certification.md](certification.md)

**Activity Tracking**: [enrollment.md](enrollment.md) → [journals.md](journals.md) → [guidance.md](guidance.md)

**Reporting**: [reports.md](reports.md)

### By Role

**Super Admin**: [admin.md](admin.md), [user.md](user.md), [settings.md](settings.md)

**Academics**: [academics.md](academics.md), [program.md](program.md), [assignment.md](assignment.md)

**Supervisor**: [program.md](program.md), [enrollment.md](enrollment.md), [journals.md](journals.md), [guidance.md](guidance.md)

**Teacher**: [user.md](user.md), [evaluation.md](evaluation.md), [incident.md](incident.md)

**Student**: [enrollment.md](enrollment.md), [journals.md](journals.md), [assignment.md](assignment.md), [guidance.md](guidance.md)

---

## Implementation Guidelines

### Creating New Features
1. Identify which domain(s) own the feature
2. Review [domain.md](domain.md) for business rules
3. Check [domain-reference.md](domain-reference.md) for API structure
4. Follow aggregate-based organization under `app/Domain/{Domain}/Aggregates/`
5. Create Action, Model, Policy, and tests

### Extending Domains
1. Understand current aggregate boundaries
2. Add features to existing aggregates when possible
3. Create new aggregates only for distinct business concepts
4. Update documentation after changes

### Cross-Domain Communication
1. One domain depends on another
2. Use dependency injection in Actions
3. Emit events for loose coupling
4. Avoid circular dependencies

---

## Architecture Overview

All 16 domains built on:
- **Layer 1**: Database (SQLite for development)
- **Layer 2**: Models & Relationships
- **Layer 3**: Actions & Business Logic
- **Layer 4**: Policies & Authorization
- **Layer 5**: HTTP Routes & Controllers
- **Layer 6**: Form Requests & Validation
- **Layer 7**: Livewire Components
- **Layer 8**: Blade Views

Each domain is a vertical slice cross-cutting all layers.

---

*Last synchronized with architecture at 2026-06-03*
