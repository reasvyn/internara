# Document — Documentation Overview

> Last updated: 2026-06-06  
> Changes: Integrated school policy handbooks and compliance acknowledgements under the Document module's scope (unifying `handbooks` and `documents` tables).

Manages official correspondence templates, PDF letter rendering, policy handbooks, and student compliance acknowledgements.

For complete technical reference including API, models, actions, and components, see [document-reference.md](document-reference.md).

---

## Key Principles

- **Unified Document Repository** — Holds both downloadable PDF letter templates (permits, parent consent letters) and text-based guidebooks/policies (handbooks) under a single table structure distinguished by `type` (`template` | `policy` | `guideline`).
- **PDF Generation Pipeline** — Renders templates dynamically using Blade and DomPDF (`DocumentRenderer`) for official correspondence (surat menyurat).
- **Compliance Tracking** — Policies (handbooks) require mandatory user sign-offs. Acknowledgement logs are immutable, recording the user ID, timestamp, and IP address for compliance audits.

---

## Context Boundary

The **Document** module:
- Owns `Document` and `DocumentAcknowledgement` models.
- Provides required file templates (e.g. parent consent forms) consumed by **Enrollment** and **Program**.
- Tracks policy acknowledgements (handbooks) that students must sign before starting operations in **Journals**.

---

## Module Rules

- **Access Restrictions:** Only admins can create or edit templates and policies. Students can read active policies and download assigned templates.
- **Handbook Acknowledgement:** A student can only acknowledge each policy version once. Updates to a handbook template increment its version, requiring a new acknowledgement log.
- **IP Auditing:** All policy acknowledgements must record the user's IP address and browser fingerprint.

---

## Submodules

- **OfficialDocument**: Correspondence template management, PDF generation, and download endpoints (letters, permits).
- **Handbook**: Text-based school policies requiring role-targeted visibility filters (`student` | `mentor` | `all`) and version-controlled acknowledgements.

---

## Error Handling & Failure Modes

- **Deleting Active Templates:** Deleting templates referenced by active program requirements is blocked with a `RejectedException`.
- **PDF Compilation Failure:** Missing assets or incorrect syntax throws a `RenderException`, which is logged via `SmartLogger`.
- **Duplicate Sign-Offs:** Attempting to record a duplicate acknowledgement for the same document version is ignored.

---

## Quick References

### Actions & Business Logic
- **6** actions:
  - `SaveDocumentTemplateAction` — Creates or updates templates/policies.
  - `RenderDocumentAction` — Compiles documents to PDF.
  - `AcknowledgeDocumentAction` — Records policy sign-off.
  - `GenerateReportAction` / `DeleteReportAction` — Correspondence logs.
  - `PruneAcknowledgementsAction` — Maintenance pruning.

### Data & Persistence
- **2** models: `Document`, `DocumentAcknowledgement`.
- UUID PKs. `Document` uses JSON metadata; `DocumentAcknowledgement` is append-only.

### User Interface
- **3** Livewire components:
  - `TemplateManager` — Manage templates and policy guides.
  - `ReportsManager` — Manage generated letters.
  - `DocumentAcknowledgementTracker` — View student sign-off compliance.

### Authorization
- **2** policies: `DocumentPolicy`, `DocumentAcknowledgementPolicy`.

---

For complete technical reference, see [document-reference.md](document-reference.md).
