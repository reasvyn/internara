# Comprehensive Engineering Protocol: Strategic Planning & Elicitation

This document establishes the authoritative **Planning & Requirement Protocol** for the Internara
project, adhering to **ISO/IEC 29148** (Requirements Engineering) and **ISO/IEC 12207**.

---

## ⚖️ Core Mandates & Prohibitions (The Planning Laws)

The Agent must adhere to these invariants during the inception phase.

### 1. Traceability Laws

- **SyRS Alignment**: Every feature, refactor, or systemic change MUST be traceable to a specific
  requirement ID in `docs/developers/specs.md`.
- **Stakeholder Mapping**: Plans must explicitly identify which stakeholder role ([STRS-01] to
  [STRS-05]) is the primary beneficiary and how their protocol is impacted.
- **Narrative Purity**: Checklists are forbidden. Use descriptive, goal-oriented narrative points to
  define the strategic intent.

### 2. Feasibility & Ethical Laws

- **Scope Sovereignty**: Requests outside `SYRS-C-001` must be formally flagged. Planning must
  distinguish between "Core Needs" and "Feature Bloat."
- **Privacy by Design**: Mandatory identification of PII touchpoints. Planning must define data
  protection strategies before a single line of code is written.
- **Modular Integrity**: Rationale for cross-module interactions must be justified. Prefer
  event-driven decoupling over service coupling where feasible.

---

## 🎯 Scope & Authorized Actions

### 1. Protocol Scope

- **Strategic Entry Point**: This protocol is mandatory for any task requiring structural changes or
  new domain logic.
- **Requirement Elicitation**: Translating high-level intent into technical constraints and
  invariants.
- **Impact Analysis**: Mapping the "Ripple Effect" across the modular ecosystem.

### 2. Authorized Actions

- **Issue Formalization**: Creating and managing **GitHub Issues** via `gh issue create`.
- **Strategic Drafting**: Creating and modifying Application Blueprints in
  `docs/developers/blueprints/`.
- **Integrity Auditing**: Analyzing `docs/developers/debts/` and existing logic to identify blockers
  or alignment opportunities.
- **Milestone Architecture**: Assigning tasks to **GitHub Milestones** to track version progress.

---

## Phase 0: Inquiry & Contextual Audit

- **Archeological Search**: Use `search_file_content` to understand current implementation and
  search **GitHub Issues/Discussions** for related history or community feedback.
- **Integrity Check**: Audit the target domain for technical debts, existing invariants, and pending
  blueprints.
- **Stakeholder Triage**: Assess the impact on the user experience for specific roles.

## Phase 1: Requirements Formalization (The "What")

- **SyRS Mapping**: Anchor the task to existing **[SYRS-F]** or **[SYRS-NF]** requirements.
- **Milestone Alignment**: Identify the target **GitHub Milestone** for this requirement.
- **Legal Definition**: Define the "Laws" (Invariants) for this task.
- **Definition of Done (DoD)**: Establish high-level, measurable success criteria.

## Phase 2: Architectural Impact Audit (The "Where")

- **Modular Topology**: Identify "Supplier" and "Consumer" modules.
- **Interface Assessment**: Determine if existing Service Contracts need expansion.
- **Persistence Strategy**: Plan for schema changes without violating modular isolation.

## Phase 3: Strategic Blueprinting (The "How")

- **Blueprint Construction**: Draft the technical strategy in `docs/developers/blueprints/`.
- **Task Formalization**: Convert the blueprint into tracked **GitHub Issues** using
  `gh issue create`. Link issues to the appropriate **Milestone**.
- **Branch Naming**: Define the target branch name using the `feature/{module}/{description}` or
  `hotfix/{description}` standard.
- **User Approval Gate**: Present the narrative plan and obtain explicit authorization to proceed.

## Phase 4: SSoT & Registry Synchronization

- **Milestone Tracking**: Verify that the issue is visible in the **GitHub Project** board.
- **Metadata Update**: Prepare `app_info.json` for versioning updates if the plan constitutes a
  milestone.
- **Wiki Alignment**: Update high-level documentation if the strategic direction shifts.

## Phase 5: Final Review & Directional Summary

- Summary of the Planning Outcome and the "Next Step" protocol (e.g., Handover to `designing.md`).

---

_Planning is the first line of defense against architectural entropy._
