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

**Strategic Benefits:**
- **Integrated Management**: A single, unified platform for orchestrating all aspects of the
  internship lifecycle systematically.
- **Continuous Oversight**: Real-time visibility into student progress and mentoring consistency.
- **Institutional Reference**: Designed to serve as a high-quality engineering benchmark for similar
  management systems in other educational institutions.

---

## 2. Stakeholder Requirements (StRS)

The system must satisfy the operational needs of the following identified stakeholders:

| ID            | Stakeholder Role     | Operational Need                                    |
| :------------ | :------------------- | :-------------------------------------------------- |
| **[STRS-01]** | Instructor (Teacher) | Supervision, on-site monitoring, and competency assessment. |
| **[STRS-02]** | Practical Work Staff | Schedule management, location documentation, and administrative oversight. |
| **[STRS-03]** | Student              | Activity logging, progress tracking, and viewing achievements. |
| **[STRS-04]** | Industry Supervisor  | Mentoring feedback, performance scoring, and student guidance. |
| **[STRS-05]** | System Administrator | Identity management, system-wide configuration, and maintenance. |

---

## 3. Functional Requirements (SyRS-F)

The system shall implement the following core capabilities:

### 3.1 System Initialization & Orchestration (Setup & Admin)
- **[SYRS-F-101]**: **Installation Wizard**: The system shall provide a sequential 8-step wizard to orchestrate environment auditing, school identity creation, and super-admin initialization.
- **[SYRS-F-102]**: **Setup Protection**: The system shall enforce a "Single-Install" invariant, locking setup routes once the `app_installed` state is achieved.
- **[SYRS-F-103]**: **Authoritative Reporting**: The system shall generate certified institutional records and competency reports in PDF/Excel formats.

### 3.2 Identity & Institutional Management (User, School, Dept)
- **[SYRS-F-201]**: **Unified Profile**: The system shall map stakeholder identities using a single-profile strategy for national (NISN/NIP) and institutional identifiers (Registration Number).
- **[SYRS-F-202]**: **Academic Scoping**: The system shall automatically partition all domain data by active **Academic Years** and **Departmental** boundaries.
- **[SYRS-F-203]**: **Hierarchical Account Creation**: The system shall restrict user creation based on a delegated authority model (e.g., Admins create Teachers, Teachers manage Students).

### 3.3 Internship Lifecycle Management (Internship, Assignment, Guidance)
- **[SYRS-F-301]**: **Pre-Placement Checklist**: The system shall enforce mandatory requirement verification (Requirements) before allowing student internship registration.
- **[SYRS-F-302]**: **Slot Atomic Integrity**: The system shall maintain real-time industrial placement availability, enforcing the "One-Placement Law" to prevent double-registration.
- **[SYRS-F-303]**: **Digital Guidance**: The system shall track mandatory handbook reading via digital acknowledgement loops before program commencement.
- **[SYRS-F-304]**: **Task Management**: The system shall provide a dynamic engine for submitting and verifying mandatory internship assignments.

### 3.4 Monitoring & Vocational Telemetry (Attendance, Journal)
- **[SYRS-F-401]**: **Temporal Presence**: The system shall provide a real-time Check-In/Check-Out mechanism for tracking daily vocational attendance.
- **[SYRS-F-402]**: **Absence Orchestration**: The system shall manage authorized absence requests with mandatory justification.
- **[SYRS-F-403]**: **Dual-Supervision Journals**: The system shall require daily activity logs (Journals) with a verification workflow involving both Academic and Field supervisors.
- **[SYRS-F-404]**: **Forensic Evidence**: The system shall support attaching digital media (photos/documents) as technical proof of activity execution.
- **[SYRS-F-405]**: **On-site Monitoring**: The system shall enable instructors to document mentoring content and on-site student conditions to ensure learning objectives are met.

### 3.5 Assessment & Performance Synthesis (Assessment)
- **[SYRS-F-501]**: **Rubric-Based Evaluation**: The system shall allow role-specific performance scoring based on institutional competency rubrics.
- **[SYRS-F-502]**: **Compliance Automation**: The system shall automatically calculate participation scores derived from attendance and journal consistency.
- **[SYRS-F-503]**: **Readiness Auditing**: The system shall verify program finalization eligibility ("Go/No-Go") by auditing all mandatory evaluations and assignments.
- **[SYRS-F-504]**: **Visual Analytics**: The system shall visualize learning outcomes and competency achievements to support decision-making and performance evaluation.

---

