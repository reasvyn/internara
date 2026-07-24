# Assessment — Rubric Management, Competency Grading & Auto-Calculation

> **Last updated:** 2026-07-22 **Changes:** feat — initial spec covering rubric CRUD, competency/indicator
> scoring, multi-evaluator grading, auto-calculation from cross-module data, weighted finalization,
> and student assessment view

## Description

Complete specification of the Internara Assessment module: configurable rubric templates with nested
competency hierarchies, per-indicator scoring by authorized evaluators, automated score aggregation
from submissions/attendance/logbooks/supervision logs, weighted final score calculation with
competency-level weight redistribution, immutability after finalization, and student-facing read-only
assessment view.

---

## 1. Problem Statements

### PS-1 — Configurable Evaluation Framework Without Schema Drift

Different PKL programs require different competency frameworks — some need 3 competencies, others
need 10. Each competency has indicators weighted differently. Hardcoding rubric structures into the
database schema creates drift when programs evolve. The system must store rubric structures as
JSON while still enforcing structural validity at the Action layer.

### PS-2 — Multi-Evaluator Scoring With Role-Based Authorization

A single assessment record may be scored by multiple evaluators (admin, teacher, industry supervisor),
each authorized to score only specific competencies based on their role and mentor assignment.
Without enforcement, an evaluator could score competencies outside their authority, producing
inaccurate final grades.

### PS-3 — Automated Score Aggregation From Cross-Module Data

Manual score entry for attendance, logbook compliance, supervision quality, and submission grades
is tedious and error-prone. The system must pull raw data from Journals, Assignment, and Reports
modules, compute normalized sub-scores, and populate the assessment's `scores_data` automatically.

### PS-4 — Weighted Final Score Calculation With Normalization

Competencies and indicators have different weights. Raw scores across indicators with different
`max_score` values cannot be compared directly. The system must normalize all scores to a 0–100
scale, apply indicator weights, then competency weights, and redistribute weight when supervisor
competencies are unscored.

### PS-5 — Assessment Immutability After Finalization

Once an assessment is finalized (approved by coordinator), no further scoring changes should be
permitted. Without immutability enforcement, a late edit could silently alter a student's final
grade after it has been communicated or used for certification decisions.

### PS-6 — Student-Facing Assessment Transparency

Students need visibility into their own assessment scores and competency breakdowns to understand
their performance. Without a dedicated read-only view, students would need to ask teachers for
grade information, creating unnecessary friction.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide full CRUD for rubric templates with nested competencies and indicators stored as JSON |
| G2  | Allow authorized evaluators (admin, teacher, supervisor) to score indicators within their assigned competencies |
| G3  | Auto-calculate sub-scores from attendance, logbooks, submissions, supervision logs, and monitoring visits |
| G4  | Compute weighted final scores with normalization and weight redistribution |
| G5  | Enforce immutability after assessment finalization |
| G6  | Provide students with a read-only view of their finalized assessments |
| G7  | Dispatch domain events on finalization with audit logging |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Rubric versioning or historical rubric snapshots (rubric changes apply to future assessments) |
| NG2  | Automated remediation workflows based on low scores (future feature) |
| NG3  | Real-time collaborative scoring (evaluators score independently) |
| NG4  | Student self-assessment or peer assessment |
| NG5  | Grade appeal or dispute workflow |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Creates a Rubric Template

**Actor:** Admin
**Preconditions:** Admin is authenticated with `admin` or `super_admin` role
**Flow:**
1. Admin navigates to `admin/assessments/rubrics`
2. `RubricManager` displays list of existing rubrics
3. Admin clicks "Add Rubric", fills in `name`, `description`, `is_active`
4. Calls `CreateRubricAction::execute(name, description, isActive)`
5. Rubric created with `created_by = auth()->id()`
6. Admin adds competencies via `CreateCompetencyAction` (name, description, weight, evaluator_role, order)
7. Admin adds indicators within each competency via `CreateIndicatorAction` (name, description, max_score, weight, order)
**Postconditions:** Rubric with nested competency hierarchy exists and is active

### UC-2 — Teacher Scores a Competency Indicator

**Actor:** Teacher
**Preconditions:** Teacher is assigned as mentor for the student's registration; assessment exists with active rubric
**Flow:**
1. Teacher navigates to `admin/assessments/{registration}/grade`
2. `AssessmentGrading` mounts, calls `InitializeAssessmentAction` to ensure assessment exists
3. Teacher sees `evaluableCompetencies` (competencies matching their role + mentor assignment)
4. Teacher enters score for an indicator
5. `updatedScores` listener fires `UpdateAssessmentScoresAction` — live save
6. Score stored in `scores_data['competencies'][competencyId][indicatorId]`
**Postconditions:** Indicator score persisted, `AssessmentView` updated in real-time

