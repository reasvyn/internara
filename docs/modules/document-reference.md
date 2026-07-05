# Document — Technical Reference

> **Last updated:** 2026-07-05 **Changes:** sync — fix base class extends: BaseAction →
> BaseCommandAction/BaseReadAction

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

## Support

| File                           | Class              | Purpose                                |
| ------------------------------ | ------------------ | -------------------------------------- |
| `Support/DocumentRenderer.php` | `DocumentRenderer` | Renders document templates to PDF/HTML |

---

## Routes

File: `routes/web/document.php` Naming pattern: `document.{resource}.{action}`

## Views

Views are located in `resources/views/document/`. See [UI/UX](../foundation/ui-ux.md) for the design
system.

## Tests

Tests are located in `tests/{Feature,Unit}/Document/`. See [Testing](../infrastructure/testing.md)
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

---

## Architectural Integration

- **Submodules**: `OfficialDocument`
- **Business Logic**: `app/Document/`
- **Routing**: `routes/web/document.php`
- **Views**: `resources/views/document/`
- **Testing**: `tests/Feature/Document/`, `tests/Unit/Document/`
- **Dependencies**: Core, User

_For overview and business context, see [document.md](document.md)._
