# Entity Relationship Diagram

> **Last updated:** 2026-06-16
>
> **Total: 54 tables** (36 Domain Tables + 18 System/Package Tables)

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
| `handbooks` merged into `documents` | Unified document table with `type = 'handbook'` |
| Rubric metrics inlined | `competencies`/`indicators` stored as JSON in `rubrics.structure` |
| `absence_requests` merged into `attendances` | Absence fields inlined; separate table eliminated |
| `registration_mentor` eliminated | Mentor assignments via `internship_group_members` |
| `reports` snapshot columns consolidated | Snapshot metadata in `archived_data` JSON; score columns remain denormalized for query performance |
| `presentations` eliminated | Exam schedules managed offline by school administration |
| Policy acknowledgements to `activity_log` | Compliance audit without dedicated table |

---

## Domain Tables (36)

### System Configuration & Core (4)

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `settings` | Global key-value configuration registry. Replaces `setups` and `schools`. | `key` PK, `group`, `value`, `type`, `description` |
| `access_tokens` | Password resets, email verification, account recovery, API tokens | `user_id` FK, `token` (hashed), `token_type`, `expires_at`, `attempts` |
| `gdpr_deletion_logs` | Compliance audit for data erasure requests | `user_id`, `metadata_snapshot` JSON |
| `account_applications` | Prospective student pre-registration portal | `name`, `email`, `student_id_number`, `department_id` FK, `form_data` JSON, `status`, `rejection_reason`, `processed_by` FK, `processed_at` |

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

### Daily Operations (4)

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `registration_documents` | Student document uploads per requirement | `registration_id` FK, `document_id` FK, `status`, `admin_notes`, `verified_by` FK, `verified_at` |
| `attendances` | Clock-in/out with GPS and integrated absence management | `user_id` FK, `registration_id` FK, `date` UNIQUE per user, `clock_in`, `clock_out`, `clock_in_ip`, `clock_out_ip`, `clock_in_lat`, `clock_in_lng`, `clock_out_lat`, `clock_out_lng`, `status`, `absence_type`, `absence_reason`, `absence_status`, `absence_processed_by` FK, `is_verified`, `verified_by` FK |
| `logbooks` | Student daily journal entries | `user_id` FK, `registration_id` FK, `date` UNIQUE per registration, `content`, `learning_outcomes`, `status`, `mentor_feedback`, `supervisor_note`, `supervisor_id` FK |
| `supervision_logs` | Teacher site visit and virtual supervision records | `registration_id` FK, `supervisor_id` FK, `type`, `date`, `topic`, `notes`, `status` |

### Grading & Certification (14)

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `rubrics` | Evaluation sheet templates. Competencies and indicators as JSON. | `internship_id` FK, `name`, `structure` JSON |
| `assessments` | Student midterm, final, periodic, and industry scores | `registration_id` FK, `evaluator_id` FK, `rubric_id` FK, `assessment_type`, `score`, `scores_data` JSON |
| `evaluations` | *(deprecated)* Legacy mentor feedback — replaced by evaluation_forms + responses | `evaluator_id` FK, `evaluation_type`, `mentor_id` FK, `registration_id` FK, `target_type`, `target_id`, `overall_score`, `feedback`, `criteria_scores` JSON |
| `evaluation_forms` | Reusable evaluation templates (Google Forms-like) | `name`, `description`, `target_type`, `is_active`, `created_by` FK |
| `evaluation_sections` | Question groupings within a form | `form_id` FK, `title`, `description`, `order` |
| `evaluation_questions` | Individual questions with scoring rules | `form_id` FK, `section_id` FK, `question_text`, `question_type`, `options` JSON, `weight`, `order`, `is_required` |
| `evaluation_responses` | Submitted evaluation instances | `form_id` FK, `evaluator_id` FK, `target_type`, `target_id`, `registration_id` FK, `overall_score`, `notes`, `submitted_at` |
| `evaluation_answers` | Individual answer to a question | `response_id` FK, `question_id` FK, `value`, `score` |
| `assignments` | Tasks published by teachers | `internship_id` FK, `document_id` FK, `assignment_type`, `title`, `description`, `due_date`, `status`, `created_by` FK |
| `submissions` | Student uploads for assignments | `assignment_id` FK, `registration_id` FK, `student_id` FK, `content`, `status`, `score`, `feedback`, `graded_by` FK, `verified_by` FK, `submitted_at` |
| `reports` | Final grade cards. 1:1 with registration. | `registration_id` FK UNIQUE, `supervisor_score`, `teacher_score`, `exam_score`, `final_score`, `grade_letter`, `industry_feedback`, `status`, `finalized_by` FK, `finalized_at`, `archived_data` JSON |
| `certificates` | Cryptographically signed completion credentials | `registration_id` FK, `certificate_number` UNIQUE, `qr_hash` UNIQUE, `status`, `template_content` (rendered snapshot at issuance), `issued_by` FK, `issued_at` |
| `certificate_templates` | Reusable certificate layouts and branding for issuance | `name`, `layout`, `content_template`, `is_active`, `created_by` FK |
| `incident_reports` | Welfare and safety logs | `registration_id` FK, `reported_by` FK, `incident_date`, `severity`, `status` |
| `placement_change_requests` | Company switch requests | `registration_id` FK, `from_placement_id` FK, `to_placement_id` FK, `status` |

### Communication & Cohorts (3)

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `announcements` | System-wide notice boards | `created_by` FK, `title`, `message`, `type`, `status`, `target_roles` JSON, `scheduled_at`, `link` |
| `internship_groups` | Cohorts/teams within a program | `internship_id` FK, `placement_id` FK, `name`, `description`, `is_active` |
| `internship_group_members` | Cohort membership and mentor assignment pivot. Replaces `registration_mentor`. | `internship_group_id` FK (nullable), `registration_id` FK, `mentor_id` FK, `role`, `joined_at` |

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
    users ||--oN access_tokens : "has"
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
    evaluation_forms ||--oN evaluation_sections : "groups questions"
    evaluation_forms ||--oN evaluation_questions : "contains"
    evaluation_sections ||--oN evaluation_questions : "categorizes"
    evaluation_forms ||--oN evaluation_responses : "receives submissions"
    evaluation_responses ||--oN evaluation_answers : "captures answers"
    evaluation_questions ||--oN evaluation_answers : "receives scores"
    users ||--oN evaluation_responses : "submits evaluations"
```