### UC-3 — Admin Auto-Imports Scores From Cross-Module Data

**Actor:** Admin
**Preconditions:** Assessment exists; student has attendance, logbook, submission, supervision log data
**Flow:**
1. Admin views `AssessmentGrading` for a registration
2. Clicks "Auto Import" button
3. `AutoCalculateAssessmentAction` executes:
   - Queries `Submission` for avg verified score
   - Queries `logbooks` for submitted/total completeness %
   - Queries `Attendance` for present+late / total rate %
   - Queries `SupervisionLog` for reviewed+acknowledged / total %
   - Queries `MonitoringVisit` for verified / total %
   - Queries `Report` for approved final_score
4. Results stored under `scores_data['auto']`
**Postconditions:** Auto-calculated sub-scores populated in assessment

### UC-4 — Admin Finalizes Assessment

**Actor:** Admin
**Preconditions:** Assessment has at least one scored competency; not yet finalized
**Flow:**
1. Admin clicks "Finalize" on `AssessmentGrading`
2. Confirmation modal appears
3. `FinalizeAssessmentAction` executes in transaction:
   - Validates not already finalized
   - Validates rubric exists and at least one competency scored
   - Normalizes indicator scores to 0–100 scale
   - Applies indicator weights within each competency
   - Applies competency weights (redistributes if supervisor competencies unscored)
   - Sets `score`, `finalized_at`, `evaluator_id`
   - Dispatches `AssessmentFinalized` event
4. `LogAssessmentFinalized` listener logs via SmartLogger
**Postconditions:** Assessment is immutable; score and finalization timestamp set

### UC-5 — Student Views Their Assessment

**Actor:** Student
**Preconditions:** Student has at least one finalized assessment
**Flow:**
1. Student navigates to `/assessments`
2. `AssessmentView` loads finalized assessments filtered by student's registrations
3. Displays rubric competencies/indicators with scores and final score
**Postconditions:** Student sees read-only assessment breakdown

---

## 4. Functional Requirements

### Rubric Management

| ID   | Requirement |
| ---- | ----------- |
| FR-RM1 | `RubricManager` must be accessible at route `admin/assessments/rubrics` with `auth` and `role:super_admin\|admin` middleware |
| FR-RM2 | `CreateRubricAction` must accept `name` (string), `description` (?string), `isActive` (bool) and return `Rubric` |
| FR-RM3 | `Rubric` model must use `#[Fillable]` attribute with `internship_id`, `name`, `structure`, `is_active`, `created_by` |
| FR-RM4 | `Rubric.structure` must be cast to `array` and store nested competencies with UUID keys |
| FR-RM5 | Each competency must contain: `name`, `description`, `weight` (int), `evaluator_role` (string), `order` (int), and nested `indicators` array |
| FR-RM6 | Each indicator must contain: `name`, `description`, `max_score` (int), `weight` (int), `order` (int) |
| FR-RM7 | `CreateCompetencyAction` must append a UUID-keyed competency to `structure['competencies']` |
| FR-RM8 | `CreateIndicatorAction` must append a UUID-keyed indicator to a competency's `indicators` array |
| FR-RM9 | `DeleteCompetencyAction` and `DeleteIndicatorAction` must filter out the target from the structure |
| FR-RM10 | `RubricPolicy` must restrict create/update/delete to admin roles |

### Assessment Scoring

