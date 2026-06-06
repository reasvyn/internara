# Entity Relationship Diagram — Internara (Balanced Design)

> **Target: 52 tables** (34 Domain Tables + 18 System/Package Tables).  
> **Reduction:** Balanced ~25% reduction in table count (from 69/62 tables down to 52).  
> **Goal:** Balance Separation of Concerns (SoC) with database efficiency, preventing monolithic tables while enforcing strict module context boundaries.

---

## 1. Balanced Design Philosophy & Context Boundaries

This design maintains a pragmatic middle ground between **strict normalization** and **table consolidation**. It preserves independent tables for entities with different lifecycles, access controls, or workflows, while pruning redundant tables that do not justify their own existence.

### Key Boundary Corrections

* **Elimination of `presentations` Table:** Presentation exam schedules and examiner panels are determined manually by individual school administrations. Removing this operational concern eliminates the `presentations` table entirely.
* **Redefinition of `reports` to "Final Grade / Report Card" (*Rapor PKL*):**
  * **Document Drafts are Assignments:** The student's final report document draft is treated as a regular coursework submission. The teacher creates an assignment for it in the **`assignments`** table, and the student submits the document in the **`submissions`** table.
  * **`reports` as the Final Grade Card:** The `reports` table is redefined to act as the final student report card (*Rapor PKL*). It aggregates all grading components (supervisor score, teacher score, exam score), calculates the final composite score based on the program's weights, stores overall industry feedback, and locks the student's grade records upon final sign-off.
  * **Eligibility for Certificates:** A finalized record in `reports` acts as the trigger for generating the student's digital **`certificates`**.

### Key Separation of Concerns Preserved

* **`users` ──1:1──> `profiles`:** Kept separate. Auth data (username, passwords, locking state) stays clean and fast. Sensitive Personally Identifiable Information (PII) like national ID numbers, NISN, NIP, place of birth, and date of birth are isolated to the `profiles` table.
* **`companies` ──1:N──> `partnerships`:** Kept separate. Partnerships have an independent contract lifecycle (agreement numbers, renewal dates, signatures, status) that changes over time, while the host company profile remains static.
* **`logbooks` vs. `supervision_logs`:** Kept separate. Student daily diaries (`logbooks`) and teacher field visit logs (`supervision_logs`) serve different users, have different data models, and must respect strict authorization boundaries.
* **`assessments` vs. `evaluations`:** Kept separate. Student academic grading (`assessments`) is distinct from mentor performance surveys (`evaluations`).

### Areas Optimized (Redundancies Pruned)

* **Eliminated `mentors` and `mentees` tables:** Student notes and supervisor types fit naturally into the `profiles` table. Deleting these tables eliminates redundant 1:1:1 query chains (`users` ➔ `profiles` ➔ `mentees`).
* **Eliminated `schools` and `setups` tables:** Internara is single-tenant (one school per instance). All setup wizard logs and school-wide metadata (NPSN, address, principal name) are stored as configuration values under namespaces in the `settings` table.
* **Merged `handbooks` into `documents`:** Handbooks and guidelines are unified under the `documents` table with `type = 'policy'`, using `document_acknowledgements` for compliance tracking.
* **Inlined Rubric Metrics (`competencies` and `indicators`):** Rather than using a complex 3-table join for static evaluation sheets, the rubric structure is stored as a JSON column in `rubrics`. This preserves historical grading integrity if templates are edited later.
* **Inlined Internship Phases & Requirements:** Stored as JSON arrays inside `internships` for simple program setup.

---

## 2. Before vs. After Schema Summary

