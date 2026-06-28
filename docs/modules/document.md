# Document — Templates, Handbooks & Rendering

> **Last updated:** 2026-06-16
> **Changes:** sync — initial metadata sync with new format

## Description
Official correspondence template management, PDF letter rendering, policy handbook storage, and compliance acknowledgement tracking.


## Purpose & Boundary

Document manages the school's official document repository — correspondence templates (permits, parent consent letters) rendered via Blade + DomPDF. Handbooks are managed by the Guidance/Handbook module and share the same unified `documents` table distinguished by `type = 'handbook'`. All acknowledgements are recorded in the `activity_log` table for compliance audit.

Out of scope: certificate generation (Certification), final grade card (Reports), daily logbook entries (Journals).

## Submodules

### OfficialDocument
Correspondence template management: create, edit, and render templates using Blade syntax with DomPDF compilation. Supports variable substitution for student name, program details, dates, and school information. Generated PDFs can be downloaded individually. Templates are versioned — updates create new versions while preserving old ones for historical accuracy.

### Handbook (Moved to Guidance/Handbook)

Handbooks are now managed by the **Guidance/Handbook** module (`app/Guidance/Handbook/`). They use the
same `documents` table with `type = 'handbook'` and the same `activity_log` for acknowledgment
tracking. See [Guidance module](guidance.md) for the full Handbook reference.

## Key Concepts

### Unified Document Table

Templates and handbooks share a single `documents` table distinguished by a `type` discriminator
(`template`, `handbook`, `policy`, `guideline`). This prevents table sprawl while enabling document
type-specific behavior (rendering for templates, acknowledgement for handbooks). Each document
stores metadata as JSON.

### PDF Rendering Pipeline

Templates are rendered using Laravel Blade with DomPDF (`DocumentRenderer`). The pipeline: load template → substitute variables → compile Blade → render PDF. Variables resolve from the registration context (student name, program, dates) and system settings (school name, principal name). Rendering failures throw a `RenderException` logged via SmartLogger.

### Policy Acknowledgement Tracking

Compliance-driven mandatory read-and-sign workflow for school policies. Key rules:
- Each policy version can be acknowledged once per user.
- Handbook updates increment the version, requiring a new acknowledgement.
- Acknowledgements are recorded in `activity_log` (append-only), not a separate table.
- IP address is captured for compliance audit.

## Dependencies

- Core (base classes, SmartLogger)
- Settings (school metadata for variable substitution)
- Enrollment (student context for personalized documents)

## Used By

- Program (required document template references)
- Enrollment (document upload verification references)
