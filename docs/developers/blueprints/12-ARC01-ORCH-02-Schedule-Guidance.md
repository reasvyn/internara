# Application Blueprint: Timeline Transparency & Instructional Guidance (ARC01-ORCH-02)

**Series Code**: `ARC01-ORCH-02` **Status**: `Done`

> **System Requirements Specification Alignment:** This blueprint implements the **Administrative
> Orchestration** ([SYRS-F-101]) and **Stakeholder Onboarding** requirements by providing
> transparency in the internship schedule and formalizing the briefing (Pembekalan) phase.

---

## 1. Design Objectives & Scope

**Strategic Purpose**: Bridge the information gap between institutional planning and student
readiness by formalizing the internship timeline and providing a structured guidance hub.

**Objectives**:

- **Timeline Transparency**: Provide a centralized schedule for all internship-related events
  (Briefing, Deployment, Deadlines, Assessment Release).
- **Briefing Formalization**: Ensure students have access to institutional guidelines and provide a
  digital trail of their acknowledgement (Checklist).
- **Administrative Flexibility**: Allow administrators to enable or disable the Briefing phase
  requirement based on institutional readiness.
- **Stakeholder Alignment**: Align Students, Teachers, and Staff on critical institutional
  milestones.

---

## 2. Functional Specification (MVP Focus)

### 2.1 Capability Set

- **Internship Scheduler**: A management tool for Staff to define institutional milestones and
  events associated with a program or academic year.
- **Student Timeline View**: A chronological visualization for students to track their journey from
  start to finish.
- **Instructional Guidance Hub**: A dedicated workspace for students to download PDF guidelines and
  official school manuals.
- **Readiness Checklist**: A simple "I have read and understood" mechanism for students to confirm
  their participation in the briefing phase.
- **Feature Governance Toggle**: A global setting for Administrators to toggle the visibility and
  requirement of the Guidance/Briefing module.

---

## 3. Architectural Impact (Logical View)

### 3.1 Domain Integration & Data Architecture

- **Schedule Module (New)**:
    - `Schedule`: (UUID, title, description, start_at, end_at, type: `event, deadline, briefing`).
    - Events can be scoped to an `Internship` program or `Academic Year`.
- **Guidance Module (New)**:
    - `Handbook`: (UUID, title, file_url, version, is_active).
    - `HandbookAcknowledgement`: (UUID, student_id, handbook_id, acknowledged_at).
- **Cross-Module Relation**:
    - `Schedule` utilizes the `setting()` registry for default phase labels.
    - `Handbook` visibility is governed by `setting('feature_guidance_enabled', true)`.
    - `Handbook` integrates with the `Media` module for secure PDF storage.

### 3.2 Logic Invariants

- **The Optionality Invariant**: If `setting('feature_guidance_enabled')` is false, the Guidance
  menu is hidden and no acknowledgement gating is applied.
- **The Acknowledgement Invariant**: If enabled, certain internship activities (like starting a
  journal) may optionally be gated by the completion of the "Guideline Checklist" if configured by
  the school.
- **The Period Alignment**: Schedule events must reside within the broad boundaries of the academic
  year.

---

## 4. Presentation Strategy (MVP UI)

### 4.1 UI Design Invariants

- **Journey Timeline**: A vertical, reactive timeline (MaryUI/Tailwind) on the student dashboard.
- **Document Hub**: A clean, card-based interface for downloading resources.
- **Onboarding Modal**: A one-time or prominent checklist for students who haven't acknowledged the
  guidelines.

---

## 5. Exit Criteria & Verification Protocols

A design series is considered done only when it satisfies the following gates:

- **Verification Gate**: 100% pass rate on schedule and guideline verification tests.
- **Quality Gate**: zero violations in static analysis (`composer lint`).
- **Acceptance Criteria**:
    - Admin can create a "Pembekalan" event that appears on the student's timeline.
    - Student can download a guideline PDF and mark it as "Read".
    - System records the timestamp of student acknowledgement for audit purposes.

---

_By implementing this blueprint, Internara ensures that the "Planning" phase of the internship
lifecycle is transparent and legally sound for all participants._
