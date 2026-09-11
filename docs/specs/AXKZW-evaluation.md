# AXKZW — Evaluation

> **Spec ID:** AXKZW
> **Status:** Full
> **Owner:** Evaluation
> **Depends on:** J9GBH

## Description

Evaluation is the qualitative feedback half of student appraisal. Administrators build reusable
forms with typed, weighted questions grouped into sections; respondents — students, teachers,
supervisors — submit answers targeting a mentor, program, or company; the system derives
per-question scores, blends them into a weighted overall score, and classifies it into a
human-readable band. Submissions freeze on submit. Where [ARDA6](ARDA6-assessment.md) scores
competencies through rubrics, this module captures stakeholder perspective — and both feed the
certificate decision in [J0M04](J0M04-certification.md).

---

## 1. Problem Statements

### PS-1 — Standardized Feedback Collection Across PKL Roles

Students rate mentors, companies rate students, teachers rate programs — today on scattered
paper surveys and spreadsheets whose results cannot be aggregated or compared.
**→ Requirement:** FR-EVAL-001 (reusable forms), FR-EVAL-010 (identified submissions).

### PS-2 — Configurable Form Structure With Typed Questions

Satisfaction needs a scale, preference needs a choice, reasoning needs free text. One flat
question shape cannot evaluate mentor guidance and workshop facilities with equal fidelity.
**→ Requirement:** FR-EVAL-005 (typed questions), FR-EVAL-006 (constrained options),
FR-EVAL-008 (required gating).

### PS-3 — Weighted Scoring With Automatic Aggregation

Guidance quality outweighs administrative responsiveness, and hand-computed averages across
evaluators drift. Weight must be structural, and the math must run itself.
**→ Requirement:** FR-EVAL-007 (question weights), FR-EVAL-015 (per-question derivation),
FR-EVAL-016 (weighted overall excluding text).

### PS-4 — Polymorphic Target System for Multi-Context Evaluation

One form definition should serve mentor reviews, program reviews, and company reviews alike.
Cloning the form per target triples maintenance and splits comparable data.
**→ Requirement:** FR-EVAL-011 (polymorphic target pair), FR-EVAL-002 (target contract).

### PS-5 — Immutable Submissions With Score Classification

Feedback edited after submission — after seeing aggregates, after a dispute — loses all
evidentiary value. And a bare number like 73 means little without its band.
**→ Requirement:** FR-EVAL-013 (submission freeze), FR-EVAL-017 (score bands).

---

## 2. Goals & Non-Goals

### Goals

- **Reusable evaluation forms with sections and typed questions** — administrators compose forms once and deploy them across contexts. *Why:* PS-1 and PS-2; ad-hoc surveys are where comparability goes to die.
- **Six question types with per-type score derivation** — rating scales, yes/no, agreement, multiple choice, and free text each normalize to the same 0–100 space. *Why:* PS-2 and PS-3; nuance in, comparability out.
- **Polymorphic targeting of mentor, program, and company** — one form, many subjects. *Why:* PS-4; form definitions must not multiply with targets.
- **Automatic weighted overall score with band classification** — the math runs at submit time and the number arrives with its human label. *Why:* PS-3 and PS-5; manual aggregation is slow and bare numbers are mute.
- **Frozen submissions with identified respondents** — every answer carries its author and no edit path survives submit. *Why:* PS-5; feedback without integrity is gossip.
- **Normalized relational storage for forms, sections, and questions** — structure stays queryable for per-question analytics. *Why:* aggregation and reporting need to reach inside the form, which a blob would prevent.

### Non-Goals

- **Real-time collaborative form editing**. *Why:* one administrator authors a form at a time; presence and merge resolution add no MVP value.
- **Anonymous submissions**. *Why:* respondent identity is always recorded — accountability is the point of the record.
- **Automated notification triggers on scores**. *Why:* alerting on low bands is a separate workflow decision, not MVP feedback capture.
- **Multi-language form content**. *Why:* forms ship in the author's locale; per-question translation matrices are post-MVP depth.
- **Form template import, export, or cross-school sharing**. *Why:* single-tenant deployments author their own forms; sharing infrastructure serves no MVP need.
- **Self-assessment or peer-assessment matrices**. *Why:* MVP respondents evaluate programs, mentors, and companies — reciprocal student-rating instruments change the authorization model.

---

## 3. User Stories / Use Cases

