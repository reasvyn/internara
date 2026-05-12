# School Configuration

**Event:** Configuring institutional structure — academic years, departments, and system settings.

**Phase:** 1 — Foundation

**Previous Event:** [Setup Wizard](setup-wizard.md)

**Next Events:** [User Creation](user-creation.md), [Internship Creation](internship-creation.md)

---

## Overview

After the setup wizard creates the initial school profile, the institution needs ongoing configuration management: academic years define the teaching calendar, departments organize students and programs, and system settings control branding and operational parameters.

## Trigger

- First login after setup (initial configuration)
- Start of a new academic period (annual)
- Change in school leadership or branding (ad-hoc)

## Pre-conditions

- [Setup Wizard](setup-wizard.md) has completed
- User is logged in as Super Admin or Admin
- School record exists (created during setup)
- At least one department exists (created during setup)

## Actors

| Actor | Role | Can do |
|---|---|---|
| Super Admin | System administrator | Full access to all settings |
| Admin | School administrator | School profile, departments, academic years, settings |

---

## Event A: Academic Year Management

### Overview

Academic years define the operational calendar. Only one academic year can be active at a time. The `AcademicYearIndex` Livewire component provides the management interface.

### Actions

| Action | Description |
|---|---|
| `CreateAcademicYearAction` | Creates a new academic year record |
| `UpdateAcademicYearAction` | Updates an existing academic year's details |
| `ActivateAcademicYearAction` | Sets a year as active, deactivates all others |
| `DeleteAcademicYearAction` | Deletes an inactive academic year (active years cannot be deleted) |

### Flow

#### Create Academic Year

```
Admin → Admin → Academic Years → Create → Set dates → Save
```

1. Navigate to **Admin → Academic Years**
2. Click **Create**
3. Fill in the form:

| Field | Validation | Description |
|---|---|---|
| **Name** | Required, max 50 | e.g., "2025/2026" |
| **Start Date** | Required, valid date | First day of the academic year |
| **End Date** | Required, after start date | Last day of the academic year |
| **Is Active** | Boolean (default: false) | If true, deactivates any currently active year |

4. Submit — `CreateAcademicYearAction` creates the record
5. Optionally activate immediately from the listing

#### Activate Academic Year

```
Admin → Academic Years → Activate
```

1. Click **Activate** on the desired year
2. `ActivateAcademicYearAction` executes:
   - Deactivates all currently active years: `UPDATE academic_years SET is_active = false WHERE is_active = true`
   - Activates the selected year: `is_active = true`
3. The active year becomes the default for new internships via `CreateInternshipAction`

#### Update Academic Year

```
Admin → Academic Years → Edit → Modify → Save
```

`UpdateAcademicYearAction` handles changes to name, dates, or active status. Changes are audit-logged.

#### Delete Academic Year

- Only inactive years can be deleted (guard: `if ($year->is_active)` → throws exception)
- `DeleteAcademicYearAction` performs the deletion with audit logging
- Database foreign key constraints prevent deletion if internships are linked to the year

### State Transitions

```
Inactive ← → Active (only one at a time)
```

An academic year has no terminal state — it can be toggled between active and inactive as needed. However, an active year cannot be directly deleted (must be deactivated first).

---

## Event B: School Profile Management

### Overview

The school profile (created during setup wizard) can be updated through the admin panel.

### Flow

```
Admin → Admin → School → Edit → Save
```

Navigate to **Admin → School** to update:

| Field | Description |
|---|---|
| **Institution Name** | Official school name |
| **Institutional Code** | NPSN or institutional identifier |
| **Address** | Street address |
| **Email** | Official contact email |
| **Phone** | Office phone number |
| **Website** | School website |
| **Principal Name** | Head of institution |
| **Logo** | Upload via media library |

`UpdateSchoolAction` handles the update with audit logging. The logo is managed via Spatie Media Library's `logo` collection on the School model.

---

## Event C: Department Management

### Overview

Departments organize students, teachers, and internships by academic discipline. The `DepartmentManager` Livewire component provides the management interface.

### Actions

| Action | Description |
|---|---|
| `CreateDepartmentAction` | Creates a new department |
| `UpdateDepartmentAction` | Updates department details |
| `DeleteDepartmentAction` | Deletes a department |

### Flow

#### Create Department

```
Admin → Departments → Create
```

