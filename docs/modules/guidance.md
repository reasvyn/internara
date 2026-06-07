# Guidance — Documentation Overview

> Last updated: 2026-06-06  
> Changes: Removed references to the eliminated `mentors` and `mentees` tables (now inlined in
> `profiles`). Mentor assignments now use `internship_group_members` instead of the removed
> `registration_mentor` pivot.

Mentoring relationships coordination and teacher supervision field logging.

For complete technical reference including API, models, actions, and components, see
[guidance-reference.md](guidance-reference.md).

---

## Key Principles

- **Dual-Mentor supervision** — Establishes relationships between the student's registration, the
  assigned School Teacher (_Guru Pembimbing_), and Industry Supervisor (_Pembimbing Lapangan_).
  These assignments are tracked in the `internship_group_members` table with roles `school_teacher`
  and `industry_supervisor`.
- **Private Supervision Logs** — Teachers write visitation, virtual meetings, or call logs to
  monitor student progress. These logs are private to school mentors and administrators, keeping
  supervision notes separate from student-facing daily logbooks.
- **Immutable Log Auditing** — Supervision logs are immutable records once written, producing an
  audit trail for school inspection requirements.

---

## Context Boundary

The **Guidance** module:

- Owns the **`SupervisionLog`** model.
- Consumes **Enrollment (`registrations`)** to identify active placements and student records.
- Consumes **User (`users` / `profiles`)** to identify students and verify assigned mentors.

---

## Module Rules

- **Access Controls:** Supervision logs are private. Students cannot read supervision notes written
  by their teachers.
- **Immutability:** Once submitted, a supervision log cannot be edited. Corrections require a new
  entry.
- **Guidance Assignment:** Teachers are assigned to students during enrollment verification.

---

## Submodules

- **SupervisionLog**: Tracks site visits, online video meetings, or phone supervisions conducted by
  school teachers, storing dates, types, and private notes.

---

## Error Handling & Failure Modes

- **Unauthorized Logging:** Attempting to write a supervision log for a student who is not assigned
  to the teacher (via the registration mentor mappings) throws a `403 Forbidden` response.
- **Editing Supervision Logs:** Patching or updating a finalized supervision log throws a
  `RejectedException`.

---

## Quick References

### Actions & Business Logic

- **4** actions:
    - `CreateSupervisionLogAction` — Creates an immutable log.
    - `VerifySupervisionLogAction` — Finalizes verification of a log.
    - `AssignMentorAction` — Map school/company supervisors.
    - `RemoveMentorAction` — Revoke supervisor mappings.

### Data & Persistence

- **1** model: `SupervisionLog`.
- UUID PKs, index on `registration_id`.

### User Interface

- **2** Livewire components:
    - `SupervisionLogManager` — Dashboard for teachers to track visitations.
    - `MenteeViewer` — Student/Mentee overview board for supervisors.

### Authorization

- **1** policy: `SupervisionLogPolicy`.
- Teachers write and manage own logs; students have no read access.

---

For complete technical reference, see [guidance-reference.md](guidance-reference.md).
