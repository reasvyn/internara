# Product Definition & System Boundary

> Last updated: 2026-06-05 Changes: Aligned terminology with developer-friendly paradigms and
> Action-based MVC modular structure

This document defines the core product scope, design principles, tenant topology, user personas, and
localization parameters of the **Internara** system.

---

## 1. Executive Summary

**Internara** is an open-source, self-hosted, single-tenant web application engineered specifically
to manage compulsory industrial fieldwork programs (PKL - _Praktik Kerja Lapangan_ / _industrial
placement_) for vocational upper-secondary schools and technical education institutions.

Internara digitizes and orchestrates the complete fieldwork lifecycle:

- **Intake & Readiness**: Student registration, administrative phase management, and document
  prerequisites verification.
- **Placement & Quotas**: Slot allocations at partner companies, agreement registration, and
  approval workflows.
- **Operations & Journals**: Daily geotagged clock-in/out attendance logging, reflective journals,
  and review queues.
- **Academic Assessment**: Multi-rubric grading, student assignment evaluation, and final grade
  compilation workflows.
- **Issuance**: Signed certificate template compilation, automated grade sheets generation, and
  verifier check QR codes.

Unlike typical SaaS platforms, Internara is distributed as a self-packaged codebase designed to run
entirely on school-owned private virtual servers or bare-metal setups, guaranteeing data sovereignty
and offline robustness.

---

## 2. Core Architectural Pillars

### 2.1 Private Tenancy & Data Sovereignty

- **Single-Tenant Infrastructure**: Each educational institution runs its own isolated instance.
  There is no shared database or shared compute across schools.
- **Database Agnosticism**: Internara uses SQLite by default for zero-configuration, single-file
  setups. Larger schools can switch seamlessly to MySQL, MariaDB, or PostgreSQL via standard
  environment configurations.
- **Offline-Ready Design**: Designed to work reliably over local school LANs without active internet
  connectivity. Attendance, logging, and evaluation services run locally; internet connectivity is
  only required for external email/SMS notifications or remote supervisor access.

### 2.2 Dual Supervision Model

Internara separates the supervisory roles to match educational regulations:

- **School Mentors (Teachers)**: Assigned to student cohorts to guide curriculum alignment, verify
  reflective journals, grade assignments, and compile composite scores.
- **Company Mentors (Supervisors)**: On-site industry staff verifying daily student presence,
  logging progress remarks, and grading student work performance according to the host company's
  rubrics.

### 2.3 Composite Multi-Component Evaluation

The grading engine calculates composite student outcomes based on configurable weights:

```
Final Score = (Attendance × WA) + (Journals × WJ) + (Assignments × WT) + (Supervisor Evaluation × WS) + (Report & Exam × WR)
```

_Where W represents the configurable weight for each category._

---

## 3. User Persona Matrix

Internara serves five primary operational roles:

| Role                        | Operational Scope                                                              | Core System Actions                                                                                                |
| --------------------------- | ------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------ |
| **System Administrator**    | Complete system configuration, settings management, infrastructure monitoring. | Setup school profile, NPSN, departments, academic years, user accounts, system backup, and audit logs.             |
| **School Mentor (Teacher)** | Local academic tracking, journal review, grading, company site visits.         | View assigned student lists, grade journal entries, score final reports, and compile final grade cards.            |
| **Industry Supervisor**     | On-site company tracking, daily logging, final fieldwork evaluation.           | Verify check-in/out logs, approve daily journal entries, submit the end-of-placement supervisor evaluation.        |
| **Student**                 | Active placement participation, daily logbook entry, task submissions.         | Clock-in/out, write daily reflective journals, submit assignments and final reports, view grades and certificates. |
| **Guest / Public**          | Certificate authenticity validation.                                           | Scan certificate QR codes to verify authenticity against the signed database.                                      |

---

## 4. Functional Boundary (Scope Matrix)

### 4.1 In-Scope Features

- **Academic Calendar & Phase Progression**: Support for academic cycles, enrollment phases, and
  progression rules.
