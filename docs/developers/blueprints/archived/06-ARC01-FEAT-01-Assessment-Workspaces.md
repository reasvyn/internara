# Application Blueprint: Assessment & Workspaces (ARC01-FEAT-01)

**Series Code**: `ARC01-FEAT-01` **Status**: `Archived` (Done)

---

## 1. Design Objectives & Scope

**Strategic Purpose**: Decompose the application into specialized role-based workspaces and
implement a formal assessment engine for verifiable credentialing.

**Objectives**:

- Establish rigorous logical boundaries between stakeholder roles (Instructor, Staff, Student,
  Mentor).
- Standardize the qualitative evaluation process and automate high-fidelity certificate issuance.
- Resolve "role confusion" by providing dedicated, task-optimized interfaces for all actors.

---

## 2. Functional Specification

### 2.1 Capability Set

- **Role-Centric Workspaces**: Dedicated dashboards and navigation architectures for every
  identified stakeholder role.
- **Unified Assessment Engine**: Centralized scoring logic for multi-stakeholder evaluations
  (Teachers and Mentors) using a weighted average formula.
- **Verifiable Credentials**: Automated generation of completion certificates and transcripts utilizing QR-based cryptographic verification.
- **Compliance Monitoring**: Automated calculation of engagement scores derived from Attendance and Journal data.

### 2.2 Stakeholder Personas

- **Mentor**: Interacts with assigned intern profiles to provide efficient qualitative feedback on work quality and behavior.
- **Student**: Utilizes the workspace to download verified transcripts and completion certificates once readiness criteria are met.
- **Instructor**: Accesses a unified view of student achievements and submits academic evaluations.
- **Administrator**: Orchestrates system-wide assessment criteria and monitors overall engagement.

---

## 3. Architectural Impact (Logical View)

### 3.1 Modular Decomposition

- **Workspace Modules**: Systematic decomposition into `Admin`, `Student`, `Teacher`, and `Mentor`
  modules.
- **Assessment Module**: Dedicated domain for grading logic, competency mapping, and document orchestration.
- **UI Module**: Centralization of layout engines (`dashboard` layout) to ensure thematic consistency across roles.

### 3.2 Data Architecture

- **Identity Invariant**: Assessments and Competencies utilize **UUID v4** identification.
- **Communication Protocol**: Workspaces consume assessment telemetry via **Service Contracts** exclusively.
- **Weighting Invariant**: Final grades are calculated as: Mentor (40%) + Teacher (40%) + Compliance (20%).

---

## 4. Presentation Strategy (User Experience View)

### 4.1 Design Invariants

- **Dynamic Routing**: Role-aware redirection upon authentication handled via `RedirectService`.
- **Role-Optimized Forms**: Specialized evaluation interfaces for Mentors (Industry focus) and Teachers (Academic focus).
- **Credential Integrity**: High-fidelity PDF generation via `AssessmentPdfController`, supporting full localization in **ID** and **EN**.

---

## 5. Documentation Strategy (Knowledge View)

- **Access Standards**: Formalization of the **[Access Control Standards](../access-control.md)** mapping roles to system functions.
- **Assessment Logic**: Documentation of the weighted grading formula and readiness checking protocols in the `Assessment` module README.
- **Workspace Manuals**: Strategy for creating role-specific user guides in the Wiki.

---

## 6. Audit & Evaluation Report (v0.13.0 Audit)

**Date**: 2026-02-04 | **Auditor**: Gemini

### 6.1 Realized Outcomes
- **Multi-Stakeholder Grading**: Successfully implemented the weighted assessment engine in `AssessmentService`.
- **Role Isolation**: Verified that `Teacher` and `Mentor` workspaces are logically separated and protected by strict Policies.
- **Verifiable PDF**: Implementation of QR-code backed certificates and transcripts in the `Assessment` module.
- **Readiness Checking**: `getReadinessStatus()` protocol implemented to ensure all criteria (Period, Evaluations, Assignments) are met before graduation.

### 6.2 Identified Anomalies & Corrections
- **Hardcoded Weights**: Grading weights (40/40/20) are currently static in the service. **Status**: Acceptable for MVP, added to improvement suggestions for dynamic configuration.
- **Component Redundancy**: Similar logic in `EvaluateIntern` and `AssessInternship`. **Resolution**: Retained for role-specific UX optimization, sharing the same `AssessmentService` contract.

### 6.3 Improvement Plan
- [x] Link assessment telemetry to the institutional reporting engine.
- [x] Verify RBAC middleware enforcement across all workspace routes.

---

## 7. Exit Criteria & Verification Protocols

A design series is considered done only when it satisfies the following gates:

- **Verification Gate**: 100% pass rate across the workspace and assessment verification suites via **`composer test`**.
- **Quality Gate**: zero static analysis violations via **`composer lint`**.
- **Acceptance Criteria**:
    - Demonstrated role-based access control (RBAC) integrity.
    - Verified QR-link authenticity for generated certificates.
    - Workspaces match the "Stakeholder Requirements" defined in the System Requirements Specification.

---

## 8. Improvement Suggestions (Legacy)

- **Dynamic Weighting**: Configuration interface for administrators to adjust grading weights.
- **Prerequisite Validation**: Realized via the **[Instructional Execution (ARC01-GAP-02)](11-ARC01-GAP-02-Instructional-Execution.md)** series.