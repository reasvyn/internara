# Entity Relationship Diagram

> **Last updated:** 2026-06-10
>
> **Total: 48 tables** (30 Domain Tables + 18 System/Package Tables)

---

## Design Philosophy

The schema balances strict normalization with pragmatic consolidation. Independent tables are
preserved for entities with different lifecycles, access controls, or workflows. Redundant tables
that do not justify their own existence are eliminated.

### Key Optimizations

| Decision | Rationale |
|----------|-----------|
| `mentors`/`mentees` eliminated | Role-specific metadata fits in `profiles` with `mentor_type` column |
| `schools` eliminated | Single-tenant — school metadata stored in `settings` key-value |
| `handbooks` merged into `documents` | Unified document table with `type = 'policy'` |
| Rubric metrics inlined | `competencies`/`indicators` stored as JSON in `rubrics.structure` |
| `absence_requests` merged into `attendances` | Absence fields inlined; separate table eliminated |
| `registration_mentor` eliminated | Mentor assignments via `internship_group_members` |
| `reports` snapshot columns consolidated | Nine columns replaced by single `archived_data` JSON |
| `presentations` eliminated | Exam schedules managed offline by school administration |
| Policy acknowledgements to `activity_log` | Compliance audit without dedicated table |

---

## Domain Tables (31)

### System Configuration & Core (4)

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `settings` | Global key-value configuration registry. Replaces `setups` and `schools`. | `key` PK, `group`, `value`, `type`, `description` |
| `activation_tokens` | Password resets, email verification, account recovery codes | `user_id` FK, `token` (hashed), `token_type`, `expires_at` |
| `gdpr_deletion_logs` | Compliance audit for data erasure requests | `user_id`, `metadata_snapshot` JSON |
| `account_applications` | Prospective student pre-registration portal | `name`, `email`, `student_id_number`, `status` |

### Identity & Access (2)

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `users` | Core authentication and authorization. UUID PK. | `email` UNIQUE, `username` UNIQUE, `password`, `locked_at` |
| `profiles` | Demographic metadata. PII isolation from auth data. 1:1 with users. | `user_id` FK UNIQUE, `student_id_number` (NISN), `employee_id_number` (NIP), `mentor_type`, `department_id` FK, `company_id` FK |

### Academic Structure (3)

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `departments` | Academic study programs (jurusan) | `name` UNIQUE, `description` |
| `academic_years` | School calendars | `name` UNIQUE, `start_date`, `end_date`, `is_active` |
| `documents` | Templates, policy handbooks, guidelines. Replaces `handbooks`. | `type`, `slug` UNIQUE, `title`, `content`, `version`, `is_active` |

### Program & Enrollment (5)

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `companies` | Partner company registry (DUDI) | `name`, `industry_sector`, `address`, `phone`, `email` |
| `partnerships` | School-company formal agreements (MoU) | `company_id` FK, `agreement_number` UNIQUE, `status`, `start_date`, `end_date` |
| `internships` | Internship program definitions. Phases and requirements as JSON. | `academic_year_id` FK, `name`, `status`, `phases` JSON, `grading_weights` JSON |
| `placements` | Company slot allocations | `company_id` FK, `internship_id` FK, `quota`, `filled_quota` |
| `registrations` | Student enrollment records | `student_id` FK, `internship_id` FK, `placement_id` FK, `status`, `proposed_company_details` JSON |

### Daily Operations (4)

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `registration_documents` | Student document uploads per requirement | `registration_id` FK, `document_id` FK, `status`, `file_path` |
| `attendances` | Clock-in/out with GPS and integrated absence management | `user_id` FK, `registration_id` FK, `date` UNIQUE per user, `clock_in`, `clock_out`, `status`, `absence_type`, `absence_status` |
| `logbooks` | Student daily journal entries | `registration_id` FK, `author_id` FK, `date` UNIQUE per registration, `content`, `status`, `verification_details` JSON |
| `supervision_logs` | Teacher site visit and virtual supervision records | `registration_id` FK, `supervisor_id` FK, `supervision_type`, `notes` |

