# Guidance

> **Last updated:** 2026-06-08

Mentoring relationship coordination and teacher supervision field logging: dual-mentor assignments and immutable supervision visit records.

## Purpose & Boundary

Guidance manages the mentoring relationships between students and their assigned school teachers (Guru Pembimbing) and industry supervisors (Pembimbing Lapangan). It also provides teachers with a private supervision log system for recording site visits, virtual meetings, and phone supervisions. These logs are immutable and private to school mentors and administrators — students cannot read them.

Out of scope: student-facing daily logbooks (Journals), rubric-based assessment (Assessment), user profiles (User).

## Submodules

### SupervisionLog
Immutable record of teacher supervision activities: date of visit/meeting, supervision type (site_visit, virtual_meeting, phone_call), location, duration, private notes, and follow-up actions. Linked to a specific registration. Once submitted, logs cannot be edited — corrections require a new entry. Students have no read access to these logs.

### MentorAssignment
Tracks the mapping of school teachers and industry supervisors to students via `internship_group_members` with role classification. Teachers are assigned during enrollment verification. Supervisors are assigned when a placement is confirmed. The `AssignMentorAction` and `RemoveMentorAction` handle these mappings.

## Key Concepts

### Dual-Mentor Model

Every student has two mentors:
- **School Teacher** (Guru Pembimbing): Responsible for academic guidance, supervision visits, and logbook fallback verification. Assigned at enrollment time.
- **Industry Supervisor** (Pembimbing Lapangan): Responsible for workplace mentoring, daily logbook verification, and industry-specific guidance. Assigned at placement time.

Both mentor roles are tracked in the `internship_group_members` table rather than a separate mentors table, keeping the schema lean.

### Private Supervision Logs

Unlike daily logbooks (which are student-facing), supervision logs are private to the teacher and administrators. Students cannot view notes written about them. This encourages candid documentation of student progress, issues, and follow-up actions. Each log is immutable once submitted, creating a reliable audit trail for school accreditation and parental reporting.

## Dependencies

- Core (base classes)
- Enrollment (registration context)
- User (student, teacher, supervisor identity)

## Used By

- Reports (supervision compliance data for grade card)
