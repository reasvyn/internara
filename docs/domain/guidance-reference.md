# Guidance — API Reference
> Last updated: 2026-05-31
> Changes: implemented PDF attachment, teacher/supervisor routes, renamed HandbookManager/HandbookIndex

> **Legend:** ✅ Implemented = code exists | ⏳ Planned = not yet implemented

Total: 11 files — ✅ 11 Implemented, ⏳ 2 Planned

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Guidance/Actions/AcknowledgeHandbookAction.php` | `AcknowledgeHandbookAction` | `BaseAction` | ✅ Records user acknowledgment of a handbook |
| `Guidance/Actions/CreateHandbookAction.php` | `CreateHandbookAction` | `BaseAction` | ✅ Creates a new handbook with slug; ✅ optional PDF file attachment |
| `Guidance/Actions/DeleteHandbookAction.php` | `DeleteHandbookAction` | `BaseAction` | ✅ Deletes a handbook |
| `Guidance/Actions/UpdateHandbookAction.php` | `UpdateHandbookAction` | `BaseAction` | ✅ Updates an existing handbook; ✅ file replace/remove |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Guidance/Entities/HandbookPublishState.php` | `HandbookPublishState` | `BaseEntity` | ✅ Read-only DTO for handbook publish state |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Guidance/Livewire/HandbookManager.php` | `HandbookManager` | `BaseRecordManager` | ✅ Admin handbook CRUD with PDF upload |
| `Guidance/Livewire/HandbookIndex.php` | `HandbookIndex` | `Component` | ✅ Student/teacher/supervisor handbook view with acknowledgment and PDF download |

### Livewire Form Objects

| File | Class | Extends | Fields | Used By |
|---|---|---|---|---|
| `Guidance/Livewire/Forms/HandbookForm.php` | `HandbookForm` | `Form` | ✅ id, title, content, version, is_active, target_audience | `HandbookManager` |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Guidance/Models/Handbook.php` | `Handbook` | `BaseModel`, `HasMedia` | ✅ Eloquent model for handbooks; ✅ `file` media collection for PDF attachments |
| `Guidance/Models/HandbookAcknowledgement.php` | `HandbookAcknowledgement` | `BaseModel` | ✅ Eloquent model for handbook acknowledgment records |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Guidance/Policies/HandbookPolicy.php` | `HandbookPolicy` | `BasePolicy` | ✅ Authorization for handbook operations |

## Routes

| Route | Component | Middleware | Description |
|---|---|---|---|
| `admin/handbooks` | `HandbookManager` | `auth`, `role:super_admin\|admin` | ✅ Admin handbook CRUD with PDF upload |
| `student/handbooks` | `HandbookIndex` | `auth`, `role:student` | ✅ Student handbook browse & acknowledge |
| `teacher/handbooks` | `HandbookIndex` | `auth`, `role:teacher` | ✅ Teacher handbook browse & acknowledge |
| `supervisor/handbooks` | `HandbookIndex` | `auth`, `role:supervisor` | ✅ Supervisor handbook browse & acknowledge |

## Views

| File | Description |
|---|---|
| `guidance/handbook-manager.blade.php` | ✅ Admin CRUD table with PDF upload modal |
| `guidance/handbook-index.blade.php` | ✅ Role-facing card list with acknowledge button and PDF download |
| ⏳ `guidance/handbook-reader.blade.php` | ⏳ Full-screen reading view with Markdown ToC sidebar |

## Where to Find It

- `app/Domain/Guidance/Models/`
- `app/Domain/Guidance/Actions/`

## Dependency Graph

```
Guidance Domain
├── Core        → BaseModel, BaseAction, SmartLogger
├── User        → User model (author/reader identity)
├── Auth        → Role definitions (audience filtering)
└── MediaLibrary → PDF file storage (HasMedia)
```

Consumed by:
  Mentee (reading guidance), Mentor (reading guidance materials)