### Grading & Certification (9)

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `rubrics` | Evaluation sheet templates. Competencies and indicators as JSON. | `internship_id` FK, `name`, `structure` JSON |
| `assessments` | Student midterm, final, periodic, and industry scores | `registration_id` FK, `evaluator_id` FK, `rubric_id` FK, `assessment_type`, `score`, `scores_data` JSON |
| `evaluations` | Mentor performance feedback from students | `evaluator_id` FK, `target_id` FK, `overall_score`, `criteria_scores` JSON |
| `assignments` | Tasks published by teachers | `internship_id` FK, `document_id` FK, `assignment_type`, `title`, `due_date` |
| `submissions` | Student uploads for assignments | `assignment_id` FK, `registration_id` FK, `student_id` FK, `status`, `score` |
| `reports` | Final grade cards. 1:1 with registration. | `registration_id` FK UNIQUE, `supervisor_score`, `teacher_score`, `exam_score`, `final_score`, `grade_letter`, `status`, `archived_data` JSON |
| `certificates` | Cryptographically signed completion credentials | `registration_id` FK, `certificate_number` UNIQUE, `qr_hash` UNIQUE, `status` |
| `incident_reports` | Welfare and safety logs | `registration_id` FK, `reported_by` FK, `incident_date`, `severity`, `status` |
| `placement_change_requests` | Company switch requests | `registration_id` FK, `from_placement_id` FK, `to_placement_id` FK, `status` |

### Communication & Cohorts (3)

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `announcements` | System-wide notice boards | `created_by` FK, `title`, `message`, `type`, `target_roles` JSON |
| `internship_groups` | Cohorts/teams within a program | `internship_id` FK, `placement_id` FK, `name`, `is_active` |
| `internship_group_members` | Cohort membership and mentor assignment pivot. Replaces `registration_mentor`. | `internship_group_id` FK (nullable), `registration_id` FK, `mentor_id` FK, `role` |

---

## Package/Framework Tables (18)

These are managed by Laravel and core packages:

| # | Table | Package |
|---|-------|---------|
| 1 | `password_reset_tokens` | Laravel Core |
| 2 | `sessions` | Laravel Core |
| 3 | `cache` | Laravel Core |
| 4 | `cache_locks` | Laravel Core |
| 5 | `jobs` | Laravel Core |
| 6 | `failed_jobs` | Laravel Core |
| 7 | `job_batches` | Laravel Core |
| 8 | `pulse_values` | Laravel Pulse |
| 9 | `pulse_entries` | Laravel Pulse |
| 10 | `pulse_aggregates` | Laravel Pulse |
| 11 | `media` | spatie/laravel-medialibrary |
| 12 | `activity_log` | spatie/laravel-activitylog |
| 13 | `roles` | spatie/laravel-permission |
| 14 | `permissions` | spatie/laravel-permission |
| 15 | `model_has_roles` | spatie/laravel-permission |
| 16 | `model_has_permissions` | spatie/laravel-permission |
| 17 | `role_has_permissions` | spatie/laravel-permission |
| 18 | `notifications` | Laravel Database Channel |

---

## ERD (Mermaid)

```mermaid
erDiagram
    users ||--|| profiles : "1:1"
    profiles ||--o| departments : "belongs to"
    profiles ||--o| companies : "belongs to"
    users ||--oN activation_tokens : "has"
    companies ||--oN partnerships : "has"
    internships ||--oN placements : "has"
    placements ||--oN registrations : "receives"
    registrations ||--oN registration_documents : "requires"
    documents ||--oN registration_documents : "uploaded for"
    registrations ||--oN attendances : "clocks"
    registrations ||--oN logbooks : "records daily log"
    registrations ||--oN supervision_logs : "supervised in"
    rubrics ||--oN assessments : "defines criteria"
    registrations ||--oN assessments : "evaluated in"
    registrations ||--|| reports : "compiled grade card"
    registrations ||--oN certificates : "issued certificate"
    assignments ||--oN submissions : "has"
    registrations ||--oN submissions : "submits under"
    registrations ||--oN incident_reports : "associated with"
    registrations ||--oN placement_change_requests : "requests change"
    internships ||--oN internship_groups : "defines cohorts"
    internship_groups ||--oN internship_group_members : "has members"
    internship_group_members ||--o| registrations : "mentor assignment"
```
