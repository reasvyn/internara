# Data Model: Internara Persistence Architecture

This document formalizes the **Data Model** for the Internara system, standardized according to **ISO/IEC 11179** (Metadata registries) and consistent with the **[System Architecture](system-architecture.md)**.

---

## 1. Persistence Philosophy: UUID Identity

Internara enforces **UUID v4** as the universal primary key for all domain entities. This ensures:
- **Security**: Resistance to ID enumeration attacks.
- **Portability**: Facilitates data migration and distributed systems.
- **Independence**: Allows for decoupled record creation across modular boundaries.

---

## 2. Core Entity Relationship Model (ERM)

Internara follows a **Strict Modular Data Ownership** strategy. Entities are owned by their respective domain modules.

### 2.1 Identity & Profile (User/Profile Module)
- **User**: Core authentication entity (`id`, `name`, `email`, `username`).
- **Profile**: Unified institutional identity (`id`, `user_id`, `phone`, `address`, `national_identifier`, `registration_number`).
- **Department**: Academic partitioning (`id`, `name`, `school_id`).

### 2.2 Internship Lifecycle (Internship Module)
- **Internship**: The program container (`id`, `title`, `academic_year`, `semester`).
- **InternshipPlacement**: Industry partner capacity (`id`, `company_id`, `internship_id`, `capacity_quota`).
- **InternshipRegistration**: The student-program link (`id`, `internship_id`, `student_id`, `placement_id`).
- **InternshipRequirement**: Pre-placement gate (`id`, `name`, `type`, `is_mandatory`).

### 2.3 Vocational Telemetry (Journal/Attendance Module)
- **JournalEntry**: Daily activity log (`id`, `registration_id`, `date`, `work_topic`, `activity_description`).
- **AttendanceLog**: Presence record (`id`, `registration_id`, `date`, `check_in_at`, `check_out_at`).

### 2.4 Performance (Assessment/Assignment Module)
- **Assessment**: Rubric-based evaluation (`id`, `registration_id`, `evaluator_id`, `type`, `score`).
- **Assignment**: Institutional task (`id`, `internship_id`, `title`, `due_date`).
- **Submission**: Student deliverable (`id`, `assignment_id`, `student_id`, `content`).

---

## 3. Modular Referential Integrity (SLRI)

Internara enforces **Software-Level Referential Integrity (SLRI)** across module boundaries.

- **No Physical Cross-Module FKs**: Database-level foreign keys across modules are prohibited.
- **Service Validation**: Existence of foreign entities is verified at the Service Layer via Service Contracts.
- **Weak Coupling**: Relationships across modules are maintained via UUID references, allowing for independent module evolution.

---

## 4. Metadata Governance

- **Timestamps**: All tables must include `created_at` and `updated_at`.
- **Soft Deletes**: Critical domain entities must implement `deleted_at` for forensic resilience.
- **Audit Trails**: Changes to sensitive entities are tracked via the `ActivityLog` module.
