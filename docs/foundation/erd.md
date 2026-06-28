# Entity Relationship Diagram — Database Schema Overview

> **Last updated:** 2026-06-24
> **Total: 55 tables** (37 Domain Tables + 18 System/Package Tables)
> **Changes:** Migration audit & optimization; add monitoring_visits, backups; update internship_group_members structure; add evaluation forms architecture
## Description

Entity Relationship Diagram showing all database tables, relationships, and key fields across all 19 modules.

## Design Philosophy

The schema balances strict normalization with pragmatic consolidation. Independent tables are
preserved for entities with different lifecycles, access controls, or workflows. Redundant tables
that do not justify their own existence are eliminated.

### Key Optimizations

| Decision | Rationale |
|----------|-----------|
| `mentors`/`mentees` eliminated | Role-specific metadata fits in `profiles` with `mentor_type` column |
| `schools` eliminated | Single-tenant — school metadata stored in `settings` key-value |
| `handbooks` merged into `documents` | Unified document table with `type = 'handbook'` |
| Rubric metrics inlined | `competencies`/`indicators` stored as JSON in `rubrics.structure` |
| `absence_requests` merged into `attendances` | Absence fields inlined; separate table eliminated |
| `registration_mentor` eliminated | Mentor assignments via `internship_group_members` |
| `reports` snapshot columns consolidated | Snapshot metadata in `archived_data` JSON; score columns remain denormalized for query performance |
| `presentations` eliminated | Exam schedules managed offline by school administration |
| Policy acknowledgements to `activity_log` | Compliance audit without dedicated table |

---

## Domain Tables (37)

### System Configuration & Core (5)

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `settings` | Global key-value configuration registry. Replaces `setups` and `schools`. | `key` PK, `group`, `value`, `type`, `description` |
| `access_tokens` | Password resets, email verification, account recovery, API tokens | `user_id` FK, `token` (hashed), `token_type`, `expires_at`, `attempts` |
| `gdpr_deletion_logs` | Compliance audit for data erasure requests | `user_id`, `metadata_snapshot` JSON |
| `account_applications` | Prospective student pre-registration portal | `name`, `email`, `student_id_number`, `department_id` FK, `form_data` JSON, `status`, `rejection_reason`, `processed_by` FK, `processed_at` |
| `backups` | Encrypted database backup metadata and retention | `id` PK, `disk`, `filename`, `backup_size`, `is_protected`, `last_cleanup_at` |

### Identity & Access (2)

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `users` | Core authentication and authorization. UUID PK. | `name`, `email` UNIQUE, `username` UNIQUE, `password`, `setup_required`, `locked_at`, `locked_reason`, `status`, `is_active` |
| `profiles` | Demographic metadata. PII isolation from auth data. 1:1 with users. | `user_id` FK UNIQUE, `phone`, `address`, `bio`, `gender`, `blood_type`, `pob`, `dob`, `emergency_contact` JSON, `id_number` (NISN/NIP/Employee ID), `national_id_number` (NISN), `competence_field`, `employment_status`, `job_title`, `department_id` FK, `company_id` FK |

### Academic Structure (3)

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `departments` | Academic study programs (jurusan) | `name` UNIQUE, `description` |
| `academic_years` | School calendars | `name` UNIQUE, `start_date`, `end_date`, `is_active` |
| `documents` | Templates, handbooks, guidelines. Replaces `handbooks`. | `type` (template, handbook, policy, guideline), `slug` UNIQUE, `title`, `content`, `file_path`, `version`, `is_active`, `created_by` FK |

### Program & Enrollment (5)

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `companies` | Partner company registry (DUDI) | `name`, `industry_sector`, `address`, `phone`, `email`, `website`, `description` |
| `partnerships` | School-company formal agreements (MoU) | `company_id` FK, `agreement_number` UNIQUE, `title`, `start_date`, `end_date`, `status`, `scope`, `contact_person_name`, `contact_person_phone`, `contact_person_email`, `signed_by_school`, `signed_by_company`, `signed_at` |
| `internships` | Internship program definitions | `academic_year_id` FK, `name`, `start_date`, `end_date`, `status`, `phases` JSON, `required_document_ids` JSON, `grading_weights` JSON |
| `placements` | Company slot allocations | `company_id` FK, `internship_id` FK, `name`, `address`, `quota`, `filled_quota`, `description` |
| `registrations` | Student enrollment records | `student_id` FK, `internship_id` FK, `placement_id` FK, `start_date`, `end_date`, `status`, `proposed_company_details` JSON |

