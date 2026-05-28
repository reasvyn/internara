# Document Domain

## Purpose

Document manages templates and renders output files — PDF generation for reports,
certificates, and other generated documents.

---

## Models

| Model | Key Fields |
|---|---|
| `Document` | name, slug, category, content (template body), is_active |

## Actions

| Action | Type |
|---|---|
| `SaveDocumentTemplateAction` | Command |
| `RenderDocumentAction` | Process |
| `GenerateReportAction` | Process |
| `DeleteReportAction` | Command |

## Where to Find It

- `app/Domain/Document/Models/Document.php`
- `app/Domain/Document/Actions/`
