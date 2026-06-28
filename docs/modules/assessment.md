# Assessment — Rubrics, Evaluation & Grading

> **Last updated:** 2026-06-10
> **Changes:** sync — initial metadata sync with new format

## Description
Rubric-based competency evaluation: rubric templates with structured criteria stored as JSON, and student assessment grading with supervisor proxy.


## Purpose & Boundary

Assessment defines the scoring framework for evaluating student performance during internships. Rubrics are reusable evaluation templates containing weighted competencies and indicators stored as JSON. Assessments record actual grades submitted by evaluators against rubric indicators, with support for finalization immutability and supervisor proxy when industry supervisors are unavailable.

Out of scope: program-level grade aggregation (Reports), evaluation feedback collection (Evaluation), task-specific grading (Assignment).

## Submodules

### Rubric
Evaluation template containing a JSON `structure` array of competencies, each with weighted indicators bearing `id`, `name`, `max_score`, and `weight`. Rubrics can be reused across multiple programs and assessment rounds. Template changes do not affect finalized assessments because scores reference indicator keys, not live template data.

##Assessment — Rubrics, Evaluation & Grading
Records actual grades submitted by an evaluator (school teacher or industry supervisor) for a student's registration using a specific rubric. Tracks scores per indicator, overall comments, grader identity, and finalization status. Once finalized, an assessment is immutable — corrections require a new assessment round.

## Key Concepts

### JSON Rubric Structure

Competencies and indicators are stored as a structured JSON column rather than normalized tables. This prevents schema drift between rubrics, enables free-form rubric design, and ensures historical grades remain readable even if the rubric template is later modified. Example structure:

```json
[{"competency": "Work Attitude", "weight": 40, "indicators": [{"id": "ind_1", "name": "Discipline", "max_score": 100, "weight": 50}]}]
```

##Assessment — Rubrics, Evaluation & Grading Finalization

Once an assessment record is finalized, all scores become immutable. This preserves grade integrity for audit purposes. Correcting a finalized assessment requires creating a new assessment round, which preserves the original as a historical record.

### Cross-Role Proxy (Supervisor Proxy)

Industry supervisor evaluations are optional to prevent blocking student workflows. Teachers can
act as proxy for supervisors via the Cross-Role Proxy mechanism (see [ADR-014](../adr/adr-cross-role-proxy.md)):

- **Supervisor Proxy**: The school teacher inputs scores on behalf of the supervisor — covering
  scoring, feedback, and all supervisor-scoped actions. The action is logged with the teacher's
  identity and a `proxy_role = 'supervisor'` property in the audit trail.
- **Weight Redistribution**: If the supervisor is unavailable and no proxy acts, the supervisor's
  grading weight is shifted to the teacher and exam components in the Reports module.
- **Scope**: Teacher can only proxy for students assigned to their mentorship. Admin can proxy
  for any student.

## Dependencies

- Core (base classes, Cross-Role Proxy trait)
- Enrollment (registrations for student context)
- User (evaluator identity)

## Used By

- Reports (grade card score aggregation)
- Evaluation (grading context)
