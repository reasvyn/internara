# Evaluation Domain

## Purpose

Evaluation collects structured feedback and satisfaction ratings from students about their
internship experience. While Assessment measures student competency against rubrics, Evaluation
measures the quality of the internship itself — the mentor, the program, the host company, the
facilities, and overall satisfaction. This domain provides a multi-type evaluation framework that
captures quantitative scores and qualitative feedback across configurable criteria, enabling
program coordinators to measure and improve the quality of every aspect of the internship
experience.

## Boundary

**In scope:** Multi-type evaluation submissions (mentor, program, company, facility, overall
satisfaction), criteria-based scoring with configurable indicators per evaluation type, overall
score computation and band classification, feedback collection with structured and open-ended
input, evaluation listing and filtering by type, edit and delete of non-finalized evaluations,
type-filtered browsing in both student and admin interfaces.

**Out of scope:** Rubric-based competency scoring and criteria definition (Assessment domain),
daily task grading (Assignment domain), mentor-private supervision notes (Mentor domain),
evaluation cycles and form templates (future enhancement — current implementation uses free-form
criteria per type), incident reporting (Incident domain).

## Key Concepts

**Evaluation Types.** Each evaluation belongs to a category that determines what is being
evaluated and what criteria apply. MENTOR: the student evaluates their mentor's performance
(communication, responsiveness, guidance quality). PROGRAM: the student evaluates the internship
program itself (curriculum relevance, administration, facility support). COMPANY: the student
evaluates the host company (workplace safety, task relevance, mentoring quality). FACILITY: the
student evaluates the physical or virtual facilities (equipment quality, workspace comfort,
infrastructure). OVERALL: the student provides an overall satisfaction rating (overall
satisfaction, recommendation score, experience rating). Each type has a pre-defined set of
criteria with descriptive labels, all scored on a 0-100 scale.

**Criteria Scores.** Each evaluation type has a set of named criteria scored on a 0-100 scale.
Scores are stored as a JSON object keyed by criterion identifier. The criteria are defined in
the EvaluationCategory enum and can be extended per type. The overall score is a separate,
independent rating that may differ from the average of criteria scores — a student might be
generally satisfied (high overall) while noting specific areas for improvement (lower individual
criteria). This distinction allows both aggregate scoring and granular diagnostic data.

**Score Bands.** The EvaluationResult entity classifies overall scores into bands for quick
interpretation: EXCELLENT (85-100), GOOD (70-84), SATISFACTORY (55-69), NEEDS_IMPROVEMENT
(40-54), and POOR (0-39). These bands are computed by the entity, not stored — they always
reflect the current score.

**Targeted vs. Open Evaluations.** Mentor evaluations require a specific mentor_id target.
Program, company, and other evaluations use a polymorphic target system (target_type + target_id)
allowing any entity to be evaluated without adding foreign key columns. The registration_id
links evaluations to the student's internship context when applicable.

## Requirements

### User Stories & Rules

| Role | Story |
|------|-------|
| Student | As a student, I want to evaluate my mentor so that their performance is documented |
| Student | As a student, I want to evaluate the internship program so that I can provide feedback on its quality |
| Student | As a student, I want to evaluate the host company so that my workplace experience is recorded |
| Student | As a student, I want to rate overall satisfaction so that program coordinators have a complete picture |
| Student | As a student, I want to provide written feedback so that I can elaborate on my scores |
| Admin | As an admin, I want to view evaluations filtered by type so that I can assess specific areas |
| Admin | As an admin, I want to see aggregate scores and trends so that I can identify improvement areas |

### Process Flow

```
Evaluation Lifecycle:

CREATED ──→ UPDATED (editable until deleted)
    │
    ↓
  DELETED
```

- Evaluations are mutable — they can be updated or deleted
- Each evaluation belongs to exactly one type (mentor, program, company, facility, overall)
- Future enhancement: add immutable / finalized state when cycles are introduced

### Key Operations

| Action | Description |
|--------|-------------|
| `EvaluateMentorAction` | Submits or updates a mentor-specific evaluation (backward compatible) |
| `SubmitEvaluationAction` | Submits or updates any evaluation type (generic, type-aware) |
| `DeleteEvaluationAction` | Removes an evaluation record |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Evaluation` (evaluator_id, evaluation_type, mentor_id, registration_id, target_type, target_id, overall_score, feedback, criteria_scores) |
| **Entity** | `EvaluationResult` (score band classification, average criterion score, validity check) |
| **Enums** | `EvaluationCategory` — `MENTOR`, `PROGRAM`, `COMPANY`, `FACILITY`, `OVERALL` (each with `label()` and `defaultCriteria()`) |
| **Livewire** | `MentorEvaluationManager` (supports all types with dynamic criteria form, type filter) |

## Dependencies

| Dependency | Reason |
|---|---|
| User | Evaluator and mentor identity for evaluation records |
| Registration | Optional link to the student's internship registration for context |
| Core | BaseAction, BaseModel, BaseEntity, SmartLogger |


- Each evaluation records who submitted it (evaluator_id) and what type it is (evaluation_type).
- Mentor evaluations require a mentor_id; other types use the polymorphic target_type/target_id.
- Scores must be between 0 and 100 inclusive — enforced at the validation layer.
- Evaluations are not immutable by default; a finalized/closed state can be added later.
- The criteria_scores JSON structure is flexible per type — no fixed schema beyond the 0-100 range.
