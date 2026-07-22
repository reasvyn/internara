# Incident — Workplace Incident Reporting, Investigation & Resolution

> **Last updated:** 2026-07-22 **Changes:** feat — initial spec covering incident report creation,
> investigation workflow, severity classification, resolution tracking, and notification dispatch

## Description

Complete specification of the Internara Incident module: a structured workplace incident reporting
and resolution system where any authenticated user can report incidents during their PKL internship.
Reports are classified by type and severity, progress through an investigation workflow
(REPORTED → INVESTIGATING → RESOLVED → CLOSED), and trigger admin notifications on submission.
Admins manage, investigate, escalate, and resolve incidents via a filterable table interface.

---

## 1. Problem Statements

### PS-1 — Unstructured Incident Capture Without Audit Trail

Interns encounter workplace incidents — accidents, safety violations, harassment, disciplinary
issues — but have no standardized channel to report them. Paper-based or verbal reports are
inconsistent, easily lost, and create no audit trail. Without structured capture, schools cannot
track incident frequency, types, or patterns across PKL programs.

### PS-2 — No Severity Classification for Triage

All incidents are treated equally when reported verbally, meaning critical safety hazards receive
the same attention as minor concerns. Without a severity classification system, admins cannot
prioritize responses or allocate investigation resources proportionally.

### PS-3 — No Formal Investigation Workflow

Once an incident is reported, there is no defined process for tracking its investigation status.
Incidents fall through the cracks because there is no mechanism to transition a report from
"reported" to "investigating" to "resolved" with accountability at each step.

### PS-4 — Missing Resolution Tracking and Accountability

When incidents are resolved, the outcome and resolution notes are rarely documented. Without
recording who resolved the incident, when, and how, schools cannot demonstrate due diligence or
reference past resolutions for similar future incidents.

### PS-5 — Delayed Admin Notification on Incident Submission

When a student reports an incident, admins may not learn about it until the next school day.
Critical or high-severity incidents require immediate attention, but without notification dispatch,
there is no mechanism to alert administrators in real time.

### PS-6 — No Cross-Module Visibility Into Incident Patterns

Incident data informs evaluation of company partnerships, supervision quality, and program safety.
Without a structured incident module feeding data to SysAdmin dashboards and Evaluation scoring,
schools lack the cross-module visibility needed for data-driven safety decisions.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Allow any authenticated user to submit a structured incident report linked to their registration |
| G2  | Classify incidents by type (ACCIDENT, SAFETY_VIOLATION, HARASSMENT, DISCIPLINARY, OTHER) and severity (LOW, MEDIUM, HIGH, CRITICAL) |
| G3  | Enforce a deterministic status workflow: REPORTED → INVESTIGATING → RESOLVED → CLOSED with valid transition guards |
| G4  | Record resolution details (resolved_by, resolved_at, resolution_notes) on resolution |
| G5  | Dispatch `IncidentReportedNotification` to admins on report submission |
| G6  | Provide admins with a filterable, searchable incident list view |
| G7  | Allow admins to update incident details, transition status, and resolve incidents with resolution notes |
| G8  | Link incidents to registrations for cross-module reporting and evaluation context |
| G9  | Enforce role-based authorization via `IncidentReportPolicy` for all incident operations |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Evidence file attachments via Spatie Media Library (planned, not implemented) |
| NG2  | Automated severity-based notification routing (email vs. in-app) per severity level |
| NG3  | Incident escalation with auto-assignment to specific investigators |
| NG4  | Real-time incident dashboard or Pulse monitoring card for CRITICAL incidents |
| NG5  | Incident timeline audit log with immutable transition history |

---

## 3. User Stories / Use Cases

### UC-1 — Student Reports a Workplace Incident

