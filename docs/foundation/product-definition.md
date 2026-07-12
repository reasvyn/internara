# Product Definition — Scope, Personas & System Boundary

> **Last updated:** 2026-06-10 **Changes:** sync — initial metadata sync with new format

## Description

This document defines the core product scope, design principles, user personas, system boundary,
deployment model, localization parameters, and licensing for the Internara system.

---

## 1. Executive Summary

**Internara** is an open-source, self-hosted, single-tenant web application engineered for managing
compulsory industrial fieldwork programs (PKL — _Praktik Kerja Lapangan_) at Indonesian vocational
upper-secondary schools (SMA/SMK) and technical education institutions.

The system digitizes the complete fieldwork lifecycle:

- **Foundation:** School profile, department structure, academic years, branding
- **User Lifecycle:** Registration, authentication, role-based dashboards, account recovery
- **Partnerships:** Company registry, MoU management, slot quota tracking
- **Program Management:** Internship periods, phases, document requirements, cohort groups
- **Enrollment:** Student application, registration wizard, placement assignment, change requests
- **Daily Operations:** Geotagged attendance, reflective logbook, absence requests
- **Assessment:** Competency rubrics, multi-evaluator grading, score aggregation
- **Evaluation:** Mentor feedback, company satisfaction, program quality surveys
- **Certification:** Certificate templates, batch issuance, QR-code verification
- **Reporting:** Final grade card compilation, score aggregation, coordinator sign-off
- **Closure:** Readiness checks, archival, data retention

Unlike SaaS platforms, Internara distributes as a self-packaged codebase designed to run on
school-owned infrastructure, guaranteeing data sovereignty and offline robustness.

---

## 2. Design Principles

The architecture follows the **3S Governing Doctrine**:

| Principle         | Definition                                                                                                                                   |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| **S1 — Secure**   | Protect data integrity, enforce authorization at every layer, prevent leakage. Credentials and PII are separated into distinct tables.       |
| **S2 — Sustain**  | Module colocation ensures the codebase remains maintainable across 22 modules. Actions enforce single responsibility by construction.        |
| **S3 — Scalable** | Single-tenant design eliminates tenant-ID overhead. CQRS-inspired Action triad (Command/Read/Process) keeps queries and mutations decoupled. |

---

## 3. User Personas

### Interns (Students)

Students are the primary end-users. They register for programs, submit daily logbook entries, clock
in/out with GPS verification, complete assignments, submit final reports, view grades, download
certificates, and evaluate their mentors. Each student belongs to a department (jurusan) and is
placed at a company for a defined internship period.

### Schools (Administrators & Teachers)

Administrators configure the system: school profile, departments, academic years, internship
programs, companies, partnerships, and user accounts. They manage announcements, audit logs, and
system settings.

Teachers serve as school-side mentors: they review journal entries, grade assignments, conduct site
visits (supervision logs), evaluate final reports, and compile grade cards. Teachers can bypass
inactive industry supervisors via the Cross-Role Proxy mechanism.

### Companies (Supervisors)

Industry supervisors are on-site mentors who verify daily attendance, review logbook entries, and
submit end-of-placement competency evaluations. Each supervisor is associated with a company and can
oversee multiple students.

---

## 4. System Boundary

### In-Scope

- Academic calendar and phase progression
- Company partnership management with MoU tracking
- Quota-based student placement with change request workflow
- Geofenced attendance with clock-in/out and absence management
- Reflective logbook with dual-mentor review workflow
- Rubric-based competency assessment
- Student-to-mentor evaluation
- Incident reporting and investigation
- Document template rendering and certificate generation with QR verification
- Final grade card compilation and sign-off
- Program closure readiness checks and archival

### Out-of-Scope

- Multi-tenant SaaS hosting (no billing, tenant routing, or database partitioning)
- General HR/ATS features (job boards, payroll, post-graduation tracking)
- Real-time messaging (no embedded chat; communication via notifications and email)
- Government database synchronization (Dapodik integration via CSV import/export only)

---

## 5. Deployment Model

Internara supports three deployment models:

| Model              | Description                                                   |
| ------------------ | ------------------------------------------------------------- |
| **VPS / VM**       | PHP 8.4-FPM, Nginx, optional Redis, SQLite or MySQL           |
| **Containerized**  | Docker Compose stack with queue worker, cron scheduler, Redis |
| **Shared Hosting** | SQLite with sync queue processing, cron endpoint trigger      |

The application defaults to SQLite for zero-configuration setups, with seamless migration to MySQL,
MariaDB, or PostgreSQL via environment configuration.

---

## 6. Localization

Internara targets the Indonesian education market with full bilingual support (English/Indonesian).

| Concept                   | Local Term          | Database Field       |
| ------------------------- | ------------------- | -------------------- |
| Student National ID       | NISN                | `student_id_number`  |
| School Institutional Code | NPSN                | `institutional_code` |
| Study Program             | Jurusan             | `department`         |
| Host Company              | DUDI                | `company`            |
| Fieldwork Program         | PKL                 | `internship`         |
| School Mentor             | Guru Pembimbing     | `teacher`            |
| Industry Supervisor       | Pembimbing Lapangan | `supervisor`         |

The language switcher toggles between `en` and `id` locales. Defaults are set during initial setup.

---

## 7. Licensing

Internara is open-source software distributed under the **MIT License**. Schools are granted full
rights to modify, customize, and host their own instances indefinitely without licensing fees or
operational limits.