| Original Area | Original Tables | Balanced Table | Strategy & Justification |
|---|---|---|---|
| **Framework & Package** | 18 | **18** | Retained for framework completeness. Note that transient tables (`sessions`, `cache`, `jobs`, `pulse_*`) can be offloaded to memory cache drivers (e.g. Redis) in production. |
| **Core Setup & Config** | 2 | **1** (`settings`) | Merge `setups` wizard state and `schools` single-tenant profile into the key-value `settings` table. |
| **Identity & Access** | 6 | **4** (`users`, `profiles`, `activation_tokens`, `gdpr_deletion_logs`) | Merge `profiles` is retained 1:1 with `users`. Role-specific metadata is merged into `profiles`, eliminating `mentors` and `mentees` tables. |
| **Academic & Documents**| 8 | **5** (`departments`, `academic_years`, `documents`, `document_acknowledgements`, `account_applications`) | Merge `handbooks` and `handbook_acknowledgements` into unified `documents` and `document_acknowledgements` by introducing a document `type` column. |
| **Program & Enrollment**| 10 | **6** (`companies`, `partnerships`, `internships`, `placements`, `registrations`, `registration_mentor`) | Keep `partnerships` separate from `companies`. Merge `internship_phases` and requirements pivots into `internships` (as JSON columns). Keep group pivot and `registration_mentor` pivot. |
| **Daily Operations** | 6 | **6** (`attendances`, `absence_requests`, `schedules`, `logbooks`, `supervision_logs`, `registration_documents`) | Keep daily operations separate: daily attendance, leave requests, logs, student journals, and teacher supervision logs. |
| **Grading & Certification**| 11 | **9** (`rubrics`, `assessments`, `evaluations`, `assignments`, `submissions`, `reports`, `certificates`, `incident_reports`, `placement_change_requests`) | Merge `competencies` and `indicators` into `rubrics` structure JSON. Keep assessments, evaluations, reports, certificates, incidents, and change requests separate. Redefine `reports` as the final Grade Card (*Rapor PKL*) and delegate the report document drafts to `assignments`. Delete `presentations`. |
| **Communication & Cohorts**| 1 | **3** (`announcements`, `internship_groups`, `internship_group_members`) | Retain announcements and group/cohort management tables. |
| **Total Tables** | **69 / 62** | **52** | **Pragmatic ~25% Reduction** (34 Domain Tables + 18 Package/Framework Tables) |

---

## 3. Entity Relationship Map (ERD)