Each flow is exercised through the form builder and submission journeys, so every row carries
a test layer.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-EVAL-001 | Administrator composes an evaluation form with sections and typed, weighted questions, then activates it | P0 | F | Full |
| UC-EVAL-002 | Student submits a mentor evaluation with per-question answers and receives a computed, frozen score | P0 | F | Full |
| UC-EVAL-003 | Administrator reviews aggregated results with band classifications and per-question breakdowns | P1 | F | Full |

### 3.1 Authoring & Responding

#### UC-EVAL-001 — Administrator composes and activates a form

The coordinator preparing end-of-period reviews needs a mentor form with three sections —
guidance, discipline, facilities — mixing five-point ratings with two free-text prompts.
She builds it in the form manager: sections ordered deliberately, questions typed and
weighted inside them, required flags on the ratings, optional on the essays. Activation is
the moment of commitment — only active forms accept responses, so a half-built draft can
never collect half-meant answers. The same definition will serve every mentor this period,
which is precisely what makes cross-mentor comparison possible later.

#### UC-EVAL-002 — Student submits a mentor evaluation

A student finishing placement opens the evaluation page, picks their industry supervisor
from the list, and works through the questions — ratings tapped, one honest paragraph in
the text box. Submit runs the whole pipeline in a transaction: answers stored one per
question, per-question scores derived, overall blended by weight, timestamp stamped. The
page then goes still. There is no edit button because a submitted evaluation is testimony,
and testimony does not get revised after the fact. If they misspelled something, it stands
— the integrity guarantee covers everyone equally.

### 3.2 Oversight

#### UC-EVAL-003 — Administrator reviews aggregated results

Results week brings the coordinator to the response viewer: per-form aggregates filtered by
target, each overall wearing its band label — EXCELLENT, GOOD, and the rest — with
drill-down into individual responses and their per-question rows. A mentor whose guidance
ratings shine while facilities ratings sag tells a story no single number could: praise the
mentoring, fix the workshop. The viewer reads across targets because the polymorphic shape
kept every response in one table, and bands make the scan fast enough to actually happen.

---

## 4. Functional Requirements

Common contracts apply project-wide: mutations run through Command actions in transactions,
models carry UUID v7 keys, authorization is dual-layer, and failures speak through the
rejection contract. Rows below are the evaluation-specific behaviors.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-EVAL-001 | Evaluation forms support full lifecycle (create, update, delete, activate) with name, description, target contract, creator, and active flag | P0 | F | Full |
| FR-EVAL-002 | Form target contract covers teacher, supervisor, program, company, and overall subjects | P0 | F | Full |
| FR-EVAL-003 | Sections group questions with title, description, and order inside a form, preserving question history when a section is removed | P1 | F | Full |
| FR-EVAL-004 | Deleting a form cascades to its sections, questions, and responses | P0 | F | Full |
| FR-EVAL-005 | Questions support six types: five-point rating, ten-point rating, yes/no, multiple choice, free text, and agreement | P0 | F | Full |
| FR-EVAL-006 | Option lists are populated exclusively for multiple-choice questions and rejected for every other type | P0 | F | Full |
| FR-EVAL-007 | Each question carries a weight determining its share of the overall score | P0 | F | Full |
| FR-EVAL-008 | Required flags gate submission completeness: unanswered required questions reject the response | P0 | F | Full |
| FR-EVAL-009 | Questions order within their section or form through an explicit order field | P1 | F | Full |
| FR-EVAL-010 | Responses link evaluator, form, and optional enrollment context with the evaluator identity matching the authenticated user | P0 | F | Full |
| FR-EVAL-011 | Responses target their subject through a validated type-plus-identifier pair resolving to an existing mentor, program, or company record | P0 | F | Full |
| FR-EVAL-012 | Responses optionally link the enrollment context in which the feedback was given | P1 | F | Full |
| FR-EVAL-013 | Responses freeze at submission: timestamp stamped, no update or delete path afterward, one answer per question enforced by unique constraint | P0 | F | Full |
| FR-EVAL-014 | Response creation runs inside a database transaction covering answers, derived scores, and the overall value | P0 | F | Full |
| FR-EVAL-015 | Per-question scores derive from raw answers normalized to a 0–100 scale per question type | P0 | U | Full |
| FR-EVAL-016 | Overall score blends answer scores by question weight, excluding unscored text answers, and persists as a float on the response | P0 | U | Full |
| FR-EVAL-017 | Overall scores classify into five bands: excellent, good, satisfactory, needs improvement, and poor | P0 | U | Full |
| FR-EVAL-018 | Inactive forms refuse new responses and form lookup stays indexed by target and active flag | P0 | F | Full |
| FR-EVAL-019 | Evaluation models expose the persistence contract: UUID keys, fillable attributes, typed casts, declared relations, and explicit delete behavior | P0 | A | Full |
| FR-EVAL-020 | Business-rule failures surface as RejectedException with translatable messages while unexpected failures log with context and render generically | P0 | A | Full |
| FR-EVAL-021 | Every user-facing string in builder, submission, and results views passes through the translation helper with mirrored Indonesian and English keys | P0 | A | Full |

