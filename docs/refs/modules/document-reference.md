# Document — Technical Reference

## Description

Detailed structural and implementation reference for the **Document** module.

---

## Overview

Manages official document templates, correspondence generation (MoU, agreements), report generation,
and compliance acknowledgements.

## Actions

| File                                                      | Class                        | Extends             |
| --------------------------------------------------------- | ---------------------------- | ------------------- |
| `OfficialDocument/Actions/GenerateDocumentAction.php`     | `GenerateDocumentAction`     | `BaseCommandAction` |
| `OfficialDocument/Actions/GenerateReportAction.php`       | `GenerateReportAction`       | `BaseCommandAction` |
| `OfficialDocument/Actions/RenderDocumentAction.php`       | `RenderDocumentAction`       | `BaseCommandAction` |
| `OfficialDocument/Actions/SaveDocumentTemplateAction.php` | `SaveDocumentTemplateAction` | `BaseCommandAction` |
| `OfficialDocument/Actions/DeleteReportAction.php`         | `DeleteReportAction`         | `BaseCommandAction` |

---

## Models

| File                  | Class      | Extends     |
| --------------------- | ---------- | ----------- |
| `Models/Document.php` | `Document` | `BaseModel` |

---

## Enums

| File                         | Enum               | Implements  | Values                                                             |
| ---------------------------- | ------------------ | ----------- | ------------------------------------------------------------------ |
| `Enums/DocumentCategory.php` | `DocumentCategory` | `LabelEnum` | application, permit, certificate, report, letter, policy, handbook |

---

## Policies

| File                          | Policy           | Extends      |
| ----------------------------- | ---------------- | ------------ |
| `Policies/DocumentPolicy.php` | `DocumentPolicy` | `BasePolicy` |

---

## HTTP Controllers

| File                                                             | Controller                 | Extends          |
| ---------------------------------------------------------------- | -------------------------- | ---------------- |
| `OfficialDocument/Http/Controllers/DocumentRenderController.php` | `DocumentRenderController` | `BaseController` |

## Form Requests

| File                                                       | Request                 | Purpose                      |
| ---------------------------------------------------------- | ----------------------- | ---------------------------- |
| `OfficialDocument/Http/Requests/GenerateReportRequest.php` | `GenerateReportRequest` | Report generation validation |

## Livewire Components

| File                                            | Component         | Extends     |
| ----------------------------------------------- | ----------------- | ----------- |
| `OfficialDocument/Livewire/TemplateManager.php` | `TemplateManager` | `Component` |
| `OfficialDocument/Livewire/ReportsManager.php`  | `ReportsManager`  | `Component` |
| `Handbook/Livewire/HandbookManager.php`         | `HandbookManager` | `BaseRecordManager` |
| `Handbook/Livewire/StudentHandbookList.php`     | `StudentHandbookList` | `Component` |

## Support

| File                           | Class              | Purpose                                |
| ------------------------------ | ------------------ | -------------------------------------- |
| `Services/DocumentRenderer.php` | `DocumentRenderer` | Renders document templates to PDF/HTML |

---

## Handbook Submodule

| Kind      | File                                           | Class                     | Extends / Implements    |
| --------- | ---------------------------------------------- | ------------------------- | ----------------------- |
| Action    | `Handbook/Actions/CreateHandbookAction.php`    | `CreateHandbookAction`    | `BaseCommandAction`     |
| Action    | `Handbook/Actions/UpdateHandbookAction.php`    | `UpdateHandbookAction`    | `BaseCommandAction`     |
| Action    | `Handbook/Actions/DeleteHandbookAction.php`    | `DeleteHandbookAction`    | `BaseCommandAction`     |
| Action    | `Handbook/Actions/AcknowledgeHandbookAction.php` | `AcknowledgeHandbookAction` | `BaseCommandAction`   |
| Data      | `Handbook/Data/HandbookData.php`               | `HandbookData`            | `BaseData`              |
| Entity    | `Handbook/Entities/HandbookEntity.php`         | `HandbookEntity`          | `BaseEntity`            |
| Enum      | `Handbook/Enums/HandbookAudience.php`          | `HandbookAudience`        | `LabelEnum` (all, student, teacher, supervisor) |
| Event     | `Handbook/Events/HandbookCreated.php`          | `HandbookCreated`         | `BaseEvent`             |
| Event     | `Handbook/Events/HandbookUpdated.php`          | `HandbookUpdated`         | `BaseEvent`             |
| Event     | `Handbook/Events/HandbookDeleted.php`          | `HandbookDeleted`         | `BaseEvent`             |
| Listener  | `Handbook/Listeners/ClearHandbookCache.php`    | `ClearHandbookCache`      | —                       |
| Form      | `Handbook/Livewire/Forms/HandbookForm.php`     | `HandbookForm`            | —                       |

Handbooks are stored in the shared `documents` table (category `handbook`); no separate
migration or factory.

---

## Jobs

| File                          | Class                 | Queue       | Purpose                                    |
| ----------------------------- | --------------------- | ----------- | ------------------------------------------ |
| `Jobs/GenerateDocumentJob.php` | `GenerateDocumentJob` | `documents` | Async PDF generation via `GenerateDocumentAction` (7H5D6 FR-GW1, ZT6VS) |

---

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
- **Business Logic**: `app/Document/`
- **Routing**: `routes/web/document.php`
- **Views**: `resources/views/document/`
- **Testing**: `tests/Document/`
- **Dependencies**: Core, User

_For overview and business context, see [document.md](document.md)._