```mermaid
erDiagram
    settings {
        string key PK
        string group
        text value
    }
    
    users {
        uuid id PK
        string email UNIQUE
        string username UNIQUE
        string password
        string name
        boolean is_active
        timestamp locked_at
        string locked_reason
        remember_token string
    }

    profiles {
        uuid id PK
        uuid user_id FK "1:1"
        string phone
        text address
        string gender
        string blood_type
        string pob
        date dob
        json emergency_contact
        string student_id_number UNIQUE "NISN"
        string employee_id_number UNIQUE "NIP/NIDN"
        string mentor_type "school_teacher | industry_supervisor | null"
        text internal_notes
        uuid department_id FK
        uuid company_id FK
    }

    activation_tokens {
        uuid id PK
        uuid user_id FK
        string token
        string token_type
        timestamp expires_at
        integer attempts
    }

    departments {
        uuid id PK
        string name UNIQUE
        text description
    }

    academic_years {
        uuid id PK
        string name UNIQUE
        date start_date
        date end_date
        boolean is_active
    }

    documents {
        uuid id PK
        string type "template | policy | guideline"
        string slug UNIQUE
        string title
        text content
        string file_path
        integer version
        boolean is_active
        json metadata
        uuid created_by FK
    }

    document_acknowledgements {
        uuid id PK
        uuid user_id FK
        uuid document_id FK
        timestamp acknowledged_at
        string ip_address
    }

    companies {
        uuid id PK
        string name
        string address
        string phone
        string email
        string website
        string industry_sector
    }

    partnerships {
        uuid id PK
        uuid company_id FK
        string agreement_number UNIQUE
        string title
        date start_date
        date end_date
        string status
        string scope
        string contact_person
        timestamp signed_at
    }

    internships {
        uuid id PK
        uuid academic_year_id FK
        string name
        date start_date
        date end_date
        string status
        json phases "stages: Observation, Field Practice, etc."
        json required_document_ids "list of document templates required"
        json grading_weights "weights for supervisors, teachers, exam"
    }

    placements {
        uuid id PK
        uuid company_id FK
        uuid internship_id FK
        string name
        integer quota
        integer filled_quota
    }

    registrations {
        uuid id PK
        uuid student_id FK "users.id"
        uuid internship_id FK
        uuid placement_id FK
        date start_date
        date end_date
        string status "pending | approved | active | completed | terminated"
        json proposed_company_details
    }

    registration_mentor {
        uuid registration_id FK
        uuid user_id FK "users.id (mentor/supervisor)"
        string role "school_teacher | industry_supervisor"
    }

    registration_documents {
        uuid id PK
        uuid registration_id FK
        uuid document_id FK
        string status "pending | submitted | verified | rejected"
        string file_path
        uuid verified_by FK "users.id"
    }

    attendances {
        uuid id PK
        uuid user_id FK
        uuid registration_id FK
        date date
        time clock_in
        time clock_out
        string status "present | late | early_out | absent"
        json verification_details "signatures, GPS tags, photo paths"
    }

    absence_requests {
        uuid id PK
        uuid user_id FK
        uuid registration_id FK
        date start_date
        date end_date
        string reason_type "sick | permission | other"
        text reason_description
        string attachment_path
        string status
        uuid processed_by FK
    }

    logbooks {
        uuid id PK
        uuid registration_id FK
        uuid author_id FK "users.id"
        date date
        text content
        string learning_outcomes
        string status "draft | submitted | verified"
        json verification_details "mentor feedback"
    }

    supervision_logs {
        uuid id PK
        uuid registration_id FK
        uuid supervisor_id FK "users.id"
        date date
        string supervision_type "site_visit | online | phone"
        text notes
        string status
        boolean is_verified
        uuid verified_by FK
    }

    schedules {
        uuid id PK
        uuid internship_id FK
        uuid created_by FK
        string title
        datetime start_at
        datetime end_at
        string type "orientation | supervision | workshop | exam"
        string location
    }

    rubrics {
        uuid id PK
        uuid internship_id FK
        string name
        json structure "competencies & indicators nested with weights"
    }

    assessments {
        uuid id PK
        uuid registration_id FK
        uuid evaluator_id FK "users.id"
        uuid rubric_id FK
        string assessment_type "midterm | final | periodic | industry"
        float score
        json scores_data "points mapped to indicators"
        text feedback
        timestamp finalized_at
    }

    evaluations {
        uuid id PK
        uuid evaluator_id FK "users.id"
        uuid target_id FK "users.id (mentor/supervisor)"
        float overall_score
        json criteria_scores
        text feedback
    }

    assignments {
        uuid id PK
        uuid internship_id FK
        uuid document_id FK
        string assignment_type "report | essay | project"
        string title
        text description
        datetime due_date
        string status
    }

    submissions {
        uuid id PK
        uuid assignment_id FK
        uuid registration_id FK
        uuid student_id FK
        text content
        json metadata "attachments list"
        string status "draft | submitted | graded"
        float score
        uuid graded_by FK
    }

    reports {
        uuid id PK
        uuid registration_id FK UNIQUE
        float supervisor_score
        float teacher_score
        float exam_score
        float final_score
        string grade_letter "e.g. A, B, C"
        text industry_feedback "qualitative feedback from host company"
        string status "draft | finalized"
        uuid finalized_by FK "users.id"
        timestamp finalized_at
    }

    certificates {
        uuid id PK
        uuid registration_id FK
        string certificate_number UNIQUE
        string qr_hash UNIQUE
        string status "issued | revoked"
        text template_content
        uuid issued_by FK
        timestamp issued_at
    }

    incident_reports {
        uuid id PK
        uuid registration_id FK
        uuid reported_by FK
        date incident_date
        string type
        string severity "low | medium | high | critical"
        text description
        string status
    }

    placement_change_requests {
        uuid id PK
        uuid registration_id FK
        uuid from_placement_id FK
        uuid to_placement_id FK
        text reason
        string status
        uuid processed_by FK
    }

    announcements {
        uuid id PK
        uuid created_by FK
        string title
        text message
        string type "info | warning | urgent"
        json target_roles "['student', 'mentor', 'admin']"
    }

    internship_groups {
        uuid id PK
        uuid internship_id FK
        uuid placement_id FK
        string name
        boolean is_active
    }

    internship_group_members {
        uuid id PK
        uuid internship_group_id FK
        uuid registration_id FK
        uuid user_id FK "mentor/supervisor user"
        string role
        timestamp joined_at
    }

    users ||--|| profiles : "has 1:1"
    profiles ||--o| departments : "belongs to"
    profiles ||--o| companies : "belongs to (for supervisors)"
    users ||--oN activation_tokens : "has"
    users ||--oN document_acknowledgements : "acknowledges"
    documents ||--oN document_acknowledgements : "is acknowledged in"
    companies ||--oN partnerships : "has"
    internships ||--oN placements : "has"
    placements ||--oN registrations : "receives"
    registrations ||--oN registration_mentor : "assigned to"
    users ||--oN registration_mentor : "acts as mentor in"
    registrations ||--oN registration_documents : "requires"
    documents ||--oN registration_documents : "uploaded for"
    registrations ||--oN attendances : "clocks"
    registrations ||--oN absence_requests : "requests leave for"
    registrations ||--oN logbooks : "records daily log"
    registrations ||--oN supervision_logs : "supervised in"
    rubrics ||--oN assessments : "defines criteria for"
    registrations ||--oN assessments : "evaluated in"
    registrations ||--|| reports : "compiled report card (1:1)"
    registrations ||--oN certificates : "issued certificate"
    assignments ||--oN submissions : "has"
    registrations ||--oN submissions : "submits under"
    registrations ||--oN incident_reports : "associated with"
    registrations ||--oN placement_change_requests : "requests change"
    internships ||--oN internship_groups : "defines cohorts in"
    internship_groups ||--oN internship_group_members : "has members"
```

