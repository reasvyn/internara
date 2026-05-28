# Product Definition
> Last updated: 2026-05-27
> Changes: docs: comprehensive infrastructure, architecture, and conventions overhaul


## What Is Internara?

Internara is a **self-hosted, single-tenant web application** for managing compulsory industrial fieldwork programs — the structured work placement that students in vocational upper-secondary education must complete before graduation. It replaces paper-based logs, scattered spreadsheets, and manual coordination with a unified digital system that covers the full program lifecycle: registration, placement, daily attendance, reflective journaling, assignments, competency assessment, final reporting, and certification.

Designed for schools and educational institutions that run mandatory work-experience programs — particularly vocational high schools — Internara is installed on the school's own infrastructure and operated by its own staff. It is not a SaaS platform, not multi-tenant, and not centrally managed.

---

## Core Principles

### Self-Hosted, Single-Tenant

Every school runs its own instance on its own hardware. There is no shared infrastructure, no central database, no vendor-managed hosting. This means:

- **Data sovereignty** — Student records, company partnerships, and assessment data never leave the school's control.
- **Offline-capable** — The system works on a local network without internet connectivity. Core operations (attendance, logbooks, grading) continue regardless of external connectivity.
- **Simple infrastructure** — SQLite is the default database (zero configuration, one file). MySQL, MariaDB, and PostgreSQL are supported for larger deployments but never required.
- **No vendor lock-in** — The school's data is its own. Backup is a file copy. Migration between database engines is a configuration change.

### Designed for Vocational Education

Internara is purpose-built for structured industry placement programs — not general work-experience programs, not university co-op, not corporate apprenticeship management. This shapes every feature:

- **Dual supervision** — Every student has two mentors: a school-based teacher and a company-based supervisor. Both have distinct roles, permissions, and evaluation responsibilities.
- **Multi-component assessment** — A student's final grade is a weighted composite of attendance records, daily journal quality, supervisor evaluation, assignment submissions, written report, and (where applicable) a presentation exam.
- **Competency alignment** — Programs are tied to specific skill areas. Rubrics, assignments, and evaluations map to the competencies students are expected to develop.
- **Regulatory readiness** — The system produces the documentation required for program compliance: attendance summaries, signed journal logs, evaluation forms, and completion certificates.

### Globally Usable, Locally Rooted

Internara uses international terminology and supports multiple languages (Indonesian and English shipped, more via community translation). The data model uses generic identifiers — `national_id` rather than a country-specific field. The domain-driven architecture allows workflows to be adapted per installation through configuration, not code changes.

The primary design reference is the Indonesian vocational upper-secondary school system, where mandatory industry placement is a graduation requirement. However, the system is structured to accommodate placement programs in other educational systems by localizing terminology, adjusting academic calendars, and configuring assessment weights.

---

## Who It Serves

### School Administrators

The people who configure and operate the system: create user accounts, manage academic calendars, register partner companies, define program requirements, oversee placements, and monitor completion. They have full access to all operational features but do not need technical infrastructure skills — the system is configured through a web interface and a CLI installer.

### Teachers (School Mentors)

Teachers who supervise students during their placement. Each teacher is assigned a group of students and is responsible for monitoring attendance, reviewing logbook entries, grading assignments and reports, conducting site visits, and submitting assessment scores. A teacher may supervise students across multiple companies simultaneously.

### Students

Students enrolled in a placement program. They use Internara daily to clock attendance, write reflective journal entries, submit assignments and reports, view grades, send placement change requests, and download their completion certificate. Each student sees only their own data.

### Industry Supervisors (Company Mentors)

Personnel at the host organization who supervise the student on-site. They verify attendance records, review journal entries, provide informal feedback through the logbook, and submit a formal end-of-placement evaluation. Their access is limited to students placed at their organization.

---

## Scope

### In Scope

- Management of work placement programs (academic years, phases, document requirements)
- Student registration and enrollment into programs
- Company and partnership management (profiles, agreements, contact records)
- Slot-based placement with quota tracking and change requests
- Daily attendance tracking (clock-in/clock-out) with absence management
- Reflective journaling with mentor review workflow
- Task and assignment management with submission and grading
- Rubric-based competency assessment and presentation exams
- Multi-type evaluation (program quality, company quality, mentor effectiveness, overall satisfaction)
- Final report management with revision workflow
- Certificate issuance with template management and revocation
- Incident reporting and investigation tracking
- Handbook/document management with acknowledgement tracking
- Schedule and event management
- Role-based access control (five user roles with distinct permissions)
- Activity audit log
- Bilingual interface (Indonesian and English)

### Out of Scope

- Multi-tenant / SaaS deployment model
- Centralized user management across schools
- Automated sync with government education databases
- Recruitment or job placement after program completion
- General HR or talent management functionality
- Corporate work placement or apprenticeship program management
- Real-time chat or instant messaging
- Payment, invoicing, or financial management
- Alumni tracking or career services

---

## Deployment Model

Internara is designed to be installed on the school's own infrastructure. Three deployment paths are supported:

| Path | Typical Use Case |
|---|---|
| **Bare-metal / VM** | School with its own server or a dedicated VM. Nginx/Apache + PHP-FPM + MySQL/PostgreSQL + Redis. Supervisor manages queue worker, scheduler, and optional WebSocket server. |
| **Docker** | School using containerized infrastructure. Docker Compose stack with application, queue worker, scheduler, WebSocket server, nginx, database, and Redis. |
| **Shared hosting** | School with budget constraints. Uses SQLite or shared MySQL. Queue runs in `sync` mode. Cron replaced by web-callable scheduler endpoint. No WebSockets. |

The default installation uses SQLite, the `database` queue driver, and the `file` cache driver. No external services are required.

---

## Language and Localization

Internara ships with two language packs:

- **English** — The default interface language and the language of all code, documentation, and identifier naming.
- **Indonesian** — Primary localized interface for the Indonesian market, including all user-facing labels, messages, and validation text.

Additional languages can be added via Laravel's standard JSON translation files. Community contributions are welcome.

Terminology throughout the codebase and data model follows international conventions:

| Concept | Field Name | Notes |
|---|---|---|
| Student identifier | `national_id_number` | Not specific to any country's system |
| School code | `institutional_code` | Not tied to a specific national registry |
| Department/Program | `department` | Represents a field of study or competency area |
| Class/cohort | `class_name` | Represents the graduating cohort or class group |

---

## Licensing

Internara is open-source software released under the MIT License. Schools are free to install, use, modify, and distribute the software without restriction. There are no paid tiers, no feature gates, and no licensing fees.
