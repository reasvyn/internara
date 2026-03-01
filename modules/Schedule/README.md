# Schedule Module

The `Schedule` module manages institutional milestones, event scheduling, and student journey
visualization within the internship ecosystem.

> **Governance Mandate:** This module implements the requirements defined in the authoritative
> **[System Requirements Specification](../../docs/dev/specs.md)**. All implementation must adhere
> to the **[Coding Conventions](../../docs/dev/conventions.md)**.

---

## Purpose

- **Milestone Management:** Centralized repository for critical institutional dates (Briefings,
  Deployments, Deadlines).
- **Timeline Transparency:** Bridging the information gap between planning and execution.
- **Engagement Monitoring:** Providing a vertical chronological view of the student's internship
  path.

## Key Features

### 1. Internship Scheduler (Admin)

- **Event Management:** Staff can create and manage events scoped to specific programs or global
  academic years.
- **Categorization:** Supports multiple agenda types including `event`, `deadline`, and `briefing`.
- **Auto-Scoping:** Automatically filters agendas by the active academic year via `HasAcademicYear`.

### 2. Student Journey View

- **Vertical Timeline:** A reactive, TALL-stack powered visualization on the student dashboard.
- **Contextual Icons:** Visual differentiation between briefing sessions, general events, and
  critical deadlines.
- **Status Awareness:** Highlights events occurring "Today" to ensure immediate awareness.

### 3. Integrated Isolation

- **Zero Coupling:** Interacts with the `Internship` module strictly via Service Contracts.
- **UUID Identity:** Consistent use of **UUID v4** for all schedule entities.

---

_The Schedule module ensures that the internship process is documented, transparent, and easy to
follow._
