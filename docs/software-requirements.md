# System Requirements Specification (SyRS): Internara

This document constitutes the formal **System Requirements Specification (SyRS)** for the Internara
project, standardized according to ISO/IEC 29148. It establishes the authoritative baseline for all
developmental, architectural, and verification activities.

> **Governance Mandate:** This document is the **Single Source of Truth (SSoT)**. All technical
> modifications, architectural designs, and software artifacts must demonstrate traceability to the
> requirements defined herein.

---

## 1. Project Overview & Strategic Intent

**Product Name:** Internara – Practical Work Management Information System (SIM-PKL)

**System Purpose:** To provide a secure, modular, and human-centered platform for the
administration, monitoring, and mentoring of student practical work (internships) within educational
institutions and their industry partners.

**Strategic Benefits:**

- **Integrated Management**: A single, unified platform for orchestrating all aspects of the
  internship lifecycle systematically.
- **Continuous Oversight**: Real-time visibility into student progress and mentoring consistency.
- **Institutional Reference**: Designed to serve as a high-quality engineering benchmark for similar
  management systems in other educational institutions.

---

## 2. Stakeholder Requirements (StRS)

The system must satisfy the operational needs of the following identified stakeholders:

| ID            | Stakeholder Role     | Operational Need                                                           |
| :------------ | :------------------- | :------------------------------------------------------------------------- |
| **[STRS-01]** | Instructor (Teacher) | Supervision, on-site monitoring, and competency assessment.                |
| **[STRS-02]** | Practical Work Staff | Schedule management, location documentation, and administrative oversight. |
| **[STRS-03]** | Student              | Activity logging, progress tracking, and viewing achievements.             |
| **[STRS-04]** | Industry Supervisor  | Mentoring feedback, performance scoring, and student guidance.             |
| **[STRS-05]** | System Administrator | Identity management, system-wide configuration, and maintenance.           |

---

## 3. Functional Requirements (SyRS-F)

### 3.1 System Initialization & Orchestration (Setup & Admin)

- **[SYRS-F-101]**: **Installation Wizard**: The system shall provide a sequential 8-step wizard to
  orchestrate environment auditing, school identity creation, and super-admin initialization.
- **[SYRS-F-102]**: **Setup Protection**: The system shall enforce a "Single-Install" invariant,
  locking setup routes once the `app_installed` state is achieved.
- **[SYRS-F-103]**: **Authoritative Reporting**: The system shall generate certified institutional
  records and competency reports in PDF/Excel formats.

### 3.2 Identity & Institutional Management (User, School, Dept)

- **[SYRS-F-201]**: **Unified Profile**: The system shall map stakeholder identities using a
  single-profile strategy for national (NISN/NIP) and institutional identifiers (Registration
  Number).
- **[SYRS-F-202]**: **Academic Scoping**: The system shall automatically partition all domain data
  by active Academic Years and Departmental boundaries.
- **[SYRS-F-203]**: **Hierarchical Account Creation**: The system shall restrict user creation based
  on a delegated authority model (e.g., Admins create Teachers, Teachers manage Students).

### 3.3 Internship Lifecycle Management (Internship, Assignment, Guidance)

- **[SYRS-F-301]**: **Pre-Placement Checklist**: The system shall enforce mandatory requirement
  verification (Requirements) before allowing student internship registration.
- **[SYRS-F-302]**: **Slot Atomic Integrity**: The system shall maintain real-time industrial
  placement availability, enforcing the "One-Placement Law" to prevent double-registration.
- **[SYRS-F-303]**: **Digital Guidance**: The system shall track mandatory handbook reading via
  digital acknowledgement loops before program commencement.
- **[SYRS-F-304]**: **Task Management**: The system shall provide a dynamic engine for submitting
  and verifying mandatory internship assignments.

### 3.4 Monitoring & Vocational Telemetry (Attendance, Journal)

- **[SYRS-F-401]**: **Temporal Presence**: The system shall provide a real-time Check-In/Check-Out
  mechanism for tracking daily vocational attendance.
- **[SYRS-F-402]**: **Absence Orchestration**: The system shall manage authorized absence requests
  with mandatory justification.
- **[SYRS-F-403]**: **Dual-Supervision Journals**: The system shall require daily activity logs
  (Journals) with a verification workflow involving both Academic and Field supervisors.
- **[SYRS-F-404]**: **Forensic Evidence**: The system shall support attaching digital media
  (photos/documents) as technical proof of activity execution.
- **[SYRS-F-405]**: **On-site Monitoring**: The system shall enable instructors to document
  mentoring content and on-site student conditions to ensure learning objectives are met.

