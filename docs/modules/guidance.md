# Guidance — Monitoring Visits

> **Last updated:** 2026-07-21 **Changes:** sync — move SupervisionLog to Journals; Guidance now only contains MonitoringVisit

## Description

Field monitoring visit scheduling, verification, and tracking for internship programs.

## Purpose & Boundary

Guidance manages monitoring visit scheduling, verification, and tracking for internship fieldwork.
Supports multiple visit methods (site visit, virtual meeting, phone call), location tracking, and
supervisor verification workflow.

Out of scope: student-facing daily logbooks (Journals), supervision logs (Journals), rubric-based
assessment (Assessment), user profiles (User), policy handbooks (Document).

## Submodules

### MonitoringVisit

Scheduling and tracking of field monitoring visits by school administrators and teachers. Supports
multiple visit methods (site visit, virtual meeting, phone call), location tracking, and supervisor
verification workflow. Visit status progresses through scheduled → verified. Linked to a registration
and requires teacher assignment.

## Dependencies

- Core (base classes)
- Enrollment (registration context)
- User (student, teacher, supervisor identity)

## Used By

- Reports (visit compliance data for grade card)
