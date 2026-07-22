# Product Definition — Scope, Personas & System Boundary

> **Last updated:** 2026-07-22 **Changes:** feat — expand business problems & values, enrich
> personas/boundary/deployment/localization/licensing with rationale and market context

## Description

This document defines the core product scope, business rationale, design principles, user personas,
system boundary, deployment model, localization parameters, and licensing for the Internara system.

---

## 1. Executive Summary

**Internara** is an open-source, self-hosted, single-tenant web application engineered for managing
compulsory industrial fieldwork programs (PKL — _Praktik Kerja Lapangan_) at Indonesian vocational
upper-secondary schools (SMA/SMK) and technical education institutions.

PKL is a mandatory component of the Indonesian vocational curriculum (regulated by Kemendikbud),
requiring every SMK student to complete 3–6 months of supervised fieldwork at an approved company
(DUDI — _Dunia Usaha dan Dunia Industri_). A typical medium-to-large SMK manages **500–1,000
active students** placed across **150–300 partner companies** per placement period, generating
tens of thousands of attendance records, logbook entries, and evaluation forms. Nearly all of this
is managed today through paper forms, Excel spreadsheets, WhatsApp groups, and ad-hoc email chains.

Internara digitizes the complete fieldwork lifecycle end-to-end:

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
school-owned infrastructure, guaranteeing data sovereignty, offline robustness, and zero
recurring costs. Schools retain full control over their data — no third-party cloud dependency,
no vendor lock-in, no subscription fees.

---

## 2. Business Problems — Why This Must Exist

Indonesian vocational schools are legally required to run PKL programs, yet nearly all of them
manage the process through a patchwork of paper forms, Excel spreadsheets, WhatsApp groups, and
ad-hoc email chains. This creates systemic problems that no amount of teacher dedication can
overcome at scale.

### 2.1 The Scale Problem

A typical medium-to-large SMK manages **500–1,000 active students** placed across **150–300
partner companies (DUDI)** per placement period. Every student generates daily attendance records,
weekly logbook entries, assignment submissions, and evaluation forms. The administrative volume is
staggering:

- **500 students × 90 days** = 45,000 attendance records per period
- **500 students × 12 weekly logbooks** = 6,000 logbook entries per period
- **500 students × 3–5 assignments** = 1,500–2,500 submissions per period
- **500 students × 1 evaluation** = 500 evaluation forms per period

No coordinator team can manually process this volume without errors, delays, or burnout.

### 2.2 The Fragmentation Problem

Without a unified system, information is scattered across dozens of disconnected artifacts:

| Artifact | Owner | Problem |
| -------- | ----- | ------- |
| Excel enrollment lists | Admin | Version conflicts, no single source of truth |
| Paper attendance sheets | Teachers/Supervisors | Lost easily, no aggregate view, manual counting |
| WhatsApp logbook messages | Students → Teachers | Unsearchable, ungraded, no audit trail |
| Printed evaluation forms | Supervisors | Slow turnaround, manual score aggregation |
| Email-based change requests | Students → Admin | No tracking, no SLA, forgotten requests |
| Paper certificates | Admin | No verification mechanism, easy to forge |

When a coordinator needs to compile final grade cards, they must manually gather data from all
these sources — a process that routinely takes **2–3 weeks** of dedicated staff time per cohort.

### 2.3 The Visibility Problem

School administrators have **no real-time view** of program status:

- How many students are currently placed? (Requires counting Excel rows)
- Which companies have available slots? (Requires calling each company)
- Which students haven't submitted logbooks this week? (Requires manual comparison)
- What is the overall program completion rate? (Requires manual calculation)

This opacity leads to reactive management — problems are discovered weeks after they occur, when
remediation is too late.

### 2.4 The Compliance Problem

Indonesian education regulations (Peraturan Menteri Pendidikan) require schools to maintain
complete, auditable records of PKL activities for accreditation. Manual systems fail this
requirement consistently:

- Attendance records must be signed by both teacher and supervisor — paper forms often lack
  one or both signatures
- Logbook entries must demonstrate reflective learning — WhatsApp messages don't qualify
- Evaluation forms must follow standardized rubrics — ad-hoc grading doesn't meet standards
- Certificate verification must be possible by third parties — paper certificates can't be
  verified remotely