### 4.1 Form Builder

#### FR-EVAL-001 — Form lifecycle with activation

A form is a small administrative act with outsized consequences, so its lifecycle is
deliberate: create with name and description, refine through updates, retire through
deactivation, destroy only with full cascade awareness. The active flag is the load-bearing
piece — drafting and collecting are different states, and the flag is what keeps a
half-written questionnaire from gathering real answers. Creator tracking names the author
on every form, because a puzzling question two periods later deserves someone to ask.

#### FR-EVAL-002 — Five-subject target contract

Teacher, supervisor, program, company, overall — five subjects cover every feedback
direction the PKL domain needs: upward at mentors, sideways at programs and companies,
blanket at the whole experience. Fixing the vocabulary matters more than extending it,
because aggregates compare within a subject and a sixth ad-hoc value would silently split
them. A form declares its subject once; every response and every report downstream honors
that declaration.

#### FR-EVAL-003 — Ordered sections with history-preserving removal

Long forms need chapters — guidance, facilities, administration — each with its own title,
description, and position. Removing a section mid-period is the awkward case this row
plans for: questions detach to the form level instead of vanishing, so submitted answers
keep their meaning. History outranks tidiness. A coordinator reorganizing a live form
should worry about clarity, not about orphaning evidence already collected.

#### FR-EVAL-004 — Cascading form deletion

Deleting a form is demolition, and this row makes the blast radius explicit: sections,
questions, and responses go together, in one transaction, with no stragglers. The severity
is intentional — it pushes coordinators toward deactivation for anything with collected
responses and reserves deletion for mistakes caught early. Referential cleanliness is
enforced by the database cascade, not by application code remembering every child table.

### 4.2 Questions

#### FR-EVAL-005 — Six typed questions

Walk the six types through one mentor form and each earns its place: five-point ratings
for satisfaction snapshots, ten-point for finer discrimination on guidance quality, yes/no
for factual checks like attendance at briefings, multiple choice for preference capture,
free text for the story behind the numbers, agreement scales for statement-based items.
The type sticks at creation and governs derivation at submit — a question cannot change
shape mid-period without invalidating the scores already derived from it.

#### FR-EVAL-006 — Options exclusive to multiple choice

An options list on a rating question is either dead weight or a derivation ambiguity, so
the Action refuses it anywhere but multiple choice. Conversely a multiple-choice question
without options is an unanswerable prompt, rejected at build time rather than discovered
by a confused student. The constraint feels fussy until the first malformed form reaches a
respondent — after that it reads as basic care.

#### FR-EVAL-007 — Weight as scoring share

Not every question deserves equal voice, and weight is how the form says so: guidance
quality at three, administrative responsiveness at one. The number is a plain positive
weight folded into the overall blend, defaulting to one so unweighted forms behave as
simple averages. Coordinators set weights when authoring, before any response exists —
reweighting after collection would rewrite the meaning of submitted testimony, so weights
belong to the form's definition, frozen in practice once answers arrive.

#### FR-EVAL-008 — Required gating on completeness

A response missing its core ratings is an anecdote, not data, which is why required
questions gate the submit: unanswered required items reject the whole response with a
message naming the gap. Optional questions — usually the essays — stay genuinely optional,
because forcing prose produces resentment, not insight. Completeness is checked
server-side at submit, since client-side gating is a suggestion and this row is a rule.

#### FR-EVAL-009 — Explicit ordering

Forms read top to bottom, and the order field is what top-to-bottom means: sections
sequence within the form, questions within their section or directly within the form.
Defaults of zero keep quick drafts working while deliberate numbering shapes the
respondent's journey — easy ratings first, demanding essays last. Display order derives
from this single field everywhere, so the builder preview and the respondent view can
never disagree about sequence.

