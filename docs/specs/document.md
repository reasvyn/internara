# Document — Official Document Templates, PDF Rendering, Generated Reports & Handbooks

> **Last updated:** 2026-07-22 **Changes:** feat — initial spec covering document template management,
> dynamic PDF rendering, 4 report types, handbook CRUD with audience targeting, student acknowledgment
> tracking, and registration-document verification

## Description

Complete specification of the Internara Document module: unified document repository for official
templates (permits, letters, consent forms) rendered via Blade + DomPDF, four admin-generated report
types (internship completion, student performance, company participation, mentor evaluation),
policy handbook CRUD with audience-based targeting, student acknowledgment tracking via activity
log, and registration-document verification linkage.

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

Admins need structured reports for internship completion, student performance, company participation
hardcoded report types that accept a document ID and registration context, render via the shared
`DocumentRenderer` service, and persist the generated PDF. Without a dedicated generation pipeline,
admins would export raw data or manually compile reports from multiple sources.

### PS-4 — Handbook Distribution With Audience Targeting

School policy handbooks must reach specific audiences — all staff, students only, teachers only,
or industry supervisors only. A generic file share has no audience filtering and no way to verify
receipt. The handbook sub-module provides CRUD with audience selection, version tracking, and
file upload, ensuring the right document reaches the right people.

### PS-5 — Student Acknowledgment Tracking for Compliance

Regulatory compliance requires proof that students have read and acknowledged policy handbooks.
Without structured tracking, schools rely on paper sign-offs or email confirmations that are
hard to audit. Recording acknowledgments in the append-only `activity_log` table provides an
immutable compliance trail with IP address and user agent for audit purposes.

### PS-6 — Registration-Document Verification Linkage

During enrollment, schools collect documents from students (permits, consent forms, medical
certificates). Without a linkage between registrations and uploaded documents, administrators
cannot quickly see which required documents are missing, pending, or verified. The
`registration_documents` pivot provides per-registration document status tracking with admin
notes and verification timestamps.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide a unified document repository with type-based discrimination (template, handbook, policy) |
| G2  | Render document templates to HTML and PDF with variable substitution from registration and settings context |
| G3  | Generate four report types: internship completion, student performance, company participation, mentor evaluation |
| G4  | Support handbook CRUD with audience targeting (all, student, teacher, supervisor) and file upload |
| G5  | Track student handbook acknowledgments via append-only activity log with IP/UA capture |
| G6  | Link documents to registrations with status tracking (pending, verified, rejected) and admin notes |
| G7  | Version documents to preserve historical accuracy while allowing updates |
| G8  | Enforce role-based access control: admins manage templates/handbooks, students view targeted handbooks |

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

### UC-3 — Admin Manages Handbooks

**Actor:** Admin
**Preconditions:** Admin is authenticated with `admin` or `super_admin` role
**Flow:**
1. Admin navigates to `admin/handbooks`
2. `HandbookManager` displays paginated list of handbook documents
3. Admin clicks "Add Handbook"
4. Fills in `title`, `audience` (HandbookAudience enum), optional `description`, `isActive` toggle
5. Uploads a PDF file via the form
6. `CreateHandbookAction::execute(HandbookData $data)` creates Document with `type = 'handbook'`
7. `HandbookCreated` event dispatched, `ClearHandbookCache` listener clears dashboard cache
8. Admin may update handbook (triggers `HandbookUpdated` event) or delete (triggers `HandbookDeleted` event)
**Postconditions:** Handbook exists with correct audience, file uploaded, cache cleared

### UC-4 — Student Views and Acknowledges Handbooks

