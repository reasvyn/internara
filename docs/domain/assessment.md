# Assessment Domain
> Last updated: 2026-05-27
> Changes: docs: comprehensive infrastructure, architecture, and conventions overhaul


## Purpose

Assessment defines the framework for measuring student competency during internships. While daily 
task grading (Assignment) tracks individual assignments and mentor feedback (Evaluation) captures 
qualitative observations, Assessment provides the structured, rubric-based evaluation that 
answers: what does a successful student on placement look like, and does this student measure up against that 
standard? It defines the criteria (rubrics), the scoring model (competency assessments), and the 
formal evaluation events (presentations) that produce comparable, defensible competency data. 
This data is used by the Registration domain to determine completion and by the Certificate 
domain to credential graduates.

## Boundary

**In scope:** Rubric definitions (named criteria with performance levels, descriptive anchors, 
and weightings), competency assessments (scoring a student against a rubric at a point in time), 
self-assessments by students using the same rubrics, presentation evaluations (formal panel-based 
assessment events with independent scoring), assessment periods and cycles (mid-term, final, 
custom), grading scale configuration and rubric-to-grade mapping, assessment result reporting, 
competency growth tracking over multiple assessment rounds.

**Out of scope:** Daily task assignments and their grading (Assignment domain owns task-level 
evaluation), qualitative mentor feedback collection (Evaluation domain), program-level 
requirement definitions (Internship domain), certificate eligibility determination (Certificate 
domain uses assessment results but does not own the rubric data), attendance tracking (Attendance 
domain), logbook journaling (Logbook domain), incident reporting (Incident domain).

## Key Concepts

**Rubrics.** A rubric is a structured evaluation framework containing multiple criteria, each 
describing a specific competency area — communication skills, technical proficiency, 
professional behavior, problem-solving ability, teamwork, and so on. Each criterion has defined 
performance levels (typically 3-5 levels such as Below Expectation, Needs Improvement, Meets 
Expectation, Exceeds Expectation, Outstanding) with descriptive anchors that explain what each 
level looks like in practice. Criteria are assigned weightings that must total exactly 100%, 
producing a composable weighted total score. Rubrics are versioned: once a rubric version is used 
in any assessment, that version becomes immutable, preventing retrospective drift in evaluation 
standards. Different internship programs can use the same rubric, a modified version, or a 
completely different rubric.

**Competency Assessments.** An assessment applies a specific rubric version to a specific student 
at a specific point in time. The evaluator (mentor, teacher, or assessment panel) scores each 
rubric criterion and can add criterion-level comments explaining the score. Multiple evaluators 
can assess the same student independently; their scores can be averaged or compared. Assessments 
can be performed by mentors (formal evaluation) or by students themselves (self-assessment for 
reflective growth). When multiple assessments exist over the course of an internship, they create 
a competency growth trajectory — students and mentors can see how scores improve or where they 
plateau. Once finalized, an assessment is immutable and becomes part of the student's permanent 
academic record.

**Presentation Assessments.** A specialized assessment type for formal presentation events where 
students present their internship work. A panel of evaluators (typically the mentor, a teacher, 
and possibly an external industry representative) scores the presentation against predefined 
criteria: content quality and depth, delivery and presentation skills, visual aids quality, Q&A 
handling, and overall impression. Each panel member scores independently before the panel 
discusses and agrees on final scores. Presentation assessments have their own workflow: scheduled 
(date set), in-progress (presentation happening), scored (all panel members submitted scores), 
finalized (scores agreed and recorded). Panel composition and scoring methodology are 
configurable per program.

**Assessment Periods.** To ensure consistent and comparable evaluation timing across a cohort, 
assessments are grouped into periods. Standard periods are mid-term (halfway through the 
internship) and final (at the end), but custom periods can be defined per program. Each period 
has a start date, an end date, a set of required assessments (which students and which rubrics or 
presentation types), and optional weighting for final grade calculation. The system tracks 
completion rates per period — which students have been assessed, which mentors have submitted 
their evaluations — and sends reminders for overdue assessments. Period boundaries ensure every 
student is evaluated at the same relative maturity point in their internship journey.

**Scoring and Grading.** Raw rubric scores are composited using the rubric's weightings to 
produce a numerical total. This total can be mapped to letter grades (A, B, C, D, F), competency 
labels (Pass, Merit, Distinction), or a simple Pass/Fail determination. The mapping is 
configurable per program, not hardcoded. The final assessment score feeds into the Registration 
domain's completion computation but does not independently determine completion — it is one 
input among many (attendance, assignments, logbook consistency, guidance acknowledgements).

## Requirements