| Field | Validation | Description |
|---|---|---|
| **Name** | Required, max 255 | e.g., "Computer and Informatics Engineering" |
| **Code** | Defined in migration (max 20) | Short identifier (e.g., "TKJ") |
| **Description** | Optional | Department details |

The `CreateDepartmentAction` passes data through to `Department::create()`. Validation is handled by the Livewire component's rules.

#### Update Department

Edit name, code, or description. `UpdateDepartmentAction` handles the update with audit logging.

#### Delete Department

`DeleteDepartmentAction` performs the deletion with audit logging. Database foreign key constraints prevent deletion if the department has linked profiles or other dependent records — no explicit guard is in the action itself.

---

## Event D: System Settings

### Overview

System settings control branding, colors, locale, and operational parameters. They are managed through a single admin panel via the `SystemSetting` Livewire component.

### Flow

```
Admin → Admin → Settings
```

Navigate to **Admin → Settings** to manage:

#### General

| Setting | Type | Description | Default |
|---|---|---|---|
| **Brand Name** | String | Institution display name | From composer.json `display_name` |
| **Site Title** | String | Browser tab title | "{name} - Management System" |
| **Default Locale** | `id` or `en` | Application language | `id` |

#### Brand Assets

| Setting | Type | Validation |
|---|---|---|
| **Brand Logo** | Image upload | Max 1MB |
| **Site Favicon** | Image upload | Max 512KB |

Uploaded images are stored in `public/brand/` via `UploadBrandAssetAction` and served as public URLs.

#### Color Scheme

| Setting | Validation | Default |
|---|---|---|
| **Primary Color** | Hex (#RRGGBB) | `#0ea5e9` (sky-500) |
| **Secondary Color** | Hex (#RRGGBB) | `#64748b` (slate-500) |
| **Accent Color** | Hex (#RRGGBB) | `#f59e0b` (amber-500) |

Colors are injected as CSS custom properties (`--brand-primary`, `--brand-secondary`, `--brand-accent`) and mapped to DaisyUI theme variables (`--p`, `--s`, `--a`). In dark mode, secondary and accent are lightened via `color-mix()`.

#### Operational

| Setting | Type | Description |
|---|---|---|
| **Active Academic Year** | String (YYYY/YYYY) | Current operational year |
| **Attendance Check-In Start** | Time (HH:MM) | Earliest allowed check-in time |
| **Attendance Late Threshold** | Time (HH:MM) | Time after which student is marked late |

#### Mail (SMTP)

| Setting | Type | Storage |
|---|---|---|
| Mail Host | String | Plain text |
| Mail Port | Numeric | Plain text |
| Mail Encryption | `tls`, `ssl`, or `none` | Plain text |
| Mail Username | String | Plain text |
| Mail Password | String | **Encrypted** (`type: encrypted`) |
| Mail From Address | Email | Plain text |
| Mail From Name | String | Plain text |

A **Test Email** button verifies SMTP configuration by temporarily applying the config and sending a `TestMailNotification` to the current user via `TestMailSettingsAction`.

### Settings Resolution Chain

When `setting('key')` is called:

1. **Runtime overrides** (testing only, in-memory)
2. **AppInfo metadata** (composer.json — for `app_name`, `app_version`, `app_author`, `app_license`, `app_support`)
3. **Database settings** (cached forever in `settings.{key}`)
4. **Laravel config** (`config('key')` fallback)
5. **Default value** (passed as parameter)

Cache invalidation:
- Individual key: `Settings::forget($key)` clears key + group + all
- Batch update: Automatic via `SetSettingAction::executeBatch()`
- Full flush: `php artisan cache:clear`

---

## State Changes

| Component | Before | After |
|---|---|---|
| Academic Years | None or inactive | At least one active academic year |
| Departments | Only setup-created ones | Full department structure |
| System Settings | Defaults from seeder | Institution-specific values |
| Brand Colors | Default sky/slate/amber | Institution colors applied site-wide |
| Brand Logo | Fallback `/brand/logo.png` | Uploaded institution logo |
| Site Favicon | `/brand/favicon.ico` | Uploaded favicon |

## Seamless Connection

Once the school structure is configured, the institution is ready for:

- **[User Creation](user-creation.md)** — adding teachers, students, and supervisors
- **[Internship Creation](internship-creation.md)** — setting up internship programs with the active academic year as default
