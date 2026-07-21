# Guidance — Supervision Logs & Monitoring Visits

> **Last updated:** 2026-07-21 **Changes:** sync — remove Handbook (moved to Document); add MonitoringVisit submodule description

## Description

Teacher supervision field logging and field monitoring visit coordination for internship programs.

## Purpose & Boundary

Guidance provides teachers with a private supervision log system for recording site visits, virtual
me meetings, and phone supervisions. It also manages monitoring visit scheduling, verification,
and tracking for internship fieldwork. These logs are immutable and private to school mentors and
administrators — students cannot read them.

Out of scope: student-facing daily logbooks (Journals), rubric-based assessment (Assessment), user
profiles (User), policy handbooks (Document).

## Submodules

### SupervisionLog

Immutable record of teacher supervision activities: date of visit/meeting, supervision type
(site_visit, virtual_meeting, phone_call), location, duration, private notes, and follow-up actions.
Linked to a specific registration. Once submitted, logs cannot be edited — corrections require a new
entry. Students have no read access to these logs.

### MonitoringVisit

Scheduling and tracking of field monitoring visits by school administrators and teachers. Supports
multiple visit methods (site visit, virtual meeting, phone call), location tracking, and supervisor
verification workflow. Visit status progresses through scheduled → verified. Linked to a registration
and requires teacher assignment.

## Key Concepts

### Dual-Mentor Model

Every student has two mentors:

- **School Teacher** (Guru Pembimbing): Responsible for academic guidance, supervision visits, and
  logbook Cross-Role Proxy verification. Assigned at enrollment time.
- **Industry Supervisor** (Pembimbing Lapangan): Responsible for workplace mentoring, daily logbook
  verification, and industry-specific guidance. Assigned at placement time.

Both mentor roles are tracked in the `internship_group_members` table rather than a separate mentors
table, keeping the schema lean. Mentor assignment and removal are handled by the Enrollment module.

### Private Supervision Logs

Unlike daily logbooks (which are student-facing), supervision logs are private to the teacher and
administrators. Students cannot view notes written about them. This encourages candid documentation
of student progress, issues, and follow-up actions. Each log is immutable once submitted, creating a
reliable audit trail for school accreditation and parental reporting.

## Dependencies

- Core (base classes)
- Enrollment (registration context)
- User (student, teacher, supervisor identity)

## Used By

- Reports (supervision compliance data for grade card)