**Actor:** Student
**Preconditions:** Student is authenticated with `student` role; student has an active registration
**Flow:**
1. Student navigates to `GET /student/incidents/report`
2. `IncidentForm` Livewire component mounts with student's active registration
3. Student fills in: `incident_date`, `type`, `severity`, `description`, `location` (optional), `action_taken` (optional)
4. Component calls `ReportIncidentAction::execute(array $data)`
5. Action creates `IncidentReport` with `status = REPORTED`, `reported_by = auth()->id()`
6. Action dispatches `IncidentReportedNotification` to admin users
7. Component redirects or flashes success message
**Postconditions:** Incident report exists with REPORTED status; admins notified

### UC-2 — Admin Investigates an Incident

**Actor:** Admin
**Preconditions:** Admin is authenticated with `admin` or `super_admin` role; at least one incident exists with REPORTED or INVESTIGATING status
**Flow:**
1. Admin navigates to `GET /admin/incidents`
2. `IncidentManager` displays filterable table of all incidents
3. Admin filters by status, severity, or type
4. Admin selects an incident and transitions status to INVESTIGATING
5. `UpdateIncidentAction::execute(IncidentReport $incident, array $data)` updates the record
**Postconditions:** Incident status updated to INVESTIGATING; admin is tracking the case

### UC-3 — Admin Resolves an Incident

**Actor:** Admin
**Preconditions:** Admin is authenticated with `admin` or `super_admin` role; incident is in INVESTIGATING status
**Flow:**
1. Admin opens incident from `IncidentManager` table
2. Admin clicks "Resolve" — resolve modal appears
3. Admin enters `resolution_notes` describing the outcome
4. `ResolveIncidentAction::execute(IncidentReport $incident, array $data)` transitions status to RESOLVED
5. Action sets `resolved_by = auth()->id()`, `resolved_at = now()`, stores `resolution_notes`
**Postconditions:** Incident status is RESOLVED with resolution metadata recorded

### UC-4 — Admin Escalates Incident Severity

**Actor:** Admin
**Preconditions:** Admin is authenticated; incident exists in REPORTED or INVESTIGATING status
**Flow:**
1. Admin opens incident detail from `IncidentManager`
2. Admin changes `severity` field to a higher level (e.g., LOW → HIGH)
3. `UpdateIncidentAction::execute(IncidentReport $incident, array $data)` persists the change
**Postconditions:** Incident severity updated; subsequent notifications reflect new severity

### UC-5 — Admin Views Incident List With Filters

**Actor:** Admin
**Preconditions:** Admin is authenticated; one or more incidents exist
**Flow:**
1. Admin navigates to `GET /admin/incidents`
2. `IncidentManager` renders table with columns: incident_date, type, severity, status, reported_by, description
3. Admin applies filters (status, severity, type) — table re-renders with filtered results
4. Admin sorts by incident_date or severity
**Postconditions:** Admin has full visibility into all incidents with contextual filtering

---

## 4. Functional Requirements

### Reporting

| ID   | Requirement |
| ---- | ----------- |
| FR-R1 | `IncidentReport` model must use `#[Fillable]` with all 12 fillable fields |
| FR-R2 | `ReportIncidentAction` must accept `array $data`, create an `IncidentReport`, and return the model instance |
| FR-R3 | New incidents must default to `status = IncidentStatus::REPORTED` |
| FR-R4 | `reported_by` must be set to `auth()->id()` on creation |
| FR-R5 | `registration_id` must link the incident to a valid Registration (FK, cascade, indexed) |
| FR-R6 | `IncidentType` enum must implement `LabelEnum` with cases: ACCIDENT, SAFETY_VIOLATION, HARASSMENT, DISCIPLINARY, OTHER |
| FR-R7 | `IncidentSeverity` enum must implement `LabelEnum` with cases: LOW, MEDIUM, HIGH, CRITICAL |
| FR-R8 | `description` (text) is required; `location` (string) and `action_taken` (text) are nullable |
| FR-R9 | `IncidentForm` Livewire component must be student-only and call `ReportIncidentAction` on submit |

