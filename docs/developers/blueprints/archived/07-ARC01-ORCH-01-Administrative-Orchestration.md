# Application Blueprint: Administrative Orchestration (ARC01-ORCH-01)

**Series Code**: `ARC01-ORCH-01` **Status**: `Archived` (Done)

---

## 1. Design Objectives & Scope

**Strategic Purpose**: Transform Internara into an active orchestrator by automating administrative
rule enforcement and centralizing institutional oversight.

**Objectives**:

- Automate the validation of internship prerequisites to eliminate procedural bottlenecks.
- Implement participation-driven scoring to ensure objective student evaluation.
- Enhance operational efficiency through bulk placement orchestration and centralized verification.

---

## 2. Functional Specification

### 2.1 Capability Set

- **Requirement Orchestrator**: Subsystem for defining, submitting, and verifying mandatory
  institutional prerequisites before placement activation.
- **Participation Scoring**: Automated calculation of engagement metrics based on attendance and
  journal compliance invariants (50/50 weighting).
- **Bulk Placement Engine**: Integrated tools for batch-assigning students to internship placements with automated capacity checking.
- **Global Notifier**: Formalized dual-path notification system:
    - **UI Feedback**: Real-time toast notifications via the `Notifier` contract.
    - **System/User Notifications**: Persistent (DB) and external (Email) alerts via the `NotificationService` contract.

### 2.2 Stakeholder Personas

- **Student**: Interacts with the requirement dashboard to upload mandatory documentation.
- **Staff**: Orchestrates bulk verification and placement workflows via centralized management interfaces.
- **Teacher**: Utilizes participation-driven scores to facilitate objective qualitative assessment.

---

## 3. Architectural Impact (Logical View)

### 3.1 Modular Decomposition

- **Internship Module**: Enhanced with prerequisite validation, bulk assignment logic, and placement history tracking.
- **Assessment Module**: Integrated with **Attendance** and **Journal** modules via `ComplianceService` for engagement telemetry.
- **Notification Module**: Formalized as the orchestrator for cross-module UI feedback via the `Notifier` contract.

### 3.2 Data Architecture

- **Entities**: `internship_requirements` and `requirement_submissions` (UUID-based).
- **Communication Invariant**: Service-to-service metric retrieval via **Service Contracts** exclusively to maintain modular isolation.

---

## 4. Presentation Strategy (User Experience View)

### 4.1 Design Invariants

- **Flow State**: Conditional progression—"Ready for Placement" status is locked until requirements
  are certified by staff.
- **Feedback Consistency**: Systematic adoption of the `Notifier` service across all workspace actions to ensure uniform UI alerts.
- **i18n Integrity**: Full localization of requirement definitions, rejection feedback, and
  notifications in **ID** and **EN**.

---

## 5. Documentation Strategy (Knowledge View)

- **Standardization**: Formalization of the **[Participation & Compliance Scoring](../patterns.md)** formula.
- **Module Records**: Authoring of the initial `README.md` for the `Notification` module.
- **Service Contracts**: Documentation of the `Notifier` and `ComplianceService` APIs.

---

## 6. Audit & Evaluation Report (v0.13.0 Audit)

**Date**: 2026-02-04 | **Auditor**: Gemini

### 6.1 Realized Outcomes
- **Prerequisite Gating**: Successfully enforced in `RegistrationService::approve`, preventing placement of non-compliant students.
- **Compliance Scoring**: `ComplianceService` implemented with working-day calculation logic.
- **Bulk Orchestration**: Batch placement verified in `RegistrationManager`.
- **UI Consistency**: Refactored `RegistrationManager` to use the `Notifier` service instead of manual dispatch calls.

### 6.2 Identified Anomalies & Corrections
- **Notifier Inconsistency**: Found several Livewire components bypassing the `Notifier` service. **Correction**: Enforced usage of the `Notifier` contract in core management components.
- **Fixed Scoring Weight**: Scoring weights are currently hardcoded (50/50). **Resolution**: Identified as a candidate for dynamic configuration in future milestones to allow "direction shifts" as requested by stakeholders.

### 6.3 Improvement Plan
- [x] Refactor core Livewire components to inject and use the `Notifier` service.
- [x] Document the compliance scoring formula in the technical patterns.

---

## 7. Exit Criteria & Verification Protocols

A design series is considered done only when it satisfies the following gates:

- **Verification Gate**: 100% pass rate across the orchestration and validation suites via
  **`composer test`**.
- **Quality Gate**: zero static analysis violations via **`composer lint`**.
- **Acceptance Criteria**:
    - Demonstrated dynamic requirement verification lifecycle.
    - Verified accuracy of participation-weighted scoring logic.
    - Reports match the "Administrative Management" requirements defined in the SyRS.

---

## 8. Improvement Suggestions (Legacy)

- **Dynamic Weighting**: Develop an administrative interface to adjust participation weights (Attendance vs Journal).
- **Asynchronous Reporting**: Realized via the **[Reporting (ARC01-INTEL-01)](08-ARC01-INTEL-01-Reporting-Intelligence.md)** series.
- **Placement Audit**: Realized via `PlacementLogger` in the **Internship** module.