---

## 4. Balanced Table Schema Dictionary

### 4.1 System Configuration & Core (3 Tables)

#### `settings`
Global configuration registry. Replaces `setups` and `schools`.
* `key` (varchar 255, PK) — E.g., `school.name`, `school.institutional_code`, `setup.is_installed`, `setup.completed_steps`.
* `group` (varchar 100, indexed) — E.g., `school`, `setup`, `grading`, `theme`.
* `value` (text, nullable) — Value casted dynamically by Laravel at runtime.
* `created_at`, `updated_at` (timestamps)

#### `activation_tokens`
Registration activation, email verification, password reset, and recovery codes.
* `id` (uuid, PK)
* `user_id` (uuid, FK -> `users`, cascade delete)
* `token` (varchar 255) — Hashed.
* `token_type` (varchar 30) — E.g., `email_verification`, `password_reset`, `account_recovery`.
* `expires_at` (timestamp)
* `attempts` (integer, default 0)
* `last_attempt_at` (timestamp, nullable)

#### `gdpr_deletion_logs`
Compliance audit log for user data erasure requests.
* `id` (uuid, PK)
* `user_id` (uuid, indexed) — Orphaned UUID reference of deleted user.
* `metadata_snapshot` (json) — Anonymized metadata.
* `created_at` (timestamp)

---

### 4.2 Identity & Access (3 Tables)

#### `users`
Core authentication and authorization account registry.
* `id` (uuid, PK)
* `email` (varchar 255, unique, nullable) — Nullable for students.
* `username` (varchar 255, unique)
* `password` (varchar 255) — Hashed.
* `is_active` (boolean, default true)
* `locked_at` (timestamp, nullable)
* `locked_reason` (varchar 255, nullable)
* `remember_token` (varchar 100, nullable)
* `created_at`, `updated_at` (timestamps)

#### `profiles`
Demographic metadata and academic/industry scopes. Holds role-specific student and supervisor metadata.
* `id` (uuid, PK)
* `user_id` (uuid, FK -> `users`, cascade delete, UNIQUE) — 1:1 Link.
* `phone` (varchar 30, nullable)
* `address` (text, nullable)
* `gender` (varchar 10, nullable)
* `blood_type` (varchar 5, nullable)
* `pob` (varchar 100, nullable) — Place of birth.
* `dob` (date, nullable) — Date of birth.
* `emergency_contact` (json, nullable) — E.g., `{"name": "...", "phone": "...", "relation": "..."}`.
* `student_id_number` (varchar 50, unique, nullable, indexed) — NISN.
* `employee_id_number` (varchar 50, unique, nullable, indexed) — NIP/NIDN.
* `mentor_type` (varchar 30, nullable) — `school_teacher` | `industry_supervisor` | `null`.
* `internal_notes` (text, nullable) — Student-related records/counseling notes.
* `department_id` (uuid, FK -> `departments`, null on delete, nullable)
* `company_id` (uuid, FK -> `companies`, null on delete, nullable) — Affiliation for industry supervisors.
* `created_at`, `updated_at` (timestamps)

#### `account_applications`
Prospective student pre-registration portal.
* `id` (uuid, PK)
* `name` (varchar 255)
* `email` (varchar 255)
* `student_id_number` (varchar 50) — Target NISN.
* `department_id` (uuid, FK -> `departments`)
* `form_data` (json)
* `status` (varchar 20, default 'pending') — `pending` | `approved` | `rejected`.
* `rejection_reason` (varchar 255, nullable)
* `processed_by` (uuid, FK -> `users`, nullable)
* `created_at`, `updated_at` (timestamps)

---

### 4.3 Academic & Document Structure (4 Tables)

#### `departments`
Academic majors inside the school.
* `id` (uuid, PK)
* `name` (varchar 255, unique)
* `description` (text, nullable)
* `created_at`, `updated_at` (timestamps)

#### `academic_years`
Operational school calendars.
* `id` (uuid, PK)
* `name` (varchar 50, unique)
* `start_date`, `end_date` (date)
* `is_active` (boolean, default false)
* `created_at`, `updated_at` (timestamps)