### Investigation

| ID   | Requirement |
| ---- | ----------- |
| FR-I1 | `IncidentStatus` enum must implement `StatusEnum` with cases: REPORTED, INVESTIGATING, RESOLVED, CLOSED |
| FR-I2 | `validTransitions()` must return: REPORTED→[INVESTIGATING, RESOLVED], INVESTIGATING→[RESOLVED, CLOSED], RESOLVED→[CLOSED], CLOSED→[] |
| FR-I3 | `CLOSED` must be the terminal state — no further transitions allowed |
| FR-I4 | `UpdateIncidentAction` must accept an `IncidentReport` instance and an `array $data` for partial updates |
| FR-I5 | `IncidentManager` must render an admin-only table with status, severity, and type filter controls |

### Resolution

| ID   | Requirement |
| ---- | ----------- |
| FR-RE1 | `ResolveIncidentAction` must accept an `IncidentReport` instance and `array $data` containing `resolution_notes` |
| FR-RE2 | `ResolveIncidentAction` must transition status to `IncidentStatus::RESOLVED` |
| FR-RE3 | Resolution must set `resolved_by = auth()->id()` and `resolved_at = now()` |
| FR-RE4 | `resolution_notes` (text) must be stored on resolution |

### Notifications

| ID   | Requirement |
| ---- | ----------- |
| FR-N1 | `IncidentReportedNotification` must be dispatched on successful incident creation |
| FR-N2 | Notification must be sent to admin/teacher-role users per RBAC policy |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-S1 | All incident mutations must be authorized via `IncidentReportPolicy` — only admins may update; only admins may delete |
| NFR-S2 | Students may only create incidents — they must not update or delete existing reports |
| NFR-S3 | Status transitions must be validated against `IncidentStatus::validTransitions()` before persistence |
| NFR-S4 | `reported_by` and `resolved_by` must reference valid User IDs via foreign key constraints |
| NFR-P1 | Incident list load must complete in < 300ms for up to 200 incidents |
| NFR-P2 | Incident report submission must complete in < 2s including notification dispatch |
| NFR-R1 | `ReportIncidentAction` and `ResolveIncidentAction` must execute within a database transaction |
| NFR-R2 | Composite index on `(registration_id, status)` must support efficient filtered queries |
| NFR-U1 | `IncidentForm` must display all fields with appropriate input controls and validation feedback |
| NFR-U2 | `IncidentManager` must display severity and status with human-readable labels from enum `label()` methods |
| NFR-M1 | All PHP files must declare `strict_types=1` and follow PSR-12 coding standards |
| NFR-L1 | All user-facing strings must use `__()` translation helper with keys in both `lang/en/` and `lang/id/` |

---

## 6. API / Data Contracts

### IncidentReport Model

```
App\Incident\IncidentReport\Models\IncidentReport
  Table: incident_reports (UUID PK)
  Fillable: registration_id, reported_by, incident_date, type, severity, description,
            location, action_taken, status, resolved_by, resolved_at, resolution_notes
  Casts: type → IncidentType, severity → IncidentSeverity, status → IncidentStatus,
         incident_date → datetime, resolved_at → datetime
  Default: status = IncidentStatus::REPORTED
  Relations: registration() BelongsTo Registration,
             reporter() BelongsTo User (reported_by),
             resolver() BelongsTo User (resolved_by)
  Indexes: type (indexed), severity (indexed), status (indexed),
           (registration_id, status) composite
```

### IncidentType Enum

```
App\Incident\IncidentReport\Enums\IncidentType: string implements LabelEnum
  Cases: ACCIDENT='accident', SAFETY_VIOLATION='safety_violation',
         HARASSMENT='harassment', DISCIPLINARY='disciplinary', OTHER='other'
  Methods: label(): string
```

### IncidentSeverity Enum

