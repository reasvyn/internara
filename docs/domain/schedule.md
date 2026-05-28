# Schedule Domain

## Purpose

Schedule manages calendar events — deadlines, site visits, presentations, and other
program events.

---

## Models

| Model | Key Fields |
|---|---|
| `Schedule` | title, description, start_at, end_at, type, location, internship_id |

## Actions

| Action | Type |
|---|---|
| `CreateScheduleAction` | Command |
| `UpdateScheduleAction` | Command |
| `DeleteScheduleAction` | Command |

## Where to Find It

- `app/Domain/Schedule/Models/Schedule.php`
- `app/Domain/Schedule/Actions/`
