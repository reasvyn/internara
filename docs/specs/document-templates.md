# Document Templates — Template Management, PDF Rendering & Report Generation

> **Last updated:** 2026-07-22 **Changes:** feat — split from document.md; template infrastructure:
> document template CRUD, DocumentRenderer service, 4 admin-generated report types

## Description

Specification of the document template infrastructure: unified document repository for official
templates (permits, letters, consent forms) rendered via Blade + DomPDF, the `DocumentRenderer`
service for HTML/PDF compilation, and four admin-generated report types (internship completion,
student performance, company participation, mentor evaluation).

See also: [handbooks.md](handbooks.md) — handbook CRUD, audience targeting, student acknowledgment
tracking, and registration-document verification.

---

## 1. Problem Statements

### PS-1 — Standardized Document Templates

Schools produce dozens of official documents — permits, parent consent letters, internship
applications — each following slightly different formats. Without centralized templates, teachers
copy-paste from old documents, leading to inconsistent formatting, outdated letterheads, and
version drift across departments. A template repository with versioning ensures every generated
document uses the latest approved format.

### PS-2 — Dynamic PDF Rendering From Variable-Substituted Templates

Templates contain placeholders for student names, program dates, school metadata, and company
details. Manually filling these for each student is tedious and error-prone. A rendering pipeline
that resolves variables from the registration context and system settings, then compiles to PDF
via DomPDF, eliminates manual effort and ensures accuracy.

### PS-3 — Report Generation for Four Standard Report Types

Admins need structured reports for internship completion, student performance, company participation,
and mentor evaluation. Without a dedicated generation pipeline, admins would export raw data or
manually compile reports from multiple sources.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide a unified document repository with type-based discrimination (template, handbook, policy) |
| G2  | Render document templates to HTML and PDF with variable substitution from registration and settings context |
| G3  | Generate four report types: internship completion, student performance, company participation, mentor evaluation |
| G4  | Enforce role-based access control: admins manage templates, teachers/students view active documents |
| G5  | Version documents to preserve historical accuracy while allowing updates |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Certificate generation — belongs in the Certification module |
| NG2  | Final grade card rendering — belongs in the Reports module |
| NG3  | Real-time collaborative document editing (single-admin editing) |
| NG4  | Workflow/approval chains for document publishing (direct publish on save) |
| NG5  | External document storage (S3, GCS) — local disk only at `generated-documents/` |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Manages Document Templates

**Actor:** Admin
**Preconditions:** Admin is authenticated with `admin` or `super_admin` role
**Flow:**
1. Admin navigates to template management (accessible via admin routes)
2. `TemplateManager` displays paginated list of templates filtered by `type = 'template'`
3. Admin clicks "Add Template", fills in `title`, `type` (DocumentCategory), `content` (Blade markup), `version`
4. Calls `SaveDocumentTemplateAction::execute(array $data)`
5. Template created with `created_by = auth()->id()`, `slug` auto-generated, `is_active = true`
6. Admin may upload a file attachment via Spatie MediaLibrary (collection: `file`)
7. Admin may edit or deactivate existing templates
**Postconditions:** Template exists in `documents` table with `type = 'template'`, is active and renderable

### UC-2 — Admin Generates a Report

**Actor:** Admin
**Preconditions:** Admin is authenticated with `admin` or `super_admin` role; target document template and registration exist
**Flow:**
1. Admin navigates to `admin/reports`
2. `ReportsManager` displays 4 report type cards: internship completion, student performance, company participation, mentor evaluation
3. Admin selects a report type, picks a document template, and selects a registration
4. Submits via `GenerateReportRequest` (validates `document_id`, `registration_id`, optional `options` array)
5. `GenerateReportAction::execute(Document $template, object $target)` invokes `DocumentRenderer::renderPdf()`
6. Generated PDF is stored to `generated-documents/` via `DocumentRenderer::storePdf()`
7. New Document record created with `type = 'report'`, linked metadata, and `is_active = true`
**Postconditions:** Generated report PDF exists on disk and as a Document record; admin can download via `DocumentRenderController`