```
App\Incident\IncidentReport\Enums\IncidentSeverity: string implements LabelEnum
  Cases: LOW='low', MEDIUM='medium', HIGH='high', CRITICAL='critical'
  Methods: label(): string
```

### IncidentStatus Enum

```
App\Incident\IncidentReport\Enums\IncidentStatus: string implements StatusEnum
  Cases: REPORTED='reported', INVESTIGATING='investigating',
         RESOLVED='resolved', CLOSED='closed'
  Terminal: CLOSED
  Transitions: REPORTED → [INVESTIGATING, RESOLVED]
               INVESTIGATING → [RESOLVED, CLOSED]
               RESOLVED → [CLOSED]
               CLOSED → []
  Methods: label(): string, validTransitions(): array
```

### Actions

| Action | Base | Accepts | Returns | Dispatches |
| ------ | ---- | ------- | ------- | ---------- |
| `ReportIncidentAction` | `BaseCommandAction` | `array $data` | `IncidentReport` | `IncidentReportedNotification` |
| `UpdateIncidentAction` | `BaseCommandAction` | `IncidentReport $incident, array $data` | `IncidentReport` | — |
| `ResolveIncidentAction` | `BaseCommandAction` | `IncidentReport $incident, array $data` | `IncidentReport` | — |

### Notification

```
App\Incident\IncidentReport\Notifications\IncidentReportedNotification
  Triggered: on ReportIncidentAction success
  Recipients: admin/teacher-role users
  Payload: incident details (type, severity, description, reported_by)
```

### Policy

```
App\Incident\IncidentReport\Policies\IncidentReportPolicy
  viewAny: super_admin, admin, teacher, supervisor
  view:    admin, reporter (reported_by matches auth user)
  create:  any authenticated user
  update:  admin
  delete:  admin
```

### Routes

| Route | Component | Name | Middleware |
| ----- | --------- | ---- | ---------- |
| `GET /student/incidents/report` | `IncidentForm` | — | `auth`, `role:student` |
| `GET /admin/incidents` | `IncidentManager` | — | `auth`, `role:admin\|super_admin` |

### Database Schema

```
incident_reports:
  id: uuid (PK)
  registration_id: foreignUuid → registrations.id (cascadeOnDelete, indexed)
  reported_by: foreignUuid → users.id (nullable)
  incident_date: dateTime
  type: string (indexed) — incident classification
  severity: string (indexed) — incident urgency
  description: text
  location: string (nullable)
  action_taken: text (nullable)
  status: string (default 'reported', indexed) — workflow state
  resolved_by: foreignUuid → users.id (nullable)
  resolved_at: dateTime (nullable)
  resolution_notes: text (nullable)
  timestamps
  Indexes: type, severity, status, (registration_id, status)
```

---

## 7. Design Decisions

### DD-1 — Shared Status Enum With Deterministic Transition Map

**Decision:** Use a single `IncidentStatus` enum implementing `StatusEnum` with an explicit `validTransitions()` method that defines all allowed state changes. Terminal state `CLOSED` allows no outgoing transitions.
**Rationale:** A centralized transition map prevents ad-hoc status changes that bypass the investigation workflow. The `StatusEnum` contract ensures every status has a `label()` for UI display and that transitions are validated consistently across all Actions.
**Trade-off:** Adding a new transition requires modifying the enum. Rejected alternative: separate transition table or event-sourced state machine (over-engineered for a 4-state workflow with no branching logic).

### DD-2 — Any-User Create Policy for Incident Reporting

**Decision:** The `IncidentReportPolicy` grants `create` permission to any authenticated user, while restricting `update` and `delete` to admins only.
**Rationale:** Students, teachers, supervisors, and admins may all witness or be affected by workplace incidents. Restricting creation to specific roles would leave reporting gaps. The asymmetry between create (open) and update (restricted) ensures anyone can report while only authorized personnel can investigate and resolve.
**Trade-off:** Higher volume of reports may require admin triage. Rejected alternative: restrict creation to students only (misses incidents witnessed by teachers/supervisors).

