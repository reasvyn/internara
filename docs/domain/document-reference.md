# Document — API Reference
> Last updated: 2026-05-23
> Changes: fix: complete system initialization overhaul — security, middleware, recovery, form objects, docs


Total: 12 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Document/Actions/DeleteReportAction.php` | `DeleteReportAction` | `BaseAction` | Deletes a generated report document |
| `Document/Actions/GenerateReportAction.php` | `GenerateReportAction` | `BaseAction` | Generates a report document and saves to storage |
| `Document/Actions/RenderDocumentAction.php` | `RenderDocumentAction` | `BaseAction` | Renders a document using Blade + DomPDF |
| `Document/Actions/SaveDocumentTemplateAction.php` | `SaveDocumentTemplateAction` | `BaseAction` | Saves or updates a document template |

## Controllers

| File | Class | Extends | Description |
|---|---|---|---|
| `Document/Http/Controllers/DocumentRenderController.php` | `DocumentRenderController` | `BaseController` | HTTP controller for rendering/downloading documents |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Document/Enums/DocumentCategory.php` | `DocumentCategory` | `LabelEnum` | Document category classification |

## Form Requests

| File | Class | Extends | Description |
|---|---|---|---|
| `Document/Http/Requests/GenerateReportRequest.php` | `GenerateReportRequest` | `FormRequest` | Validation for report generation |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Document/Livewire/ReportsManager.php` | `ReportsManager` | `Component` | Paginated list of generated reports |
| `Document/Livewire/TemplateManager.php` | `TemplateManager` | `Component` | Management interface for document templates |

## Models

| File | Class | Extends/Implements | Description |
|---|---|---|---|
| `Document/Models/Document.php` | `Document` | `BaseModel`, `HasMedia` | Eloquent model for documents with media library |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Document/Policies/DocumentPolicy.php` | `DocumentPolicy` | `BasePolicy` | Authorization for document operations |

## Support

| File | Class | Description |
|---|---|---|
| `Document/Support/DocumentRenderer.php` | `DocumentRenderer` | Renders Blade templates to PDF via DomPDF |
