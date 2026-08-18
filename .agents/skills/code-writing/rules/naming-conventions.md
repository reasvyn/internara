# Naming Conventions — Classes, Files, Routes, Data

> **Last updated:** 2026-08-17 **Changes:** rewritten comprehensively — per-element naming rule, rationale, and anti-patterns

Names are contracts: they encode module, role, and layer so code, docs, and scanners agree without
reading bodies. Every name below is enforced (or greppable) — use the table and the anti-patterns,
not ad-hoc deviations.

---

## Naming Table

| Element | Convention | Example |
|---------|-----------|---------|
| Submodule directory | Singular `{Name}` | `User`, `Profile`, `Internship` |
| Model | Singular `{Name}` | `User`, `AcademicYear` |
| Command Action | `{Verb}{Entity}Action` | `CreateUserAction` |
| Read Action | `Read{Entity}Action` | `ReadTeacherDashboardAction` |
| Process Action | `Process{Entity}Action` | `ProcessRegistrationAction` |
| Entity | `{Name}` | `Apprentice`, `RegistrationState` |
| DTO | `{Verb}{Entity}Data` or `{Entity}Data` | `SetupTokenData` |
| Livewire | `{Name}` suffixed with Manager/Editor/Center | `UserManager` |
| Livewire alias (submodule) | `{kebab-module}.{kebab-submodule}.{kebab-name}` | `admin.user.user-manager` |
| Livewire Form | `{Entity}Form` | `AcademicYearForm` |
| Policy | `{Name}Policy` | `UserPolicy` |
| Exception | `{Name}Exception` | `RejectedException` |
| Event | `{Entity}{Actioned}` (past tense) | `InternshipCreated` |
| Listener | `{Verb}{Entity}` | `NotifyAdminsInternshipCreated` |
| Notification | `{Entity}{NotificationType}Notification` | `WelcomeNotification` |
| Console command | `{module}:{action}` | `system:health` |
| Route name | descriptive (mirror URL path) | `login`, `admin.users.index` |
| Config key | `snake_case` with `{file}.{key}` | `app.name` |
| Column/table | `snake_case` | `user_id`, `academic_years` |
| Boolean methods | `is`/`has`/`can`/`should` prefix | `isActive()`, `allowsLogin()` |
| Test method | Pest `test()` with `{SpecID}-{ReqID}:` prefix, grouped by `describe("{SpecID}: Test description...")` | `test("{SpecID}-{ReqID}: Test description...")` |
| Test file | `{Name}Test.php` | `CreateUserActionTest.php` |
| Factory | `{Name}Factory` | `UserFactory` |
| Migration | `YYYY_MM_DD_HHMMSS_create_{table}_table.php` | `2026_04_29_092750_create_users_table.php` |

---

## Per-Element Rules

### Actions — `{Verb}{Entity}Action`, `Read{Entity}Action`, `Process{Entity}Action`

**Intent:** The name states both the operation and the subject, so any reader knows the action's
single responsibility without opening the file.

**Why it matters:** Ambiguous names like `UpdateAction` or `SyncDataAction` collide across modules
and defeat the Action's single-response purpose. The verb prefix (`Create`/`Update`/`Delete`/
`Read`/`Process`/`Approve`/`Reject`/...) plus entity is the stable, greppable handle.

**How to apply:** Command = verb+entity (`FinalizeSubmissionAction`); Read = `Read`+entity
(`ReadStudentDashboardAction`); Process = `Process`+entity (`ProcessRegistrationAction`).

**Anti-patterns to avoid:** `HelperAction`, `ExtraAction`, verbs that overlap (`Create` vs `Add`);
repeating the module in the class name.

### Entities / DTOs / Models

**Intent:** Entity and Model share the domain name (`User` Model ↔ `User` Entity); DTOs name the
verbal operation over the entity (`CreateUserData`).

**Why it matters:** The Model-Entity bridge pattern assumes matching names — `User::asTeacherEntity()`
returns `Teacher` Entity while the model is `User`. Predictable pairing keeps bridges greppable.

**How to apply:** Entity = `{Name}` (the role/user's domain noun); Model = `{Name}`; DTO = verb+name
or plain name; Form = `{Entity}Form`.

### Livewire — `{Name}` + `Manager`/`Editor`/`Center`

**Intent:** The suffix signals the component's role: a Manager lists+manages, an Editor edits a
single record, a Center drives a workflow.

**Why it matters:** The alias (`admin.user.user-manager`) resolves the component in routes and
Livewire; a clear noun groups the file, alias, route, and test.

**Anti-patterns to avoid:** `UsersListPage`, `Page`-only names without a role suffix; aliases that
diverge from the path structure.

### Events — `{Entity}{Actioned}` (past tense)

**Intent:** The event names the fact that happened (`InternshipCreated`), past tense, noun first.

**Why it matters:** Past-tense facts read correctly in listener wiring and in log traces
("on InternshipCreated → Notify..."). Wrong tense (`CreateInternship`) reads like a command and
muddies intent.

### Test names — `{SpecID}-{ReqID}: description`

**Intent:** Every test name opens with the spec + requirement ID, grouped under a `describe()` of
the spec ID, so the suite maps 1:1 to spec requirements.

**Why it matters:** Spec-traceable names let `spec-audit` and review check coverage mechanically —
no test without a requirement, no requirement without a test (see `pest-testing`).

**How to apply:** `describe("{SpecID}: Test description...")` + `test("{SpecID}-{ReqID}: Test
description...")`, full prefix repeated on each `test()` even inside the `describe`.