### 3.5 Assessment & Performance Synthesis (Assessment)

- **[SYRS-F-501]**: **Rubric-Based Evaluation**: The system shall allow role-specific performance
  scoring based on institutional competency rubrics.
- **[SYRS-F-502]**: **Compliance Automation**: The system shall automatically calculate
  participation scores derived from attendance and journal consistency.
- **[SYRS-F-503]**: **Readiness Auditing**: The system shall verify program finalization eligibility
  ("Go/No-Go") by auditing all mandatory evaluations and assignments.
- **[SYRS-F-504]**: **Visual Analytics**: The system shall visualize learning outcomes and
  competency achievements to support decision-making and performance evaluation.

---

## 4. Non-Functional Requirements (SyRS-NF)

### 4.1 UI/UX & Visual Identity (ISO 9241-210)

- **[SYRS-NF-401]**: Mobile-first responsive layout.
- **[SYRS-NF-402]**: Instrument Sans typography.
- **[SYRS-NF-403]**: Full localization (ID/EN), zero hard-coded text.
- **[SYRS-NF-404]**: Emerald Green accent on white/soft-gray background.
- **[SYRS-NF-405]**: Standardized thin borders (1px) and consistent corner radius.

### 4.2 Security & Integrity (ISO/IEC 27034)

- **[SYRS-NF-501]**: Secure authentication.
- **[SYRS-NF-502]**: Strict RBAC via Permission module.
- **[SYRS-NF-503]**: Encryption at rest for sensitive data.
- **[SYRS-NF-504]**: UUID v4 for all domain primary keys.

### 4.3 Architecture & Maintainability (ISO/IEC 25010)

- **[SYRS-NF-601]**: Modular Monolith with strict domain isolation.
- **[SYRS-NF-602]**: Laravel v12, Livewire v3, Tailwind CSS v4.
- **[SYRS-NF-603]**: SQLite, PostgreSQL, MySQL support.

### 4.4 Verification & Quality (ISO/IEC 29119)

- **[SYRS-NF-701]**: 3S Audit (Security, Sustainability, Scalability).
- **[SYRS-NF-702]**: ≥ 90% behavioral coverage per domain module.
- **[SYRS-NF-703]**: Zero high-severity static analysis violations.

---

## 5. Feasibility & Validation Criteria

- **[SYRS-V-001]**: Media Expert Validation.
- **[SYRS-V-002]**: Curriculum Expert Validation.
- **[SYRS-V-003]**: End-User Acceptance.

---

## 6. Scope & Constraints

- **[SYRS-C-001]**: Restricted to internship administration & monitoring.
- **[SYRS-C-002]**: Multi-language mandatory.
- **[SYRS-C-003]**: All logic in Service Layer.
- **[SYRS-C-004]**: Distinction between `app_name` and `brand_name`.

---

## 7. Developer Notice

All deviations require formal approval and documentation.

---

# 8. Glossary & Terminology Control

Normative definitions added to eliminate ambiguity.

---

# 9. Requirements Traceability Framework

Mandatory traceability chain:

StRS → SyRS-F → SyRS-NF → Test Case → Validation Evidence

No orphan requirement permitted.

---

# 10. Quantitative Non-Functional Extensions

Aligned with ISO/IEC 25010:

- Response Time ≤ 2s (95%).
- ≥ 500 concurrent users baseline.
- Uptime ≥ 99.5%.
- RTO ≤ 4 hours, RPO ≤ 24 hours.
- Cyclomatic complexity ≤ 10 per service.

---

# 11. 3S Audit Formalization

Aligned with ISO/IEC 27034:

- S1: Zero critical vulnerabilities.
- S2: No deprecated API usage.
- S3: Load-tested scalability.

---

# 12. Testing Strategy Annex

Aligned with ISO/IEC 29119:

- 70% Unit
- 20% Integration
- 10% E2E
- Branch-aware coverage required.

---

# 13. Data Governance & Retention

- Academic records ≥ 5 years.
- Logs ≥ 2 years.
- Encrypted backups mandatory.
- Soft-delete enforced.

---

# 14. Technology Review Policy

- Annual stack review.
- Mandatory compatibility audit for major upgrades.
- ADR documentation required.

---

# 15. Concurrency & Atomic Integrity Specification

For **[SYRS-F-302]**:

- Enforced via database unique composite key.
- Application-level transactional locking.
- Isolation level ≥ REPEATABLE READ.

---

_This System Requirements Specification establishes the authoritative configuration for Internara.
All engineering artifacts must demonstrate full compliance to proceed to implementation and
verification phases._
