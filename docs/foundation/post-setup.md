# Post-Setup — Initial Data Population & Configuration

> **Last updated:** 2026-07-22 **Changes:** feat — rewrite to developer reference; merge from `docs/guide/03-post-setup.md`

## Description

Reference for the configuration steps required after the setup wizard completes. Covers settings
initialization, academic structure, partnership registration, user provisioning, and program
configuration. Organized as a developer checklist with code paths and module cross-references.

---

## Phase 1: Foundation

| Step | Action | Module | Key Code Path |
| ---- | ------ | ------ | ------------- |
| 1 | Configure system settings (branding, locale, timezone) | [Settings](../modules/settings.md) | `Setting::update()` |
| 2 | Configure backup schedule | [Settings](../modules/settings.md) | `BackupSetting` |
| 3 | Create academic years | [Academics](../modules/academics.md) | `AcademicYear` |
| 4 | Add remaining departments | [Academics](../modules/academics.md) | `Department` |

---

## Phase 2: People

| Step | Action | Module | Key Code Path |
| ---- | ------ | ------ | ------------- |
| 5 | Register partner companies | [Partners](../modules/partners.md) | `Company` |
| 6 | Create partnership agreements (MoU) | [Partners](../modules/partners.md) | `Partnership` |
| 7 | Create teacher/supervisor/student accounts | [User](../modules/user.md) | `CreateUserAction` |
| 8 | Assign mentor flags to teachers/supervisors | [User](../modules/user.md) | `User.mentor_type` |

---

## Phase 3: Program Configuration

| Step | Action | Module | Key Code Path |
| ---- | ------ | ------ | ------------- |
| 9 | Create internship period | [Program](../modules/program.md) | `InternshipProgram` |
| 10 | Configure document requirements | [Enrollment](../modules/enrollment.md) | `RegistrationRequirement` |
| 11 | Open student registration | [Enrollment](../modules/enrollment.md) | `Registration` |

---

## Phase 4: Go-Live Verification

| Step | Action | Module | Verification |
| ---- | ------ | ------ | ------------ |
| 12 | Approve student registrations | [Enrollment](../modules/enrollment.md) | Registration status transitions |
| 13 | Place students at companies | [Enrollment](../modules/enrollment.md) | `Placement` |
| 14 | Verify attendance workflow | [Journals](../modules/journals.md) | Clock in/out flow |
| 15 | Verify logbook workflow | [Journals](../modules/journals.md) | Journal entry → mentor review |
| 16 | Verify assignment workflow | [Assignment](../modules/assignment.md) | Create → submit → grade |
| 17 | Configure email notifications | [Settings](../modules/settings.md) | SMTP settings |

---

## Settings Reference

Key settings populated during/after setup:

| Key | Type | Default | Description |
| --- | ---- | ------- | ----------- |
| `school_name` | string | *(from wizard)* | Institution name |
| `institutional_code` | string | *(from wizard)* | NPSN or similar |
| `school_email` | string | *(from wizard)* | Official email |
| `school_address` | string | `""` | Street address |
| `school_phone` | string | `""` | Contact phone |
| `school_website` | string | `""` | Website URL |
| `principal_name` | string | `""` | Head of school |
| `active_academic_year` | string | `""` | Current year (e.g., `2025/2026`) |
| `brand_name` | string | *(school_name)* | Display name |
| `site_title` | string | `""` | Browser tab title |
| `default_locale` | enum | `id` | `id` or `en` |

---

## Quick References

- `app/Settings/` — Settings CRUD and observer
- `app/SysAdmin/Console/Commands/` — CLI commands for provisioning
- [Installation](installation.md) — CLI provisioning
- [Setup Wizard](setup-wizard.md) — Browser-based initial config
- [System Health](system-health.md) — Post-setup verification
- `docs/foundation/project-requirements.md` — Full feature inventory