**Actor:** Student
**Preconditions:** Student is authenticated with `student` role; at least one active handbook targeting their audience exists
**Flow:**
1. Student navigates to `student/handbooks`
2. `StudentHandbookList` loads computed `handbooks()` — active documents with `type = 'handbook'` where audience matches student's role
3. Student sees handbook list with title, version, description, and acknowledgment status
4. `acknowledgments()` computed property queries `activity_log` for existing `HandbookAcknowledged` entries
5. Student clicks "Acknowledge" on a handbook
6. `AcknowledgeHandbookAction::execute(Document $handbook, User $student)` records acknowledgment in `activity_log` with IP and user agent
**Postconditions:** Acknowledgment recorded in `activity_log`; handbook shows as acknowledged in student's list

### UC-5 — Admin Renders Document for a Registration

**Actor:** Admin
**Preconditions:** Admin is authenticated with `admin` or `super_admin` role; document template and registration exist
**Flow:**
1. Admin triggers render from a registration context (e.g., enrollment page)
2. `DocumentRenderController::show(Document $document, Registration $registration)` is invoked
3. `RenderDocumentAction::execute(Document, Registration)` injects registration context (student name, program, dates, school metadata)
4. `DocumentRenderer::renderHtml()` resolves Blade variables and produces HTML
5. `DocumentRenderer::renderPdf()` compiles HTML to PDF via DomPDF
6. PDF returned as inline download
7. Admin may optionally call `DocumentRenderController::store(Document, Registration, RenderDocumentAction)` to persist the generated PDF
8. `RenderDocumentAction::download(Document)` returns raw PDF string for browser download
**Postconditions:** PDF downloaded or stored; if stored, new Document record created with `file_path` set

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

### Handbook Management

| ID   | Requirement |
| ---- | ----------- |
| FR-HM1 | `HandbookManager` must be a Livewire component extending `BaseRecordManager`, accessible at `GET /admin/handbooks` with `auth` and `role:admin` middleware |
| FR-HM2 | `HandbookManager` must support CRUD operations with file upload and audience selection |
| FR-HM3 | `CreateHandbookAction::execute(HandbookData): Document` must create a Document with `type = 'handbook'` |
| FR-HM4 | `UpdateHandbookAction::execute(Document, HandbookData): Document` must update an existing handbook Document |
| FR-HM5 | `DeleteHandbookAction::execute(Document): void` must soft-delete or hard-delete a handbook Document |
| FR-HM6 | `HandbookData` DTO must be `final readonly`, extend `BaseData`, with fields: `title` (string), `audience` (HandbookAudience), `description` (?string), `isActive` (bool), `file` (?UploadedFile) |
| FR-HM7 | `HandbookAudience` enum must implement `LabelEnum` with cases: ALL, STUDENT, TEACHER, SUPERVISOR |
| FR-HM8 | `HandbookEntity` must be `final readonly`, extend `BaseEntity`, with constructor: `id`, `title`, `version`, `isActive`, `audience` (HandbookAudience), `description`, `hasFile`, `createdAt` |
| FR-HM9 | `HandbookEntity::isTargetedAt(?User): bool` must check if the handbook's audience matches the user's role |
| FR-HM10 | `HandbookEntity::isNewerThan(?Activity): bool` must compare handbook version against an acknowledgment activity |
| FR-HM11 | `HandbookEntity::isAvailable(): bool` must return true when `isActive` is true |
| FR-HM12 | `HandbookEntity::canBeDeleted(): bool` must return true only if no acknowledgments exist |
| FR-HM13 | `Document.asHandbook(): HandbookEntity` must be a bridge method returning a `HandbookEntity` snapshot |
| FR-HM14 | Handbook events must dispatch: `HandbookCreated`, `HandbookUpdated`, `HandbookDeleted` (all extend `BaseEvent`) |
| FR-HM15 | `ClearHandbookCache` listener must handle all 3 handbook events and clear dashboard cache |

### Student Handbook View

