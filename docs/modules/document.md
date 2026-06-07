# Document — Documentation Overview

> Last updated: 2026-06-06  
> Changes: Integrated school policy handbooks under the Document module's scope (unifying
> `handbooks` and `documents` tables). Policy acknowledgements are now tracked via `activity_log`
> instead of a dedicated `document_acknowledgements` table.

Manages official correspondence templates, PDF letter rendering, policy handbooks, and compliance
acknowledgement tracking.

For complete technical reference including API, models, actions, and components, see
[document-reference.md](document-reference.md).

---

## Key Principles

- **Unified Document Repository** — Holds both downloadable PDF letter templates (permits, parent
  consent letters) and text-based guidebooks/policies (handbooks) under a single table structure
  distinguished by `type` (`template` | `policy` | `guideline`).
- **PDF Generation Pipeline** — Renders templates dynamically using Blade and DomPDF
  (`DocumentRenderer`) for official correspondence.
- **Compliance Tracking via Activity Log** — Policies (handbooks) require mandatory user sign-offs.
  Acknowledgement events are recorded in the `activity_log` table with event `acknowledged`,
  capturing the user ID, timestamp, and IP address for compliance audits.

---

## Context Boundary

The **Document** module:

- Owns the `Document` model.
- Provides required file templates (e.g. parent consent forms) consumed by **Enrollment** and
  **Program**.
- Tracks policy acknowledgements (handbooks) through activity logging — students must sign policies
  before starting operations in **Journals**.

---

## Module Rules

- **Access Restrictions:** Only admins can create or edit templates and policies. Students can read
  active policies and download assigned templates.
- **Handbook Acknowledgement:** A student can only acknowledge each policy version once. Updates to
  a handbook template increment its version, requiring a new acknowledgement entry in
  `activity_log`.
- **IP Auditing:** All policy acknowledgement events must record the user's IP address in the
  activity log properties.

---

## Submodules

- **OfficialDocument**: Correspondence template management, PDF generation, and download endpoints
  (letters, permits).
- **Handbook**: Text-based school policies requiring role-targeted visibility filters (`student` |
  `mentor` | `all`) and version-controlled acknowledgements.

---

## Error Handling & Failure Modes

- **Deleting Active Templates:** Deleting templates referenced by active program requirements is
  blocked with a `RejectedException`.
- **PDF Compilation Failure:** Missing assets or incorrect syntax throws a `RenderException`, which
  is logged via `SmartLogger`.
- **Duplicate Sign-Offs:** Attempting to record a duplicate acknowledgement for the same document
  version is handled by application logic; the `activity_log` table is append-only so duplicates are
  filtered at the query level.

---

## Quick References

### Actions & Business Logic

- **4** implemented actions (2 planned):
    - `SaveDocumentTemplateAction` — Creates or updates templates/policies.
    - `RenderDocumentAction` — Compiles documents to PDF.
    - `GenerateReportAction` — Correspondence logs.
    - `DeleteReportAction` — Correspondence logs.
    > **Note:** `AcknowledgeDocumentAction` and `PruneAcknowledgementsAction` are planned but not yet implemented. Acknowledgements are currently handled inline via `activity()` helper.

### Data & Persistence

- **1** model: `Document`.
- UUID PKs. `Document` uses JSON metadata. Policy acknowledgements stored in `activity_log` with
  `event = 'acknowledged'`.

### User Interface

- **2** Livewire components:
    - `TemplateManager` — Manage templates and policy guides.
    - `ReportsManager` — Manage generated letters.

### Authorization

- **1** policy: `DocumentPolicy`.

---

For complete technical reference, see [document-reference.md](document-reference.md).
