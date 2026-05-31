# Mentee Domain
> Last updated: 2026-05-31
> **Status:** ✅ **Fully Implemented** — all 6 files in [reference](mentee-reference.md) exist

## Purpose

Mentee is the student's lens into the system — dashboard, progress tracking, self-service
access. Each student has one mentee record linking them to the program.

---

## Design Principles

### 1. Read-Only Aggregation

The mentee dashboard reads and aggregates data from operational domains — it never
mutates them. Attendance, logbook, assignment, and evaluation data are owned by their
respective domains. The dashboard provides a unified view without owning the source data.

### 2. Real-Time Progress

All progress metrics reflect the current state of the system. When a mentor verifies a
logbook entry, the student's progress bar updates. No manual refresh or recalculation
is needed.

### 3. Role-Activation Link

A single mentee record links a student user to their program and activates program
participation. Without this record, the student has no operational access — no clock-in,
no journal, no submissions. The mentee record is the gate to the entire placement
experience.

---

## Domain Boundary

The Mentee domain owns the student-facing experience — the dashboard, progress tracking, and self-service access that define how students interact with their placement program. It provides real-time progress tracking across all relevant dimensions: assignment completion rates, attendance percentages, logbook entry counts, evaluation participation, and guidance document review status. It surfaces mentor contact information and photos so students can see who is supervising them. Quick-action shortcuts allow students to jump directly to key workflows — write a logbook entry, clock in for the day, submit an assignment, or view evaluation results.

Mentee does not own the underlying data behind any of these views. It does not own logbook entries (Logbook), attendance records (Attendance), assignment definitions or submissions (Assignment), evaluation forms or scores (Evaluation), guidance documents (Guidance), or program definitions (Internship). Mentee reads and aggregates data from those domains to present a unified student dashboard. It does not manage the lifecycle or business rules of any referenced data.

The domain depends on nearly every operational domain for the data it displays, but it owns only the mentee record itself — the link between a student user and an internship program that grants the student access to the program's operations. All aggregate calculations and progress metrics are derived from data owned by other domains.

---

## Key Features

- Display real-time progress across assignments completed, attendance rate, logbook entries, and evaluation participation.
- Show assigned mentors with contact details and photos so students know who supervises them.
- Provide quick-action shortcuts to write a logbook entry, clock in, submit assignments, and view evaluations.
- View a color-coded progress dashboard showing completion percentages for assignments, attendance, logbook, and evaluations.
- See mentor profile cards with photo, name, role, and contact details displayed prominently on the dashboard.
- Click quick-action buttons that navigate directly to the relevant workflow screen with pre-filled context.
- Access a sidebar menu that groups quick actions by category with icons for quick visual scanning.