### 4.3 Responses

#### FR-EVAL-010 — Identified respondents with enrollment context

Every response names its author — the evaluator link must equal the authenticated user, no
submitting as someone else, no orphaned feedback. The form link pins which questionnaire
was answered; the optional enrollment link pins in which placement it was given, so a
mentor reviewed across two periods keeps the periods distinct. Identity here is not
surveillance — it is what lets a coordinator weigh one detailed known review over ten
anonymous shrugs, and what keeps the record auditable.

#### FR-EVAL-011 — Validated polymorphic subjects

The type-plus-identifier pair is the row where flexibility meets discipline. Flexibility:
one columns pair addresses a user row for mentors, a program row, a company row — no
sparse forest of nullable foreign keys. Discipline: the Action resolves the identifier
against the named subject's table and rejects dangling references, because the database
cannot enforce what it cannot see. A response pointing at a deleted company is refused at
submit with a readable message, not discovered as a null during results week.

#### FR-EVAL-012 — Optional enrollment linkage

Feedback given inside a placement differs from feedback given about a placement in the
abstract, and the enrollment link records which it was. A supervisor evaluating a student
mid-placement links the registration; a coordinator rating overall program quality may
leave it empty. Optional, never required — the link enriches aggregates that join
responses to placements, and its absence simply excludes the response from those joins
rather than failing anything.

#### FR-EVAL-013 — Frozen submissions with single answers

Two guarantees share this row because both protect the same testimony. The timestamp
stamps at submit and every update and delete path closes behind it — submitted means
frozen, for respondents and administrators alike. And the unique constraint on
response-plus-question holds exactly one answer per question, so a double-submit race
resolves into one stored answer instead of twins. Together they make the submitted record
something a dispute can stand on: complete, singular, unchanging.

#### FR-EVAL-014 — Transactional response creation

Answers, derived per-question scores, and the blended overall land together or not at
all. A crash between answer rows and the overall write would leave a response that looks
complete but scores null — the exact corruption this transaction exists to prevent.
Results-week aggregates never see a half-written response because half-written responses
cannot exist. At school scale the lock window is milliseconds; the alternative, partial
records with compensating cleanup, costs more than it saves.

### 4.4 Scoring

#### FR-EVAL-015 — Per-type normalization to 0–100

A five-point rating and a ten-point rating cannot share a blend until they share a scale,
so each type maps its raw answer into 0–100: five-point multiplies by twenty, ten-point
by ten, yes/no resolves to full or zero, agreement steps across its mapped range. The
mapping lives beside the question type, deterministic and recomputable by hand — a
coordinator with a printed response must reach the same per-question numbers the system
stored. Free text produces no score at all, which is a feature: prose testifies, it does
not average.

#### FR-EVAL-016 — Weighted blend excluding text

The overall is the weighted mean — each scored answer times its question weight, summed,
divided by the summed weights of scored questions — and text answers simply do not enter
either sum. Excluding them from the denominator is the subtle half: counting prose as
zero would punish thoughtful respondents, while excluding it keeps the number honest.
An all-text form yields no overall rather than a misleading zero, and the null says
exactly what it means — nothing here was countable.

#### FR-EVAL-017 — Five human-readable bands

Eighty-seven is precise and forgettable; GOOD with eighty-seven beside it is scannable in
a results meeting. Five bands span the scale — excellent from eighty-five, good from
seventy, satisfactory from fifty-five, needs improvement from forty, poor below — with
boundaries fixed in the enum so every surface agrees. The band method derives purely from
the score, unit-testable without a database, and the label renders alongside the number
everywhere results appear. Precision for disputes, bands for decisions.

### 4.5 Lifecycle & Contracts

#### FR-EVAL-018 — Active-only collection with indexed lookup

Collection opens and closes with the active flag: inactive forms refuse new responses
while preserving every old one for reporting. The refusal message names the state plainly
so a student with a stale link understands what happened. Behind the flag, the composite
index on target plus active keeps the "which forms may this respondent see" query fast —
the lookup that runs on every evaluation page load, every results week, without ever
appearing in a profile.

#### FR-EVAL-019 — Model persistence contract across five tables

