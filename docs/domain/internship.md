# Internship Domain

## Purpose

Internship is the core operational domain — program definitions, document requirements, reports,
groups, phases, and the full program lifecycle from DRAFT through ARCHIVED.

---

## Design Principles

### 1. Program Lifecycle

Programs flow through DRAFT → PUBLISHED → ACTIVE → COMPLETED → ARCHIVED. Each transition
has explicit preconditions. Only ARCHIVED programs are immutable.

### 2. Report Workflow

Student reports go through DRAFT → SUBMITTED → REVISION_REQUIRED → APPROVED. Revisions
are tracked with version history.

### 3. Closure Readiness

Before a program can be closed, the system verifies:
- All assessments finalized
- All submissions graded
- All attendance verified
- All supervision logs signed
- All certificates issued

---

## Domain Boundary

The Internship domain owns the core operational backbone of the application — the definition, lifecycle management, and execution of work placement programs. It manages program definitions with names, dates, academic year and department associations, and program types. Each program can specify document requirements that students must fulfill, organized by requirement type (document, skill, or text). Programs flow through a five-stage lifecycle: draft, published, active, completed, and archived — each transition guarded by explicit preconditions. The domain also handles student report writing and submission with a review workflow, supervisor notes on student reports, program phase and timeline management, group organization with member roles, and the full program closure and archival process including readiness checks, data snapshots, and archive report generation.

Internship does not own student identity data (User), placement slot management (Placement), registration workflows (Registration), attendance tracking (Attendance), logbook entries (Logbook), assignment grading (Assignment), assessment rubrics (Assessment), evaluation collection (Evaluation), or certificate issuance (Certificate). It owns the program container — the definition, phases, groups, and requirements — while the other operational domains fill that container with student activity data.

The domain references virtually every other domain: School for academic year and department context, Partnership for company-program linkage via placements, User for student and staff identity, and all operational domains for the data that populates program reports and closure snapshots. It coordinates these references but does not own their underlying records.

---

## Key Features

- Create, update, and delete internship programs with name, date range, academic year, department, and type.
- Manage program status through a five-stage lifecycle from draft through published, active, completed, and archived.
- Define document, skill, and text requirements that students must fulfill for each program.
- Organize students into groups with assigned member roles within a program.
- Define program phases and timeline stages to structure the placement period.
- Write and submit final program reports with a revision-based review workflow.
- Verify closure readiness by checking that all assessments are finalized, submissions graded, attendance verified, and certificates issued.
- Generate immutable archival snapshots of all program data and produce comprehensive archive reports for school records.
- Search programs by name, department, or academic year with a text filter bar.
- Sort the program list by clicking on column headers for name, status, dates, or department.
- Filter programs by status using a dropdown selector for draft, published, active, completed, and archived states.
- View a closure readiness checklist with checkmarks and cross marks for each prerequisite before closing a program.
- Export the current program list to CSV respecting active search and filter state.
- Write and submit program reports with a rich text editor supporting formatting and attachment uploads.