Schools that cannot produce these records during accreditation audits face **program suspension
or grade penalties**, directly affecting student graduation and institutional standing.

### 2.5 The Equity Problem

Teacher workload is distributed unevenly. Teachers who are skilled at paperwork get assigned
coordinator roles and spend **60–80% of their time on administration** instead of actual mentoring.
Meanwhile, students at companies without active supervisors receive no feedback for weeks.

The Cross-Role Proxy mechanism (teacher can proxy inactive supervisor) exists specifically because
this problem is endemic — industry supervisors frequently disengage, and the school needs a
fallback that doesn't break the audit trail.

---

## 3. Business Values — Why Internara Solves This

Internara replaces the fragmented manual ecosystem with a single, structured system that provides
compounding value across the entire PKL lifecycle.

### 3.1 Single Source of Truth

Every stakeholder sees the same data in real-time:

- Admins see enrollment status, placement capacity, and company slots
- Teachers see their assigned students' logbooks, attendance, and grades
- Supervisors see their assigned students' daily attendance and logbook entries
- Students see their own progress, grades, and certificates

No more Excel version conflicts. No more "I sent that form last week but haven't heard back."

### 3.2 Automated Workflow Enforcement

Business rules are enforced by the system, not by memory:

- Students cannot register for closed internships (OpenForRegistration rule)
- Attendance cannot be backdated beyond allowed window
- Logbook entries must pass quality checks before submission
- Grade cards cannot be compiled until all evaluations are complete
- Certificates cannot be issued until all prerequisites are met

This eliminates the "we forgot to check" category of errors entirely.

### 3.3 Real-Time Program Visibility

Administrators gain instant visibility into program health:

- Dashboard shows enrollment count, placement rate, completion rate
- Company slot capacity is tracked in real-time (no more calling companies)
- Student progress is visible per-cohort, per-company, per-department
- Attendance anomalies are detected automatically (students with low attendance flagged)

This shifts management from reactive to proactive — problems are caught in days, not weeks.

### 3.4 Audit-Ready Records

Every action is logged with timestamps, user attribution, and IP address via SmartLogger:

- Attendance records are GPS-tagged and timestamped
- Logbook entries are immutable once submitted (status machine)
- Grade calculations are traceable to individual evaluations
- Certificate issuance generates QR-verifiable digital credentials

Accreditation audits become a matter of exporting reports, not reconstructing history from
paper fragments.

### 3.5 Reduced Administrative Burden

Tasks that previously required hours of manual work become instant:

| Task | Manual Time | With Internara |
| ---- | ----------- | -------------- |
| Compile 500 student grade cards | 2–3 weeks | Instant (automated aggregation) |
| Check attendance for all students | 1–2 days | Real-time dashboard |
| Verify certificate authenticity | Phone call to school | QR scan → instant verification |
| Track company MoU expiry dates | Spreadsheet + memory | Automated alerts |
| Generate placement change requests | Paper form + email chain | In-app workflow with SLA tracking |

### 3.6 Data Sovereignty

Schools own their data completely:

- Self-hosted on school infrastructure (no third-party cloud dependency)
- No subscription fees, no vendor lock-in, no data export limitations
- Offline-capable design (works on school LAN without internet)
- Full database access for custom reporting or government data submission

---

## 4. Design Principles

The architecture follows the **3S Governing Doctrine** — three non-negotiable principles that
guide every architectural decision:

| Principle         | Definition                                                                                                                                   |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| **S1 — Secure**   | Protect data integrity, enforce authorization at every layer, prevent leakage. Credentials and PII are separated into distinct tables.       |
| **S2 — Sustain**  | Module colocation ensures the codebase remains maintainable across 18 modules. Actions enforce single responsibility by construction.        |
| **S3 — Scalable** | Single-tenant design eliminates tenant-ID overhead. CQRS-inspired Action triad (Command/Read/Process) keeps queries and mutations decoupled. |

**Why these three?** The PKL management domain has unique constraints:

- **Security is existential** — student PII (NISN, personal data, attendance with GPS coordinates)
  and academic records (grades, certificates) must be protected. A data breach could expose
  minors' location data and academic standing. The 3S doctrine makes security a first-class
  architectural concern, not an afterthought.

