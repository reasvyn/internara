# Logbook — API Reference
> Last updated: 2026-05-23
> Changes: fix: complete system initialization overhaul — security, middleware, recovery, form objects, docs


Total: 11 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Logbook/Actions/CreateLogbookAction.php` | `CreateLogbookAction` | `BaseAction` | Creates a daily logbook entry |
| `Logbook/Actions/DeleteLogbookAction.php` | `DeleteLogbookAction` | `BaseAction` | Deletes a logbook entry |
| `Logbook/Actions/SubmitLogbookAction.php` | `SubmitLogbookAction` | `BaseAction` | Submits a daily log entry |
| `Logbook/Actions/UpdateLogbookAction.php` | `UpdateLogbookAction` | `BaseAction` | Updates a logbook entry |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Logbook/Entities/LogbookState.php` | `LogbookState` | `BaseEntity` | Read-only DTO for logbook entry state |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Logbook/Enums/LogbookStatus.php` | `LogbookStatus` | `LabelEnum`, `StatusEnum` | Logbook entry lifecycle status |

## Form Requests

| File | Class | Extends | Description |
|---|---|---|---|
| `Logbook/Http/Requests/CreateLogbookRequest.php` | `CreateLogbookEntryRequest` | `FormRequest` | Validation for creating logbook entries |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Logbook/Livewire/LogbookEntry.php` | `LogbookEntry` | `Component`, `WithPagination`, `WithFileUploads` | Student logbook entry form with camera/photo upload |
| `Logbook/Livewire/LogbookManager.php` | `LogbookManager` | `BaseRecordManager` | CRUD manager for logbook entries |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Logbook/Models/Logbook.php` | `Logbook` | `BaseModel`, `HasMedia` | Eloquent model for daily logbook entries with photo collection support |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Logbook/Policies/LogbookPolicy.php` | `LogbookPolicy` | `BasePolicy` | Authorization for logbook operations |

## Where to Find It

- `app/Domain/Logbook/Models/Logbook.php`
- `app/Domain/Logbook/Actions/`

## Dependency Graph

```
Logbook Domain
├── Core         → BaseModel, BaseAction, SmartLogger
├── User         → User model (author identity)
└── Registration → Registration records (logbook context)
```

Consumed by:
  Mentee (dashboard entry), Mentor (logbook review),
  Internship (closure verification)