Forms, sections, questions, responses, answers — five tables, one agreement: UUID v7 keys,
declared fillables, typed casts for flags and scores, named relations in both directions,
and delete behavior stated per link rather than defaulted. Cascades where children are
meaningless alone, nulls where history must survive parents. This row is the handshake
every migration, factory, action, and test honors, so that a new contributor reading any
one table can predict the other four.

#### FR-EVAL-020 — Rejection contract for business failures

A student submitting to a deactivated form, answering a phantom question, or targeting a
deleted company meets a sentence in their own language — the business rejection carrying
its translatable message to the screen. A database outage mid-submit meets a different
path: context into the system log, generic wording outward, no internals exposed. The two
failure trees stay in their own catches, so a friendly refusal can never be mislogged as
an incident nor an incident misrendered as a verdict.

#### FR-EVAL-021 — Translated strings in both locales

Builder labels, question prompts rendered through views, validation messages, band labels,
results headings — every respondent- or administrator-facing string resolves through the
helper with keys present in both locale files. Dynamic content like mentor names travels
as placeholders inside whole translatable sentences. A missing key renders as an
identifier and tells a student the system is unfinished, which is why the scan gates it:
bilingual is a property of the build, not a hope about content entry.

---

## 5. Non-Functional Requirements

Constraints on how the behaviors above must hold. `N/A` marks requirements enforced by
structure and scans rather than runtime measurement.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-EVAL-001 | Form authoring is restricted to administrative roles through policy gates with action-layer re-validation | N/A | P0 | A | Full |
| NFR-EVAL-002 | Response submission is atomic: answers, derived scores, and overall persist in one transaction | N/A | P0 | F | Full |
| NFR-EVAL-003 | Duplicate answers are impossible at the database level through the response-question unique constraint | N/A | P0 | F | Full |
| NFR-EVAL-004 | Results and builder views eager-load relations with no N+1 query patterns | N/A | P1 | F | Full |
| NFR-EVAL-005 | Score math is deterministic: identical answers and weights always yield the identical overall and band | N/A | P0 | U | Full |
| NFR-EVAL-006 | Evaluation audit entries mask personal data before reaching any log sink | N/A | P0 | F | Full |
| NFR-EVAL-007 | Evaluation tables use UUID v7 primary keys with UUID foreign keys and explicit delete behavior | N/A | P0 | A | Full |
| NFR-EVAL-008 | All PHP files declare strict types and follow the shared style gate | N/A | P1 | A | Full |

### 5.1 Protection

#### NFR-EVAL-001 — Administrative authorship gates

Form definitions shape every downstream number, so their authorship is fenced twice: route
and component gates admit only administrative roles, and the actions re-check before
writing. A teacher with a direct-action call and a borrowed payload still meets the second
fence. Respondents, by contrast, need no special role beyond authentication — submitting
feedback is a participant's right, authoring the instrument is an administrator's. The two
permissions never share a gate.

#### NFR-EVAL-002 — Atomic submission writes

The transaction behind FR-EVAL-014 restated as a constraint: atomicity is not an
implementation choice the next refactor may revisit, it is a property the suite holds.
Every submission test asserts the all-or-nothing shape — answers without an overall, or an
overall without answers, fails the build. Partial records are not degraded service; they
are corruption with a timestamp, and this row bans them outright.

#### NFR-EVAL-003 — Database-level answer uniqueness

Application checks race; constraints do not. The unique pair on response and question
means two simultaneous submits for the same question resolve into one stored row and one
rejection, regardless of what the action layer believed a millisecond earlier. Defense in
depth puts the friendly check in the action and the final word in the schema — this row
is the final word, and it holds even if every line of application code is bypassed.

### 5.2 Performance & Determinism

#### NFR-EVAL-004 — No N+1 on results and builder views

The response viewer joins responses, answers, questions, sections, and forms; results week
multiplies that by every collected evaluation. Eager loading in the read paths keeps page
cost proportional to records, not to nested rows. A coordinator waiting minutes for
aggregates stops checking them, and unchecked feedback might as well not exist — so query
shape here is adoption infrastructure, not optimization vanity.

#### NFR-EVAL-005 — Deterministic scores and bands

Same answers, same weights, same overall — on any database, on any day, to the displayed
decimal. Summation order is fixed, normalization maps are constant, band boundaries are
enum facts rather than configuration. A disputed evaluation recomputes to the identical
figure in front of the respondent, which is the only response to "the system changed my
score" that ends the conversation.

