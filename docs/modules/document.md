# Document — Documentation Overview

> Last updated: 2026-06-05
> Changes: Added Error Handling & Failure Modes section

Manages official document templates and generation for institutional correspondence — permits, letters, certificates of completion, applications, and reports (surat menyurat)

For complete technical reference including API, models, actions, and components, see [document-reference.md](document-reference.md).

---

## Key Principles

- Document templates define reusable layouts for official correspondence
- Documents are rendered to PDF via DomPDF for distribution and printing
- Only administrators can create, update, and delete document templates
- Document categories organize templates by purpose (permits, letters, reports, etc.)

---

## Context Boundary

Owns official document templates, rendering, and generation. Separated from Certification (which handles student completion certificates and credentials). Uses User module for authorization context and Core for base services.

---

## Module Rules

- Only admin roles can create, update, and delete document templates
- Documents support PDF rendering via DomPDF (`DocumentRenderer`)
- Document categories are managed via the `DocumentCategory` enum
- All document modifications are audit-logged

---

## Submodules

- **OfficialDocument**: Template management, document generation, and PDF rendering for official correspondence

---

## Cross-Submodule Components

- **Models/Document.php**: Eloquent model for document persistence
- **Enums/DocumentCategory.php**: String-backed enum categorizing document types
- **Policies/DocumentPolicy.php**: Authorization gates for document operations
- **Support/DocumentRenderer.php**: DomPDF rendering service for document output

---

## Error Handling & Failure Modes

- **Template deletion with active documents**: Deleting a document template that has rendered documents is blocked with a `RejectedException`. Archive documents first.
- **PDF rendering failure**: If DomPDF encounters an error (missing font, invalid Blade syntax, memory limit), the render throws a `RenderException` with details logged via `SmartLogger`.
- **Unauthorized template access**: Non-admin roles attempting to create, update, or delete templates receive a 403 at the policy layer.
- **Category mismatch**: Assigning a document to an invalid or deprecated `DocumentCategory` enum value throws a `ValidationFailedException`.

---

## Quick References

### Actions & Business Logic
- **4** actions across all submodules
- Business logic operations for document module

### Data & Persistence
- **1** model managing core data
- Eloquent relationships and queries

### User Interface
- **2** Livewire components for real-time interaction
- Views in `resources/views/document/`

### Authorization
- **1** authorization policy
- Role-based access control per resource

---

For complete technical reference, see [document-reference.md](document-reference.md).
