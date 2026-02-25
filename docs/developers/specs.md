# System Requirements Specification (SyRS): Internara

This document constitutes the formal **System Requirements Specification (SyRS)** for the Internara
project, standardized according to **ISO/IEC 29148** (Requirements Engineering). It establishes the
authoritative baseline for all developmental, architectural, and verification activities.

> **Governance Mandate:** This document is the **Single Source of Truth (SSoT)**. All technical
> modifications, architectural designs, and software artifacts must demonstrate traceability to the
> requirements defined herein.

---

## 1. Project Overview & Strategic Intent

**Product Name:** Internara – Practical Work Management Information System (SIM-PKL)

**System Purpose:**  
To provide a secure, modular, and human-centered platform for the administration, monitoring, and
mentoring of student practical work (internships) within educational institutions and their industry
partners.

---

## 2. Stakeholder Requirements (StRS)

The system must satisfy the operational needs of the following identified stakeholders:

| ID            | Stakeholder Role     | Operational Need                                    |
| :------------ | :------------------- | :-------------------------------------------------- |
| **[STRS-01]** | Instructor (Teacher) | Supervision, mentoring, and competency assessment.  |
| **[STRS-02]** | Practical Work Staff | Schedule management and administrative oversight.   |
| **[STRS-03]** | Student              | Activity logging, progress tracking, and reporting. |
| **[STRS-04]** | Industry Supervisor  | Mentoring feedback and student performance logs.    |
| **[STRS-05]** | System Administrator | Identity management and system-wide configuration.  |

---

## 3. Functional Requirements (SyRS-F)

The system shall implement the following core capabilities:

### 3.1 Administrative Orchestration

- **[SYRS-F-101]**: The system shall record student profiles, internship schedules, and
  institutional locations.
- **[SYRS-F-102]**: The system shall provide centralized storage for digitized internship documents
  (permissions, reports, evaluations).

### 3.2 Progress Monitoring & Traceability

- **[SYRS-F-201]**: The system shall provide a real-time tracking mechanism for student activities
  via daily journals.
- **[SYRS-F-202]**: The system shall enable instructors to document mentoring content and on-site
  student conditions.

### 3.3 Assessment & Reporting

- **[SYRS-F-301]**: The system shall generate competency achievement reports based on instructor
  input and supervisor feedback.
- **[SYRS-F-302]**: The system shall visualize learning outcomes across academic periods.

---

## 4. Non-Functional Requirements (SyRS-NF)

The system shall adhere to the following quality and technical constraints:

### 4.1 UI/UX & Accessibility (ISO 9241-210)

- **[SYRS-NF-401]**: **Mobile-First**: The interface must default to mobile-responsive layouts,
  utilizing Tailwind v4 breakpoints for progressive enhancement.
- **[SYRS-NF-402]**: **Typography**: The system must utilize **Instrument Sans** as the primary
  font.
- **[SYRS-NF-403]**: **Localization**: The system must support Indonesian (primary) and English,
  with zero hard-coded user-facing text.

### 4.2 Security & Integrity (ISO/IEC 27034)

- **[SYRS-NF-501]**: **Authentication**: Secure entry via email/password or authorized tokens.
- **[SYRS-NF-502]**: **Access Control**: Implementation of strict **Role-Based Access Control
  (RBAC)** as defined in section 2, managed via the **Permission** module.
- **[SYRS-NF-503]**: **Data Privacy**: Sensitive data (PII) must be encrypted at rest and masked
  during logging.
- **[SYRS-NF-504]**: **Identity Protection**: All domain primary keys must utilize **UUID v4**
  (implemented via `Shared\Models\Concerns\HasUuid`) to prevent ID enumeration attacks. System-level
  tables (e.g., jobs, migrations) may utilize standard sequences.

### 4.3 Architecture & Maintainability (ISO/IEC 25010)

- **[SYRS-NF-601]**: **Modular Monolith**: The system must maintain strict domain isolation with no
  physical foreign keys across module boundaries.
- **[SYRS-NF-602]**: **TALL Stack**: Implementation must utilize Laravel v12, Livewire v3, Tailwind
  CSS v4, and DaisyUI.
- **[SYRS-NF-603]**: **Database**: Support for SQLite, PostgreSQL, or MySQL as persistence engines.

---

## 5. Scope & Constraints

- **[SYRS-C-001]**: System logic is restricted to the administration and monitoring of practical
  work; it does not cover curriculum development.
- **[SYRS-C-002]**: Multi-language support is mandatory for all user-facing interfaces.
- **[SYRS-C-003]**: All logic must reside within the **Service Layer** to ensure testability.
- **[SYRS-C-004]**: **System Identity**: The system distinguishes between `app_name` (Product
  Identity, e.g., "Internara") and `brand_name` (Instance Identity, e.g., "SMK Negeri 1 Jakarta") to
  ensure institutional flexibility.

---

_This System Requirements Specification establishes the authoritative configuration for Internara.
Every subsequent engineering artifact must demonstrate compliance with these requirements to satisfy
the V&V phase._
