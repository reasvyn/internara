# Evaluation — Google Forms-Like Feedback System With Weighted Scoring

> **Last updated:** 2026-07-22 **Changes:** feat — initial spec covering evaluation form builder,
> section/question management, response submission, weighted score calculation, score bands,
> and polymorphic target system

## Description

Complete specification of the Internara Evaluation module: a reusable, Google Forms-like feedback
system where admins build evaluation forms with typed, weighted questions organized into sections.
Evaluators (students, teachers, admins) submit responses targeting any PKL aspect (mentor, program,
company). Scores are auto-calculated from weighted answers and classified into score bands. The
module is currently data-layer only — Models and migrations exist; Actions, Entities, Enums,
Livewire, Events, and Routes are planned.

---

## 1. Problem Statements

### PS-1 — Standardized Feedback Collection Across PKL Roles

Students need to evaluate their mentors, teachers, and company programs. Companies need to
evaluate student performance. Without a standardized form system, schools use ad-hoc paper
surveys or spreadsheets, making aggregation impossible and comparisons unreliable.

### PS-2 — Configurable Form Structure With Typed Questions

Different evaluation contexts require different question types —Likert scales for satisfaction,
multiple choice for preferences, free text for qualitative feedback. A one-size-fits-all form
cannot capture the nuances of mentor effectiveness vs. company facilities vs. program quality.

### PS-3 — Weighted Scoring With Automatic Aggregation

Not all questions contribute equally to an overall score. A mentor's guidance quality may be
weighted more heavily than administrative responsiveness. Manual scoring is error-prone and
inconsistent across evaluators.

### PS-4 — Polymorphic Target System for Multi-Context Evaluation

A single evaluation form might target a mentor (specific person), a program (internship structure),
or a company (organization). The system must support polymorphic targeting so forms are reusable
across different evaluation contexts without duplicating form definitions.

### PS-5 — Immutable Submissions With Score Classification

