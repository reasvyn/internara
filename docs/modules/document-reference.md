# Document — Technical Reference

> Last updated: 2026-06-06  
> Changes: Removed `DocumentAcknowledgement` model. Policy acknowledgements are now tracked via `activity_log` (event: `acknowledged`). Reduced models from 2 to 1, components from 3 to 2, policies from 2 to 1.

Detailed structural and implementation reference for the **Document** module.

---

## Overview

Manages correspondence templates, policy handbooks, and compliance acknowledgements.

### Module Statistics
- **Actions**: 4 business logic operations
- **Models**: 1 data entity (`Document`)
- **Livewire Components**: 2 UI components
- **Policies**: 1 authorization rule
- **Submodules**: 2 module submodules

### Submodules
- `OfficialDocument`: File templates and PDF compiler endpoints.
- `Handbook`: Policy guides and acknowledgement tracking via `activity_log`.

---

## Dependency Graph

This module depends on:
- **Core** (base classes and DomPDF wrapper)
- **User** (recipient user records)
- **SysAdmin** (activity_log for compliance tracking)

---

## Actions

| File | Class | Extends |
|---|---|---|
| `OfficialDocument/Actions/SaveDocumentTemplateAction.php` | `SaveDocumentTemplateAction` | `BaseAction` |
| `OfficialDocument/Actions/RenderDocumentAction.php` | `RenderDocumentAction` | `BaseAction` |
| `OfficialDocument/Actions/GenerateReportAction.php` | `GenerateReportAction` | `BaseAction` |
| `OfficialDocument/Actions/DeleteReportAction.php` | `DeleteReportAction` | `BaseAction` |

> **Note:** `Handbook/Actions/AcknowledgeDocumentAction.php` and `PruneAcknowledgementsAction.php` are planned but not yet implemented. Acknowledgements are currently handled inline via `activity()` helper.

---

## Models

| File | Class | Extends |
|---|---|---|
| `Models/Document.php` | `Document` | `BaseModel` |

> **Note:** `DocumentAcknowledgement` model removed. Compliance tracking uses `activity_log` with `event = 'acknowledged'`.

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `OfficialDocument/Livewire/TemplateManager.php` | `TemplateManager` | `Component` |
| `OfficialDocument/Livewire/ReportsManager.php` | `ReportsManager` | `Component` |

> **Note:** `Handbook/Livewire/DocumentAcknowledgementTracker.php` is planned but not yet implemented.

---

## Authorization Policies

| File | Policy | Extends |
|---|---|---|
| `Policies/DocumentPolicy.php` | `DocumentPolicy` | `BasePolicy` |

> **Note:** `Handbook/Policies/DocumentAcknowledgementPolicy.php` is planned but not yet implemented.

---

## HTTP Controllers

| File | Controller | Extends |
|---|---|---|
| `OfficialDocument/Http/Controllers/DocumentRenderController.php` | `DocumentRenderController` | `BaseController` |

---

## File Organization

```
app/Document/
├──            ← Submodule roots
│   ├── OfficialDocument/
│   │   ├── Actions/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   └── Requests/
│   │   └── Livewire/
│   └── Handbook/
│       ├── Actions/        ← Planned
│       └── Livewire/       ← Planned
├── Enums/                ← DocumentCategory enum
├── Models/               ← Document model
├── Policies/             ← DocumentPolicy
└── Support/              ← DocumentRenderer (DomPDF wrapper)
```

---

## Architectural Integration

This module integrates with the system across the following directories and resources:

- **Submodules**: `OfficialDocument`, `Handbook`
- **Business Logic (`app/`)**: Located in [app/Document/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/app/Document/)
- **Routing (`routes/`)**: [routes/web/document.php](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/routes/web/document.php)
- **Views (`views/`)**: Blade templates and layouts are in [resources/views/document/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/resources/views/document/)
- **Testing (`tests/`)**: Feature `tests/Feature/Document/`, Unit `tests/Unit/Document/`


*For overview and business context, see [document.md](document.md)*
