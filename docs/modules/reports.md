# Reports

> **Last updated:** 2026-06-10

Final student grade card (Nilai Raport PKL): grade aggregation from multiple assessment sources, composite score calculation, coordinator sign-off, and certificate eligibility trigger.

## Purpose & Boundary

Reports compiles the student's final internship grade card by aggregating scores from industry supervisors, school teachers, and exam assessments according to the program-defined weight distribution. The grade card is locked on finalization (coordinator sign-off) and serves as the prerequisite for certificate issuance. A full identity and metadata snapshot is captured at finalization to ensure the grade card persists even if source records are later deleted.

Out of scope: individual assessment grading (Assessment), assignment grading (Assignment), evaluation feedback (Evaluation), certificate generation (Certification).

## Submodules

### Report (Grade Card)
Core entity with 1:1 relationship to Registration. Stores: industry supervisor score, school teacher score, exam score, computed composite score, qualitative feedback from host company, letter grade, and finalization status. On finalization, snapshots student identity, internship metadata, host company details, department, and supervisor names for standalone archival persistence.

## Key Concepts

### Grade Aggregation Formula

The composite final grade is calculated using program-defined weights. The standard formula:

```
Final Grade = (Industry Supervisor × 40%) + (School Teacher × 20%) + (Exam × 40%)
```

Weights are configurable per internship program in the Program module. The `CalculateFinalGradeAction` reads weights from the program and computes the composite score.

### Finalization Immutability

Once a grade card is marked `finalized` by the coordinator, all scores are locked. No further changes are permitted without administrative override (special permission in `UpdateReportCardAction`). Finalization triggers:
1. Immutable snapshot capture of all related identity and metadata.
2. Certificate eligibility flag on the registration.

### Standalone Archiving

The grade card captures a full snapshot at finalization time: student NISN, name, and class; host company name and address; department name; school teacher name; industry supervisor name; all component scores and composite score. This ensures the grade card remains readable and valid even if the student's account, the company, or the program is later deleted.

### Cross-Role Proxy

If the Assessment module's cross-role proxy is active (teacher acting as supervisor proxy, see
[ADR-014](../adr/adr-cross-role-proxy.md)), the grade aggregation adjusts: the supervisor weight
may be redistributed to teacher and exam components. The grade card records the proxy status for
audit transparency.

## Dependencies

- Core (base classes)
- Enrollment (registration context)
- Assessment (supervisor, teacher, exam scores)
- Program (grading weight configuration)
- User (student, coordinator identity)

## Used By

- Certification (finalized grade card as prerequisite)