### 5.3 Integrity & Hygiene

#### NFR-EVAL-006 — Masked personal data in audit entries

Submission logs carry respondent identity, subject identity, and score context — exactly
the triple that must never rest in plaintext. Masking applies before either sink, so
hurried full-payload logging still lands safe. The activity table remains queryable for
oversight — who evaluated whom, when — without becoming a roster leak waiting for a log
export to escape the building.

#### NFR-EVAL-007 — UUID keys with explicit delete behavior

Unguessable identifiers matter here more than most places: evaluation URLs and identifiers
must not enumerate respondents or reveal collection scale. UUID v7 keys across all five
tables, UUID foreign keys between them, every delete behavior declared — cascade where
children die with parents, null where history survives. No integer keys, no implicit
behavior, no mixed types for a future join to trip over.

#### NFR-EVAL-008 — Strict types and style gate

Score arithmetic is exactly where silent string-to-float coercion does its quiet damage,
so strict typing stands at the top of every file and the formatter stands across the
module. Neither row will ever appear in a demo, and both prevent the class of defect that
does — the blended overall that drifted by a rounding ghost nobody can reproduce.

---

## 6. API / Data Contracts

### 6.1 EvaluationForm Model

```
Evaluation/Models/EvaluationForm
  Table: evaluation_forms (UUID PK)
  Fillable: name, description, target_type, is_active, created_by
  Casts: is_active → boolean
  Relations: createdBy() BelongsTo User, sections() HasMany EvaluationSection,
             questions() HasMany EvaluationQuestion, responses() HasMany EvaluationResponse
  Indexes: is_active, (target_type, is_active)
```

### 6.2 EvaluationSection Model

```
Evaluation/Models/EvaluationSection
  Table: evaluation_sections (UUID PK)
  Fillable: form_id, title, description, order
  Relations: form() BelongsTo EvaluationForm, questions() HasMany EvaluationQuestion
  Deletes: section delete detaches questions to form level (nullOnDelete)
  Indexes: (form_id, order)
```

### 6.3 EvaluationQuestion Model

```
Evaluation/Models/EvaluationQuestion
  Table: evaluation_questions (UUID PK)
  Fillable: form_id, section_id, question_text, question_type, options, weight, order, is_required
  Casts: options → array, weight → integer, order → integer, is_required → boolean
  Relations: form() BelongsTo EvaluationForm, section() BelongsTo EvaluationSection,
             answers() HasMany EvaluationAnswer
  Indexes: (form_id, order), (section_id, order)
```

### 6.4 EvaluationResponse Model

```
Evaluation/Models/EvaluationResponse
  Table: evaluation_responses (UUID PK)
  Fillable: form_id, evaluator_id, target_type, target_id, registration_id,
            overall_score, notes, submitted_at
  Casts: overall_score → float, submitted_at → datetime
  Relations: form() BelongsTo EvaluationForm, evaluator() BelongsTo User,
             registration() BelongsTo Registration, answers() HasMany EvaluationAnswer
  Freeze: no update or delete path once submitted_at is set
  Indexes: (form_id, evaluator_id), (target_type, target_id), submitted_at,
           (registration_id, form_id), registration_id
```

### 6.5 EvaluationAnswer Model

```
Evaluation/Models/EvaluationAnswer
  Table: evaluation_answers (UUID PK)
  Fillable: response_id, question_id, value, score
  Casts: score → float
  Relations: response() BelongsTo EvaluationResponse, question() BelongsTo EvaluationQuestion
  Unique: (response_id, question_id)
```

### 6.6 Enums

```
Evaluation/Enums/TargetType: string (LabelEnum)
  Cases: TEACHER = 'teacher', SUPERVISOR = 'supervisor', PROGRAM = 'program',
         COMPANY = 'company', OVERALL = 'overall'

Evaluation/Enums/QuestionType: string (LabelEnum)
  Cases: RATING_1_5 = 'rating_1_5', RATING_1_10 = 'rating_1_10', YES_NO = 'yes_no',
         MULTIPLE_CHOICE = 'multiple_choice', TEXT = 'text', AGREEMENT = 'agreement'
  Derivation (0–100): RATING_1_5 → value × 20; RATING_1_10 → value × 10;
    YES_NO → 100 / 0; AGREEMENT → mapped steps; MULTIPLE_CHOICE → per-option score;
    TEXT → unscored (excluded from overall)

Evaluation/Enums/ScoreBand: string (LabelEnum)
  Cases: EXCELLENT = 'excellent', GOOD = 'good', SATISFACTORY = 'satisfactory',
         NEEDS_IMPROVEMENT = 'needs_improvement', POOR = 'poor'
  Bands: EXCELLENT 85–100, GOOD 70–84, SATISFACTORY 55–69,
         NEEDS_IMPROVEMENT 40–54, POOR 0–39
  Methods: fromScore(float $score): self, label(): string
```

