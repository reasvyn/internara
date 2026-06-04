# Document — Technical Reference

> Last updated: 2026-06-04
> Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Document** domain.

---

## Overview

Manages official document templates and generation for institutional correspondence — permits, letters, certificates of completion, applications, and reports (surat menyurat)

### Domain Statistics
- **Actions**: 4 business logic operations
- **Models**: 1 data entity
- **Livewire Components**: 2 UI components
- **Policies**: 1 authorization rule
- **Aggregates**: 1 domain aggregate

### Aggregates
- `OfficialDocument`

---

## Dependency Graph

This domain depends on:
- **Core**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Aggregates/OfficialDocument/Actions/DeleteReportAction.php` | `DeleteReportAction` | `BaseAction` |
| `Aggregates/OfficialDocument/Actions/GenerateReportAction.php` | `GenerateReportAction` | `BaseAction` |
| `Aggregates/OfficialDocument/Actions/RenderDocumentAction.php` | `RenderDocumentAction` | `BaseAction` |
| `Aggregates/OfficialDocument/Actions/SaveDocumentTemplateAction.php` | `SaveDocumentTemplateAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Models/Document.php` | `Document` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Aggregates/OfficialDocument/Livewire/ReportsManager.php` | `ReportsManager` | `Component` |
| `Aggregates/OfficialDocument/Livewire/TemplateManager.php` | `TemplateManager` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Policies/DocumentPolicy.php` | `DocumentPolicy` |

---

## HTTP Controllers

| File | Controller |
|---|---|
| `Aggregates/OfficialDocument/Http/Controllers/DocumentRenderController.php` | `DocumentRenderController` |

---

## File Organization

```
app/Domain/Document/
├── Aggregates/           ← Aggregate roots
│   └── OfficialDocument/
│       ├── Actions/
│       ├── Http/
│       │   ├── Controllers/
│       │   └── Requests/
│       └── Livewire/
├── Enums/                ← Document category enum
├── Models/               ← Cross-aggregate model
├── Policies/             ← Cross-aggregate policy
└── Support/              ← DocumentRenderer (DomPDF)
```

---

*For overview and business context, see [document.md](document.md)*