#### `documents`
Document templates, forms, and policy handbooks. Replaces `handbooks`.
* `id` (uuid, PK)
* `type` (varchar 30) — `template` | `policy` (handbook) | `guideline`.
* `slug` (varchar 255, unique)
* `title` (varchar 255)
* `content` (longtext, nullable) — Handbook policy Markdown/HTML.
* `file_path` (varchar 255, nullable) — Path for templates download.
* `version` (integer, default 1)
* `is_active` (boolean, default true)
* `metadata` (json, nullable) — Target audience parameters.
* `created_by` (uuid, FK -> `users`)
* `created_at`, `updated_at` (timestamps)

#### `document_acknowledgements`
Tracks policy/handbook agreement compliance audits. Replaces `handbook_acknowledgements`.
* `id` (uuid, PK)
* `user_id` (uuid, FK -> `users`, cascade delete)
* `document_id` (uuid, FK -> `documents`, cascade delete)
* `acknowledged_at` (timestamp)
* `ip_address` (varchar 45, nullable)
* `UNIQUE` (user_id, document_id)

---

### 4.4 Program & Placements (6 Tables)

#### `companies`
Vocational partner host company registry.
* `id` (uuid, PK)
* `name` (varchar 255, indexed)
* `address`, `phone`, `email`, `website` (nullable)
* `industry_sector` (varchar 100, indexed, nullable)
* `created_at`, `updated_at` (timestamps)

#### `partnerships`
Separate tracking for company-school formal agreements.
* `id` (uuid, PK)
* `company_id` (uuid, FK -> `companies`, cascade delete)
* `agreement_number` (varchar 100, UNIQUE)
* `title` (varchar 255)
* `start_date`, `end_date` (date)
* `status` (varchar 20, default 'active') — `active` | `expired` | `terminated`.
* `scope` (text, nullable)
* `contact_person` (varchar 255, nullable)
* `signed_at` (timestamp, nullable)
* `created_at`, `updated_at` (timestamps)

#### `internships`
PKL program definitions. Replaces `internship_phases` and requirements pivots.
* `id` (uuid, PK)
* `academic_year_id` (uuid, FK -> `academic_years`, cascade delete)
* `name` (varchar 255)
* `start_date`, `end_date` (date)
* `status` (varchar 20, default 'draft') — `draft` | `active` | `closed`.
* `phases` (json, nullable) — Phases timeline: `[{"name": "Observation", "start": "...", "end": "..."}]`.
* `required_document_ids` (json, nullable) — Array of document UUIDs.
* `grading_weights` (json, nullable) — E.g., `{"supervisor": 40, "teacher": 20, "exam": 40}`.
* `created_at`, `updated_at` (timestamps)

#### `placements`
Company slots allocated for the internship program.
* `id` (uuid, PK)
* `company_id` (uuid, FK -> `companies`, cascade delete)
* `internship_id` (uuid, FK -> `internships`, cascade delete)
* `name` (varchar 255)
* `quota` (integer, default 1)
* `filled_quota` (integer, default 0)
* `created_at`, `updated_at` (timestamps)

#### `registrations`
Student PKL enrollments.
* `id` (uuid, PK)
* `student_id` (uuid, FK -> `users`, cascade delete)
* `internship_id` (uuid, FK -> `internships`, cascade delete)
* `placement_id` (uuid, FK -> `placements`, null on delete, nullable)
* `start_date`, `end_date` (date, nullable)
* `status` (varchar 20, default 'pending') — `pending` | `approved` | `active` | `completed` | `terminated`.
* `proposed_company_details` (json, nullable) — For custom student placements.
* `created_at`, `updated_at` (timestamps)

#### `registration_mentor`
Dual-mentor pivot mappings.
* `registration_id` (uuid, FK -> `registrations`, cascade delete)
* `user_id` (uuid, FK -> `users`, cascade delete) — Mentor/supervisor.
* `role` (varchar 30) — `school_teacher` | `industry_supervisor`.
* `PRIMARY KEY` (registration_id, user_id, role)

---

### 4.5 Daily Operations (6 Tables)

#### `registration_documents`
Tracks students' required document uploads.
* `id` (uuid, PK)
* `registration_id` (uuid, FK -> `registrations`, cascade delete)
* `document_id` (uuid, FK -> `documents`, cascade delete)
* `status` (varchar 20, default 'pending') — `pending` | `submitted` | `verified` | `rejected`.
* `file_path` (varchar 255, nullable)
* `verified_by` (uuid, FK -> `users`, nullable)
* `verified_at` (timestamp, nullable)
* `admin_notes` (varchar 255, nullable)
* `UNIQUE` (registration_id, document_id)
* `created_at`, `updated_at` (timestamps)

