# Assessment Domain

## Purpose

Assessment defines the framework for measuring student competency during internships. While daily 
task grading (Assignment) tracks individual assignments and mentor feedback (Evaluation) captures 
qualitative observations, Assessment provides the structured, rubric-based evaluation that 
answers: what does a successful intern look like, and does this student measure up against that 
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

## Dependencies

| Dependency | Reason |
|---|---|
| Internship | Program definitions determine which rubrics apply, when assessment periods occur, 
and what grading scale is used |
| Registration | Links students to internship programs so assessments can be correctly assigned 
and tracked |
| Core | BaseAction for assessment operations, BaseModel for persistence (extends BaseModel), 
SmartLogger for audit trail |

## Important Rules

- Every competency assessment must reference exactly one rubric version — rubric evolution does 
not retroactively affect completed assessments.
- Once finalized, assessments cannot be edited or deleted. Corrections require a new assessment 
round that documents the correction context.
- Presentation panels must have at least two evaluators; a solo evaluator is insufficient for 
panel-based scoring validity.
- Rubric criterion weightings must total exactly 100% across all criteria within a rubric.
- All assessment periods must fall entirely within the parent internship program's date range.
- Self-assessments are visible to the assigned mentor alongside the mentor's own assessment — 
discrepancies in scoring can trigger a mentor-student discussion.
- Assessment data feeds into the completion calculation but does not independently determine 
completion; the Registration domain makes the final eligibility decision.
