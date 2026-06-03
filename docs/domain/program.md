# Program Domain

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Formerly core Internship lifecycle domain

## Purpose

The **Program** domain manages the core configuration and structural lifecycle of the internship program (PKL - *Praktik Kerja Lapangan*). It defines the program periods (internships), operational progression phases, group setups, and checklist document requirements that students must satisfy.

It provides the administrative framework for the internship. All registrations, placements, daily logs, and assessments take place within the context of a configured `Internship` program.

---

## Design Principles

### 1. Program Lifecycle States
An internship program moves through distinct status phases managed by `InternshipStatus`:
`DRAFT` ➔ `PUBLISHED` (Open for registration) ➔ `ACTIVE` (Ongoing execution) ➔ `CLOSING` ➔ `CLOSED`
- **Draft**: Hidden from students. Admins define requirements and dates.
- **Published**: Open for student applications.
- **Active**: Execution phase. Students clock-in and submit daily journals.
- **Closing**: Assessments are compiled. The system audits close-readiness.
- **Closed**: Locked from modifications. Archived for historical review.

### 2. Segmented Phasing
Each internship program is divided into sequential calendar blocks (`InternshipPhase`). For example, preparation, industrial execution, report writing, and final examinations. Phases help students and mentors track deadlines and milestones.

### 3. Student Grouping (Internship Groups)
Students are organized into operational `InternshipGroup` entries (typically based on departments and host company placements). Groups map students to their corresponding company supervisors and academic mentors, centralizing the supervision context.

### 4. Mandatory Document Requirements
Administrators configure a list of checklist requirements (`InternshipDocumentRequirement`, e.g., parent consent slip, health clearance) that students must upload during enrollment. Requirements support different types: PDFs, images, or signature inputs.

---

## Domain Boundary

### Technical Ownership
- **Program Configuration**: CRUD operations for internships, calendar dates, status states.
- **Phasing Spans**: Timeline splits and phase dates within an internship.
- **Grouping Structures**: Mappings of students and mentors into supervising groups.
- **Upload Checklists**: Defining document requirements, categories, and types.
- **Close Auditing**: Verification actions checking if grading and documents are complete before closure.

### Dependencies
- **Core**: Uses base models, base actions, base policies, and event systems.
- **User**: Connects users (students and mentors) to group structures.
- **Academics**: Programs are scoped within an active `AcademicYear`.
- **Enrollment**: Registration entries validate their target against active program dates.

---

## Domain Rules & Invariants

- **R1 — Exclusivity of Active Program**: A department can run only one `ACTIVE` internship program at any given calendar date.
- **R2 — Phase Date Bounds**: The calendar spans of all `InternshipPhase` entries must lie entirely within the start and end dates of the parent `Internship` program.
- **R3 — Safe Status Transitions**: An internship program cannot transition from `ACTIVE` to `CLOSING` if there are pending student registrations. It cannot transition to `CLOSED` unless the `CheckCloseReadinessAction` confirms all student grades have been compiled.
- **R4 — Mandatory Group Allocations**: A student cannot be active in a program without being assigned to exactly one `InternshipGroup`.
- **R5 — Immutability of Closed Programs**: Once a program is `CLOSED`, all associated registrations, placements, logbooks, and grades are locked and cannot be updated.

---

## Key Features

- **Internship Period CRUD**: Define program names, descriptions, start/end dates, and manage lifecycle states.
- **Phase Timeline Manager**: Dynamic timeline scheduler splitting program spans into customized milestones.
- **Supervision Group Manager**: Admin interface mapping multiple students, academic mentors, and company supervisors into single groups.
- **Document Requirements Checklist**: Configure required enrollments files, marking them as mandatory or optional.
- **Close-Readiness Inspector**: System check evaluating student documents and grading completeness before closure.
- **CLI Close Command**: Headless closing options for administrators.
- **Lifecycle Events**: Triggers events (e.g., `InternshipCreated`) notifying users of program publication.
