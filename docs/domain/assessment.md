# Assessment Domain

## Purpose

Assessment provides rubric-based competency evaluation — defining criteria, scoring students
against rubrics, and managing presentation exams.

---

## Design Principles

### 1. Weighted, Multi-Criteria Scoring

A student's final grade is a weighted composite of multiple criteria — never a single score.
Each competency carries a defined weight, and each indicator within a competency has its own
weight. The system auto-calculates totals from individual scores.

### 2. Rubric-Driven Evaluation

All scoring references a defined rubric. No free-form grading exists — every score ties to a
specific indicator within a rubric. This ensures consistency across evaluators and programs.

### 3. Immutable Finalization

Once an assessment is finalized, it becomes immutable. Corrections require initiating a new
assessment round — existing scores are never overwritten. This preserves audit trails and
prevents grade tampering.

---

## Domain Boundary

The Assessment domain owns rubric-based competency evaluation — the framework for defining what competencies students should demonstrate and measuring how well they perform. It manages rubrics with weighted criteria, performance level descriptors, and descriptive anchors that define what each performance level looks like in practice. Within each rubric, competencies and their observable indicators are managed to create a complete evaluation framework. Teachers score students against these rubrics, with the system auto-calculating weighted totals from individual criterion scores. The domain also handles presentation exam scheduling — panel-based oral or practical evaluations that follow their own lifecycle from scheduled to completed or cancelled. Once an assessment is finalized, it becomes immutable — corrections require initiating a new assessment round.

Assessment does not own student identity data (User/Mentee), program definitions (Internship), assignment submissions (Assignment), or certificate issuance (Certificate). It owns the evaluation criteria and the scores, but the tasks being evaluated (assignments) belong to the Assignment domain. Assessment provides the scoring framework that other domains consume — assignments may reference assessment rubrics for grading, and certificates may reference assessment results for credentialing decisions.

The domain depends on Internship for program context, on User for student and teacher identity, and on Mentee for the student-program link. Its rubric definitions and scores are consumed by the Certificate domain (for issuance eligibility), the Internship domain (for closure readiness checks and archival), and the Mentee dashboard (for progress tracking).

---

## Key Features

- Create and manage weighted rubric criteria with performance-level descriptors and descriptive anchors for each level.
- Manage competencies and their observable indicators within the assessment rubric framework.
- Score students against a rubric, with the system auto-calculating weighted totals from individual criterion scores.
- Schedule panel-based presentation exams for oral or practical competency evaluation.
- Track presentation exams through a scheduled, completed, and cancelled lifecycle.
- Finalize assessments as immutable records, requiring a new assessment round for any corrections.
- Build rubrics interactively with a visual criteria editor supporting drag-and-drop reordering of criteria.
- Score students against a rubric with a side-by-side view showing the rubric criteria and the scoring input fields.
- View auto-calculated weighted totals update in real time as individual criterion scores are entered.
- Filter the assessment list by program, type, or status using dropdown selectors.
