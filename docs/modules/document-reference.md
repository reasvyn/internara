# Document — Technical Reference

> Last updated: 2026-06-06  
> Changes: Added handbook policies and document acknowledgements models, actions, and UI references.

Detailed structural and implementation reference for the **Document** module.

---

## Overview

Manages correspondence templates, policy handbooks, and compliance acknowledgements.

### Module Statistics
- **Actions**: 6 business logic operations
- **Models**: 2 data entities (`Document`, `DocumentAcknowledgement`)
- **Livewire Components**: 3 UI components
- **Policies**: 2 authorization rules
- **Submodules**: 2 module submodules

### Submodules
- `OfficialDocument`: File templates and PDF compiler endpoints.
- `Handbook`: Policy guides and user acknowledgements tracking.

---

## Dependency Graph

This module depends on:
- **Core** (base classes and DomPDF wrapper)
- **User** (recipient user records)

---

## Actions

| File | Class | Extends |
|---|---|---|
| `OfficialDocument/Actions/SaveDocumentTemplateAction.php` | `SaveDocumentTemplateAction` | `BaseAction` |
| `OfficialDocument/Actions/RenderDocumentAction.php` | `RenderDocumentAction` | `BaseAction` |
| `OfficialDocument/Actions/GenerateReportAction.php` | `GenerateReportAction` | `BaseAction` |
| `OfficialDocument/Actions/DeleteReportAction.php` | `DeleteReportAction` | `BaseAction` |
| `Handbook/Actions/AcknowledgeDocumentAction.php` | `AcknowledgeDocumentAction` | `BaseAction` |
| `Handbook/Actions/PruneAcknowledgementsAction.php` | `PruneAcknowledgementsAction` | `BaseAction` |

---

## Models

| File | Class | Extends |
|---|---|---|
| `Models/Document.php` | `Document` | `BaseModel` |
| `Handbook/Models/DocumentAcknowledgement.php` | `DocumentAcknowledgement` | `BaseModel` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `OfficialDocument/Livewire/TemplateManager.php` | `TemplateManager` | `Component` |
| `OfficialDocument/Livewire/ReportsManager.php` | `ReportsManager` | `Component` |
| `Handbook/Livewire/DocumentAcknowledgementTracker.php` | `DocumentAcknowledgementTracker` | `Component` |

---

## Authorization Policies

| File | Policy | Extends |
|---|---|---|
| `Policies/DocumentPolicy.php` | `DocumentPolicy` | `BasePolicy` |
| `Handbook/Policies/DocumentAcknowledgementPolicy.php` | `DocumentAcknowledgementPolicy` | `BasePolicy` |

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
│       ├── Actions/
│       ├── Models/
│       ├── Policies/
│       └── Livewire/
├── Enums/                ← DocumentCategory enum
├── Models/               ← Document model
├── Policies/             ← DocumentPolicy
└── Support/              ← DocumentRenderer (DomPDF wrapper)
```

---

*For overview and business context, see [document.md](document.md)*
