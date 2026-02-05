# Application Blueprint: Timeline Transparency & Instructional Guidance (ARC01-ORCH-02)

**Series Code**: `ARC01-ORCH-02` | **Status**: `Done`

---

## 1. Strategic Context

- **Spec Alignment**: This blueprint implements the **Administrative Orchestration** ([SYRS-F-101]) and **Stakeholder Onboarding** requirements by providing transparency in the internship schedule and formalizing the briefing phase.

---

## 2. Logic & Architecture (Systemic View)

### 2.1 Capabilities
- **Internship Scheduler**: Management tool for defining institutional milestones (Briefings, Deadlines) scoped to programs or academic years.
- **Guidance Gating**: Systematic lock on critical features (Journals, Attendance) until mandatory guidelines are acknowledged by the student.
- **Readiness Tracking**: Digital trail of student acknowledgements using a checklist mechanism.

### 2.2 Service Contracts
- **ScheduleService**: Orchestrates institutional event timelines.
- **HandbookService**: Manages instructional materials and acknowledgement state.

### 2.3 Data Architecture
- **Entities**:
    - `schedules`: Scoped events with type categorization (`event`, `deadline`, `briefing`).
    - `handbooks`, `handbook_acknowledgements`: Documents and student verification records.
- **Invariants**: Feature Governance Toggle (Global administrative control over the guidance module).

---

## 3. Presentation Strategy (User Experience View)

### 3.1 UX Workflow
- **Acknowledgement Loop**: One-time prominent checklist for students who have not completed mandatory briefings.
- **Timeline Discovery**: Vertical chronological visualization of critical milestones on the student dashboard.

### 3.2 Interface Design
- **Document Hub**: Card-based interface for secure PDF guideline downloads.
- **Visual Feedback**: Differentiation between briefing sessions and critical deadlines via contextual icons.

---

## 4. Documentation Strategy (Knowledge View)

### 4.1 Engineering Record
- **Gating Patterns**: Documentation of the **[Guidance Gating Invariant](../conventions.md)**.
- **Storage Standards**: Configuration of private storage streaming for institutional manuals.

### 4.2 Module Standards
- **Knowledge Base**: Authoring of the initial `README.md` files for the `Schedule` and `Guidance` modules.

---

## 5. Exit Criteria & Verification Protocols

- **Verification Gate**: 100% pass rate on schedule and guideline verification tests.
- **Quality Gate**: zero violations in static analysis (`composer lint`).
- **Acceptance Criteria**:
    - Admin can create a "Pembekalan" event that appears on the student's timeline.
    - Student can download a guideline PDF and mark it as "Read".
    - System records the timestamp of student acknowledgement for audit purposes.

---

## 6. Forward Outlook: Improvement Suggestions

- **Automatic Notifications**: Realized via the **[Orchestration (ARC01-ORCH-01)](archived/07-ARC01-ORCH-01-Administrative-Orchestration.md)** series.