---

## 4. Functional Requirements

### Template Management

| ID   | Requirement |
| ---- | ----------- |
| FR-TM1 | `TemplateManager` must be a Livewire component providing paginated CRUD for document templates |
| FR-TM2 | `SaveDocumentTemplateAction` must accept an `array $data` with at least `title`, `type`, `content`, `version` and return `Document` |
| FR-TM3 | `Document` model must use `#[Fillable]` attribute with `type`, `slug`, `title`, `content`, `file_path`, `version`, `is_active`, `metadata`, `created_by` |
| FR-TM4 | `Document.type` must default to `'template'` and be indexed; composite index on `(type, is_active)` must exist |
| FR-TM5 | `Document.slug` must be unique and auto-generated from `title` |
| FR-TM6 | `Document.version` must be cast to `integer` and default to `1` |
| FR-TM7 | `Document.is_active` must be cast to `boolean` and default to `true`, indexed |
| FR-TM8 | `Document.metadata` must be cast to `json` and nullable |
| FR-TM9 | `DocumentCategory` enum must implement `LabelEnum` with cases: APPLICATION, PERMIT, CERTIFICATE, REPORT, LETTER, POLICY, HANDBOOK |
| FR-TM10 | `DocumentPolicy` must restrict create/update/delete to admin roles; view to admin+active document; viewAny to super_admin/admin/teacher/student |
| FR-TM11 | `Document.createdBy()` must be a BelongsTo relation to User with `nullOnDelete` |
| FR-TM12 | Document must support Spatie MediaLibrary attachments: `file` (single) and `handbook_file` (single) collections |

### PDF Rendering

| ID   | Requirement |
| ---- | ----------- |
| FR-PR1 | `DocumentRenderer` must be a `final readonly` service class injectable via constructor |
| FR-PR2 | `DocumentRenderer::renderHtml(Document, object): string` must resolve Blade template variables from the provided context object |
| FR-PR3 | `DocumentRenderer::renderPdf(Document, object): string` must compile resolved HTML to PDF via DomPDF |
| FR-PR4 | `DocumentRenderer::storePdf(Document, object, ?suffix): string` must render PDF, store to `generated-documents/` disk, and return stored file path |
| FR-PR5 | Variable resolution must include registration context (student name, program, dates) and system settings (school name, principal name) |
| FR-PR6 | `RenderDocumentAction::execute(Document, Registration): Document` must invoke `DocumentRenderer` and return a new Document record with stored PDF |
| FR-PR7 | `RenderDocumentAction::download(Document): string` must return raw PDF content string for browser download |
| FR-PR8 | `DocumentRenderController::show(Document, Registration)` must render PDF on-the-fly and return inline download |
| FR-PR9 | `DocumentRenderController::store(Document, Registration, RenderDocumentAction)` must render, store, and redirect |

### Report Generation

| ID   | Requirement |
| ---- | ----------- |
| FR-RG1 | `ReportsManager` must be a Livewire component accessible at `GET /admin/reports` with `auth` and `role:admin` middleware |
| FR-RG2 | `ReportsManager` must support 4 report types: `internship_completion`, `student_performance`, `company_participation`, `mentor_evaluation` |
| FR-RG3 | `GenerateReportAction::execute(Document, object $target): Document` must accept a document template and context object, invoke `DocumentRenderer`, and return a new Document |
| FR-RG4 | `GenerateReportRequest` must validate: `document_id` (uuid, exists in documents), `registration_id` (uuid, exists in registrations), `options` (array, optional) |
| FR-RG5 | `GenerateReportAction` must inject `DocumentRenderer` via constructor |
| FR-RG6 | Generated reports must be created as Document records with `type = 'report'` and `is_active = true` |
| FR-RG7 | `ReportsManager` must support report deletion via `DeleteReportAction::execute(Document): void` |
| FR-RG8 | `GenerateReportAction` must create a Document record linked to the source template via `metadata['source_template_id']` |
| FR-RG9 | `GenerateReportAction` must store the generated PDF via `DocumentRenderer::storePdf()` |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-S1 | All document mutations must be authorized via `DocumentPolicy` — no bypass allowed |
| NFR-S2 | Template content rendered via Blade must be sanitized before DomPDF compilation to prevent XSS |
| NFR-S3 | Generated PDFs stored on disk must not expose absolute server paths in responses |
| NFR-P1 | On-the-fly PDF rendering (`DocumentRenderController::show`) must complete in < 5s for documents under 50 pages |
| NFR-P2 | `TemplateManager` page load with pagination must complete in < 1s |
| NFR-R1 | `DocumentRenderer::storePdf()` must write atomically — partial writes must not leave orphan files |
| NFR-U1 | `ReportsManager` must visually present 4 report types as distinct selectable cards |
| NFR-M1 | All PHP files must declare `strict_types=1` and follow PSR-12 |

