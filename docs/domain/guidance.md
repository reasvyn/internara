# Guidance Domain

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Consolidated Student Mentoring, Teacher/Supervisor Roles, Handbooks, and Supervision Visit Logs

## Purpose

The **Guidance** domain manages the mentoring, supervision, and reading compliance workflows for the internship program. This includes student role activation (`Mentee`), teacher/supervisor profiles (`Mentor`), student-mentor supervision logging, handbook publishing, and reader acknowledgment tracking.

It governs the daily guidance relationship. A student (`Mentee`) must be assigned to one or more `Mentor` records (school teacher and company supervisor) who monitor and verify their activities throughout the program.

---

## Design Principles

### 1. Active Supervision Relationships
- **Mentee (Student Role)**: Activated upon verified registration. Tracks progress flags like `canClockIn`, `canSubmitLogbook`, and remaining internship days.
- **Mentor (Supervisor/Teacher Role)**: Connects a user profile to mentoring duties. Tracks whether they are an industrial supervisor or school teacher.
- **Supervision Log**: Chronicles supervision interactions (visitations, phone calls, or digital syncs) between mentors and mentees. Logs are immutable once verified by administrators.

### 2. Versioned Handbooks and Acknowledgment Compliance
The system tracks reading compliance for key documents:
- **Handbooks**: Contain procedure manuals, guidelines, and policies (in Markdown format) targeted to specific audiences (All, Students only, Teachers only, or Supervisors only) with optional PDF attachments.
- **Acknowledgements**: Immutable receipts tracking who read what handbook version, including the reader's IP address and exact timestamp, guaranteeing complete traceability.

### 3. Derived Mentoring Capability
- The system resolves users to `Mentor` capabilities dynamically using their database roles (`Role::resolvesTo()`).
- This allows both host company supervisors and school teachers to execute supervision reviews and log validations without duplicating business rules.

---

## Domain Boundary

### Technical Ownership
- **Mentee States**: Activations, program durations, permission flags.
- **Mentor Profiles**: Industrial vs academic classifications, supervisor profile details.
- **Supervision Logs**: Logging visitation details, sync topics, dates, and verification states.
- **Handbooks**: Markdown content rendering, slug generation, PDF attachments.
- **Immutable Acknowledgements**: Reader logs, timestamp audits, and IP address mappings.

### Dependencies
- **Core**: Uses `BaseModel`, `BaseAction`, `BasePolicy`, and `SmartLogger` for mutation audit logs.
- **User**: Resolves identities for mentors, mentees, and handbook readers.
- **Enrollment**: Placement assignments establish the student-mentor matching context.
- **Media Library (Spatie)**: Stores uploaded PDF copies of handbooks.

---

## Domain Rules & Invariants

- **R1 — Supervision Verification Constraint**: Supervision visit logs can only transition to `VERIFIED` by an administrator or school teacher, not by industrial mentors.
- **R2 — Role-Restricted Handbook Views**: Handbook indexes automatically filter documents so users only see books matching their assigned role.
- **R3 — Immutable Acknowledgement Record**: Acknowledgements cannot be modified or deleted. Only additions are allowed.
- **R4 — Supervision Logs Scope**: Supervision log entries must lie within the academic year dates of the student's active registration.
- **R5 — Mentee State Gating**: `canSubmitLogbook` resolves to false if the registration is suspended or if the program end date has passed.

---

## Key Features

- **Handbook Publisher (Markdown)**: Create version-controlled handbooks with online Markdown preview and PDF download link.
- **Audience Filtering**: Targets handbooks specifically to students, teachers, supervisors, or system-wide users.
- **Supervision Visit Logger**: Allows school teachers to log on-site visits to company locations, recording topics discussed and student conditions.
- **Supervisor Verification Workflow**: Admins or teachers can verify supervision logs, transforming them into permanent records.
- **Mentee Progress Checkers**: Dynamic DTO evaluations on remaining internship days, status warnings, and submittal rights.
- **Handbook Reading Compliance Tracker**: Grid showing reading statistics across classes and roles for compliance checks.
- **Online Handbook Reader**: Full-screen reading viewer containing Markdown content sections and downloading features.