### 6.7 Actions

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| `CreateEvaluationFormAction` | `BaseCommandAction` | form data DTO | `EvaluationForm` |
| `UpdateEvaluationFormAction` | `BaseCommandAction` | form, form data DTO | `EvaluationForm` |
| `DeleteEvaluationFormAction` | `BaseCommandAction` | form | `void` |
| `CreateEvaluationSectionAction` | `BaseCommandAction` | form, section data DTO | `EvaluationSection` |
| `CreateEvaluationQuestionAction` | `BaseCommandAction` | form, question data DTO | `EvaluationQuestion` |
| `SubmitEvaluationResponseAction` | `BaseCommandAction` | response data DTO (evaluator = authenticated user) | `EvaluationResponse` |
| `CalculateEvaluationScoreAction` | `BaseProcessAction` | response | `EvaluationResponse` |

### 6.8 Events & Listeners

| Event | Dispatched by | Listener | Queued |
| ----- | ------------- | -------- | ------ |
| `EvaluationSubmitted` | `SubmitEvaluationResponseAction` | `LogEvaluationSubmitted` (dual-channel, PII masked) | No |

### 6.9 Policy

| Policy | Abilities |
| ------ | --------- |
| `EvaluationFormPolicy` | viewAny: super_admin, admin, teacher · view: admin, teacher · create/update/delete: super_admin, admin |
| `EvaluationResponsePolicy` | viewAny: super_admin, admin · view: admin, evaluator, subject mentor · submit: authenticated participant · update/delete: none once submitted |

### 6.10 Routes

| Route | Component | Name | Middleware |
| ----- | --------- | ---- | ---------- |
| `GET /admin/evaluations` | Form manager (Livewire) | `sysadmin.evaluations` | `auth`, `role:super_admin\|admin` |
| `GET /admin/evaluations/{form}/responses` | Response viewer (Livewire) | `sysadmin.evaluations.responses` | `auth`, `role:super_admin\|admin` |
| `GET /evaluations` | Submission form (Livewire) | `evaluations` | `auth` |

### 6.11 Database Schema