| ID   | Requirement |
| ---- | ----------- |
| FR-SHV1 | `StudentHandbookList` must be a Livewire component accessible at `GET /student/handbooks` with `auth` and `role:student` middleware |
| FR-SHV2 | `StudentHandbookList::handbooks()` computed must return active Documents with `type = 'handbook'` filtered by audience matching student's role |
| FR-SHV3 | `StudentHandbookList::acknowledgments()` computed must return student's existing acknowledgments from `activity_log` |
| FR-SHV4 | `HandbookForm` must be a Livewire Form object for handbook create/edit within `HandbookManager` |

### Registration Documents

| ID   | Requirement |
| ---- | ----------- |
| FR-RD1 | `registration_documents` pivot must link `registration_id` (FK→registrations, cascade) and `document_id` (FK→documents, cascade) |
| FR-RD2 | `registration_documents` must include `status` field defaulting to `'pending'`, indexed, with composite index on `(registration_id, status)` |
| FR-RD3 | `registration_documents` must include `admin_notes` (text, nullable), `verified_by` (FK→users, nullable), `verified_at` (timestamp, nullable) |
| FR-RD4 | `registration_documents` must enforce unique constraint on `(registration_id, document_id)` |
| FR-RD5 | Document access via `DocumentPolicy` must restrict `view` to admins or documents where `is_active = true` |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-S1 | All document mutations must be authorized via `DocumentPolicy` — no bypass allowed |
| NFR-S2 | Template content rendered via Blade must be sanitized before DomPDF compilation to prevent XSS |
| NFR-S3 | Generated PDFs stored on disk must not expose absolute server paths in responses |
| NFR-P1 | On-the-fly PDF rendering (`DocumentRenderController::show`) must complete in < 5s for documents under 50 pages |
| NFR-P2 | `TemplateManager` page load with pagination must complete in < 1s |
| NFR-P3 | `StudentHandbookList` page load must complete in < 500ms |
| NFR-P4 | Handbook acknowledgment check must query `activity_log` efficiently (indexed on `event` + `subject_id`) |
| NFR-R1 | `DocumentRenderer::storePdf()` must write atomically — partial writes must not leave orphan files |
| NFR-R2 | Handbook events must be dispatched synchronously to ensure cache is cleared before response returns |
| NFR-U1 | `ReportsManager` must visually present 4 report types as distinct selectable cards |
| NFR-U2 | `StudentHandbookList` must show clear acknowledged/not-acknowledged status per handbook |
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

### HandbookEntity

```
App\Document\Handbook\Entities\HandbookEntity extends BaseEntity (final readonly)
  Constructor: id(string), title(string), version(int), isActive(bool), audience(HandbookAudience), description(?string), hasFile(bool), createdAt(?Carbon)
  Factory: fromModel(Model)
  Methods: isTargetedAt(?User): bool, isNewerThan(?Activity): bool, isAvailable(): bool, canBeDeleted(): bool
```

### HandbookData DTO

```
App\Document\Handbook\Data\HandbookData extends BaseData (final readonly)
  Fields: title(string), audience(HandbookAudience), description(?string), isActive(bool), file(?UploadedFile)
```

### DocumentCategory Enum

```
App\Document\Enums\DocumentCategory: string
  Implements: LabelEnum
  Cases: APPLICATION='application', PERMIT='permit', CERTIFICATE='certificate', REPORT='report', LETTER='letter', POLICY='policy', HANDBOOK='handbook'
```

### HandbookAudience Enum