#### `attendances`
Daily location-tagged check-ins.
* `id` (uuid, PK)
* `user_id` (uuid, FK -> `users`, cascade delete)
* `registration_id` (uuid, FK -> `registrations`, cascade delete)
* `date` (date)
* `clock_in`, `clock_out` (time, nullable)
* `clock_in_ip`, `clock_out_ip` (varchar 45, nullable)
* `clock_in_lat`, `clock_in_lng` (decimal 10, 8, nullable)
* `clock_out_lat`, `clock_out_lng` (decimal 10, 8, nullable)
* `status` (varchar 20) — `present` | `late` | `early_out` | `absent`.
* `verification_details` (json, nullable) — photo paths, signatures, spoof-detection flags.
* `notes` (varchar 255, nullable)
* `UNIQUE` (user_id, date)
* `created_at`, `updated_at` (timestamps)

#### `absence_requests`
Student leave requests.
* `id` (uuid, PK)
* `user_id` (uuid, FK -> `users`, cascade delete)
* `registration_id` (uuid, FK -> `registrations`, cascade delete)
* `start_date`, `end_date` (date)
* `reason_type` (varchar 20) — `sick` | `permission` | `other`.
* `reason_description` (text)
* `attachment_path` (varchar 255, nullable)
* `status` (varchar 20, default 'pending')
* `processed_by` (uuid, FK -> `users`, nullable)
* `processed_at` (timestamp, nullable)
* `admin_notes` (varchar 255, nullable)
* `created_at`, `updated_at` (timestamps)

#### `logbooks`
Student daily journals.
* `id` (uuid, PK)
* `registration_id` (uuid, FK -> `registrations`, cascade delete)
* `author_id` (uuid, FK -> `users`, cascade delete)
* `date` (date)
* `content` (text) — Markdown text of student's activities.
* `learning_outcomes` (text, nullable)
* `status` (varchar 20, default 'draft') — `draft` | `submitted` | `verified` | `revision_required`.
* `verification_details` (json, nullable) — Feedback, timestamps, verified_by.
* `UNIQUE` (registration_id, date)
* `created_at`, `updated_at` (timestamps)

#### `supervision_logs`
Teacher visitation and virtual supervision records.
* `id` (uuid, PK)
* `registration_id` (uuid, FK -> `registrations`, cascade delete)
* `supervisor_id` (uuid, FK -> `users`, cascade delete) — Guru Pembimbing.
* `date` (date)
* `supervision_type` (varchar 20) — `site_visit` | `online` | `phone`.
* `notes` (text)
* `status` (varchar 20, default 'draft')
* `is_verified` (boolean, default false)
* `verified_by` (uuid, FK -> `users`, nullable)
* `created_at`, `updated_at` (timestamps)

#### `schedules`
Calendar events, workshops, orientations, and examinations.
* `id` (uuid, PK)
* `internship_id` (uuid, FK -> `internships`, cascade delete, nullable)
* `created_by` (uuid, FK -> `users`)
* `title` (varchar 255)
* `description` (text, nullable)
* `start_at`, `end_at` (timestamp)
* `type` (varchar 30) — `orientation` | `supervision` | `workshop` | `exam`.
* `location` (varchar 255, nullable)
* `created_at`, `updated_at` (timestamps)

---

### 4.6 Grading, Milestones & Incidents (8 Tables)

#### `rubrics`
Rubric templates. Replaces `rubrics`, `competencies`, and `indicators`.
* `id` (uuid, PK)
* `internship_id` (uuid, FK -> `internships`, cascade delete, nullable)
* `name` (varchar 255)
* `structure` (json) — Nested competencies and indicators hierarchy with weights and max scores.
* `created_by` (uuid, FK -> `users`)
* `created_at`, `updated_at` (timestamps)

#### `assessments`
Student midterm, final, and industry evaluation marks. Replaces `assessments` and `industry_assessments`.
* `id` (uuid, PK)
* `registration_id` (uuid, FK -> `registrations`, cascade delete)
* `evaluator_id` (uuid, FK -> `users`, cascade delete)
* `rubric_id` (uuid, FK -> `rubrics`, null on delete, nullable)
* `assessment_type` (varchar 30) — `midterm` | `final` | `periodic` | `industry`.
* `score` (float) — Consolidated final score.
* `scores_data` (json, nullable) — Indicator scores mapping. E.g. `{"ind_1": 95, "ind_2": 88}`.
* `feedback` (text, nullable)
* `finalized_at` (timestamp, nullable)
* `UNIQUE` (registration_id, assessment_type, evaluator_id)
* `created_at`, `updated_at` (timestamps)

