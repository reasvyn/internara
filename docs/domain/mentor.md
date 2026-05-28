# Mentor Domain

## Purpose

Mentor handles supervision — teachers and company supervisors who guide students during
their placement. Includes supervision logs and mentor profile management.

---

## Design Principles

### 1. Dual Mentorship

Every student has two mentors: a school teacher (academic) and a company supervisor
(industry). Both resolve to the MENTOR functional role via `Role::resolvesTo()`.

### 2. Supervision Logs

Private notes recording observations, concerns, and action items. Must be verified
by a supervisor before they are considered complete.

---

## Models

| Model | Key Fields |
|---|---|
| `Mentor` | user_id, type (teacher/supervisor), is_active |
| `SupervisionLog` | registration_id, supervisor_id, date, topic, notes, is_verified |

## Actions

| Action | Type |
|---|---|
| `CreateMentorAction` | Command |
| `UpdateMentorAction` | Command |
| `DeleteMentorAction` | Command |
| `CreateMentorProfileAction` | Command |
| `UpdateMentorProfileAction` | Command |
| `ToggleMentorActiveAction` | Command |
| `CreateSupervisionLogAction` | Command |
| `VerifySupervisionLogAction` | Command |

## Where to Find It

- `app/Domain/Mentor/Models/`
- `app/Domain/Mentor/Actions/`