---

## 6. API / Data Contracts

### Document Model

```
App\Document\Models\Document
  Table: documents (UUID PK)
  Fillable: type, slug, title, content, file_path, version, is_active, metadata, created_by
  Casts: is_active → boolean, version → integer, metadata → json
  Relations: createdBy() BelongsTo User (nullOnDelete)
  Media: file (single), handbook_file (single)
  Scopes: active(), ofType()
  Bridge: asHandbook() → HandbookEntity
  Factory: DocumentFactory
```

### DocumentCategory Enum

```
App\Document\Enums\DocumentCategory: string
  Implements: LabelEnum
  Cases: APPLICATION='application', PERMIT='permit', CERTIFICATE='certificate', REPORT='report', LETTER='letter', POLICY='policy', HANDBOOK='handbook'
```

### DocumentRenderer Service

```
App\Document\Services\DocumentRenderer (final readonly)
  Methods:
    renderHtml(Document $document, object $context): string
    renderPdf(Document $document, object $context): string
    storePdf(Document $document, object $context, ?string $suffix = null): string
  Disk: generated-documents/
```

### Actions

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| `SaveDocumentTemplateAction` | `BaseCommandAction` | `array $data` | `Document` |
| `GenerateDocumentAction` | `BaseCommandAction` | `Document $template, object $target` | `Document` |
| `GenerateReportAction` | `BaseCommandAction` | `Document $template, object $target` | `Document` |
| `RenderDocumentAction` | `BaseCommandAction` | `Document $document, Registration $registration` | `Document` |
| `RenderDocumentAction::download` | static | `Document $document` | `string` |
| `DeleteReportAction` | `BaseCommandAction` | `Document $document` | `void` |

### Policies

| Policy | Abilities |
| ------ | --------- |
| `DocumentPolicy` | viewAny: super_admin/admin/teacher/student, view: admin/active-document, create: admin, update: admin, delete: admin |

### Routes

| Route | Component | Middleware |
| ----- | --------- | ---------- |
| `GET /admin/reports` | `ReportsManager` (Livewire) | `auth`, `role:admin` |
| `GET /admin/documents/{document}/render/{registration}` | `DocumentRenderController@show` | `auth`, `role:admin` |
| `GET /admin/documents/{document}/render/{registration}/save` | `DocumentRenderController@store` | `auth`, `role:admin` |

### Form Requests

| Request | Rules |
| ------- | ----- |
| `GenerateReportRequest` | `document_id`: uuid, exists:documents,id. `registration_id`: uuid, exists:registrations,id. `options`: array, optional |

### Database Schema

```
documents:
  id: uuid (PK)
  type: string (default 'template', indexed)
  slug: string (unique)
  title: string
  content: text (nullable)
  file_path: string (nullable)
  version: integer (default 1)
  is_active: boolean (default true, indexed)
  metadata: json (nullable)
  created_by: foreignUuid → users.id (nullOnDelete, nullable)
  timestamps
  Indexes: (type, is_active)
```

