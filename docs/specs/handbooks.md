# Handbooks — Handbook Lifecycle, Acknowledgment Tracking & Registration Documents

> **Last updated:** 2026-07-22 **Changes:** feat — split from document.md; handbook CRUD with
> audience targeting, student acknowledgment tracking via activity log, registration-document verification

## Description

Specification of the handbook lifecycle: policy handbook CRUD with audience-based targeting, student
acknowledgment tracking via append-only activity log, and registration-document verification linkage
for enrollment compliance. Handbooks share the `documents` table with a `type = 'handbook'`
discriminator and a dedicated `HandbookEntity` for business rules.

See also: [document-templates.md](document-templates.md) — document template management,
`DocumentRenderer` service, and report generation infrastructure.

---

## 1. Problem Statements

### PS-1 — Handbook Distribution With Audience Targeting

School policy handbooks must reach specific audiences — all staff, students only, teachers only,
or industry supervisors only. A generic file share has no audience filtering and no way to verify
receipt. The handbook sub-module provides CRUD with audience selection, version tracking, and
file upload, ensuring the right document reaches the right people.

### PS-2 — Student Acknowledgment Tracking for Compliance

Regulatory compliance requires proof that students have read and acknowledged policy handbooks.
Without structured tracking, schools rely on paper sign-offs or email confirmations that are
hard to audit. Recording acknowledgments in the append-only `activity_log` table provides an
immutable compliance trail with IP address and user agent for audit purposes.

### PS-3 — Registration-Document Verification Linkage

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
| G1  | Support handbook CRUD with audience targeting (all, student, teacher, supervisor) and file upload |
| G2  | Track student handbook acknowledgments via append-only activity log with IP/UA capture |
| G3  | Link documents to registrations with status tracking (pending, verified, rejected) and admin notes |
| G4  | Enforce role-based access control: admins manage handbooks, students view targeted handbooks |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Certificate generation — belongs in the Certification module |
| NG2  | Final grade card rendering — belongs in the Reports module |
| NG3  | External document storage (S3, GCS) — local disk only |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Manages Handbooks

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

### UC-2 — Student Views and Acknowledges Handbooks

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

### UC-3 — Admin Renders Document for a Registration

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
**Postconditions:** PDF downloaded or stored; if stored, new Document record created with `file_path` set

---

## 4. Functional Requirements

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
| NFR-P1 | `StudentHandbookList` page load must complete in < 500ms |
| NFR-P2 | Handbook acknowledgment check must query `activity_log` efficiently (indexed on `event` + `subject_id`) |
| NFR-R1 | Handbook events must be dispatched synchronously to ensure cache is cleared before response returns |
| NFR-U1 | `StudentHandbookList` must show clear acknowledged/not-acknowledged status per handbook |
| NFR-M1 | All PHP files must declare `strict_types=1` and follow PSR-12 |

---

## 6. API / Data Contracts

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

### HandbookAudience Enum

```
App\Document\Handbook\Enums\HandbookAudience: string
  Implements: LabelEnum
  Cases: ALL='all', STUDENT='student', TEACHER='teacher', SUPERVISOR='supervisor'
```

### Handbook Actions

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| `CreateHandbookAction` | `BaseCommandAction` | `HandbookData $data` | `Document` |
| `UpdateHandbookAction` | `BaseCommandAction` | `Document $document, HandbookData $data` | `Document` |
| `DeleteHandbookAction` | `BaseCommandAction` | `Document $document` | `void` |
| `AcknowledgeHandbookAction` | `BaseCommandAction` | `Document $document, User $user` | `void` |
| `RenderDocumentAction` | `BaseCommandAction` | `Document $document, Registration $registration` | `Document` |

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

### Routes

| Route | Component | Middleware |
| ----- | --------- | ---------- |
| `GET /admin/handbooks` | `HandbookManager` (Livewire) | `auth`, `role:admin` |
| `GET /student/handbooks` | `StudentHandbookList` (Livewire) | `auth`, `role:student` |
| `GET /admin/documents/{document}/render/{registration}` | `DocumentRenderController@show` | `auth`, `role:admin` |
| `GET /admin/documents/{document}/render/{registration}/save` | `DocumentRenderController@store` | `auth`, `role:admin` |

### Database Schema

```
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

> **Note:** The `documents` table schema is defined in [document-templates.md](document-templates.md).

---

## 7. Design Decisions

### DD-1 — HandbookEntity for Business Rules

**Decision:** Handbook business rules (`isTargetedAt`, `isNewerThan`, `isAvailable`, `canBeDeleted`) live on a dedicated `HandbookEntity` (final readonly) rather than on the `Document` model.
**Rationale:** The `Document` model is a persistence object — adding handbook-specific business logic to it would violate C1 (thin models) and mix concerns between template management and handbook compliance. `HandbookEntity` provides a clean, immutable snapshot for business rule evaluation, consistent with the entity pattern used elsewhere (e.g., `AssessmentResult`). The `asHandbook()` bridge on `Document` creates the entity.
**Trade-off:** Extra class and bridge method. Rejected alternative: business methods on Document model (violates C1; couples template and handbook concerns).

### DD-2 — Acknowledgment via Activity Log

**Decision:** Student handbook acknowledgments are recorded in the `activity_log` table (via Spatie ActivityLog) rather than a dedicated `handbook_acknowledgments` table.
**Rationale:** The `activity_log` is already used for compliance audit across the system. Adding a separate table for one boolean acknowledgment per handbook-version per student would create a redundant schema. `activity_log` provides append-only immutability, IP address, user agent, and timestamp — all required for compliance audit. Querying by `event = 'HandbookAcknowledged'` and `subject_id` is efficient with existing indexes.
**Trade-off:** Querying acknowledgments requires filtering `activity_log` by event type rather than a purpose-built table with foreign keys. Rejected alternative: dedicated `handbook_acknowledgments` table (unnecessary schema expansion; duplicates audit capability already provided by `activity_log`).

---

## 8. Success Metrics

### Data Integrity

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Duplicate registration-document links | 0 | Unique constraint on `(registration_id, document_id)` |
| Acknowledgment without IP/UA | 0 | `AcknowledgeHandbookAction` always captures request IP and user agent |

### Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Student handbook list load | < 500ms | Active handbook query + acknowledgment lookup |
| Handbook acknowledgment check | < 100ms | `activity_log` indexed query by event + subject_id |

### User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Acknowledgment status visibility | Clear acknowledged/not-acknowledged per handbook | `StudentHandbookList` display |

### Architecture Compliance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| No model mutations in Livewire (C1) | 0 violations | All mutations via Actions |
| No service locator (C2) | 0 violations | Constructor injection for `DocumentRenderer` in Actions |
| Entity purity (C5) | 0 violations | `HandbookEntity` imports no Actions/Services |
| DTO purity (C6) | 0 violations | `HandbookData` imports no Actions/Services |
| Strict types (D1) | 100% files | `declare(strict_types=1)` in all PHP files |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [document-templates.md](document-templates.md) | Document template engine — handbooks use the same rendering infrastructure |

### Build Guide
After implementing this spec, the system has student and supervisor handbooks with acknowledgement tracking (students must confirm they've read the handbook). Handbooks are versioned and tracked via activity log. The next step is to build certification, which generates the final certificates.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [certification.md](certification.md) | Certification generates certificates; handbook acknowledgement is a prerequisite for certificate issuance |

---

## Quick References

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
- **Related spec:** [document-templates.md](document-templates.md) — template management, PDF rendering, report generation
