# Document — Technical Reference

> Last updated: 2026-06-04
> Changes: Converted Status metadata to Changes format

Detailed structural and implementation reference for the **Document** module.

---

## Overview

Manages official document templates and generation for institutional correspondence — permits, letters, certificates of completion, applications, and reports (surat menyurat)

### Module Statistics
- **Actions**: 4 business logic operations
- **Models**: 1 data entity
- **Livewire Components**: 2 UI components
- **Policies**: 1 authorization rule
- **Submodules**: 1 module submodule

### Submodules
- `OfficialDocument`

---

## Dependency Graph

This module depends on:
- **Core**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `OfficialDocument/Actions/DeleteReportAction.php` | `DeleteReportAction` | `BaseAction` |
| `OfficialDocument/Actions/GenerateReportAction.php` | `GenerateReportAction` | `BaseAction` |
| `OfficialDocument/Actions/RenderDocumentAction.php` | `RenderDocumentAction` | `BaseAction` |
| `OfficialDocument/Actions/SaveDocumentTemplateAction.php` | `SaveDocumentTemplateAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Models/Document.php` | `Document` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `OfficialDocument/Livewire/ReportsManager.php` | `ReportsManager` | `Component` |
| `OfficialDocument/Livewire/TemplateManager.php` | `TemplateManager` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Policies/DocumentPolicy.php` | `DocumentPolicy` |

---

## HTTP Controllers

| File | Controller |
|---|---|
| `OfficialDocument/Http/Controllers/DocumentRenderController.php` | `DocumentRenderController` |

---

## File Organization

```
app/Document/
├──            ← Submodule roots
│   └── OfficialDocument/
│       ├── Actions/
│       ├── Http/
│       │   ├── Controllers/
│       │   └── Requests/
│       └── Livewire/
├── Enums/                ← Document category enum
├── Models/               ← Cross-submodule model
├── Policies/             ← Cross-submodule policy
└── Support/              ← DocumentRenderer (DomPDF)
```

---

*For overview and business context, see [document.md](document.md)*