#### `evaluations`
Mentor feedback and performance reviews completed by students.
* `id` (uuid, PK)
* `evaluator_id` (uuid, FK -> `users`, cascade delete) — Student user.
* `target_id` (uuid, FK -> `users`, cascade delete) — Mentor/supervisor user.
* `overall_score` (float)
* `criteria_scores` (json) — E.g. `{"responsiveness": 4.5, "guidance_quality": 5.0}`.
* `feedback` (text, nullable)
* `created_at`, `updated_at` (timestamps)

#### `assignments`
Tasks published by school mentors.
* `id` (uuid, PK)
* `internship_id` (uuid, FK -> `internships`, cascade delete)
* `document_id` (uuid, FK -> `documents`, null on delete, nullable)
* `assignment_type` (varchar 30) — `report` | `essay` | `project`.
* `title` (varchar 255)
* `description` (text, nullable)
* `due_date` (timestamp)
* `status` (varchar 20, default 'draft')
* `created_by` (uuid, FK -> `users`)
* `created_at`, `updated_at` (timestamps)

#### `submissions`
Student uploads for assignments.
* `id` (uuid, PK)
* `assignment_id` (uuid, FK -> `assignments`, cascade delete)
* `registration_id` (uuid, FK -> `registrations`, cascade delete)
* `student_id` (uuid, FK -> `users`, cascade delete)
* `content` (text, nullable)
* `metadata` (json, nullable) — Holds attachment paths.
* `status` (varchar 20, default 'submitted')
* `score` (float, nullable)
* `feedback` (text, nullable)
* `graded_by` (uuid, FK -> `users`, nullable)
* `graded_at` (timestamp, nullable)
* `created_at`, `updated_at` (timestamps)

#### `reports`
Final Student Grade Card (*Rapor PKL*). Stores the aggregated marks and locks grade records upon final sign-off.
* `id` (uuid, PK)
* `registration_id` (uuid, FK -> `registrations`, cascade delete, UNIQUE) — 1:1 Link with registration.
* `supervisor_score` (float) — Weighted/raw score from the industry supervisor.
* `teacher_score` (float) — Weighted/raw score from the school teacher/supervisor.
* `exam_score` (float) — Weighted/raw score from the final exam/presentation.
* `final_score` (float) — Composite final grade calculation.
* `grade_letter` (varchar 10) — Qualitative letter grade/predicate (e.g., "A" or "Sangat Baik").
* `industry_feedback` (text, nullable) — Overall testimonial/notes from host company.
* `status` (varchar 20, default 'draft') — `draft` | `finalized`.
* `finalized_by` (uuid, FK -> `users`, nullable)
* `finalized_at` (timestamp, nullable)
* `created_at`, `updated_at` (timestamps)

#### `certificates`
Cryptographically signed completion credentials.
* `id` (uuid, PK)
* `registration_id` (uuid, FK -> `registrations`, cascade delete)
* `certificate_number` (varchar 100, UNIQUE)
* `qr_hash` (varchar 64, UNIQUE) — Used for public validation URLs.
* `status` (varchar 20, default 'issued') — `issued` | `revoked`.
* `template_content` (text) — Rendered HTML layout.
* `issued_by` (uuid, FK -> `users`)
* `issued_at` (timestamp)
* `created_at`, `updated_at` (timestamps)

#### `incident_reports`
Welfare and safety logs.
* `id` (uuid, PK)
* `registration_id` (uuid, FK -> `registrations`, cascade delete)
* `reported_by` (uuid, FK -> `users`, cascade delete)
* `incident_date` (date)
* `type` (varchar 30) — `accident` | `disciplinary` | `safety`.
* `severity` (varchar 20) — `low` | `medium` | `high` | `critical`.
* `description` (text)
* `location` (varchar 255, nullable)
* `action_taken` (text, nullable)
* `status` (varchar 20, default 'reported')
* `resolved_by` (uuid, FK -> `users`, null on delete, nullable)
* `resolved_at` (timestamp, nullable)
* `resolution_notes` (text, nullable)
* `created_at`, `updated_at` (timestamps)

#### `placement_change_requests`
Requests to switch companies.
* `id` (uuid, PK)
* `registration_id` (uuid, FK -> `registrations`, cascade delete)
* `from_placement_id` (uuid, FK -> `placements`, cascade delete)
* `to_placement_id` (uuid, FK -> `placements`, null on delete, nullable)
* `reason` (text)
* `requested_by` (uuid, FK -> `users`)
* `status` (varchar 20, default 'pending')
* `processed_by` (uuid, FK -> `users`, nullable)
* `processed_at` (timestamp, nullable)
* `rejection_reason` (varchar 255, nullable)
* `created_at`, `updated_at` (timestamps)