- **Sustainability is practical** — with 18 modules spanning the full PKL lifecycle, code
  organization directly affects maintainability. Module colocation (each module owns its
  Models, Actions, Livewire, Events, Policies) prevents the spaghetti dependencies that
  plague monolithic educational software. The Action Triad pattern (Command/Read/Process)
  enforces single responsibility at the class level — you cannot write a 500-line God Action.

- **Scalability is realistic** — "scalable" does not mean "handle millions of users." It means
  the architecture doesn't collapse under the weight of its own complexity as features are added.
  Single-tenant design eliminates the overhead of multi-tenant isolation (no tenant-ID columns,
  no tenant-scoped queries, no cross-tenant data leakage risk). The CQRS-inspired Action triad
  keeps read and write paths independent, so optimization of one doesn't break the other.

---

## 5. User Personas

### Interns (Students)

Students are the primary end-users — typically 16–18 years old vocational students completing
their mandatory PKL requirement. They register for programs, submit daily logbook entries, clock
in/out with GPS verification, complete assignments, submit final reports, view grades, download
certificates, and evaluate their mentors. Each student belongs to a department (jurusan) and is
placed at a company for a defined internship period.

Students interact with the system daily (attendance, logbook) and weekly (assignments, evaluations).
They are primarily mobile users — the system must work on smartphones with intermittent connectivity.
Low digital literacy is common, so the interface must be simple and forgiving (clear labels, minimal
required fields, helpful error messages in Bahasa Indonesia).

### Schools (Administrators & Teachers)

Administrators configure the system: school profile, departments, academic years, internship
programs, companies, partnerships, and user accounts. They manage announcements, audit logs, and
system settings. A single admin team (typically 2–5 people) manages the entire PKL operation for
hundreds of students. They need batch operations (CSV import, bulk actions) and real-time dashboards
to manage at scale.

Teachers serve as school-side mentors: they review journal entries, grade assignments, conduct site
visits (supervision logs), evaluate final reports, and compile grade cards. A school typically has
10–30 teachers assigned as PKL mentors, each overseeing 15–30 students. Teachers are stretched thin
— they teach full-time and mentor part-time. The system must minimize their administrative overhead.
Teachers can bypass inactive industry supervisors via the Cross-Role Proxy mechanism, which is
critical because industry supervisors frequently disengage mid-program.

### Companies (Supervisors)

Industry supervisors are on-site mentors who verify daily attendance, review logbook entries, and
submit end-of-placement competency evaluations. Each supervisor is associated with a company and can
oversee multiple students (typically 3–10). Supervisors have the least time for system interaction —
they are working professionals who mentor as a secondary responsibility. The system must make their
tasks as quick as possible: one-tap attendance verification, minimal-form logbook review, and
guided evaluation forms. Supervisors may not be tech-savvy and may only access the system
occasionally (weekly or less), so the interface must be self-explanatory without training.

---

## 6. System Boundary

### In-Scope

The system covers the complete PKL lifecycle from institutional setup through program closure:

- **Institutional Setup:** School profile, department structure, academic years, branding, system
  configuration — the foundation that all other modules reference
- **Partnership Management:** Company registry with MoU tracking, partnership lifecycle (active,
  expired, suspended), slot quota tracking per company
- **Program Management:** Internship period definition, phased progression (registration → active →
  evaluation → closure), document requirements, cohort group assignment
- **Enrollment Pipeline:** Guest-to-student account application with atomic provisioning, student
  registration wizard, placement assignment with capacity enforcement, change request workflow
- **Daily Operations:** GPS-geofenced attendance with clock-in/out, absence requests with approval
  workflow, reflective logbook with dual-mentor review (teacher + supervisor)
- **Assessment & Grading:** Competency rubrics, multi-evaluator grading (teacher + supervisor),
  weighted score aggregation, assignment submission and grading
- **Evaluation:** Student-to-mentor feedback, company satisfaction surveys, program quality assessment
- **Certification:** PDF certificate templates, batch issuance, QR-code cryptographic verification
- **Reporting:** Final grade card compilation, score aggregation, coordinator sign-off, audit trail export
- **Program Closure:** Readiness checks (all prerequisites verified), archival of student accounts,
  data retention per policy

### Out-of-Scope

These are explicitly excluded to maintain focus on the PKL management domain:

- **Multi-tenant SaaS hosting** — No billing, tenant routing, or database partitioning. Internara
  is designed for single-school deployment, not commercial hosting. Schools self-host.
