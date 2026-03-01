# Application Blueprint: Academic Scoping (BP-ORG-F202)

**Blueprint ID**: `BP-ORG-F202` | **Requirement ID**: `SYRS-F-202` | **Scope**:
`Institutional Architecture`

---

## 1. Strategic Context

- **Spec Alignment**: This blueprint authorizes the automatic partitioning of domain data required
  to satisfy **[SYRS-F-202]** (Academic Scoping).
- **Objective**: Establish a systemic data isolation framework that automatically filters all
  vocational and administrative records by the active institutional cycle (Academic Year) and
  departmental boundaries.
- **Rationale**: Vocational systems must manage distinct cohorts. By enforcing academic scoping at
  the architectural level (via Global Scopes), we prevent "Data Drift" (e.g., seeing students from
  last year in the current dashboard) and ensure data sovereignty for departmental staff.

---

## 2. Logic & Architecture (Systemic View)

### 2.1 The Academic Scope Invariant

- **Global Filter Protocol**: All models representing cohort-based data (Internship, Journal,
  Attendance, Assessment) MUST implement the `HasAcademicYear` concern.
- **Bootstrapping**: The concern MUST automatically apply a Global Scope that appends
  `WHERE academic_year_id = {active_uuid}` to all Eloquent queries.

### 2.2 Persistence Strategy

- **Default Inheritance**: On model creation, the `HasAcademicYear` trait MUST automatically resolve
  the `active_academic_year` from system settings and populate the foreign key if it is null.
- **Database Hardening**: Every scoped table MUST include an indexed `academic_year_id` column to
  ensure query performance under large datasets.

### 2.3 Service Contract Specifications

- **`Modules\Core\Academic\Contracts\AcademicYearManager`**: The authoritative source for retrieving
  and switching the active institutional cycle.

---

## 3. Presentation Strategy (User Experience View)

### 3.1 UX Workflow

- **Cycle Switcher**: Admins and Staff MUST have a persistent "Active Year" indicator in the
  navigation bar, allowing them to switch contexts to view historical data.
- **Contextual Context**: All UI labels and report headers MUST dynamically reflect the active
  academic year (e.g., "Internship Dashboard - 2025/2026").

### 3.2 Interface Design

- **Scope Indicator**: A standardized UI badge (`ui::scope-badge`) that informs the user which
  cohort's data they are currently interacting with.

---

## 4. Verification Strategy (V&V View)

### 4.1 Unit Verification

- **Default Inheritance**: Unit tests verifying that a new `InternshipRegistration` instance
  automatically receives the active year UUID upon instantiation.
- **Scope Removal**: Tests verifying that the `withoutAcademicYear()` macro correctly bypasses the
  global filter for administrative reporting.

### 4.2 Feature Validation

- **Scope Leak Audit**: Integration tests verifying that setting the active year to "2025"
  physically prevents the retrieval of "2024" records in standard controllers.
- **Query Efficiency**: Verification that the academic year `WHERE` clause is applied as the first
  filter in the generated SQL.

### 4.3 Architecture Verification

- **Trait Implementation Audit**: Pest Arch tests ensuring that 100% of domain models in the
  `Internship` and `Journal` modules implement the `HasAcademicYear` concern.

---

## 5. Compliance & Standardization (Integrity View)

### 5.1 Data Sovereignty

- **Indexed Queries**: The system MUST enforce the presence of a database index on the
  `academic_year_id` column for every scoped table via the migration standards.

---

### 5.2 Mandatory 3S Audit Alignment

To guarantee architectural integrity and prevent systemic entropy, this implementation MUST strictly
adhere to the project's 3S Protocol:

- **S1 (Secure)**: Every state-altering method within the Service Layer MUST explicitly invoke
  `Gate::authorize()` prior to execution to prevent IDOR and Broken Access Control. Sensitive PII
  fields MUST utilize the `encrypted` cast.
- **S2 (Sustain)**: All files MUST declare `strict_types=1`. Virtual attributes MUST be implemented
  using PHP 8.4 Property Hooks. All user-facing strings and exceptions MUST be localized via
  `__('key')`. Every public method MUST contain professional PHPDoc explaining its intent.
- **S3 (Scalable)**: Cross-module interactions MUST use **Contract-First** dependency injection
  (Interfaces). All domain models MUST implement `HasUuid` (and `HasStatus`, `HasAcademicYear` where
  applicable). Asynchronous side-effects MUST utilize Domain Events with lightweight, UUID-only
  payloads.

## 6. Documentation Strategy (Knowledge View)

### 6.1 Engineering Record

- **Governance**: Update `docs/dev/conventions.md` to mandate academic scoping for all cohort-based
  domain models.

### 6.2 Stakeholder Manuals

- **Admin Guide**: Update `docs/wiki/program-setup.md` to explain how academic year scoping affects
  data visibility across modules.

---

## 7. Exit Criteria & Quality Gates

- **Acceptance Criteria**: Academic scoping active; Default inheritance verified; Leak audit passed;
  Performance indexes confirmed.
- **Verification Protocols**: 100% pass rate in the institutional architecture test suite.
- **Quality Gate**: Architectural audit confirms zero instances of "Unscoped Cohort Data" in the
  operational layer.

---

_Application Blueprints prevent architectural decay and ensure continuous alignment with the
foundational specifications._
