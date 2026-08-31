# Document — Technical Reference

## Description

Detailed structural and implementation reference for the **Document** module.

---

## Overview

Manages official document templates, correspondence generation (MoU, agreements), report generation,
and compliance acknowledgements.

## Actions

| File | Class | Extends |
|---|---|---|
| `Domain/Handbook/Actions/AcknowledgeHandbookAction.php` | `AcknowledgeHandbookAction` | `BaseCommandAction` |
| `Domain/Handbook/Actions/CreateHandbookAction.php` | `CreateHandbookAction` | `BaseCommandAction` |
| `Domain/Handbook/Actions/DeleteHandbookAction.php` | `DeleteHandbookAction` | `BaseCommandAction` |
| `Domain/Handbook/Actions/UpdateHandbookAction.php` | `UpdateHandbookAction` | `BaseCommandAction` |
| `Domain/OfficialDocument/Actions/DeleteReportAction.php` | `DeleteReportAction` | `BaseCommandAction` |
| `Domain/OfficialDocument/Actions/GenerateDocumentAction.php` | `GenerateDocumentAction` | `BaseCommandAction` |
| `Domain/OfficialDocument/Actions/GenerateReportAction.php` | `GenerateReportAction` | `BaseCommandAction` |
| `Domain/OfficialDocument/Actions/RenderDocumentAction.php` | `RenderDocumentAction` | `BaseCommandAction` |
| `Domain/OfficialDocument/Actions/SaveDocumentTemplateAction.php` | `SaveDocumentTemplateAction` | `BaseCommandAction` |

## Models

| File | Class | Extends |
|---|---|---|
| `Models/Document.php` | `Document` | `BaseModel` |

## Enums

| File | Enum | Implements | Values |
|---|---|---|---|
| `Domain/Handbook/Enums/HandbookAudience.php` | `HandbookAudience` | `LabelEnum` | — |
| `Enums/DocumentCategory.php` | `DocumentCategory` | `LabelEnum` | — |

## Policies

| File | Policy | Extends |
|---|---|---|
| `Policies/DocumentPolicy.php` | `DocumentPolicy` | `BasePolicy` |

## HTTP Controllers

| File | Controller | Extends |
|---|---|---|
| `Domain/OfficialDocument/Http/Controllers/DocumentRenderController.php` | `DocumentRenderController` | `BaseController` |

## Form Requests