| ID   | Requirement |
| ---- | ----------- |
| FR-AS1 | `AssessmentGrading` must be accessible at route `admin/assessments/{registration}/grade` with `auth` and `role:super_admin\|admin` middleware |
| FR-AS2 | `InitializeAssessmentAction` must find or create an Assessment record for the registration, linking the first active rubric |
| FR-AS3 | `Assessment` model must use `#[Fillable]` with `registration_id`, `rubric_id`, `evaluator_id`, `assessment_type`, `score`, `scores_data`, `feedback`, `finalized_at` |
| FR-AS4 | `scores_data` must be structured as `{'competencies': {compId: {indId: score}}, 'auto': {...}}` |
| FR-AS5 | `ScoreIndicatorAction` must validate that the competency/indicator exist in the rubric structure |
| FR-AS6 | `ScoreIndicatorAction` must authorize the evaluator: admin bypasses; teacher/supervisor must match `evaluator_role` on competency and be a mentor for the registration |
| FR-AS7 | `ScoreIndicatorAction` must validate score range is 0..indicator's `max_score` |
| FR-AS8 | `UpdateAssessmentScoresAction` must set/unset indicator scores in `scores_data` without authorization check (used by Livewire with prior authorization) |
| FR-AS9 | `AssessmentGrading` must display `evaluableCompetencies` (current user's role + mentor match) and `readOnlyCompetencies` (others) separately |
| FR-AS10 | `AssessmentGrading.updatedScores` must trigger live save on score change |

### Auto-Calculation

| ID   | Requirement |
| ---- | ----------- |
| FR-AC1 | `AutoCalculateAssessmentAction` must skip if assessment is already finalized |
| FR-AC2 | Auto-calculation must query `Submission` model for average verified score |
| FR-AC3 | Auto-calculation must query `logbooks` table for submitted/total completeness percentage |
| FR-AC4 | Auto-calculation must query `Attendance` model for (present+late)/total rate percentage |
| FR-AC5 | Auto-calculation must query `SupervisionLog` model for (reviewed+acknowledged)/total percentage |
| FR-AC6 | Auto-calculation must query `MonitoringVisit` model for verified/total percentage |
| FR-AC7 | Auto-calculation must query `Report` model for approved final_score |
| FR-AC8 | All auto-calculated values must be stored under `scores_data['auto']` key |

### Finalization

| ID   | Requirement |
| ---- | ----------- |
| FR-FN1 | `FinalizeAssessmentAction` must reject if assessment is already finalized |
| FR-FN2 | `FinalizeAssessmentAction` must reject if no rubric is linked |
| FR-FN3 | `FinalizeAssessmentAction` must reject if no competencies have been scored |
| FR-FN4 | `FinalizeAssessmentAction` must normalize indicator scores to 0–100 scale (score / max_score * 100) |
| FR-FN5 | `FinalizeAssessmentAction` must apply indicator weights within each competency |
| FR-FN6 | `FinalizeAssessmentAction` must apply competency weights to compute the overall score |
| FR-FN7 | `FinalizeAssessmentAction` must redistribute weight when supervisor competencies are unscored (proportional increase of remaining competencies) |
| FR-FN8 | `FinalizeAssessmentAction` must set `score`, `finalized_at`, and `evaluator_id` |
| FR-FN9 | `FinalizeAssessmentAction` must dispatch `AssessmentFinalized` event |
| FR-FN10 | `AssessmentResult::isFinalized()` must return true when `finalizedAt !== null` |
| FR-FN11 | `AssessmentResult::calculateTotalScore()` must sum all indicator scores from `scores_data['competencies']` |

### Student View

| ID   | Requirement |
| ---- | ----------- |
| FR-SV1 | `AssessmentView` must be accessible at route `assessments` with `auth` middleware |
| FR-SV2 | `AssessmentView` must display only finalized assessments for the current student's registrations |
| FR-SV3 | `AssessmentView` must eager-load rubric competencies/indicators and internship data |

### Assessment Types

| ID   | Requirement |
| ---- | ----------- |
| FR-AT1 | `assessment_type` must support: `midterm`, `final`, `periodic`, `industry` (default: `final`) |
| FR-AT2 | Unique constraint must prevent duplicate assessment per (`registration_id`, `assessment_type`, `evaluator_id`) |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-S1 | All assessment mutations must be authorized via `AssessmentPolicy` — no bypass allowed |
| NFR-S2 | `ScoreIndicatorAction` must enforce evaluator role + mentor assignment — teacher cannot score supervisor competencies |
| NFR-S3 | Finalized assessments must be immutable — no score updates permitted |
| NFR-S4 | Rubric structure validation must occur at Action layer, not just in UI |
| NFR-P1 | `AssessmentGrading` page must load in < 1s including rubric structure and existing scores |
| NFR-P2 | Auto-calculation must complete in < 5s for a single registration |
| NFR-P3 | `AssessmentView` for students must load in < 500ms |
| NFR-R1 | Finalization must be wrapped in a database transaction |
| NFR-R2 | Weight redistribution must be deterministic — same inputs always produce same final score |
| NFR-U1 | Score changes must save in real-time without explicit save button |
| NFR-U2 | `evaluableCompetencies` and `readOnlyCompetencies` must be visually distinguished |
| NFR-U3 | Finalization must show a confirmation modal before proceeding |
| NFR-M1 | All PHP files must declare `strict_types=1` and follow PSR-12 |
| NFR-L1 | All user-facing strings must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |

---

## 6. API / Data Contracts

### Rubric Model

```
App\Assessment\Rubric\Models\Rubric
  Table: rubrics (UUID PK)
  Fillable: internship_id, name, structure, is_active, created_by
  Casts: structure → array, is_active → boolean
  Relations: internship() BelongsTo Internship, createdBy() BelongsTo User, assessments() HasMany Assessment
  Factory: RubricFactory
```

### Assessment Model

```
App\Assessment\Models\Assessment
  Table: assessments (UUID PK)
  Fillable: registration_id, rubric_id, evaluator_id, assessment_type, score, scores_data, feedback, finalized_at
  Casts: scores_data → array, score → float, finalized_at → datetime
  Relations: registration() BelongsTo Registration, rubric() BelongsTo Rubric, evaluator() BelongsTo User
  Bridge: asAssessmentResult() → AssessmentResult
  Unique: (registration_id, assessment_type, evaluator_id)
  Factory: AssessmentFactory
```

### AssessmentResult Entity

```
App\Assessment\Entities\AssessmentResult extends BaseEntity (final readonly)
  Constructor: (?Carbon $finalizedAt, array|float $scoresData, float $score)
  Factory: fromModel(Model)
  Methods: isFinalized(): bool, calculateTotalScore(): float
```

### EvaluatorRole Enum

```
App\Assessment\Enums\EvaluatorRole: string
  Implements: LabelEnum
  Cases: ADMIN='admin', TEACHER='teacher', SUPERVISOR='supervisor', SYSTEM='system'
```

### Assessment Actions

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| `InitializeAssessmentAction` | `BaseCommandAction` | `string $registrationId` | `array{assessment: ?Assessment, rubric: ?Rubric}` |
| `AutoCalculateAssessmentAction` | `BaseCommandAction` | `Assessment` | `Assessment` |
| `ScoreIndicatorAction` | `BaseCommandAction` | `Assessment, Rubric, competencyId, indicatorId, float score, User` | `Assessment` |
| `UpdateAssessmentScoresAction` | `BaseCommandAction` | `Assessment, competencyId, indicatorId, ?float score` | `Assessment` |
| `FinalizeAssessmentAction` | `BaseCommandAction` | `Assessment, User $finalizer` | `Assessment` |

### Rubric Actions

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| `CreateRubricAction` | `BaseCommandAction` | `name, ?description, bool isActive` | `Rubric` |
| `UpdateRubricAction` | `BaseCommandAction` | `Rubric, name, ?description, bool isActive` | `Rubric` |
| `DeleteRubricAction` | `BaseCommandAction` | `Rubric` | `void` |
| `CreateCompetencyAction` | `BaseCommandAction` | `Rubric, name, ?description, weight, evaluatorRole, order` | `Rubric` |
| `UpdateCompetencyAction` | `BaseCommandAction` | `Rubric, competencyId, name, ?description, weight, evaluatorRole, order` | `Rubric` |
| `DeleteCompetencyAction` | `BaseCommandAction` | `Rubric, competencyId` | `Rubric` |
| `CreateIndicatorAction` | `BaseCommandAction` | `Rubric, competencyId, name, ?description, maxScore, weight, order` | `Rubric` |
| `UpdateIndicatorAction` | `BaseCommandAction` | `Rubric, competencyId, indicatorId, name, ?description, maxScore, weight, order` | `Rubric` |
| `DeleteIndicatorAction` | `BaseCommandAction` | `Rubric, competencyId, indicatorId` | `void` |

### Events

| Event | Dispatched By |
| ----- | ------------- |
| `AssessmentFinalized` | `FinalizeAssessmentAction` |

### Listeners

| Listener | Event | Queued |
| -------- | ----- | ------ |
| `LogAssessmentFinalized` | `AssessmentFinalized` | No |

### Policy

| Policy | Abilities |
| ------ | --------- |
| `AssessmentPolicy` | viewAny: super_admin/admin/teacher, view: admin/evaluator/student-of-registration, create: super_admin/admin/teacher, update: admin/(evaluator+not finalized)/mentorProxy, finalize: super_admin/admin/teacher, delete: admin+not finalized |

### Routes

| Route | Component | Name | Middleware |
| ----- | --------- | ---- | ---------- |
| `GET /assessments` | `AssessmentView` | `assessments` | `auth` |
| `GET /admin/assessments/rubrics` | `RubricManager` | `sysadmin.assessments.rubrics` | `auth`, `role:super_admin\|admin` |
| `GET /admin/assessments/{registration}/grade` | `AssessmentGrading` | `sysadmin.assessments.grade` | `auth`, `role:super_admin\|admin` |

### Database Schema

```
rubrics:
  id: uuid (PK)
  internship_id: foreignUuid → internships.id (cascadeOnDelete, nullable, indexed)
  name: string
  structure: json (nullable) — nested competencies/indicators
  is_active: boolean (default true)
  created_by: foreignUuid → users.id (nullOnDelete, nullable)
  timestamps

assessments:
  id: uuid (PK)
  registration_id: foreignUuid → registrations.id (cascadeOnDelete)
  evaluator_id: foreignUuid → users.id (cascadeOnDelete)
  rubric_id: foreignUuid → rubrics.id (nullOnDelete, nullable, indexed)
  assessment_type: string(30) (default 'final')
  score: float (nullable)
  scores_data: json (nullable)
  feedback: text (nullable)
  finalized_at: timestamp (nullable)
  timestamps
  Unique: (registration_id, assessment_type, evaluator_id)
  Indexes: (registration_id, assessment_type), (evaluator_id, assessment_type), assessment_type
```

---

## 7. Design Decisions

### DD-1 — JSON Rubric Structure Instead of Normalized Tables

**Decision:** Store rubric competencies and indicators as a nested JSON blob in `rubrics.structure` rather than separate `competencies` and `indicators` tables.
**Rationale:** Rubrics are immutable templates — once created, their structure doesn't change per-assessment. JSON avoids JOIN-heavy queries when reading rubrics for display. The Action layer enforces structural validity (competency/indicator existence checks in `ScoreIndicatorAction`). UUID keys prevent ordering issues and provide stable references.
**Trade-off:** Cannot query individual competencies across rubrics via SQL. Rejected alternative: normalized tables (would require complex migrations for rubric versioning, which is out of scope).

### DD-2 — EvaluatorRole on Competency, Not on Assessment

**Decision:** Each competency in the rubric structure specifies an `evaluator_role` (admin, teacher, supervisor) that determines which evaluators can score it.
**Rationale:** In PKL programs, different stakeholders evaluate different competencies — teachers assess academic competencies, supervisors assess workplace competencies. Placing the role on the competency (not the assessment) allows a single assessment to be scored by multiple evaluators, each working within their authorized scope.
**Trade-off:** Complexity in `ScoreIndicatorAction` authorization logic. Rejected alternative: single evaluator per assessment (too restrictive for real-world PKL programs).

### DD-3 — Weight Redistribution for Unscored Competencies

**Decision:** When supervisor competencies are unscored during finalization, redistribute their weight proportionally across the remaining scored competencies.
**Rationale:** If a supervisor never scores their assigned competencies (e.g., company supervisor is unavailable), the final score should still reflect the student's performance in the scored areas without penalizing them for the missing evaluation. Proportional redistribution maintains the relative importance of scored competencies.
**Trade-off:** Final score interpretation changes depending on which competencies were scored. Rejected alternative: zero-score unscored competencies (unfair to students); reject finalization if any competency is unscored (too strict for real-world scenarios).

### DD-4 — Entity Bridge for Assessment Immutability Check

**Decision:** `Assessment` model provides `asAssessmentResult()` bridge that returns `AssessmentResult` entity. The entity's `isFinalized()` method is used by Actions to gate mutations.
**Rationale:** Keeps the Model as a pure persistence object (C1 compliance). The Entity provides a clean, immutable snapshot for business rule evaluation. `calculateTotalScore()` on the entity encapsulates the sum logic without polluting the Model.
**Trade-off:** Extra class. Rejected alternative: `isFinalized` check inline in each Action (violates DRY, increases risk of missing the check in new Actions).

### DD-5 — Live-Save via Livewire `updatedScores` Listener

**Decision:** Score changes trigger immediate persistence via `UpdateAssessmentScoresAction` through Livewire's `updatedScores` listener, without an explicit save button.
**Rationale:** Teachers score many indicators in a single session. Requiring explicit saves would increase cognitive load and risk data loss on navigation. Live-save provides a seamless experience while the Action layer ensures data integrity.
**Trade-off:** More network requests. Rejected alternative: batch save on page exit (risk of data loss on crash/timeout).

### DD-6 — Auto-Calculation Stores Raw Module Data, Not Final Scores

**Decision:** `AutoCalculateAssessmentAction` stores computed sub-scores under `scores_data['auto']` as a separate namespace, keeping manual competency scores under `scores_data['competencies']`.
**Rationale:** Auto-calculated data serves as reference or as inputs to specific competencies, but should not override manual expert judgment. Storing them separately allows evaluators to import auto-scores selectively and adjust based on qualitative assessment.
**Trade-off:** Auto-scores must be manually mapped to competencies if desired. Rejected alternative: auto-populate competency scores directly (removes evaluator judgment).

---

## 8. Success Metrics

### 8.1 Data Integrity

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Invalid score range submissions | 0 | `ScoreIndicatorAction` rejects scores outside 0..max_score |
| Unauthorized scoring attempts | 0 | `ScoreIndicatorAction` enforces evaluator role + mentor match |
| Post-finalization edits | 0 | All scoring Actions check `isFinalized()` before proceeding |
| Structural rubric corruption | 0 | Action layer validates competency/indicator existence in rubric JSON |

### 8.2 Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Assessment grading page load | < 1s | Rubric structure + existing scores + student data |
| Auto-calculation (single registration) | < 5s | Cross-module queries + computation |
| Student assessment view | < 500ms | Finalized assessments + rubric display |
| Score live-save latency | < 300ms | Livewire update round-trip |

### 8.3 User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Score entry to persistence | Immediate (live-save) | No explicit save button required |
| Finalization confirmation | Modal dialog before commit | `AssessmentGrading.askFinalize()` |
| Read-only competencies | Visually distinct from evaluable | CSS differentiation in `AssessmentGrading` |
| Weight redistribution accuracy | Deterministic | Same inputs → same final score across runs |

### 8.4 Architecture Compliance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| No model mutations in Livewire (C1) | 0 violations | All mutations via Actions |
| No service locator (C2) | 0 violations | Constructor injection throughout |
| Entity purity (C5) | 0 violations | `AssessmentResult` imports no Actions/Services |
| Strict types (D1) | 100% files | `declare(strict_types=1)` in all PHP files |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [placement.md](placement.md) | Active placement records — assessment scores student performance within a placement |

### Build Guide
After implementing this spec, the system has rubric-based assessment with scoring frameworks, competency evaluation, and grade calculation. Assessments are the primary scoring mechanism for student performance. The next step is to build evaluation, which gathers qualitative feedback alongside these quantitative scores.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [evaluation.md](evaluation.md) | Evaluation gathers feedback that complements assessment scores |
| 2 | [certification.md](certification.md) | Certification combines assessment scores with evaluation data |

---

## Quick References

- `app/Assessment/Models/Assessment.php` — Assessment model with fillable, casts, bridge
- `app/Assessment/Rubric/Models/Rubric.php` — Rubric model with JSON structure
- `app/Assessment/Entities/AssessmentResult.php` — Entity with finalization check and score calculation
- `app/Assessment/Enums/EvaluatorRole.php` — Evaluator role enum (4 cases)
- `app/Assessment/Actions/InitializeAssessmentAction.php` — Assessment bootstrapping
- `app/Assessment/Actions/AutoCalculateAssessmentAction.php` — Cross-module score aggregation
- `app/Assessment/Actions/ScoreIndicatorAction.php` — Authorized indicator scoring
- `app/Assessment/Actions/UpdateAssessmentScoresAction.php` — Lightweight score update for Livewire
- `app/Assessment/Actions/FinalizeAssessmentAction.php` — Weighted finalization with redistribution
- `app/Assessment/Rubric/Actions/` — 9 rubric CRUD actions
- `app/Assessment/Livewire/AssessmentGrading.php` — Grading UI with live-save
- `app/Assessment/Livewire/AssessmentView.php` — Student read-only view
- `app/Assessment/Rubric/Livewire/RubricManager.php` — Rubric template management
- `app/Assessment/Events/AssessmentFinalized.php` — Finalization event
- `app/Assessment/Listeners/LogAssessmentFinalized.php` — Audit logging listener
- `app/Assessment/Policies/AssessmentPolicy.php` — Role-based authorization
- `database/migrations/2026_01_03_000006_create_rubrics_table.php` — Rubrics schema
- `database/migrations/2026_01_04_000011_create_assessments_table.php` — Assessments schema
- `routes/web/assessment.php` — Route definitions
- `docs/modules/assessment.md` — Module conceptual documentation