### User Stories & Rules

**Rubric Setup**
- **Admin:** As an admin, I want to create rubrics with weighted criteria so that evaluations are structured and consistent
- **Admin:** As an admin, I want to define competencies and indicators so that evaluators have clear scoring guidance
- Rubric criterion weightings must total exactly 100% across all criteria within a rubric
- Every competency assessment must reference exactly one rubric version — rubric evolution does not retroactively affect completed assessments

**Assessment Execution**
- **Teacher/Mentor:** As a teacher, I want to assess a student against a rubric so that their competency level is recorded
- **Teacher/Mentor:** As a teacher, I want to score individual indicators so that evaluations are granular and traceable
- **System:** As the system, I want to auto-calculate total scores from weighted criteria so that results are mathematically consistent
- All assessment periods must fall entirely within the parent internship program's date range
- Self-assessments are visible to the assigned mentor alongside the mentor's own assessment — discrepancies in scoring can trigger a mentor-student discussion

**Finalization**
- **Teacher/Mentor:** As a teacher, I want to finalize an assessment so that it becomes part of the student's permanent record
- **System:** As the system, I want to prevent edits to finalized assessments so that the audit trail is trustworthy
- Once finalized, assessments cannot be edited or deleted. Corrections require a new assessment round that documents the correction context

**Presentations**
- **Admin:** As an admin, I want to schedule presentations so that students have formal evaluation events
- **Panel:** As a panel member, I want to score a presentation independently so that the evaluation is fair
- Presentation panels must have at least two evaluators; a solo evaluator is insufficient for panel-based scoring validity

**Results & Reporting**
- **Admin:** As an admin, I want to view assessment results so that I can track student progress
- Assessment data feeds into the completion calculation but does not independently determine completion; the Registration domain makes the final eligibility decision

### Process Flow

```
Assessment Lifecycle:

INITIATED ──→ IN_PROGRESS ──→ FINALIZED
                                 (immutable)

Presentation Lifecycle:

SCHEDULED ──→ COMPLETED
      │
      ↓
  CANCELLED
```

- **Assessment**: Starts as initiated (scoring not yet begun), moves to in-progress (evaluator scoring), then finalized (immutable record)
- **Presentation**: Scheduled with date and panel, then completed after scoring, or cancelled if circumstances change
- Once finalized, assessments are completely immutable — corrections require a new assessment round

### Key Operations

| Action | Description |
|--------|-------------|
| `CreateRubricAction` | Creates a new evaluation rubric |
| `UpdateRubricAction` | Updates a rubric before it is used in any assessment |
| `DeleteRubricAction` | Deletes an unused rubric |
| `CreateCompetencyAction` | Adds a competency criterion to a rubric |
| `UpdateCompetencyAction` | Updates a competency definition |
| `DeleteCompetencyAction` | Removes a competency from a rubric |
| `CreateIndicatorAction` | Creates a scoring indicator within a competency |
| `UpdateIndicatorAction` | Updates an indicator definition |
| `DeleteIndicatorAction` | Removes an indicator |
| `InitializeAssessmentAction` | Starts a new assessment for a student against a rubric |
| `ScoreIndicatorAction` | Records a score for a specific indicator |
| `UpdateAssessmentScoresAction` | Updates scores during in-progress phase |
| `FinalizeAssessmentAction` | Finalizes an assessment, making it immutable |
| `AutoCalculateAssessmentAction` | Auto-calculates weighted total score from indicator scores |
| `SchedulePresentationAction` | Schedules a presentation evaluation event |
| `ScorePresentationAction` | Records a panel member's presentation score |
| `CompletePresentationAction` | Finalizes a completed presentation |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Rubric`, `Competency`, `Indicator`, `Assessment`, `Presentation`, `PresentationExaminer` |
| **Entity** | `AssessmentResult` (score calculation, finalization checks) |
| **Enums** | `EvaluatorRole` — `ADMIN`, `TEACHER`, `SUPERVISOR`, `SYSTEM`; `PresentationStatus` — `SCHEDULED`, `COMPLETED`, `CANCELLED` |
| **Livewire** | `RubricManager`, `AssessmentGrading`, `AssessmentView`, `PresentationSchedule` |
| **Policy** | `AssessmentPolicy` |

## Dependencies

| Dependency | Reason |
|---|---|
| Internship | Program definitions determine which rubrics apply, when assessment periods occur, 
and what grading scale is used |
| Registration | Links students to internship programs so assessments can be correctly assigned 
and tracked |
| Core | BaseAction for assessment operations, BaseModel for persistence (extends BaseModel), 
SmartLogger for audit trail |