```
evaluation_forms:
  id: uuid (PK)
  name: string
  description: text (nullable)
  target_type: string(30) — teacher/supervisor/program/company/overall (indexed)
  is_active: boolean (default true, indexed)
  created_by: foreignUuid → users.id (nullable, nullOnDelete)
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
  options: json (nullable — multiple_choice only)
  weight: unsigned int (default 1)
  order: unsigned int (default 0)
  is_required: boolean (default true)
  timestamps
  Indexes: (form_id, order), (section_id, order)

evaluation_responses:
  id: uuid (PK)
  form_id: foreignUuid → evaluation_forms.id (cascadeOnDelete)
  evaluator_id: foreignUuid → users.id (cascadeOnDelete)
  target_type: string(30)
  target_id: uuid (validated at Action layer against the named subject table)
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

Recorded choices with their context. None carry a test layer; the behaviors they explain are
covered by the FR rows above.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-EVAL-001 | Form structure lives in normalized relational tables rather than a JSON blob | P0 | — | — |
| DD-EVAL-002 | Response subjects resolve through a validated type-plus-identifier pair without a database-level foreign key | P0 | — | — |
| DD-EVAL-003 | Overall score is the weight-aware mean over scored answers with text excluded from both sums | P0 | — | — |
| DD-EVAL-004 | Raw answers normalize to a shared 0–100 scale per question type before blending | P0 | — | — |
| DD-EVAL-005 | Submitted responses freeze entirely with enforcement at the action layer rather than database triggers | P0 | — | — |

### 7.1 Structure & Identity

#### DD-EVAL-001 — Normalized tables over a JSON blob

Rubrics next door chose the blob because grading always needs the whole tree at once.
Forms face the opposite read pattern: aggregates slice per question across responses,
administrators filter by type, ordering queries run constantly. A blob would make every
one of those a full-scan application-side parse. Normalization costs more careful
write paths — sections, questions, and order fields maintained together — but buys
queryable structure where this module actually reads. Same product, opposite access
shape, opposite storage choice; consistency would have been the wrong virtue.

#### DD-EVAL-002 — Validated pair over rigid foreign keys

Three designs stood at this fork. Separate nullable columns per subject waste space and
multiply null management with every new subject. A formal polymorphic relation buys
framework magic at the price of framework coupling in the hottest write path. The bare
pair — type string plus identifier — keeps the model simple and pushes integrity to the
Action layer, which resolves the identifier against the named table on every submit.
Referential safety becomes a tested behavior instead of a schema property, and adding a
sixth subject later means extending validation, not migrating tables.

### 7.2 Scoring & Integrity

#### DD-EVAL-003 — Weight-aware mean with text excluded

The formula encodes two judgments. First, weights matter: guidance quality at triple
weight moves the overall three times farther than a single-weight item, which is the
entire point of weighting. Second, prose abstains: text answers leave both the numerator
and the denominator, so a thoughtful essay neither inflates nor deflates the number.
Uniform averaging would have flattened deliberate emphasis; zero-filling text would have
punished eloquence. An all-text form scores null — the honest answer when nothing was
countable.

#### DD-EVAL-004 — Normalization before blending

Blending raw scales directly lets the ten-point questions dominate the five-point ones by
arithmetic accident rather than design intent. Normalizing each answer into the shared
0–100 space first means a top rating counts the same regardless of which scale asked for
it, and weights then express deliberate emphasis on a level field. The mappings are
fixed per type and stored as derived scores, so aggregates never recompute them at read
time. Display-time normalization was considered and refused — it would have made every
report query re-derive history instead of reading it.

#### DD-EVAL-005 — Frozen testimony enforced in actions

Once submitted, a response is evidence: no edits, no deletes, no windows. Enforcement
sits in the action layer — every mutation path checks submission state first — rather
than in database triggers, keeping the rule visible in code review and testable in the
feature suite. The cost falls on respondents who spot a typo after submit, and it falls
equally on everyone, which is what makes it fair. A correction window was weighed and
set aside: any window reopens exactly the retrospective-bias cases immutability exists
to close.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Post-submission edits accepted | 0 | Freeze probes on update and delete paths |
| Duplicate answers stored per question | 0 | Unique-constraint enforcement under concurrent submit |
| Responses pointing at missing subjects | 0 | Action-layer target validation rejection |
| Unweighted or misweighted overalls | 0 | Determinism probes on blend math with fixed inputs |
| Band labels disagreeing with scores | 0 | Enum boundary tests across all five bands |
| Responses collected on inactive forms | 0 | Active-flag refusal coverage |

---

## 9. Roadmap

### Prerequisites

| Spec | What it provides |
|------|-----------------|
| [J9GBH](J9GBH-placement.md) | Active placement records — evaluations gather feedback within a placement context |

### Build Guide

With this spec implemented, the system collects weighted stakeholder feedback with frozen,
banded scores. Evaluations are the qualitative half of appraisal; assessment provides the
quantitative half, assignments add coursework evidence, and certification consumes all three.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [T657Z](T657Z-assignment.md) | Assignment submissions add coursework evidence alongside this feedback |
| 2 | [J0M04](J0M04-certification.md) | Certification combines evaluation feedback with assessment scores |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| R-1 | If response rates skew toward dissatisfied respondents, aggregates may misrepresent typical experience | Open | Maintainer | — |
| A-1 | We assume one active form per subject per period is sufficient; overlapping active forms are all collectible | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Assessment phase and build order
- [Architecture](D2FT3-architecture.md) — Action Triad, entity separation, dual-layer authorization
- [Project initialization](QLHDO-project-initialization.md) — global requirements this spec tightens
- [Assessment](ARDA6-assessment.md) — quantitative rubric scoring complementing this feedback
- [Placement](J9GBH-placement.md) — enrollment context linking responses to placements
- [Certification](J0M04-certification.md) — consumer of banded evaluation scores
- [Assignment](T657Z-assignment.md) — coursework evidence alongside this feedback
- [RBAC & authorization](T4B26-rbac-and-authorization.md) — roles and authorship gates
- [Logging & error handling](89SRA-logging-and-error-handling.md) — rejection contract and masked audit logging
