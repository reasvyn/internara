# Project Requirements — Constraints & Specifications

> **Last updated:** 2026-06-10 **Changes:** sync — initial metadata sync with new format

## Description

Functional and non-functional requirements for the Internara PKL management system, tailored to
Indonesian SMA/SMK vocational school regulations.

---

## 1. Context

Vocational schools (SMK) in Indonesia mandate PKL (_Praktik Kerja Lapangan_) for 3–6 months. A
typical medium-to-large SMK manages **500–1,000 active students** placed across **150–300 partner
companies (DUDI)** per placement period.

---

## 2. Role Model (5 Roles)

| Role        | Code          | Description                                                                               |
| ----------- | ------------- | ----------------------------------------------------------------------------------------- |
| Super Admin | `super_admin` | Unrestricted system access, infrastructure management, bypasses all permission checks     |
| Admin       | `admin`       | School-level operations: user management, programs, companies, departments                |
| Teacher     | `teacher`     | Academic supervision: journal review, assignment grading, site visits, grade compilation  |
| Student     | `student`     | Program participation: attendance, logbooks, assignments, certificate download            |
| Supervisor  | `supervisor`  | Industry-side supervision: attendance verification, journal review, competency evaluation |

Each user is assigned exactly one role. Two additional **functional roles** (`mentor`, `mentee`) are
resolved at runtime via `Role::resolvesTo()` for business logic — never stored or used in
middleware.

---

## 3. Module Overview (19 Modules)

| #   | Module        | Purpose                                                       |
| --- | ------------- | ------------------------------------------------------------- |
| 1   | Core          | Base classes, contracts, middleware, infrastructure           |
| 2   | Auth          | Authentication, password management, recovery, RBAC           |
| 3   | User          | Identity, profiles, notifications, dashboards, account status |
| 4   | SysAdmin      | User management, announcements, audit logs, health monitoring |
| 5   | Setup         | Installation wizard, environment audit, super admin creation  |
| 6   | Settings      | System configuration, branding, feature flags, mail           |
| 7   | Academics     | School profile, departments, academic years                   |
| 8   | Program       | Internship lifecycle, phases, requirements, groups            |
| 9   | Enrollment    | Registration, placement slots, change requests                |
| 10  | Assessment    | Rubric management, competency evaluation, grading             |
| 11  | Evaluation    | Mentor feedback, company satisfaction, program quality        |
| 12  | Assignment    | Task management, submission, grading workflow                 |
| 13  | Journals      | Logbook entries, attendance, absence requests                 |
| 14  | Guidance      | Supervision logs, mentoring assignments                       |
| 15  | Incident      | Issue reporting, investigation, resolution                    |
| 16  | Partners      | Company profiles, partnership agreements                      |
| 17  | Certification | Certificate templates, batch issuance, revocation             |
| 18  | Reports       | Final grade cards, score aggregation, sign-off                |
| 19  | Document      | Document templates, handbooks, rendering pipeline             |

---

## 4. Functional Requirements

### 4.1 Multi-Company Placement

Students can be placed at any company with an active partnership. Each company defines slot quotas
per internship period. Placement supports:

- Manual assignment by admin
- Change requests submitted by students, approved by admin
- Capacity enforcement (atomic quota increment/decrement, never exceeds limit)

### 4.2 Mentor Dual-Role

Each student has two mentors:

- **School Mentor (Teacher):** Academic supervision, journal verification, assignment grading
- **Industry Supervisor:** Daily attendance verification, logbook review, competency evaluation

The Cross-Role Proxy mechanism ensures academic timelines are not blocked by industry inactivity.
Teachers can proxy for inactive supervisors after a configurable window (default 48h). See
[ADR-014: Cross-Role Proxy](../adr/adr-cross-role-proxy.md).

### 4.3 Competency Assessment

Assessment supports rubric-based evaluation with nested competencies and indicators stored as JSON.
Multiple evaluator types (`ADMIN`, `TEACHER`, `SUPERVISOR`, `SYSTEM`) can score against the same
rubric. Final assessments are immutable — corrections require a new round.

Composite score formula (configurable weights):

```
Final Score = (Supervisor × 40%) + (Teacher × 20%) + (Exam × 40%)
```

### 4.4 Certificate Issuance

Certificates are issued per student with unique serial numbers and QR verification hashes
(`SHA-256(student_id + institutional_code + final_score + issuer_key)`). The verification endpoint
exposes the authentic digital record. Certificates can be revoked (terminal state).

### 4.5 Attendance & Logbook

Daily clock-in/out with GPS coordinates. One logbook entry per day per student. Logbook follows a
`DRAFT → SUBMITTED → VERIFIED/FINALIZED` state machine with optional `REVISION_REQUIRED` revert.
Compliance monitoring notifies mentors when entries are missed (3 days → mentor, 5+ days →
coordinator).

### 4.6 Program Closure

Automated readiness checks verify: assessments finalized, submissions graded, attendance verified,
supervision logs signed, certificates issued. Program states:
`DRAFT → PUBLISHED → ACTIVE → COMPLETED → CANCELLED`.

---

## 5. Non-Functional Requirements

| Category     | Requirement                                                                    |
| ------------ | ------------------------------------------------------------------------------ |
| Performance  | Peak 1,000 concurrent clock-in writes (07:00–08:30)                            |
| Database     | SQLite WAL mode or MySQL; UUID primary keys; 54 tables (36 domain + 18 system) |
| Cache        | Redis for production, file cache for development                               |
| Queue        | Separate `default` and `documents` pipelines                                   |
| Security     | PII masking in logs, rate limiting on all auth endpoints, CSP headers          |
| Backup       | 4-hour RPO, under 1-hour RTO                                                   |
| Localization | Bilingual English/Indonesian, locale stored in session                         |

---

## 6. Security & Compliance

- **PII Redaction:** Email, phone, NISN, password, address masked in logs per PDP law (UU No.
  27/2022)
- **Rate Limiting:** Multi-layer: global (30/min/IP), per-endpoint (login 5/60s, forgot 3/3600s,
  recovery 3/300s)
- **Account Locking:** Auto-lock after 10 failed attempts
- **Audit Trail:** All mutations logged via SmartLogger (system + activity dual channel)
- **GDPR:** Deletion logging, data erasure workflows