---

## 7. Design Decisions

### DD-1 — DocumentRenderer as Dedicated Service

**Decision:** PDF rendering logic lives in a standalone `DocumentRenderer` service class rather than being inlined in each Action that generates documents.
**Rationale:** Multiple Actions (`GenerateDocumentAction`, `GenerateReportAction`, `RenderDocumentAction`) need the same rendering pipeline. Extracting it to a service avoids code duplication and provides a single place to change the rendering engine (e.g., switching from DomPDF to wkhtmltopdf). Constructor injection (C2 compliance) keeps Actions testable with mock renderers.
**Trade-off:** Extra class to maintain. Rejected alternative: inline rendering in each Action (violates DRY; inconsistent rendering behavior across document types).

### DD-2 — Four Hardcoded Report Types

**Decision:** `ReportsManager` supports exactly 4 hardcoded report types: `internship_completion`, `student_performance`, `company_participation`, `mentor_evaluation`. New report types require a code change.
**Rationale:** Each report type has specific variable resolution logic (different context data, different section structures). A dynamic template system would require a DSL or formula engine that significantly increases complexity. Hardcoding ensures each report type is predictable, testable, and maintainable. The `GenerateReportAction` already accepts a `Document` template, so the rendering pipeline is reusable — only the context-gathering differs per type.
**Trade-off:** Adding new report types requires a developer. Rejected alternative: dynamic report type registry with config-driven templates (too complex for 4 fixed types; premature abstraction).

---

## 8. Success Metrics

### Data Integrity

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Unauthorized template edits | 0 | `DocumentPolicy` restricts create/update/delete to admin roles |
| Orphan generated PDFs on disk | 0 | `storePdf()` writes atomically; `DeleteReportAction` cleans up |

### Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| On-the-fly PDF render (≤50 pages) | < 5s | `DocumentRenderController::show` timing |
| Template manager page load | < 1s | Paginated query + Livewire mount |

### User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Report generation feedback | Visual card selection for 4 types | `ReportsManager` UI |
| Template content preview | HTML preview before PDF render | `DocumentRenderer::renderHtml()` exposed in UI |

### Architecture Compliance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| No model mutations in Livewire (C1) | 0 violations | All mutations via Actions |
| No service locator (C2) | 0 violations | Constructor injection for `DocumentRenderer` in Actions |
| Strict types (D1) | 100% files | `declare(strict_types=1)` in all PHP files |

---

## Quick References

- `app/Document/Models/Document.php` — Document model with fillable, casts, media, scopes, bridge
- `app/Document/Policies/DocumentPolicy.php` — Role-based authorization
- `app/Document/Services/DocumentRenderer.php` — PDF/HTML rendering service
- `app/Document/Enums/DocumentCategory.php` — Document type enum (7 cases)
- `app/Document/OfficialDocument/Actions/SaveDocumentTemplateAction.php` — Template CRUD
- `app/Document/OfficialDocument/Actions/GenerateDocumentAction.php` — Document generation
- `app/Document/OfficialDocument/Actions/GenerateReportAction.php` — Report generation
- `app/Document/OfficialDocument/Actions/RenderDocumentAction.php` — On-the-fly render + store + download
- `app/Document/OfficialDocument/Actions/DeleteReportAction.php` — Report deletion
- `app/Document/OfficialDocument/Http/Controllers/DocumentRenderController.php` — Render controller
- `app/Document/OfficialDocument/Http/Requests/GenerateReportRequest.php` — Report validation
- `app/Document/OfficialDocument/Livewire/TemplateManager.php` — Template CRUD UI
- `app/Document/OfficialDocument/Livewire/ReportsManager.php` — Report type selection + generation
- `database/migrations/` — `documents` and `registration_documents` table migrations
- `routes/web/document.php` — Route definitions
- `docs/modules/document.md` — Module conceptual documentation
- **Related spec:** [handbooks.md](handbooks.md) — handbook lifecycle, acknowledgment tracking, registration docs