- **Partnership Management**: Host company profile directory, quota tracking, slot availability, and
  active MoU tracking.
- **Geofenced/Time-bound Attendance**: Daily clock-in/out logging with lat/long tracking, absence
  requests, and status logs.
- **Reflective Logbook Workflow**: Daily student entries with a multi-step review/revision loop for
  supervisors and teachers.
- **Rubric-Based Assessments**: Dynamic scoring rubrics mapped to educational competencies and
  skills.
- **Incident Management**: Workflow for reporting, investigating, and resolving student incidents at
  host companies.
- **Secure Document Generator**: Template builder for completion certificates, attendance sheets,
  and grade records with cryptographic verification QR codes.

### 4.2 Out-of-Scope Features (System Limitations)

- **Multi-Tenant SaaS Host**: No built-in billing, tenant routing, or centralized database
  partitions.
- **General HR/ATS**: No job-board recruitment tools, payroll processing, or post-graduation job
  tracking.
- **Real-time Messaging**: No embedded chat rooms or instant messaging. Comm is handled via
  asynchronous system notifications, emails, and alerts.
- **Government Database Sync**: No real-time synchronization with government education networks
  (e.g., Dapodik in Indonesia). Data is imported/exported via CSV templates.

---

## 5. System Topology & Deployment Paths

Internara is a lightweight application designed to scale efficiently across various hardware
constraints:

```
                  +-----------------------------------+
                  |        Client Browser             |
                  +-----------------------------------+
                                    |
                                    v (HTTP / WebSockets)
                  +-----------------------------------+
                  |        Reverse Proxy (Nginx)      |
                  +-----------------------------------+
                                    |
                                    v
                  +-----------------------------------+
                  |        PHP 8.4-FPM / Reverb       |
                  +-----------------------------------+
                   /                |                \
                  /                 |                 \
                 v                  v                  v
       +---------------+    +---------------+    +---------------+
       | SQLite / DB   |    | Redis Cache   |    | Local Disk /  |
       |  Persistence  |    |  & Queue      |    | S3 Media      |
       +---------------+    +---------------+    +---------------+
```

### Supported Deployment Models:

1. **VPS / VM Setup**: PHP 8.4-FPM, Nginx, Redis for caching/queues, and a local SQLite file (or
   external MySQL database).
2. **Containerized Setup**: Standard Docker Compose stack containing application, queue workers,
   cron scheduler, Redis, and a database container.
3. **Shared Hosting**: For resource-constrained deployments, running SQLite/MySQL with `sync` queue
   processing and web-triggered cron endpoints.

---

## 6. Language & Terminology Alignment

To support local regulations while maintaining global codebase standards, the system abstracts
localized concepts into standard identifiers:

| Base Terminology              | Indonesian Translation                | Data Model Field     | Description                                          |
| ----------------------------- | ------------------------------------- | -------------------- | ---------------------------------------------------- |
| **Student National ID**       | NISN (_Nomor Induk Siswa Nasional_)   | `national_id_number` | Unique national identification code for the student. |
| **School Institutional Code** | NPSN (_Nomor Pokok Sekolah Nasional_) | `institutional_code` | Registration code of the educational institution.    |
| **Department**                | Jurusan / Kompetensi Keahlian         | `department`         | Broad study concentration or skill area.             |
| **Class**                     | Kelas / Rombel                        | `class_name`         | Grade level and cohort group.                        |
| **Supervisor**                | Pembimbing Lapangan                   | `supervisor`         | Company-assigned mentor on-site.                     |
| **School Mentor**             | Guru Pembimbing                       | `teacher`            | School-assigned academic guide.                      |
| **Fieldwork Program**         | PKL (_Praktik Kerja Lapangan_)        | `internship`         | The compulsory work placement course.                |

---

## 7. Compliance & License

Internara is open-source software distributed under the **MIT License**. Schools are granted full
rights to modify code, customize themes, and host their instances indefinitely without licensing
fees or operational limits.
