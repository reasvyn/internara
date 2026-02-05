# Application Blueprint: Instructional Execution, Mentoring & Evaluation (ARC01-GAP-02)

**Series Code**: `ARC01-GAP-02` | **Status**: `Done`

---

## 1. Strategic Context

- **Spec Alignment**: This blueprint implements the **Progress Monitoring** ([SYRS-F-201], [SYRS-F-202]) and **Assessment & Reporting** ([SYRS-F-301], [SYRS-F-302]) requirements of the authoritative **[Specs](../specs.md)**.

---

## 2. Logic & Architecture (Systemic View)

### 2.1 Capabilities
- **Competency Mapping**: Departmental rubric system allowing students to claim skills during journal entries.
- **Mentoring Registry**: Centralized log documenting formal interactions between supervisors (Teachers/Mentors) and students.
- **Submission Governance**: Implementation of journal submission windows and automated absence request workflows.
- **Participation Scoring**: Quantitative logic linking engagement telemetry (attendance/journals) to objective scores.

### 2.2 Service Contracts
- **CompetencyService**: Manages skill registries and student claim mapping.
- **MentoringService**: Orchestrates guidance logs and supervisor visits.
- **ComplianceService**: Standardized provider for engagement telemetry.

### 2.3 Data Architecture
- **Entities**:
    - `competencies`, `journal_competency` (Pivot): Mapping of skills to academic activities.
    - `mentoring_sessions`, `absence_requests`: Forensic tracking of supervision and presence.
- **Invariants**: Absence Conflict Invariant (Rejection of check-in if approved absence exists).

---

## 3. Presentation Strategy (User Experience View)

### 3.1 UX Workflow
- **Claim Integration**: Smart dropdowns in the journal form with contextual skill suggestions.
- **Verification Loop**: Streamlined approval workflow for supervisors to validate journal claims and absence requests.

### 3.2 Interface Design
- **Unified Timeline**: Chronological view of all teacher and mentor logs for comprehensive student tracking.
- **Evaluation Dashboards**: Role-specific interfaces for formative and summative assessments.

---

## 4. Documentation Strategy (Knowledge View)

### 4.1 Engineering Record
- **Instructional Standards**: Documentation of the **[Participation & Compliance Scoring](../patterns.md)** formula.
- **Lifecycle Standards**: Formalization of the "Instructional Loop" in the technical patterns.

### 4.2 Module Standards
- **Knowledge Base**: Authoring of the initial `README.md` files for the `Assessment` and `Mentor` modules.

---

## 5. Exit Criteria & Verification Protocols

- **Verification Gate**: 100% pass rate on all instructional, mentoring, and assessment test suites.
- **Quality Gate**: zero violations in static analysis (`composer lint`).
- **Acceptance Criteria**:
    - Functional mapping of journals to departmental competencies.
    - Successful generation of a printable competency transcript for a student.
    - Verified authenticated persistence for mentoring logs.

---

## 6. Improvement Suggestions

- **Dynamic Weighting**: Develop an administrative interface to adjust participation weights.
- **Credentialing Automation**: Realized via the **[Reporting (ARC01-INTEL-01)](08-ARC01-INTEL-01-Reporting-Intelligence.md)** series.