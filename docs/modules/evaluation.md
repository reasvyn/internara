# Evaluation

> **Last updated:** 2026-06-08

Multi-perspective structured feedback collection: student evaluates mentor and company, admin evaluates program quality, and overall satisfaction ratings.

## Purpose & Boundary

Evaluation collects structured feedback from multiple viewpoints using a shared scoring system. Students evaluate their industry mentor (communication, responsiveness, guidance) and host company (workplace safety, task relevance). Admins and teachers evaluate program quality at closure. Overall satisfaction ratings are collected independently. All evaluation types map to standardized score bands for cross-comparison.

Out of scope: rubric-based competency grading (Assessment), task-level feedback (Assignment), daily logbook reflections (Journals).

## Submodules

### Evaluation
Core entity with polymorphic type system (`mentor`, `company`, `satisfaction`, `program_quality`). Each evaluation records: type discriminator, numeric score, mapped score band, free-text feedback, submitter identity, and linkage to the relevant program and registration. Evaluations are immutable after submission — no edits or deletions. One evaluation per type per scope (e.g., one mentor evaluation per student per program).

## Key Concepts

### Score Bands

All evaluation types share a standardized five-band system:
- **EXCELLENT** (85–100)
- **GOOD** (70–84)
- **SATISFACTORY** (55–69)
- **NEEDS_IMPROVEMENT** (40–54)
- **POOR** (0–39)

This enables cross-evaluation comparison and consistent reporting regardless of evaluation type.

### Evaluation-Driven Closure

Program quality evaluations are triggered during the program closure workflow. The closure readiness check (`CheckCloseReadinessAction`) validates that all required evaluations are collected before a program can be archived. Missing evaluations are listed in the readiness report.

### Immutable Submissions

Once submitted, an evaluation cannot be modified or deleted. The audit trail preserves the original submission with timestamp, submitter, and all scores. This ensures feedback integrity for both school accreditation and partner company reporting.

## Dependencies

- Core (base classes)
- Program (program context for closure workflow)
- Enrollment (registration context for student evaluations)
- User (evaluator and subject identity)

## Used By

- Reports (program quality data)
- Certification (eligibility checks may reference evaluation outcomes)