```
App\Document\Handbook\Enums\HandbookAudience: string
  Implements: LabelEnum
  Cases: ALL='all', STUDENT='student', TEACHER='teacher', SUPERVISOR='supervisor'
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

### OfficialDocument Actions

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| `SaveDocumentTemplateAction` | `BaseCommandAction` | `array $data` | `Document` |
| `GenerateDocumentAction` | `BaseCommandAction` | `Document $template, object $target` | `Document` |
| `GenerateReportAction` | `BaseCommandAction` | `Document $template, object $target` | `Document` |
| `RenderDocumentAction` | `BaseCommandAction` | `Document $document, Registration $registration` | `Document` |
| `RenderDocumentAction::download` | static | `Document $document` | `string` |
| `DeleteReportAction` | `BaseCommandAction` | `Document $document` | `void` |

### Handbook Actions

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| `CreateHandbookAction` | `BaseCommandAction` | `HandbookData $data` | `Document` |
| `UpdateHandbookAction` | `BaseCommandAction` | `Document $document, HandbookData $data` | `Document` |
| `DeleteHandbookAction` | `BaseCommandAction` | `Document $document` | `void` |
| `AcknowledgeHandbookAction` | `BaseCommandAction` | `Document $document, User $user` | `void` |

### Events

| Event | Dispatched By |
| ----- | ------------- |
| `HandbookCreated` | `CreateHandbookAction` |
| `HandbookUpdated` | `UpdateHandbookAction` |
| `HandbookDeleted` | `DeleteHandbookAction` |

### Listeners

| Listener | Event | Queued |
| -------- | ----- | ------ |
| `ClearHandbookCache` | `HandbookCreated`, `HandbookUpdated`, `HandbookDeleted` | No |

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
| `GET /admin/handbooks` | `HandbookManager` (Livewire) | `auth`, `role:admin` |
| `GET /student/handbooks` | `StudentHandbookList` (Livewire) | `auth`, `role:student` |

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

registration_documents:
  id: uuid (PK)
  registration_id: foreignUuid → registrations.id (cascadeOnDelete)
  document_id: foreignUuid → documents.id (cascadeOnDelete)
  status: string (default 'pending', indexed)
  admin_notes: text (nullable)
  verified_by: foreignUuid → users.id (nullOnDelete, nullable)
  verified_at: timestamp (nullable)
  timestamps
  Unique: (registration_id, document_id)
  Indexes: (registration_id, status)
```

---

## 7. Design Decisions

### DD-1 — DocumentRenderer as Dedicated Service

**Decision:** PDF rendering logic lives in a standalone `DocumentRenderer` service class rather than being inlined in each Action that generates documents.
**Rationale:** Multiple Actions (`GenerateDocumentAction`, `GenerateReportAction`, `RenderDocumentAction`) need the same rendering pipeline. Extracting it to a service avoids code duplication and provides a single place to change the rendering engine (e.g., switching from DomPDF to wkhtmltopdf). Constructor injection (C2 compliance) keeps Actions testable with mock renderers.
**Trade-off:** Extra class to maintain. Rejected alternative: inline rendering in each Action (violates DRY; inconsistent rendering behavior across document types).

### DD-2 — HandbookEntity for Business Rules

**Decision:** Handbook business rules (`isTargetedAt`, `isNewerThan`, `isAvailable`, `canBeDeleted`) live on a dedicated `HandbookEntity` (final readonly) rather than on the `Document` model.
**Rationale:** The `Document` model is a persistence object — adding handbook-specific business logic to it would violate C1 (thin models) and mix concerns between template management and handbook compliance. `HandbookEntity` provides a clean, immutable snapshot for business rule evaluation, consistent with the entity pattern used elsewhere (e.g., `AssessmentResult`). The `asHandbook()` bridge on `Document` creates the entity.
**Trade-off:** Extra class and bridge method. Rejected alternative: business methods on Document model (violates C1; couples template and handbook concerns).

### DD-3 — Four Hardcoded Report Types

**Decision:** `ReportsManager` supports exactly 4 hardcoded report types: `internship_completion`, `student_performance`, `company_participation`, `mentor_evaluation`. New report types require a code change.
**Rationale:** Each report type has specific variable resolution logic (different context data, different section structures). A dynamic template system would require a DSL or formula engine that significantly increases complexity. Hardcoding ensures each report type is predictable, testable, and maintainable. The `GenerateReportAction` already accepts a `Document` template, so the rendering pipeline is reusable — only the context-gathering differs per type.
**Trade-off:** Adding new report types requires a developer. Rejected alternative: dynamic report type registry with config-driven templates (too complex for 4 fixed types; premature abstraction).