Once an evaluator submits their feedback, it must be frozen — no edits allowed. This prevents
retrospective bias and ensures score integrity. Responses should be classified into human-readable
score bands (EXCELLENT, GOOD, etc.) for quick interpretation.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide admin UI for creating reusable evaluation forms with sections and typed questions |
| G2  | Support 6 question types: rating_1_5, rating_1_10, yes_no, multiple_choice, text, agreement |
| G3  | Allow evaluators to submit responses targeting mentor/program/company polymorphically |
| G4  | Auto-calculate overall_score from weighted answers using `sum(score * weight) / sum(weight)` |
| G5  | Classify scores into 5 bands: EXCELLENT (85–100), GOOD (70–84), SATISFACTORY (55–69), NEEDS_IMPROVEMENT (40–54), POOR (0–39) |
| G6  | Enforce submission immutability — no editing after submit |
| G7  | Store evaluation structure as normalized relational data (forms → sections → questions) |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Real-time collaborative form editing |
| NG2  | Anonymous evaluation submissions (evaluator identity is always recorded) |
| NG3  | Automated notification triggers based on evaluation scores |
| NG4  | Multi-language form content (forms are in the admin's chosen locale) |
| NG5  | Form templates import/export or cross-tenant sharing |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Creates an Evaluation Form

**Actor:** Admin
**Preconditions:** Admin is authenticated with `admin` or `super_admin` role
**Flow:**
1. Admin navigates to evaluation form builder (planned route)
2. Creates a new form with `name`, `description`, `target_type` (teacher/supervisor/program/company/overall)
3. Adds sections with `title`, `description`, `order`
4. Adds questions to sections (or directly to form) with `question_text`, `question_type`, `options` (for multiple_choice), `weight`, `order`, `is_required`
5. Activates the form (`is_active = true`)
**Postconditions:** Active evaluation form exists with structured questions

### UC-2 — Student Evaluates Their Mentor

**Actor:** Student
**Preconditions:** Student has an active registration with an assigned mentor; an active form targeting `mentor` exists
**Flow:**
1. Student navigates to evaluation submission (planned route)
2. Selects mentor from dropdown
3. Fills in answers for each question (rating scales, text, multiple choice)
4. Submits response
5. System creates `EvaluationResponse` with `evaluator_id = student`, `target_type = mentor`, `target_id = mentor_user_id`
6. System creates `EvaluationAnswer` for each question with `value` (raw answer) and `score` (numeric derivation)
7. System computes `overall_score = sum(score * weight) / sum(weight)`
**Postconditions:** Response submitted, score calculated, submission immutable

### UC-3 — Admin Views Evaluation Results

**Actor:** Admin
**Preconditions:** At least one evaluation response exists for a form
**Flow:**
1. Admin navigates to evaluation results (planned route)
2. Views aggregated scores per form, filtered by target_type
3. Sees overall_score with band classification (EXCELLENT/GOOD/etc.)
4. Drills into individual responses for detailed per-question breakdown
**Postconditions:** Admin has visibility into evaluation outcomes

---

## 4. Functional Requirements

### Form Builder

| ID   | Requirement |
| ---- | ----------- |
| FR-FB1 | `EvaluationForm` model must use `#[Fillable]` with `name`, `description`, `target_type`, `is_active`, `created_by` |
| FR-FB2 | `target_type` must support: `teacher`, `supervisor`, `program`, `company`, `overall` |
| FR-FB3 | `EvaluationSection` model must support ordered grouping with `form_id`, `title`, `description`, `order` |
| FR-FB4 | Cascade delete: form deleted → sections, questions, responses cascade |
| FR-FB5 | Section deleted → questions nullOnDelete (preserve question-level history) |

### Question Management

| ID   | Requirement |
| ---- | ----------- |
| FR-QM1 | `EvaluationQuestion` must support 6 types: `rating_1_5`, `rating_1_10`, `yes_no`, `multiple_choice`, `text`, `agreement` |
| FR-QM2 | `options` field (JSON) must be populated only for `multiple_choice` type |
| FR-QM3 | `weight` field (int, default 1) must determine the question's contribution to overall score |
| FR-QM4 | `is_required` field (bool, default true) must gate submission completeness |
| FR-QM5 | Questions must be orderable via `order` field within their section or form |

### Response Submission

| ID   | Requirement |
| ---- | ----------- |
| FR-RS1 | `EvaluationResponse` must link `evaluator_id` → User, `form_id` → EvaluationForm |
| FR-RS2 | `target_type` + `target_id` must form a polymorphic pair (mentor/program/company) |
| FR-RS3 | `registration_id` must optionally link the response to an enrollment context |
| FR-RS4 | `EvaluationAnswer` must enforce unique constraint on `(response_id, question_id)` — one answer per question per response |
| FR-RS5 | `submitted_at` must be set on submission (useCurrent default) |
| FR-RS6 | Once submitted, responses must be immutable (enforcement via planned Action layer) |

### Score Calculation

| ID   | Requirement |
| ---- | ----------- |
| FR-SC1 | Per-question `score` must be derived from `value` (e.g., rating_1_5: score = value * 20 to normalize to 0–100) |
| FR-SC2 | `overall_score` must be computed as `sum(answer_score * question_weight) / sum(question_weight)` |
| FR-SC3 | Text questions (no numeric score) must be excluded from weighted average |
| FR-SC4 | `overall_score` must be stored as float on `EvaluationResponse` |
| FR-SC5 | Score bands: EXCELLENT (85–100), GOOD (70–84), SATISFACTORY (55–69), NEEDS_IMPROVEMENT (40–54), POOR (0–39) |

### Form Lifecycle

| ID   | Requirement |
| ---- | ----------- |
| FR-FL1 | `is_active` flag must control form availability — inactive forms cannot receive new responses |
| FR-FL2 | Composite index on `(target_type, is_active)` must support efficient form lookup |
| FR-FL3 | `created_by` must track the admin who created the form (FK → users, nullOnDelete) |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-S1 | All form mutations must be authorized via Policy classes (admin-only writes) |
| NFR-S2 | Response submission must validate evaluator identity matches `auth()->id()` |
| NFR-S3 | Submitted responses must be immutable — no UPDATE or DELETE permitted |
| NFR-S4 | `target_id` polymorphic values must be validated as existing records |
| NFR-P1 | Form listing must load in < 300ms for up to 50 forms |
| NFR-P2 | Response submission with 20 questions must complete in < 2s |
| NFR-P3 | Score calculation must complete in < 100ms per response |
| NFR-R1 | Response creation must be wrapped in a database transaction |
| NFR-R2 | Unique constraint on `(response_id, question_id)` must prevent duplicate answers at DB level |
| NFR-U1 | Evaluation forms must display question types with appropriate input controls |
| NFR-U2 | Score bands must be displayed with human-readable labels alongside numeric scores |
| NFR-M1 | All PHP files must declare `strict_types=1` and follow PSR-12 |
| NFR-L1 | All user-facing strings must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |

---

## 6. API / Data Contracts

### EvaluationForm Model

```
App\Evaluation\Models\EvaluationForm
  Table: evaluation_forms (UUID PK)
  Fillable: name, description, target_type, is_active, created_by
  Casts: is_active → boolean
  Relations: createdBy() BelongsTo User, sections() HasMany EvaluationSection,
             questions() HasMany EvaluationQuestion, responses() HasMany EvaluationResponse
  Indexes: is_active, (target_type, is_active)
  Factory: EvaluationFormFactory
```

### EvaluationSection Model

```
App\Evaluation\Models\EvaluationSection
  Table: evaluation_sections (UUID PK)
  Fillable: form_id, title, description, order
  Relations: form() BelongsTo EvaluationForm, questions() HasMany EvaluationQuestion
  Indexes: (form_id, order)
  Factory: EvaluationSectionFactory
```

### EvaluationQuestion Model

```
App\Evaluation\Models\EvaluationQuestion
  Table: evaluation_questions (UUID PK)
  Fillable: form_id, section_id, question_text, question_type, options, weight, order, is_required
  Casts: options → json, weight → integer, order → integer, is_required → boolean
  Relations: form() BelongsTo EvaluationForm, section() BelongsTo EvaluationSection,
             answers() HasMany EvaluationAnswer
  Indexes: (form_id, order), (section_id, order)
  Factory: EvaluationQuestionFactory
```

### EvaluationResponse Model

```
App\Evaluation\Models\EvaluationResponse
  Table: evaluation_responses (UUID PK)
  Fillable: form_id, evaluator_id, target_type, target_id, registration_id, overall_score, notes, submitted_at
  Casts: overall_score → float, submitted_at → datetime
  Relations: form() BelongsTo EvaluationForm, evaluator() BelongsTo User,
             registration() BelongsTo Registration, answers() HasMany EvaluationAnswer
  Indexes: (form_id, evaluator_id), (target_type, target_id), submitted_at,
           (registration_id, form_id), registration_id
  Factory: EvaluationResponseFactory
```

### EvaluationAnswer Model

```
App\Evaluation\Models\EvaluationAnswer
  Table: evaluation_answers (UUID PK)
  Fillable: response_id, question_id, value, score
  Casts: score → float
  Relations: response() BelongsTo EvaluationResponse, question() BelongsTo EvaluationQuestion
  Unique: (response_id, question_id)
  Factory: EvaluationAnswerFactory
```

### Planned Enums

```
App\Evaluation\Enums\TargetType: string
  Cases: TEACHER='teacher', SUPERVISOR='supervisor', PROGRAM='program', COMPANY='company', OVERALL='overall'

App\Evaluation\Enums\QuestionType: string
  Cases: RATING_1_5='rating_1_5', RATING_1_10='rating_1_10', YES_NO='yes_no',
         MULTIPLE_CHOICE='multiple_choice', TEXT='text', AGREEMENT='agreement'

App\Evaluation\Enums\ScoreBand: string
  Cases: EXCELLENT='excellent', GOOD='good', SATISFACTORY='satisfactory',
         NEEDS_IMPROVEMENT='needs_improvement', POOR='poor'
  Methods: fromScore(float $score): self, label(): string
```

### Planned Actions

| Action | Base | Accepts | Returns | Status |
| ------ | ---- | ------- | ------- | ------ |
| `CreateEvaluationFormAction` | `BaseCommandAction` | `StoreEvaluationFormData` | `EvaluationForm` | Planned |
| `UpdateEvaluationFormAction` | `BaseCommandAction` | `EvaluationForm, StoreEvaluationFormData` | `EvaluationForm` | Planned |
| `SubmitEvaluationResponseAction` | `BaseCommandAction` | `SubmitEvaluationResponseData` | `EvaluationResponse` | Planned |
| `CalculateEvaluationScoreAction` | `BaseProcessAction` | `EvaluationResponse` | `EvaluationResponse` | Planned |

### Planned Routes

| Route | Component | Name | Middleware | Status |
| ----- | --------- | ---- | ---------- | ------ |
| `GET /admin/evaluations` | Form Manager (Livewire) | `sysadmin.evaluations` | `auth`, `role:super_admin\|admin` | Planned |
| `GET /admin/evaluations/{form}/responses` | Response Viewer (Livewire) | `sysadmin.evaluations.responses` | `auth`, `role:super_admin\|admin` | Planned |
| `GET /student/evaluations` | Submission Form (Livewire) | `student.evaluations` | `auth`, `role:student` | Planned |

### Database Schema

```
evaluation_forms:
  id: uuid (PK)
  name: string
  description: text (nullable)
  target_type: string(30) — teacher/supervisor/program/company/overall
  is_active: boolean (default true, indexed)
  created_by: foreignUuid → users.id (nullOnDelete, nullable)
  timestamps
  Indexes: is_active, (target_type, is_active)

evaluation_sections:
  id: uuid (PK)
  form_id: foreignUuid → evaluation_forms.id (cascadeOnDelete)
  title: string
  description: text (nullable)
  order: unsigned int (default 0)
  timestamps
  Indexes: (form_id, order)

evaluation_questions:
  id: uuid (PK)
  form_id: foreignUuid → evaluation_forms.id (cascadeOnDelete)
  section_id: foreignUuid → evaluation_sections.id (nullable, nullOnDelete)
  question_text: text
  question_type: string(30) (default 'rating_1_5')
  options: json (nullable)
  weight: unsigned int (default 1)
  order: unsigned int (default 0)
  is_required: boolean (default true)
  timestamps
  Indexes: (form_id, order), (section_id, order)

evaluation_responses:
  id: uuid (PK)
  form_id: foreignUuid → evaluation_forms.id (cascadeOnDelete)
  evaluator_id: foreignUuid → users.id (cascadeOnDelete)
  target_type: string(30) — mentor/program/company
  target_id: uuid
  registration_id: foreignUuid → registrations.id (nullable, nullOnDelete)
  overall_score: float (nullable)
  notes: text (nullable)
  submitted_at: timestamp (useCurrent)
  timestamps
  Indexes: (form_id, evaluator_id), (target_type, target_id), submitted_at,
           (registration_id, form_id), registration_id

evaluation_answers:
  id: uuid (PK)
  response_id: foreignUuid → evaluation_responses.id (cascadeOnDelete)
  question_id: foreignUuid → evaluation_questions.id (cascadeOnDelete)
  value: text (nullable)
  score: float (nullable)
  timestamps
  Unique: (response_id, question_id)
```

---

## 7. Design Decisions

### DD-1 — Normalized Relational Structure for Forms, Sections, and Questions

**Decision:** Store evaluation form components as normalized relational data (forms → sections → questions) rather than a JSON blob.
**Rationale:** Unlike rubrics (which are immutable templates used in a read-heavy pattern), evaluation forms need queryable structure — admins filter by question type, aggregate responses per question, and manage section ordering. Normalized tables support these operations natively.
**Trade-off:** More complex CRUD operations for form building. Rejected alternative: JSON structure (like Rubric.module) would simplify writes but make response aggregation and per-question analytics impractical.

### DD-2 — Polymorphic Target System (target_type + target_id)

**Decision:** Evaluation responses target a polymorphic entity (mentor, program, company) via `target_type` string + `target_id` UUID, without a formal `MorphTo` relationship.
**Rationale:** The target class varies by evaluation context — a mentor is a User, a program is an Internship, a company is a Company. A polymorphic pair avoids separate foreign key columns for each target type. The `target_type` is resolved at runtime by the Action layer, keeping the Model simple.
**Trade-off:** No database-level FK constraint on `target_id` (referential integrity enforced at Action layer). Rejected alternative: separate `mentor_id`, `program_id`, `company_id` columns (wasteful for sparse usage, complex NULL management).

### DD-3 — Weighted Average Formula for Overall Score

**Decision:** Compute `overall_score = sum(answer_score * question_weight) / sum(question_weight)`, excluding text questions from the weighted average.
**Rationale:** Weighted average reflects the relative importance of each question. Text questions produce no numeric score and cannot participate in the formula — they provide qualitative data only. Excluding them from the denominator prevents diluting the weighted average.
**Trade-off:** If all questions are text, `overall_score` remains null. Rejected alternative: uniform average (ignores weight significance); counting text questions as zero (artificially deflates scores).

### DD-4 — Score Normalization Per Question Type

**Decision:** Normalize raw answers to a 0–100 scale per question type before applying weights.
**Rationale:** Different question types have different scales (1–5, 1–10, yes/no, agreement). Normalization ensures comparability across types. Rating_1_5: `value * 20`. Rating_1_10: `value * 10`. Yes/No: 100/0. Agreement: mapped to 0–100 scale.
**Trade-off:** Loss of original scale precision. Rejected alternative: store raw scores and normalize at display time (more complex, harder to aggregate).

### DD-5 — Immutable Submissions

**Decision:** Once an `EvaluationResponse` is submitted (`submitted_at` is set), it must not be edited or deleted.
**Rationale:** Evaluation integrity requires that submitted feedback cannot be retroactively changed. This prevents evaluator bias after seeing aggregate results and ensures historical score accuracy for reporting. Immutability is enforced at the Action layer (planned) rather than database triggers.
**Trade-off:** Evaluators cannot correct mistakes after submission. Rejected alternative: allow edits within a time window (adds complexity, undermines integrity guarantees).

---

## 8. Success Metrics

### 8.1 Data Integrity

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Post-submission edits | 0 | Action layer blocks UPDATE/DELETE on submitted responses |
| Duplicate answers per question | 0 | Unique constraint on `(response_id, question_id)` |
| Score calculation accuracy | 100% | Weighted average matches manual calculation for test data |
| Invalid target references | 0 | Action layer validates target_id exists in referenced model |

### 8.2 Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Form listing load | < 300ms | Admin form management page with 50 forms |
| Response submission | < 2s | 20-question form submission with score calculation |
| Score calculation | < 100ms | Per-response weighted average computation |

### 8.3 User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Form builder usability | Intuitive section/question ordering | Admin can create a 10-question form in < 5 minutes |
| Score band display | Human-readable labels | EXCELLENT/GOOD/etc. shown alongside numeric scores |
| Question type variety | 6 types supported | rating_1_5, rating_1_10, yes_no, multiple_choice, text, agreement |

### 8.4 Architecture Compliance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| No model mutations in Livewire (C1) | 0 violations | All mutations via planned Actions |
| Strict types (D1) | 100% files | `declare(strict_types=1)` in all PHP files |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [placement.md](placement.md) | Active placement records — evaluation gathers feedback within a placement context |

### Build Guide
After implementing this spec, the system has weighted feedback forms with section-based questions, Likert scales, and qualitative comments. Evaluations complement assessment scores with stakeholder perspectives (supervisors, students, teachers). The next step is to build assignment management for coursework submissions.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [assignment.md](assignment.md) | Assignment submissions are evaluated alongside this feedback |
| 2 | [certification.md](certification.md) | Certification combines evaluation feedback with assessment scores |

---

## Quick References

- `app/Evaluation/Models/EvaluationForm.php` — Form model with target_type and is_active
- `app/Evaluation/Models/EvaluationSection.php` — Ordered section within a form
- `app/Evaluation/Models/EvaluationQuestion.php` — Typed, weighted question with options
- `app/Evaluation/Models/EvaluationResponse.php` — Submitted evaluation with polymorphic target
- `app/Evaluation/Models/EvaluationAnswer.php` — Per-question answer with derived score
- `database/migrations/2026_01_06_000001_create_evaluation_forms_table.php` — Forms schema
- `database/migrations/2026_01_06_000002_create_evaluation_sections_table.php` — Sections schema
- `database/migrations/2026_01_06_000003_create_evaluation_questions_table.php` — Questions schema
- `database/migrations/2026_01_06_000004_create_evaluation_responses_table.php` — Responses schema
- `database/migrations/2026_01_06_000005_create_evaluation_answers_table.php` — Answers schema
- `docs/modules/evaluation.md` — Module conceptual documentation
