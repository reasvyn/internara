# Guidance Domain

## Purpose

Guidance manages the distribution and legally defensible acknowledgement of policy documents, 
handbooks, and agreements that students must read and digitally sign during their internship 
lifecycle. Documents like the internship handbook, code of conduct, safety guidelines, and 
confidentiality agreements are critical for legal and operational compliance — but a document 
is only useful if you can prove the student received, read, and agreed to it. This domain tracks 
versioned documents, assigns them to students through automated and manual rules, captures 
immutable acknowledgement records (who agreed to which version, when, and how), and monitors 
compliance across the student population.

## Boundary

**In scope:** Versioned guidance document management (upload documents, create new versions, 
maintain full version history), document categorization and typing (handbook, code of conduct, 
safety policy, confidentiality agreement, general information), automatic document assignment 
based on registration status and program enrollment, manual document assignment by admins to 
specific students or cohorts, student acknowledgement workflow (read confirmation with typed-name 
or click-through acceptance), immutable acknowledgement audit trail (who acknowledged which 
version, when, and the acknowledgement method), re-acknowledgement triggers when documents are 
updated to new versions, compliance tracking dashboards and exportable reports.

**Out of scope:** Document rendering and generation from templates (Document domain handles 
template-based document generation), certificate issuance (Certificate domain), internship report 
generation (Internship domain), assignment of learning tasks (Assignment domain), incident 
reporting (Incident domain), generic file upload and storage (media library handles raw file 
storage — Guidance manages the metadata and acknowledgement workflow), document content 
authoring and editing (documents are authored externally and uploaded as finalized files).

## Key Concepts

**Guidance Documents.** A guidance document is a versioned content item — typically a PDF file 
or rich text document — that students are required to read and acknowledge. Each document has a 
name (displayed to students), a category that determines workflow behavior (required documents 
can block progress; informational documents are read-only recommendations), a description visible 
to students explaining the document's purpose, and a version history. Documents can be marked as 
REQUIRED (must be acknowledged before certain actions are permitted) or INFORMATIONAL 
(recommended reading, no blocking effect). Required documents are the primary focus — they 
create compliance obligations.

**Document Versions.** Documents evolve over time — policies are updated, handbooks are 
revised, new agreements are introduced. When a document changes, a new version is created rather 
than overwriting the existing file. Each version stores: the uploaded file, a version number 
(incrementing integer), a change summary describing what changed and why, the publishing date, 
the uploader's identity, and the publication status (DRAFT — not yet assigned to anyone; 
PUBLISHED — active and assignable; SUPERSEDED — replaced by a newer version). Previous 
versions remain fully accessible for audit purposes: the acknowledgement history always 
references the exact version the student agreed to, not whatever the current version happens to 
be. This is critical for legal defensibility.

**Acknowledgements.** An acknowledgement is an immutable record establishing that a specific 
student read and agreed to a specific document version at a specific point in time. Each 
acknowledgement captures: the student's identity (User ID), the document version reference (exact 
version hash), the timestamp of acknowledgement, the acknowledgement method (CLICK_THROUGH — 
student clicked "I agree" after viewing; TYPED_NAME — student typed their full name as a 
digital signature; EXTERNAL — acknowledgement was captured outside the system, e.g., a signed 
physical form was scanned and uploaded). Once recorded, acknowledgements can never be modified or 
deleted — they are append-only by design. This provides a legally defensible audit trail for 
compliance audits and dispute resolution.

**Assignment Rules.** Documents reach students through multiple assignment mechanisms. AUTOMATIC: 
triggered by registration workflow — when a student enrolls in a program, all required 
documents for that program are auto-assigned; the student sees them in their pending 
acknowledgements list. PROGRAM-LEVEL: an admin assigns a document to all students in a specific 
program or cohort. INDIVIDUAL: an admin assigns a document to a specific student (useful for 
individually-required accommodations or agreements). TIMED: documents can be scheduled for 
assignment at a future date, becoming visible to students when the schedule triggers. Required 
documents that remain unacknowledged can block specific actions: the student may be unable to 
start their internship, access certain tools, or complete their registration. The blocking rules 
are configurable per document and per program.

**Compliance Monitoring.** The system tracks acknowledgement compliance in real time. Admins and 
mentors can view: which students have acknowledged which documents, which students are overdue 
(have not acknowledged within a configurable period after assignment), which documents have been 
updated and require re-acknowledgement, and aggregate compliance rates per document, per program, 
and per cohort. Compliance data is available through dashboards and is exportable for external 
audit or regulatory inspection. Reports include the full acknowledgement trail — every action 
is timestamped and attributable.

## Requirements

### User Stories & Rules

- **Admin:** As an admin, I want to upload and version guidance documents so that students always see the latest policies
- **Admin:** As an admin, I want to assign documents to students automatically or manually so that the right students receive the right documents
- **Student:** As a student, I want to view assigned guidance documents so that I can read and understand policies
- **Student:** As a student, I want to acknowledge a document so that my compliance is recorded
- **Admin:** As an admin, I want to view compliance reports so that I can track which students have acknowledged which documents
- Acknowledgements are completely immutable — they permanently and indelibly record the 
document version exact content hash, the acknowledgement timestamp, and the student's identity.
- When a document is updated to a new version, existing acknowledgements for the old version 
remain valid for that version, but a new acknowledgement is required for the updated version to 
maintain compliance.
- Required documents that are unacknowledged can block specific internship activities 
(configurable per program — common blocks include attendance clock-in, logbook access, and 
registration completion).
- Students cannot be required to acknowledge documents they have not been explicitly shown — 
the system requires the student to view the document before acknowledgement is possible.
- Document deletion is entirely prohibited if any acknowledgement record references that document 
or any of its versions.
- Each acknowledgement must be cryptographically traceable to the exact document version via a 
content hash stored with the acknowledgement record.
- Compliance dashboards display real-time acknowledgement status computed from raw 
acknowledgement data — no cached or stale compliance information is shown.

### Process Flow

```
Acknowledgement Lifecycle:

ASSIGNED ──→ VIEWED ──→ ACKNOWLEDGED (immutable)
```

- **ASSIGNED**: Document assigned to student, pending action
- **VIEWED**: Student has opened and viewed the document
- **ACKNOWLEDGED**: Student has acknowledged (click-through or typed name) — permanently immutable

### Key Operations

| Action | Description |
|--------|-------------|
| `CreateHandbookAction` | Creates a new guidance handbook or a new version |
| `AcknowledgeHandbookAction` | Records a student's acknowledgement of a handbook version |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Handbook` (versioned document), `HandbookAcknowledgement` (immutable acknowledgement record) |
| **Entity** | `HandbookPublishState` (published status check) |
| **Livewire** | `HandbookIndex`, `StudentHandbookIndex` |
| **Policy** | `HandbookPolicy` |

## Dependencies

| Dependency | Reason |
|---|---|
| Registration | Student registration status triggers automatic document assignments; 
unacknowledged required documents can block registration progress transitions |
| Document | Storage and retrieval of guidance document files (PDF, rich text); Document domain's 
media library serves the files |
| User | Student identity for acknowledgement records; admin identity for assignment actions |
| Core | BaseAction, BaseModel, SmartLogger |