| File | Request | Purpose |
|---|---|---|
| `Domain/OfficialDocument/Http/Requests/GenerateReportRequest.php` | `GenerateReportRequest` | — |

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Domain/Handbook/Livewire/Forms/HandbookForm.php` | `HandbookForm` | `BaseFormView` |
| `Domain/Handbook/Livewire/HandbookManager.php` | `HandbookManager` | `BaseRecordManager` |
| `Domain/Handbook/Livewire/StudentHandbookList.php` | `StudentHandbookList` | `BaseRecordManager` |
| `Domain/OfficialDocument/Livewire/ReportsManager.php` | `ReportsManager` | `BaseRecordManager` |
| `Domain/OfficialDocument/Livewire/TemplateManager.php` | `TemplateManager` | `BaseRecordManager` |

## Support

| File                           | Class              | Purpose                                |
| ------------------------------ | ------------------ | -------------------------------------- |
| `Services/DocumentRenderer.php` | `DocumentRenderer` | Renders document templates to PDF/HTML |

---

## Handbook Submodule

> See the detailed tables above — this summary consolidates the Handbook domain's components for quick reference.

| Kind      | File                                             | Class                     | Extends / Implements    |
| --------- | ------------------------------------------------ | ------------------------- | ----------------------- |
| Action    | `Domain/Handbook/Actions/CreateHandbookAction.php`    | `CreateHandbookAction`    | `BaseCommandAction`     |
| Action    | `Domain/Handbook/Actions/UpdateHandbookAction.php`    | `UpdateHandbookAction`    | `BaseCommandAction`     |
| Action    | `Domain/Handbook/Actions/DeleteHandbookAction.php`    | `DeleteHandbookAction`    | `BaseCommandAction`     |
| Action    | `Domain/Handbook/Actions/AcknowledgeHandbookAction.php` | `AcknowledgeHandbookAction` | `BaseCommandAction`   |
| Data      | `Domain/Handbook/Data/HandbookData.php`               | `HandbookData`            | `BaseData`              |
| Entity    | `Domain/Handbook/Entities/HandbookEntity.php`         | `HandbookEntity`          | `BaseEntity`            |
| Enum      | `Domain/Handbook/Enums/HandbookAudience.php`          | `HandbookAudience`        | `LabelEnum` (all, student, teacher, supervisor) |
| Event     | `Domain/Handbook/Events/HandbookCreated.php`          | `HandbookCreated`         | `BaseEvent`             |
| Event     | `Domain/Handbook/Events/HandbookUpdated.php`          | `HandbookUpdated`         | `BaseEvent`             |
| Event     | `Domain/Handbook/Events/HandbookDeleted.php`          | `HandbookDeleted`         | `BaseEvent`             |
| Listener  | `Domain/Handbook/Listeners/ClearHandbookCache.php`    | `ClearHandbookCache`      | —                       |
| Form      | `Domain/Handbook/Livewire/Forms/HandbookForm.php`     | `HandbookForm`            | `BaseFormView`          |

Handbooks are stored in the shared `documents` table (category `handbook`); no separate
migration or factory.

---

## Jobs

| File                          | Class                 | Queue       | Purpose                                    |
| ----------------------------- | --------------------- | ----------- | ------------------------------------------ |
| `Jobs/GenerateDocumentJob.php` | `GenerateDocumentJob` | `documents` | Async PDF generation via `GenerateDocumentAction` (7H5D6 FR-GW1, ZT6VS) |

---


## Data / DTOs

| File | Class | Extends |
|---|---|---|
| `Domain/Handbook/Data/HandbookData.php` | `HandbookData` | `BaseData` |


## Entities

| File | Class | Extends |
|---|---|---|
| `Domain/Handbook/Entities/HandbookEntity.php` | `HandbookEntity` | `BaseEntity` |


## Events

| File | Event | Extends |
|---|---|---|
| `Domain/Handbook/Events/HandbookCreated.php` | `HandbookCreated` | `BaseEvent` |
| `Domain/Handbook/Events/HandbookDeleted.php` | `HandbookDeleted` | `BaseEvent` |
| `Domain/Handbook/Events/HandbookUpdated.php` | `HandbookUpdated` | `BaseEvent` |


## Listeners

| File | Listener | Listens To |
|---|---|---|
| `Domain/Handbook/Listeners/ClearHandbookCache.php` | `ClearHandbookCache` | — |

## Routes

File: `routes/web/document.php` Named routes: `sysadmin.reports.index`,
`sysadmin.documents.render`, `sysadmin.documents.render.store`, `sysadmin.handbooks.index`,
`student.handbooks`

## Views

Views are located in `resources/views/document/`. See [UI/UX](../../guides/ui-ux.md) for the design
system.

## Tests

Tests are located in `tests/Document/`. See [Testing](../../guides/infra/testing.md)
for the testing conventions.

## Factories

| Factory           | Model      |
| ----------------- | ---------- |
| `DocumentFactory` | `Document` |

## Migrations

| Migration                | Table       |
| ------------------------ | ----------- |
| `create_documents_table` | `documents` |

---

## Architectural Integration

- **Submodules**: `OfficialDocument`, `Handbook`
- **Business Logic**: `app/Modules/Document/`
- **Routing**: `routes/web/document.php`
- **Views**: `resources/views/document/`
- **Testing**: `tests/Document/`
- **Dependencies**: Core, User

_For overview and business context, see [document.md](document.md)._