---

### 4.7 Communication & Cohorts (3 Tables)

#### `announcements`
System-wide notice boards.
* `id` (uuid, PK)
* `created_by` (uuid, FK -> `users`, cascade delete)
* `title` (varchar 255)
* `message` (text)
* `type` (varchar 20, default 'info') — `info` | `warning` | `urgent`.
* `status` (varchar 20, default 'draft') — `draft` | `published` | `archived`.
* `scheduled_at` (timestamp, nullable)
* `link` (varchar 255, nullable)
* `target_roles` (json) — Roles targeted: `["student", "mentor", "admin"]`.
* `created_at`, `updated_at` (timestamps)

#### `internship_groups`
Cohorts/teams within an internship program.
* `id` (uuid, PK)
* `internship_id` (uuid, FK -> `internships`, cascade delete)
* `placement_id` (uuid, FK -> `placements`, null on delete, nullable)
* `name` (varchar 255) — E.g. "Shift A".
* `is_active` (boolean, default true)
* `created_at`, `updated_at` (timestamps)

#### `internship_group_members`
Pivot mapping members/supervisors to cohorts.
* `id` (uuid, PK)
* `internship_group_id` (uuid, FK -> `internship_groups`, cascade delete)
* `registration_id` (uuid, FK -> `registrations`, cascade delete, nullable) — Null for supervisor assignment.
* `user_id` (uuid, FK -> `users`, cascade delete, nullable) — Null for student assignment (student is identified via registration).
* `role` (varchar 50) — E.g., `member`, `team_leader`, `group_supervisor`.
* `joined_at` (timestamp)
* `created_at`, `updated_at` (timestamps)

---

### 4.8 Framework & Package Tables (18 Tables)

These tables are managed directly by Laravel and core packages. They are essential but are treated as package-owned entities.
1. `password_reset_tokens` (Laravel Core)
2. `sessions` (Laravel Core Session driver)
3. `cache` (Laravel Core Cache driver)
4. `cache_locks` (Laravel Core Cache locks)
5. `jobs` (Laravel Core Queue driver)
6. `failed_jobs` (Laravel Core Failed queue log)
7. `job_batches` (Laravel Core Batch tracking)
8. `pulse_values` (Laravel Pulse monitor)
9. `pulse_entries` (Laravel Pulse monitor)
10. `pulse_aggregates` (Laravel Pulse monitor)
11. `media` (spatie/laravel-medialibrary) — Polymorphic file attachment registry.
12. `activity_log` (spatie/laravel-activitylog) — System audit logs and action histories.
13. `roles` (spatie/laravel-permission) — Static RBAC roles.
14. `permissions` (spatie/laravel-permission) — Static application gates.
15. `model_has_roles` (spatie/laravel-permission) — Pivot for user assignments.
16. `model_has_permissions` (spatie/laravel-permission) — Pivot for direct user overrides.
17. `role_has_permissions` (spatie/laravel-permission) — Pivot mapping permissions to roles.
18. `notifications` (Laravel Database Channel) — In-app notification queue.

---

## 5. Architectural Integrity (3S Governance)

This balanced schema preserves and strengthens the **3S Governing Doctrine** of Internara:

### S1 — Secure
* **Auth-Profile Boundary:** Keeping credentials in `users` and personal details in `profiles` maintains the separation of concerns. This ensures that daily auth operations do not load sensitive details (like national ID numbers), reducing security vulnerability surfaces.
* **Cryptographic PDF Verification:** Certificates are held in their own audit-safe table containing verification QR hashes.
* **Compliance Logs:** GDPR data-erasure auditing is separated in `gdpr_deletion_logs`.

### S2 — Sustain
* **Separation of Concerns:** Independent lifecycles like student assignments, grade reports, and certificate issues remain decoupled, ensuring the codebase is easy to understand, test, and adapt.
* **Rubric Flexibility:** Nesting rubric indicators as JSON inside the `rubrics` table simplifies database structures and prevents database locks or breaking schema modifications.

### S3 — Scalable
* **No Tenant Overhead:** Strictly single-tenant self-hosted design patterns are enforced, removing unnecessary `school_id` foreign key on every entity.
* **Cohort Organization:** Group/cohort relationships are isolated to the `internship_groups` tables, enabling flexible organization structures.
