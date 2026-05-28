# Mentee Domain

## Purpose

Mentee is the student's lens into the system — dashboard, progress tracking, self-service
access. Each student has one mentee record linking them to the program.

---

## Models

| Model | Key Fields |
|---|---|
| `Mentee` | user_id, is_active, internal_notes |

## Actions

| Action | Type |
|---|---|
| `CreateMenteeAction` | Command |
| `UpdateMenteeAction` | Command |
| `DeleteMenteeAction` | Command |

## Where to Find It

- `app/Domain/Mentee/Models/Mentee.php`
- `app/Domain/Mentee/Actions/`