## 4. Non-Functional Requirements (SyRS-NF)

The system shall adhere to the following quality and technical constraints:

### 4.1 UI/UX & Visual Identity (ISO 9241-210)

- **[SYRS-NF-401]**: **Mobile-First**: The interface must default to mobile-responsive layouts, utilizing Tailwind breakpoints for progressive enhancement.
- **[SYRS-NF-402]**: **Typography**: The system must utilize **Instrument Sans** as the primary font to ensure clarity and professional aesthetics.
- **[SYRS-NF-403]**: **Localization**: The system must support Indonesian (primary) and English, with zero hard-coded user-facing text.
- **[SYRS-NF-404]**: **Brand Identity**: The UI must maintain a professional balance using a white/soft-gray background and **Emerald Green** as the primary accent color for interactive elements.
- **[SYRS-NF-405]**: **Design Consistency**: Standardized UI elements (buttons, forms, cards) must utilize a consistent **1px thin border** and a **0.25 – 0.75 rem corner radius** to reduce visual fatigue.

### 4.2 Security & Integrity (ISO/IEC 27034)

- **[SYRS-NF-501]**: **Authentication**: Secure entry via email/username and password or authorized tokens.
- **[SYRS-NF-502]**: **Access Control**: Implementation of strict **Role-Based Access Control (RBAC)**, managed via the **Permission** module.
- **[SYRS-NF-503]**: **Data Privacy**: Sensitive data (PII, phone, address, NISN/NIP) must be encrypted at rest and masked during logging.
- **[SYRS-NF-504]**: **Identity Protection**: All domain primary keys must utilize **UUID v4** to prevent ID enumeration attacks.

### 4.3 Architecture & Maintainability (ISO/IEC 25010)

- **[SYRS-NF-601]**: **Modular Monolith**: The system must maintain strict domain isolation with no physical foreign keys across module boundaries.
- **[SYRS-NF-602]**: **Modern Stack**: Implementation must utilize Laravel v12, Livewire v3, and Tailwind CSS v4 to ensure long-term maintainability.
- **[SYRS-NF-603]**: **Database**: Support for SQLite, PostgreSQL, or MySQL as persistence engines.

### 4.4 Verification & Quality (ISO/IEC 29119)

- **[SYRS-NF-701]**: **3S Audit**: All technical artifacts must pass a 3-stage verification protocol: **Security (S1)**, **Sustainability (S2)**, and **Scalability (S3)**.
- **[SYRS-NF-702]**: **Test Coverage**: Minimum **90% behavioral coverage** per domain module.
- **[SYRS-NF-703]**: **Static Analysis**: Zero high-severity violations in linting and type-checking.

---

## 5. Feasibility & Validation Criteria

To ensure the product meets its intended educational and operational goals, it must be validated against:

- **[SYRS-V-001]**: **Media Expert Validation**: Design, layout, navigation, and user interaction must be audited for usability and accessibility.
- **[SYRS-V-002]**: **Curriculum Expert Validation**: System workflows must align with the official internship curriculum and institutional reporting requirements.
- **[SYRS-V-003]**: **End-User Acceptance**: Successful verification of core workflows (Setup, Monitoring, Assessment) by instructors, staff, and students.

---

## 6. Scope & Constraints

- **[SYRS-C-001]**: System logic is restricted to the administration and monitoring of practical work; it does not cover curriculum development.
- **[SYRS-C-002]**: Multi-language support is mandatory for all user-facing interfaces.
- **[SYRS-C-003]**: All logic must reside within the **Service Layer** to ensure testability.
- **[SYRS-C-004]**: **System Identity**: The system distinguishes between `app_name` (Product Identity, e.g., "Internara") and `brand_name` (Instance Identity, e.g., "SMK Negeri 1 Jakarta") to ensure institutional flexibility.

---

## 7. Developer Notice (UNAUTHORIZED MODIFICATION PROHIBITED)

- **This document constitutes the authoritative configuration for Internara.** All engineering
  artifacts, architectural decisions, and source code must demonstrate compliance with the
  requirements defined herein.
- **No content, functional requirement, or technical constraint may be altered** without formal
  approval from the project lead or the designated architecture board. 
- **Any deviation** from this specification must be formally documented, justified, and approved
  prior to implementation to ensure system integrity and compliance with institutional goals.

---

_This System Requirements Specification establishes the authoritative configuration for Internara.
Every subsequent engineering artifact must demonstrate compliance with these requirements to satisfy
the V&V phase._
