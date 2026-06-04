# Document — Documentation Overview

> Last updated: 2026-06-04
> Changes: Converted Status metadata to Changes format

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

Owns official document templates, rendering, and generation. Separated from Certification (which handles student completion certificates and credentials). Uses User domain for authorization context and Core for base services.

---

## Domain Rules

- Only admin roles can create, update, and delete document templates
- Documents support PDF rendering via DomPDF (`DocumentRenderer`)
- Document categories are managed via the `DocumentCategory` enum
- All document modifications are audit-logged

---

## Aggregates

- **OfficialDocument**: Template management, document generation, and PDF rendering for official correspondence

---

## Cross-Aggregate Components

- **Models/Document.php**: Eloquent model for document persistence
- **Enums/DocumentCategory.php**: String-backed enum categorizing document types
- **Policies/DocumentPolicy.php**: Authorization gates for document operations
- **Support/DocumentRenderer.php**: DomPDF rendering service for document output

---

## Quick References

### Actions & Business Logic
- **4** actions across all aggregates
- Business logic operations for document domain

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