### Daily Operations (5)

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `registration_documents` | Student document uploads per requirement | `registration_id` FK, `document_id` FK, `status`, `admin_notes`, `verified_by` FK, `verified_at` |
| `attendances` | Clock-in/out with GPS and integrated absence management | `user_id` FK, `registration_id` FK, `date` UNIQUE per user, `clock_in`, `clock_out`, `clock_in_ip`, `clock_out_ip`, `clock_in_latitude`, `clock_in_longitude`, `clock_out_latitude`, `clock_out_longitude`, `status`, `absence_type`, `absence_reason`, `absence_status`, `absence_processed_by` FK, `is_verified`, `verified_by` FK |
| `logbooks` | Student daily journal entries | `user_id` FK, `registration_id` FK, `date` UNIQUE per registration, `content`, `learning_outcomes`, `status`, `mentor_feedback`, `supervisor_note`, `supervisor_id` FK |
| `supervision_logs` | Teacher site visit and virtual supervision records | `registration_id` FK, `supervisor_id` FK, `type`, `date`, `topic`, `notes`, `status`, `is_verified`, `verified_by` FK |
| `monitoring_visits` | Periodic teacher monitoring visits to company site | `registration_id` FK, `teacher_id` FK, `visit_date`, `method`, `location`, `duration_minutes`, `notes`, `student_condition`, `company_feedback`, `follow_up_actions`, `is_verified`, `verified_by` FK |

### Grading & Certification (16)

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `rubrics` | Evaluation sheet templates. Competencies and indicators as JSON. | `internship_id` FK, `name`, `structure` JSON, `created_by` FK |
| `assessments` | Student midterm, final, periodic, and industry scores | `registration_id` FK, `evaluator_id` FK, `rubric_id` FK, `assessment_type`, `score`, `scores_data` JSON, `feedback`, `finalized_at` |
| `evaluation_forms` | Reusable evaluation templates (target: teacher, supervisor, program, company, overall) | `name`, `description`, `target_type`, `is_active`, `created_by` FK |
| `evaluation_sections` | Question groupings within a form for UI organization | `form_id` FK, `title`, `description`, `order` |
| `evaluation_questions` | Individual assessment questions with scoring rules | `form_id` FK, `section_id` FK, `question_text`, `question_type` (rating_1_5, rating_1_10, yes_no, multiple_choice, text, agreement), `options` JSON, `weight`, `order`, `is_required` |
| `evaluation_responses` | Submitted evaluation instances (polymorphic target) | `form_id` FK, `evaluator_id` FK, `target_type`, `target_id`, `registration_id` FK, `overall_score`, `notes`, `submitted_at` |
| `evaluation_answers` | Individual answer/response to a question within an evaluation | `response_id` FK, `question_id` FK, `value`, `score` |
| `assignments` | Tasks published by teachers for internship cohorts | `internship_id` FK, `document_id` FK, `assignment_type` (project, report, essay), `title`, `description`, `due_date`, `status`, `created_by` FK |
| `submissions` | Student uploads/answers for assignments | `assignment_id` FK, `registration_id` FK, `student_id` FK, `content`, `metadata` JSON, `submitted_at`, `status`, `score`, `feedback`, `graded_by` FK, `verified_by` FK |
| `reports` | Final grade cards. 1:1 with registration. | `registration_id` FK UNIQUE, `supervisor_score`, `teacher_score`, `exam_score`, `final_score`, `grade_letter`, `industry_feedback`, `status`, `finalized_by` FK, `finalized_at`, `archived_data` JSON |
| `certificates` | Cryptographically signed completion credentials | `registration_id` FK, `certificate_number` UNIQUE, `qr_hash` UNIQUE, `status`, `template_content` (rendered snapshot at issuance), `issued_by` FK, `issued_at` |
| `certificate_templates` | Reusable certificate layouts and branding for issuance | `name`, `layout` (portrait/landscape), `content_template`, `is_active`, `created_by` FK |
| `incident_reports` | Welfare and safety logs with resolution workflow | `registration_id` FK, `reported_by` FK, `incident_date`, `type`, `severity`, `description`, `location`, `action_taken`, `status`, `resolved_by` FK, `resolved_at`, `resolution_notes` |
| `placement_change_requests` | Company switch requests with approval workflow | `registration_id` FK, `from_placement_id` FK, `to_placement_id` FK, `reason`, `requested_by` FK, `status`, `processed_by` FK, `processed_at`, `rejection_reason` |

