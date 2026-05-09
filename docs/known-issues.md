# Known Issues

## ActivityLog Model Disconnected

`App\Models\ActivityLog` extends Spatie's `Activity` with domain scopes (`forUser`, `forSubject`, `ofAction`, `recent`, `forModule`, `groupedByDay`), but `config/activitylog.php` still points to the default `Activity::class`. This means `activity()` and `SmartLogger` create `Activity` instances — custom scopes are unavailable through the standard pipeline.

**Fix**: Update `config/activitylog.php` `'activity_model'` to `App\Models\ActivityLog::class`.

## spatie/laravel-model-states Not Used

`spatie/laravel-model-states` is installed but **not used anywhere in `app/`**. State machine behavior is handled by the Entity pattern and enum transition methods (`AccountStatus::validTransitions()`). Consider removing this dependency.

## Mentor/Mentee Architectural Context

Mentor and Mentee are **PKL-period-specific models**, not permanent extensions of User. They only carry data and relationships relevant during an active internship.

### Context Boundary

| Layer | Model | Purpose |
|---|---|---|
| Auth Identity | `User` | Login, roles (Spatie), account statuses, lock/suspend |
| Demographic | `Profile` | Personal data: phone, address, gender, NISN/NIS, school, department |
| PKL Student | `Mentee` | `user_id` (non-unique), `is_active`. Business rules in `MenteeState` entity. |
| PKL Mentor | `Mentor` | `user_id` (non-unique), `type` (school_teacher / industry_supervisor). Business rules in `MentorRole` entity. |

### Key Relationships

```
User ──hasMany──> Mentee ──hasMany──> Registration ──belongsToMany──> Mentor
User ──hasMany──> Mentor ───────────────────────────────────────────────┘
                                                    pivot: registration_mentor
                                                    (registration_id, mentor_id, role)
```

- One Mentee has one Registration per PKL period
- One Registration has many Mentors (via `registration_mentor` pivot)
- One Mentor handles many Registrations (across different periods)

### Registration Flows

| Flow | Entry Point | Creates |
|---|---|---|
| Admin Direct | `DirectPlacementAction` | Mentee + Registration (`active`) + mentor pivot |
| Student Self-Service | `RegisterInternshipAction` | Mentee + Registration (`pending`) |
| Account + PKL (new student) | `ApplyAccountAction` → `VerifyAccountAction` | User + Profile + Mentee + Registration (`active`) |

Account application collects identity + PKL preferences in one form. Admin approval creates everything in a single transaction. User is created with `setup_required = true`.

### Pending Work

- `app/Livewire/Mentee/` is empty — no Mentee-specific Livewire components exist yet
- Policies across Attendance, Logbook, SupervisionLog, etc. still reference old `teacher_id`/`mentor_id`/`student_id` fields directly
- Team feature (grouping Mentees + Mentors) is deferred
- `account_application` Livewire components (public form + admin approval list) are not yet built

## Duplicate Notification Classes

Root-level and domain-scoped notification classes overlap:

- `App\Notifications\JobFailedNotification` ↔ `App\Notifications\Document\JobFailedNotification`
- `App\Notifications\TestMailNotification` ↔ `App\Notifications\User\TestMailNotification`

Root-level versions should be removed in favor of domain-scoped ones.

## Legacy Code Reference

The legacy modular monolith is preserved at `legacy/internara-modular-monolith/` (27 modules) for reference only. The `nwidart/laravel-modules` dependency has been removed. Developers may encounter overlapping patterns still needing refactor.