- **General HR/ATS features** — No job boards, payroll integration, or post-graduation tracking.
  The system scope ends at PKL program completion and certificate issuance.
- **Real-time messaging** — No embedded chat or instant messaging. Communication happens via
  structured notifications and email. Real-time chat would require WebSocket infrastructure
  and would distract from the structured workflow.
- **Government database synchronization** — No direct Dapodik or NSP integration. Data export for
  government reporting is via CSV import/export only, keeping the integration surface minimal.
- **Mobile-native applications** — The system is a responsive web application, not a native iOS/Android
  app. This keeps the deployment model simple (no app store submissions, no version fragmentation)
  and works across all devices via the browser.

---

## 7. Deployment Model

Internara supports three deployment models, each targeting a different school infrastructure tier:

| Model              | Target Infrastructure | Stack                                                    | Trade-offs                                |
| ------------------ | --------------------- | -------------------------------------------------------- | ----------------------------------------- |
| **Shared Hosting** | Budget SMK (Rp 100K–500K/mo) | PHP 8.4, SQLite, sync queue, cron endpoint    | No background jobs, single-process, simplest |
| **VPS / VM**       | Mid-range SMK (Rp 200K–1M/mo) | PHP 8.4-FPM, Nginx, SQLite/MySQL, optional Redis | Full async queue, cron scheduler, recommended |
| **Containerized**  | Tech-savvy schools / Dinas PKL | Docker Compose: app + queue + scheduler + Redis | Most robust, easiest scaling, requires Docker |

**Why three tiers?** Indonesian schools have vastly different IT budgets and capabilities. A small
SMK in a rural area may only have shared hosting (Rp 200K/month), while a metropolitan SMK or
provincial education office (Dinas) may run proper servers. The tiered approach ensures Internara
is accessible to all schools, not just the well-funded ones.

The application defaults to **SQLite** for zero-configuration setups — no database server to install,
no credentials to configure. Schools can start using Internara immediately after `php artisan setup:install`.
When they outgrow SQLite (typically beyond ~500 concurrent users), migration to MySQL, MariaDB, or
PostgreSQL is a one-line `.env` change with no code modifications.

**Offline robustness** is a core design constraint. Many schools have unreliable internet. The system
must function on a school LAN without external connectivity — hence self-hosted, no cloud dependencies,
no CDN requirements, and no third-party API calls in the critical path.

---

## 8. Localization

Internara targets the Indonesian education market with full bilingual support (English/Indonesian).
Localization is not cosmetic — it is a regulatory requirement. Indonesian government reporting
(Dapodik, NSP) uses Bahasa Indonesia exclusively, and school administrators, teachers, and
students overwhelmingly prefer Indonesian for daily operations. English is the development and
technical documentation language.

| Concept                   | Local Term          | Database Field       | Regulatory Context |
| ------------------------- | ------------------- | -------------------- | ------------------ |
| Student National ID       | NISN                | `student_id_number`  | Required for Dapodik submission |
| School Institutional Code | NPSN                | `institutional_code` | Required for accreditation |
| Study Program             | Jurusan             | `department`         | SMK-specific (SMA uses "Program Studi") |
| Host Company              | DUDI                | `company`            | Kemendikbud terminology |
| Fieldwork Program         | PKL                 | `internship`         | Mandatory for all SMK students |
| School Mentor             | Guru Pembimbing     | `teacher`            | Assessed during accreditation |
| Industry Supervisor       | Pembimbing Lapangan | `supervisor`         | Must be registered with company |

The language switcher toggles between `en` and `id` locales. Defaults are set during initial setup.
All user-facing strings use the `__()` translation helper, ensuring consistent localization across
the entire UI. Translation keys exist in both `lang/en/` and `lang/id/` directories.

---

## 9. Licensing

Internara is open-source software distributed under the **MIT License**. Schools are granted full
rights to modify, customize, and host their own instances indefinitely without licensing fees or
operational limits.

**Why MIT?** The target audience (Indonesian vocational schools) cannot afford commercial SaaS
pricing for PKL management. MIT licensing removes all financial barriers to adoption. Schools
can fork the project, customize it for local requirements (e.g., add Dapodik integration,
modify certificate templates for regional standards), and deploy without any legal constraints.
The open-source model also enables community contributions — schools that improve the system
can contribute those improvements back, creating a shared resource for all Indonesian SMKs.