### DD-4 — Acknowledgment via Activity Log

**Decision:** Student handbook acknowledgments are recorded in the `activity_log` table (via Spatie ActivityLog) rather than a dedicated `handbook_acknowledgments` table.
**Rationale:** The `activity_log` is already used for compliance audit across the system. Adding a separate table for one boolean acknowledgment per handbook-version per student would create a redundant schema. `activity_log` provides append-only immutability, IP address, user agent, and timestamp — all required for compliance audit. Querying by `event = 'HandbookAcknowledged'` and `subject_id` is efficient with existing indexes.
**Trade-off:** Querying acknowledgments requires filtering `activity_log` by event type rather than a purpose-built table with foreign keys. Rejected alternative: dedicated `handbook_acknowledgments` table (unnecessary schema expansion; duplicates audit capability already provided by `activity_log`).

---

## 8. Success Metrics

### 8.1 Data Integrity

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Unauthorized template edits | 0 | `DocumentPolicy` restricts create/update/delete to admin roles |
| Duplicate registration-document links | 0 | Unique constraint on `(registration_id, document_id)` |
| Orphan generated PDFs on disk | 0 | `storePdf()` writes atomically; `DeleteReportAction` cleans up |
| Acknowledgment without IP/UA | 0 | `AcknowledgeHandbookAction` always captures request IP and user agent |

### 8.2 Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| On-the-fly PDF render (≤50 pages) | < 5s | `DocumentRenderController::show` timing |
| Template manager page load | < 1s | Paginated query + Livewire mount |
| Student handbook list load | < 500ms | Active handbook query + acknowledgment lookup |
| Handbook acknowledgment check | < 100ms | `activity_log` indexed query by event + subject_id |

### 8.3 User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Report generation feedback | Visual card selection for 4 types | `ReportsManager` UI |
| Acknowledgment status visibility | Clear acknowledged/not-acknowledged per handbook | `StudentHandbookList` display |
| Template content preview | HTML preview before PDF render | `DocumentRenderer::renderHtml()` exposed in UI |

### 8.4 Architecture Compliance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| No model mutations in Livewire (C1) | 0 violations | All mutations via Actions |
| No service locator (C2) | 0 violations | Constructor injection for `DocumentRenderer` in Actions |
| Entity purity (C5) | 0 violations | `HandbookEntity` imports no Actions/Services |
| DTO purity (C6) | 0 violations | `HandbookData` imports no Actions/Services |
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
- `app/Document/Handbook/Actions/CreateHandbookAction.php` — Handbook creation
- `app/Document/Handbook/Actions/UpdateHandbookAction.php` — Handbook update
- `app/Document/Handbook/Actions/DeleteHandbookAction.php` — Handbook deletion
- `app/Document/Handbook/Actions/AcknowledgeHandbookAction.php` — Student acknowledgment
- `app/Document/Handbook/Entities/HandbookEntity.php` — Business rules entity
- `app/Document/Handbook/Data/HandbookData.php` — Handbook DTO
- `app/Document/Handbook/Enums/HandbookAudience.php` — Audience enum (4 cases)
- `app/Document/Handbook/Events/` — HandbookCreated, HandbookUpdated, HandbookDeleted
- `app/Document/Handbook/Listeners/ClearHandbookCache.php` — Cache invalidation listener
- `app/Document/Handbook/Livewire/HandbookManager.php` — Admin handbook CRUD
- `app/Document/Handbook/Livewire/StudentHandbookList.php` — Student handbook view
- `app/Document/Handbook/Livewire/HandbookForm.php` — Handbook form object
- `database/migrations/` — `documents` and `registration_documents` table migrations
- `routes/web/document.php` — Route definitions
- `docs/modules/document.md` — Module conceptual documentation
- `docs/modules/document-reference.md` — Module technical reference