### DD-3 — Notification Dispatch on Report Creation

**Decision:** `ReportIncidentAction` dispatches `IncidentReportedNotification` immediately after creating the incident record, within the same transaction.
**Rationale:** Delayed notification means admins learn about incidents too late. Dispatching within the transaction ensures the notification is only sent if the incident is successfully persisted. The notification targets admin/teacher-role users so the appropriate authority is alerted.
**Trade-off:** If notification delivery fails, the incident is still recorded but admins may not be alerted. Rejected alternative: queued-only notification (risks delay if queue is backed up); event-based notification (adds indirection without clear benefit at current scale).

### DD-4 — Nullable Location and Action Taken Fields

**Decision:** `location` and `action_taken` are nullable in the schema and not required by the Action layer.
**Rationale:** Not all incidents have a specific physical location (e.g., remote harassment), and students may not know what action was taken at the time of reporting. Requiring these fields would prevent timely incident submission. The admin can update these details later during investigation.
**Trade-off:** Incomplete initial reports. Rejected alternative: make both required (delays reporting); use separate "initial report" and "detailed report" models (over-engineered for current needs).

---

## 8. Success Metrics

### 8.1 Data Integrity

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Invalid status transitions | 0 | `IncidentStatus::validTransitions()` enforced in all Actions |
| Orphaned resolved_by/resolved_at | 0 | `ResolveIncidentAction` sets both atomically |
| Incidents without registration link | 0 | `registration_id` FK required on creation |

### 8.2 Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Incident list load | < 300ms | `IncidentManager` with 200 records and active filters |
| Report submission | < 2s | `ReportIncidentAction` including notification dispatch |
| Filtered query (status + severity) | < 100ms | Composite index on (registration_id, status) + individual indexes |

### 8.3 User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Report completion time | < 3 minutes | Student fills required fields and submits |
| Enum label readability | Human-readable | All status/severity/type values display with `label()` |
| Filter responsiveness | Instant re-render | `IncidentManager` table updates on filter change via Livewire |

### 8.4 Architecture Compliance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| No model mutations in Livewire (C1) | 0 violations | All mutations via `ReportIncidentAction`, `UpdateIncidentAction`, `ResolveIncidentAction` |
| Strict types (D1) | 100% PHP files | `declare(strict_types=1)` in all non-migration files |
| No debug calls (D2) | 0 occurrences | No dd/dump/ray/var_dump/print_r/die in committed code |
| Localization (D3) | 100% user strings | All UI strings wrapped in `__()` |

---

## Quick References

- `app/Incident/IncidentReport/Models/IncidentReport.php` — Incident model with 12 fillable fields and 3 enum casts
- `app/Incident/IncidentReport/Enums/IncidentType.php` — 5-value type enum (LabelEnum)
- `app/Incident/IncidentReport/Enums/IncidentSeverity.php` — 4-value severity enum (LabelEnum)
- `app/Incident/IncidentReport/Enums/IncidentStatus.php` — 4-value status enum (StatusEnum) with transition map
- `app/Incident/IncidentReport/Actions/ReportIncidentAction.php` — Creates incident, dispatches notification
- `app/Incident/IncidentReport/Actions/UpdateIncidentAction.php` — Partial update of incident fields
- `app/Incident/IncidentReport/Actions/ResolveIncidentAction.php` — Transitions to RESOLVED with resolution metadata
- `app/Incident/IncidentReport/Policies/IncidentReportPolicy.php` — Role-based authorization for all operations
- `app/Incident/IncidentReport/Livewire/IncidentForm.php` — Student-only incident report form
- `app/Incident/IncidentReport/Livewire/IncidentManager.php` — Admin table with filters and resolve modal
- `app/Incident/IncidentReport/Notifications/IncidentReportedNotification.php` — Admin notification on report
- `docs/modules/incident.md` — Module conceptual documentation
