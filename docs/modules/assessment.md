# Assessment

> **Last updated:** 2026-06-10

Rubric-based competency evaluation: rubric templates with structured criteria stored as JSON, and student assessment grading with dual mentor fallback.

## Purpose & Boundary

Assessment defines the scoring framework for evaluating student performance during internships. Rubrics are reusable evaluation templates containing weighted competencies and indicators stored as JSON. Assessments record actual grades submitted by evaluators against rubric indicators, with support for finalization immutability and dual mentor fallback when industry supervisors are unavailable.

Out of scope: program-level grade aggregation (Reports), evaluation feedback collection (Evaluation), task-specific grading (Assignment).

## Submodules

### Rubric
Evaluation template containing a JSON `structure` array of competencies, each with weighted indicators bearing `id`, `name`, `max_score`, and `weight`. Rubrics can be reused across multiple programs and assessment rounds. Template changes do not affect finalized assessments because scores reference indicator keys, not live template data.

### Assessment
Records actual grades submitted by an evaluator (school teacher or industry supervisor) for a student's registration using a specific rubric. Tracks scores per indicator, overall comments, grader identity, and finalization status. Once finalized, an assessment is immutable — corrections require a new assessment round.

## Key Concepts

### JSON Rubric Structure

Competencies and indicators are stored as a structured JSON column rather than normalized tables. This prevents schema drift between rubrics, enables free-form rubric design, and ensures historical grades remain readable even if the rubric template is later modified. Example structure:

```json
[{"competency": "Work Attitude", "weight": 40, "indicators": [{"id": "ind_1", "name": "Discipline", "max_score": 100, "weight": 50}]}]
```

### Assessment Finalization

Once an assessment record is finalized, all scores become immutable. This preserves grade integrity for audit purposes. Correcting a finalized assessment requires creating a new assessment round, which preserves the original as a historical record.

### Dual Mentor Grading Fallback

Industry supervisor evaluations are optional to prevent blocking student workflows. When a supervisor is inactive:
- **Proxy Evaluation**: The school teacher inputs scores on behalf of the supervisor based on physical paperwork or verbal report.
- **Weight Redistribution**: The supervisor's grading weight is shifted to the teacher and exam components in the Reports module.

## Dependencies

- Core (base classes)
- Enrollment (registrations for student context)
- User (evaluator identity)

## Used By

- Reports (grade card score aggregation)
- Evaluation (grading context)