### Communication & Cohorts (3)

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `announcements` | System-wide notice boards with role targeting | `created_by` FK, `title`, `message`, `type`, `status`, `target_roles` JSON, `scheduled_at`, `link` |
| `internship_groups` | Cohorts/teams within a program. Associates students/mentors with placement. | `internship_id` FK, `placement_id` FK, `name`, `description`, `is_active` |
| `internship_group_members` | Cohort membership: students or mentors. Polymorphic pivot via registration_id/user_id. | `internship_group_id` FK (NOT NULL), `registration_id` FK (nullable, student reference), `user_id` FK (nullable, mentor reference), `joined_at` |

---

## Package/Framework Tables (18)

These are managed by Laravel, spatie packages, and core middleware:

| # | Table | Package | Purpose |
|---|-------|---------|---------|
| 1 | `password_reset_tokens` | Laravel Core | Password reset token storage |
| 2 | `sessions` | Laravel Core | Database-backed session storage |
| 3 | `cache` | Laravel Core | Cache data storage |
| 4 | `cache_locks` | Laravel Core | Atomic cache locks |
| 5 | `jobs` | Laravel Core | Queued job queue table |
| 6 | `failed_jobs` | Laravel Core | Failed job history |
| 7 | `job_batches` | Laravel Core | Batch job coordination |
| 8 | `pulse_values` | Laravel Pulse | Observability metrics (values) |
| 9 | `pulse_entries` | Laravel Pulse | Observability metrics (entries) |
| 10 | `pulse_aggregates` | Laravel Pulse | Pre-aggregated metrics |
| 11 | `media` | spatie/laravel-medialibrary | File upload metadata and associations |
| 12 | `activity_log` | spatie/laravel-activitylog | Audit trail of model changes |
| 13 | `roles` | spatie/laravel-permission | RBAC role definitions |
| 14 | `permissions` | spatie/laravel-permission | RBAC permission definitions |
| 15 | `model_has_roles` | spatie/laravel-permission | Model-to-role pivot |
| 16 | `model_has_permissions` | spatie/laravel-permission | Model-to-permission pivot |
| 17 | `role_has_permissions` | spatie/laravel-permission | Role-to-permission pivot |
| 18 | `notifications` | Laravel Database Channel | Database notification channel storage |

---

## ERD (Mermaid)

```mermaid
erDiagram
    users ||--|| profiles : "1:1"
    profiles ||--o| departments : "belongs to"
    profiles ||--o| companies : "supervisor company"
    users ||--oN access_tokens : "has"
    academic_years ||--oN internships : "schedules"
    companies ||--oN partnerships : "has agreements"
    internships ||--oN placements : "has slots"
    companies ||--oN placements : "offers placement"
    placements ||--oN registrations : "receives students"
    registrations ||--|| reports : "generates grade card"
    registrations ||--oN registration_documents : "requires docs"
    documents ||--oN registration_documents : "uploaded for"
    registrations ||--oN attendances : "clocks in/out"
    registrations ||--oN logbooks : "records daily log"
    registrations ||--oN supervision_logs : "supervised in"
    registrations ||--oN assessments : "evaluated in"
    registrations ||--oN monitoring_visits : "monitored in"
    registrations ||--oN submissions : "submits assignments"
    registrations ||--oN certificates : "receives credentials"
    registrations ||--oN incident_reports : "reports incidents"
    registrations ||--oN placement_change_requests : "requests change"
    internships ||--oN rubrics : "defines assessment rubrics"
    rubrics ||--oN assessments : "guides evaluation"
    internships ||--oN assignments : "publishes tasks"
    assignments ||--oN submissions : "has submissions"
    internships ||--oN internship_groups : "has cohorts"
    placements ||--oN internship_groups : "groups for placement"
    internship_groups ||--oN internship_group_members : "has members"
    registrations ||--oN internship_group_members : "student member"
    users ||--oN internship_group_members : "mentor member"
    evaluation_forms ||--oN evaluation_sections : "groups questions"
    evaluation_forms ||--oN evaluation_questions : "contains questions"
    evaluation_sections ||--oN evaluation_questions : "categorizes"
    evaluation_forms ||--oN evaluation_responses : "receives submissions"
    evaluation_responses ||--oN evaluation_answers : "captures answers"
    evaluation_questions ||--oN evaluation_answers : "receives scores"
    users ||--oN evaluation_responses : "submits evaluations"
    certificate_templates ||--oN certificates : "rendered from template"
```
