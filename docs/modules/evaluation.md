# Evaluation — Documentation Overview

> Last updated: 2026-06-04 Changes: Rewrote overview with developer-friendly content, added error
> handling, failure modes, and CLI commands

Structured feedback collection from multiple perspectives: student evaluates mentor and company,
program quality assessment, and overall satisfaction ratings.

For complete technical reference including API, models, actions, and components, see
[evaluation-reference.md](evaluation-reference.md).

---

## Key Principles

- **Multi-perspective feedback** — evaluations capture input from students (about mentors and
  companies), admins/teachers (about program quality), and overall satisfaction ratings. Each
  perspective has its own form and scoring.
- **Score bands standardize results** — all evaluation types map to bands: EXCELLENT (85-100), GOOD
  (70-84), SATISFACTORY (55-69), NEEDS_IMPROVEMENT (40-54), POOR (0-39). This enables
  cross-evaluation comparison.
- **Evaluations inform program closure** — the program quality evaluation is triggered during
  closure/archival workflow. All evaluations must be collected before a program can be archived.
- **Results are aggregated** — the system auto-computes submodule scores, trends, and distributions
  across all evaluation types for admin review.

---

## Context Boundary

Collects feedback from students (about their mentor and company), from admins/teachers (about
program quality), and stores results for reporting. Certification may use evaluation outcomes as
eligibility criteria.

---

## Module Rules

- **Mentor evaluation**: Student rates mentor communication, responsiveness, and guidance quality.
  One evaluation per student per program.
- **Company evaluation**: Student rates workplace safety, task relevance, and mentoring at the host
  company. One evaluation per student per program.
- **Overall satisfaction**: Independent rating (separate from mentor/company evaluations) collected
  from students.
- **Program quality evaluation**: Admin/teacher evaluates program outcomes — curriculum alignment,
  completion rates, partner satisfaction, areas for improvement. Triggered during program closure.
- **Score bands** are defined system-wide and shared across all evaluation types.
- **Evaluations are immutable after submission**: once submitted, they cannot be modified. The audit
  trail preserves the original.

---

## Submodules

- **Evaluation**: The core entity — polymorphic type (mentor/company/satisfaction/program), score,
  band, free-text feedback, and submitter identity. Immutable after submission. Linked to the
  relevant program and user.

---

## Error Handling & Failure Modes

- **Duplicate evaluation submission**: The system enforces one evaluation per type per scope (e.g.,
  one mentor evaluation per student per program). A second attempt throws a `ConflictException`.
- **Evaluation without assessment context**: If the Assessment module has not defined rubrics,
  evaluation scores fall back to the default bands. A warning is logged.
- **Missing evaluations blocking program closure**: The closure readiness check validates that all
  required evaluations are collected. Missing evaluations are listed in the readiness report.

---

## Quick References

### Actions & Business Logic

- **3** actions across all submodules
- Evaluation submission (per type), score aggregation, program quality evaluation trigger

### Data & Persistence

- **1** model managing evaluation data
- Polymorphic type system, immutable after submission, scored against standard bands

### User Interface

- **1** Livewire component for real-time interaction
- Evaluation form (adapts to type: mentor, company, satisfaction, program quality)

### Authorization

- **1** authorization policy
- Students submit own evaluations, admins view submodules and submit program quality evaluations

---

For complete technical reference, see [evaluation-reference.md](evaluation-reference.md).
