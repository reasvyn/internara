# Official Documents — School-Parent-Student-Industry Correspondence

> **Last updated:** 2026-07-24 **Changes:** feat — new spec for Phase 11 Reporting; real-world
> Indonesian SMK PKL bureaucratic document types, variable contracts, generation workflows

## Description

Defines the official document types required by Indonesian vocational school (SMK) PKL
(Praktik Kerja Lapangan) operations across the school-parent-student-industry chain. Each
document type specifies its purpose, audience, required variables, generation trigger, and
approval workflow. This spec is the authoritative catalog of WHAT documents exist; the
infrastructure for template management and PDF rendering lives in
[document-templates.md](document-templates.md).

---

## 1. Problem Statements

### PS-1 — No Centralized Document Catalog

Indonesian SMK PKL operations require 15+ distinct official documents spanning school-to-company
correspondence, parent consent, student acceptance, supervisor assignment, daily operations, and
program completion. Without a centralized catalog, schools rely on tribal knowledge — teachers
copy old files, use inconsistent formats, and sometimes omit legally required documents (e.g.,
parent consent). New teachers joining the school have no reference for which documents exist or
when they are needed.

### PS-2 — Inter-Organization Document Chain Is Untracked

PKL documents form a chain: school sends introduction → company replies with acceptance → school
assigns supervisors → parent consents → company evaluates → school issues completion letter.
This chain is currently tracked via email, WhatsApp, or paper folders. There is no system
visibility into which documents have been issued, which are pending, and which are missing for
a given registration or internship cycle.

### PS-3 — Variable Resolution Is Ad-Hoc

Each document type requires specific variables (student name, NISN, company name, program dates,
supervisor name, etc.). Without a declared variable contract per document type, developers and
admins guess which variables are available, leading to missing fields, placeholder text in
generated PDFs, and manual corrections after generation.

### PS-4 — Parent Consent Is Legally Required but Unverifiable

Indonesian education regulations require written parental consent for students participating in
off-campus activities (PKL). Schools must produce these consent forms per-student and retain them
for audit. Without a system-tracked consent workflow, schools cannot demonstrate compliance during
accreditation visits or incident investigations.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Define all official document types required in the PKL lifecycle with purpose, audience, variables, and generation rules |
| G2  | Map each document type to its lifecycle phase and trigger event |
| G3  | Declare the variable contract (name, type, source) for every placeholder in each document type |
| G4  | Define the approval/signing workflow for documents that require multi-party authorization |
| G5  | Enable the system to track document issuance status per registration (issued, pending, missing) |
| G6  | Support batch generation of per-student documents (e.g., consent letters for an entire cohort) |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Template infrastructure, PDF rendering pipeline, or DocumentRenderer — see [document-templates.md](document-templates.md) |
| NG2  | Certificate generation — see [certification.md](certification.md) |
| NG3  | Final grade card rendering — see [reports.md](reports.md) |
| NG4  | MoU document storage — see [partnership-management.md](partnership-management.md) |
| NG5  | Digital signatures, e-signatures, or blockchain-verified documents |
| NG6  | Integration with Indonesian government document systems (e.g., Dapodik, NSIS) |
| NG7  | Multi-language document templates (Indonesian only) |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Generates Introduction Letter to Company

**Actor:** Admin
**Preconditions:** Internship program is published; at least one placement exists
**Flow:**
1. Admin navigates to document generation for a specific internship program
2. Selects "Surat Pengantar PKL" template
3. System resolves variables: school name, address, logo, principal name, program name, date range, company name, company address
4. Admin reviews rendered PDF, confirms
5. PDF generated and stored; document issuance status updated
6. Admin downloads PDF for physical delivery or email to company
**Postconditions:** Introduction letter issued; registration chain status advanced

### UC-2 — Student Uploads Parent Consent Letter

**Actor:** Student
**Preconditions:** Student has active/pending registration; consent form template exists
**Flow:**
1. Student navigates to registration document upload
2. System shows required documents for the program, including "Surat Izin Orang Tua"
3. Student uploads signed consent form (PDF/image)
4. System records upload; admin notified for verification
**Postconditions:** Consent document uploaded; awaiting admin verification

### UC-3 — Admin Generates Acceptance Confirmation

**Actor:** Admin
**Preconditions:** Company has accepted student (placement active); acceptance letter template exists
**Flow:**
1. Admin selects placement for a student
2. Generates "Surat Penerimaan Siswa PKL" with variables: student name, NISN, company name, company address, program name, start date, end date
3. Admin reviews and downloads
4. Document issuance status updated for this registration
**Postconditions:** Acceptance confirmation issued; available for student records

### UC-4 — Admin Batch-Generates Supervisor Assignment Letters

**Actor:** Admin
**Preconditions:** Supervisors assigned to placements; assignment letter template exists
**Flow:**
1. Admin selects an internship program with assigned supervisors
2. Clicks "Generate Supervisor Assignment Letters"
3. System generates one PDF per supervisor (or batch PDF) with: supervisor name, NIP/NRK, student names, company name, program dates, department
4. All letters generated and stored
**Postconditions:** Assignment letters ready for principal signature and distribution

### UC-5 — Admin Tracks Document Completion for Registration

**Actor:** Admin
**Preconditions:** Registration exists with required document types defined
**Flow:**
1. Admin opens registration detail view
2. System shows document checklist: which documents are required, which are uploaded/generated, which are pending
3. Admin can filter registrations by document completion status
**Postconditions:** Admin has visibility into document compliance per student

### UC-6 — System Generates Completion Letter on Finalization

**Actor:** System (automatic)
**Preconditions:** Report is FINALIZED; all required documents have been issued
**Flow:**
1. Report finalization triggers `ReportFinalized` event
2. System generates "Surat Keterangan Selesai PKL" with: student name, NISN, company, program dates, final score, grade letter
3. Letter stored and available for download
4. Document issuance status updated
**Postconditions:** Completion letter auto-generated; student can download

---

## 4. Functional Requirements

### Document Type Registry

| ID      | Requirement |
| ------- | ----------- |
| FR-DR1  | System must maintain a registry of official document types with: id, name (Indonesian), name_en, lifecycle_phase, audience, approval_required, auto_generate, variables_contract |
| FR-DR2  | Each document type must declare its required variables as a JSON schema: variable name, type (string/date/number), source (registration/placement/school/settings), optional flag |
| FR-DR3  | Document types must be seeded as initial data via database seeder, not migration |

### Document Type Definitions

#### Pre-PKL Documents (Enrollment Phase)

| ID      | Document Type | Indonesian Name | Audience | Approval |
| ------- | ------------- | --------------- | -------- | -------- |
| FR-DT1  | Introduction Letter | Surat Pengantar PKL | School → Company | Principal signature required |
| FR-DT2  | Application Letter | Surat Permohonan PKL | School → Company | Principal signature required |
| FR-DT3  | Parent Consent Form | Surat Izin Orang Tua/Wali | Student (parent signs) | Admin verification required |
| FR-DT4  | Student Acceptance Letter | Surat Penerimaan Siswa PKL | Company → School | Company representative signature |
| FR-DT5  | Supervisor Assignment Letter | Surat Tugas Guru Pembimbing | School (internal) | Principal signature required |
| FR-DT6  | Student Registration Form | Formulir Pendaftaran PKL | Student (internal) | Auto-generated |

#### During-PKL Documents (Daily Operations Phase)

| ID      | Document Type | Indonesian Name | Audience | Approval |
| ------- | ------------- | --------------- | -------- | -------- |
| FR-DT7  | Absence Approval Letter | Surat Persetujuan Izin Absen | Student → Mentor | Mentor approval required |
| FR-DT8  | Monitoring Visit Report | Berita Acara Kunjungan | School (internal) | Teacher signature required |
| FR-DT9  | Incident Report Document | Berita Acara Insiden | School (internal) | Admin signature required |

#### Post-PKL Documents (Certification Phase)

| ID      | Document Type | Indonesian Name | Audience | Approval |
| ------- | ------------- | --------------- | -------- | -------- |
| FR-DT10 | Completion Letter | Surat Keterangan Selesai PKL | School → Student | Auto-generated on report finalization |
| FR-DT11 | Company Evaluation Form | Penilaian dari Perusahaan | Company → School | Company representative signature |
| FR-DT12 | Final Report Cover | Sampul Laporan PKL | Student (internal) | Auto-generated |

#### Administrative Documents

| ID      | Document Type | Indonesian Name | Audience | Approval |
| ------- | ------------- | --------------- | -------- | -------- |
| FR-DT13 | Document Submission Receipt | Tanda Terima Dokumen | School → Student | Auto-generated |
| FR-DT14 | Handover Record | Berita Acara Serah Terima | School ↔ Company | Both parties sign |
| FR-DT15 | Program Circular | Surat Edaran PKL | School → Parents | Principal signature required |

### Variable Contracts per Document Type

| ID      | Document | Required Variables |
| ------- | -------- | ------------------ |
| FR-VC1  | Introduction Letter | `school_name`, `school_address`, `school_phone`, `school_email`, `principal_name`, `program_name`, `program_start_date`, `program_end_date`, `company_name`, `company_address`, `letter_date`, `letter_number` |
| FR-VC2  | Application Letter | `school_name`, `principal_name`, `program_name`, `department_name`, `program_start_date`, `program_end_date`, `company_name`, `company_address`, `student_count`, `letter_date`, `letter_number` |
| FR-VC3  | Parent Consent Form | `student_name`, `student_nisn`, `student_class`, `program_name`, `company_name`, `program_start_date`, `program_end_date`, `parent_name`, `parent_phone`, `school_name`, `principal_name` |
| FR-VC4  | Acceptance Letter | `student_name`, `student_nisn`, `program_name`, `company_name`, `company_address`, `company_contact_person`, `start_date`, `end_date`, `letter_date` |
| FR-VC5  | Supervisor Assignment | `supervisor_name`, `supervisor_nip`, `program_name`, `company_name`, `student_names` (array), `department_name`, `program_start_date`, `program_end_date`, `principal_name`, `letter_date`, `letter_number` |
| FR-VC6  | Registration Form | `student_name`, `student_nisn`, `student_class`, `student_email`, `student_phone`, `program_name`, `department_name`, `company_name`, `registration_date`, `school_name` |
| FR-VC7  | Absence Approval | `student_name`, `student_nisn`, `absence_date`, `absence_reason`, `absence_type` (planned/unplanned), `mentor_name`, `program_name`, `company_name` |
| FR-VC8  | Monitoring Visit Report | `visit_date`, `teacher_name`, `teacher_nip`, `company_name`, `company_address`, `students_visited` (array), `visit_summary`, `issues_found`, `follow_up_actions`, `school_name` |
| FR-VC9  | Incident Report | `incident_date`, `incident_type`, `description`, `student_name`, `student_nisn`, `company_name`, `teacher_name`, `witnesses`, `action_taken`, `school_name`, `report_number` |
| FR-VC10 | Completion Letter | `student_name`, `student_nisn`, `student_class`, `program_name`, `company_name`, `start_date`, `end_date`, `final_score`, `grade_letter`, `school_name`, `principal_name`, `letter_date`, `certificate_number` |
| FR-VC11 | Company Evaluation | `student_name`, `student_nisn`, `program_name`, `company_name`, `supervisor_name`, `evaluation_date`, `competency_scores` (array), `overall_rating`, `comments` |
| FR-VC12 | Final Report Cover | `student_name`, `student_nisn`, `student_class`, `program_name`, `company_name`, `department_name`, `school_name`, `academic_year`, `submission_date` |
| FR-VC13 | Submission Receipt | `student_name`, `document_type`, `submission_date`, `received_by`, `school_name`, `receipt_number` |
| FR-VC14 | Handover Record | `student_name`, `student_nisn`, `company_name`, `handover_date`, `items_handed` (array), `school_representative`, `company_representative`, `school_name`, `report_number` |
| FR-VC15 | Program Circular | `program_name`, `program_start_date`, `program_end_date`, `registration_deadline`, `requirements_summary`, `school_name`, `principal_name`, `letter_date`, `letter_number` |

### Document Generation Workflow

| ID      | Requirement |
| ------- | ----------- |
| FR-GW1  | Admin-triggered documents must be generated synchronously for single documents, queued for batch operations (10+) |
| FR-GW2  | Auto-generated documents (Completion Letter, Registration Form) must be triggered by their respective events (`ReportFinalized`, `StudentRegistered`) |
| FR-GW3  | Each generated document must record: document_type_id, registration_id, generated_by (user_id), generated_at, template_version, variable_snapshot (JSON) |
| FR-GW4  | Batch generation must produce a single multi-page PDF or a ZIP of individual PDFs, configurable by admin |

### Document Status Tracking

| ID      | Requirement |
| ------- | ----------- |
| FR-DS1  | Each registration must track a `document_status` JSON column: `{document_type_id: 'issued'|'pending'|'missing'|'uploaded'}` |
| FR-DS2  | Admin dashboard must show per-registration document completion percentage |
| FR-DS3  | System must warn when a registration reaches placement-active status with missing required documents |
| FR-DS4  | Document status must auto-update: `issued` when generated, `uploaded` when student uploads, `pending` when required but not yet actioned |

### Letter Numbering

| ID      | Requirement |
| ------- | ----------- |
| FR-LN1  | Documents requiring letter numbers must follow configurable format: `{prefix}/{sequence}/{year}` (e.g., `SMK-001/PKL/2026`) |
| FR-LN2  | Letter number sequence must be per-document-type, reset annually |
| FR-LN3  | Format prefix must be configurable in settings (key: `document.letter_prefix`, default: school code) |

---

## 5. Non-Functional Requirements

| ID      | Requirement |
| ------- | ----------- |
| NFR-M1  | All document types must be defined in a PHP enum or config, not hardcoded in Blade templates |
| NFR-M2  | Variable contracts must be validated at generation time — missing required variables must throw `RenderException` |
| NFR-L1  | All document generation events must be logged via SmartLogger with module `document` |
| NFR-L2  | Variable snapshots must be stored with each generated document for audit trail |
| NFR-S1  | Documents containing student PII (NISN, name) must not be stored in publicly accessible paths |
| NFR-S2  | Parent consent forms must be retained for minimum 5 years (configurable) |
| NFR-R1  | Batch generation of 500 documents must complete within 10 minutes (queued) |
| NFR-R2  | Single document generation must complete within 5 seconds (synchronous) |
| NFR-U1  | Document checklist in registration detail must load in < 500ms |
| NFR-U2  | Generated PDF must be downloadable within 2 seconds of request |

---

## 6. API / Data Contracts

### OfficialDocumentType Enum

```php
// app/Document/Enums/OfficialDocumentType.php
enum OfficialDocumentType: string implements LabelEnum
{
    // Pre-PKL
    case INTRODUCTION_LETTER = 'introduction_letter';
    case APPLICATION_LETTER = 'application_letter';
    case PARENT_CONSENT = 'parent_consent';
    case ACCEPTANCE_LETTER = 'acceptance_letter';
    case SUPERVISOR_ASSIGNMENT = 'supervisor_assignment';
    case REGISTRATION_FORM = 'registration_form';

    // During PKL
    case ABSENCE_APPROVAL = 'absence_approval';
    case MONITORING_VISIT = 'monitoring_visit';
    case INCIDENT_REPORT_DOC = 'incident_report_doc';

    // Post-PKL
    case COMPLETION_LETTER = 'completion_letter';
    case COMPANY_EVALUATION = 'company_evaluation';
    case FINAL_REPORT_COVER = 'final_report_cover';

    // Administrative
    case SUBMISSION_RECEIPT = 'submission_receipt';
    case HANDOVER_RECORD = 'handover_record';
    case PROGRAM_CIRCULAR = 'program_circular';

    public function label(): string { /* Indonesian name */ }
    public function englishLabel(): string { /* English name */ }
    public function lifecyclePhase(): string { /* phase mapping */ }
    public function approvalRequired(): bool { /* approval flag */ }
    public function autoGenerate(): bool { /* auto-generate flag */ }
    public function requiredVariables(): array { /* variable contract */ }
}
```

### DocumentIssuance Model

```php
// app/Document/Models/DocumentIssuance.php
#[Fillable([
    'official_document_type',
    'registration_id',
    'generated_by',
    'template_version',
    'variable_snapshot',
    'letter_number',
    'issued_at',
])]
class DocumentIssuance extends BaseModel
{
    // official_document_type: string (OfficialDocumentType value)
    // registration_id: uuid, foreign key
    // generated_by: uuid, nullable (null for auto-generated)
    // template_version: string
    // variable_snapshot: json (frozen variables at generation time)
    // letter_number: string, nullable (for documents requiring sequential numbers)
    // issued_at: timestamp
}
```

### Registration Document Status

```php
// Added to registrations table
'document_status' => 'json'
// Structure: {
//   "introduction_letter": "issued",
//   "parent_consent": "uploaded",
//   "acceptance_letter": "pending",
//   ...
// }
```

### Actions

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| `GenerateOfficialDocumentAction` | `BaseCommandAction` | `OfficialDocumentType $type, Registration $registration, ?array $overrides = null` | `DocumentIssuance` |
| `BatchGenerateOfficialDocumentsAction` | `BaseCommandAction` | `OfficialDocumentType $type, Collection $registrations, ?array $overrides = null` | `Collection<DocumentIssuance>` |
| `UpdateDocumentStatusAction` | `BaseCommandAction` | `Registration $registration, OfficialDocumentType $type, string $status` | `void` |
| `ReadDocumentChecklistAction` | `BaseReadAction` | `Registration $registration` | `DocumentChecklistData` |
| `GenerateLetterNumberAction` | `BaseCommandAction` | `OfficialDocumentType $type` | `string` |

### Events

| Event | Trigger | Payload |
| ------- | ------- | ------- |
| `DocumentIssued` | After `GenerateOfficialDocumentAction` succeeds | `DocumentIssuance`, `Registration` |
| `DocumentChecklistIncomplete` | When placement activated with missing docs | `Registration`, `array $missing_types` |

### Config

```php
// config/document-official.php
return [
    'letter_prefix' => env('DOCUMENT_LETTER_PREFIX', 'SMK'),
    'retention' => [
        'parent_consent_years' => 5,
        'completion_letter_years' => 10,
    ],
    'batch_queue' => 'documents',
    'required_per_phase' => [
        'enrollment' => ['introduction_letter', 'parent_consent', 'acceptance_letter'],
        'daily_ops' => ['supervisor_assignment'],
        'certification' => ['completion_letter'],
    ],
];
```

---

## 7. Design Decisions

### DD-1 — Document Types as Enum, Not Database Table

**Decision:** Document types are defined as a PHP enum (`OfficialDocumentType`), not a database
`document_types` table.
**Rationale:** The set of official document types is fixed by Indonesian SMK PKL regulations and
school bureaucracy — it does not change at runtime. An enum provides compile-time type safety,
auto-complete in IDEs, and eliminates a migration/table for 15 static rows. Variable contracts
are declared as enum methods, not JSON configuration.
**Trade-off:** Adding a new document type requires a code change (new enum case + methods) and
a deployment. Rejected alternative: database table with JSON variable contracts (runtime
flexibility but no type safety, harder to validate, harder to test).

### DD-2 — Variable Snapshots Frozen at Generation Time

**Decision:** Each `DocumentIssuance` stores a `variable_snapshot` JSON column containing the
exact variable values used during generation.
**Rationale:** School data changes over time (principal name, school address, company details).
A generated PDF must reflect the data at generation time, not current time. The snapshot
ensures historical accuracy — critical for legal documents like consent forms and completion
letters that may be audited years later.
**Trade-off:** Increased storage per issuance (typically < 5KB per snapshot). Acceptable for
the audit benefit.

### DD-3 — Letter Numbering Per-Type, Annual Reset

**Decision:** Letter numbers follow `{prefix}/{sequence}/{year}` format, reset annually per
document type.
**Rationale:** Indonesian administrative convention uses sequential letter numbers per year.
Each document type has its own sequence (introduction letters start at 001 each January).
The prefix is configurable per-school (default: school code from settings).
**Trade-off:** Manual override not supported in v1. If a letter number needs correction,
admin must regenerate the document.

### DD-4 — Parent Consent as Upload, Not Digital Signature

**Decision:** Parent consent is implemented as file upload (scanned/photographed signed form),
not as a digital signature widget.
**Rationale:** Indonesian legal context requires wet signatures (tanda tangan basah) on consent
forms for school accreditation compliance. Digital signatures are not legally recognized for
minors' consent in this context. The system tracks upload and admin verification, not the
signing act itself.
**Trade-off:** Cannot verify signature authenticity from upload. Mitigated by admin verification
workflow and physical retention of original signed forms.

### DD-5 — Auto-Generate on Event, Not on Schedule

**Decision:** Documents like Completion Letter are auto-generated by events (`ReportFinalized`),
not by scheduled commands.
**Rationale:** Event-driven generation ensures documents are created immediately when their
prerequisite is met, with zero admin intervention. Scheduled generation would introduce
unnecessary delay and require polling for state changes.
**Trade-off:** Event handler must be idempotent (retry-safe) to handle queue retries without
creating duplicate issuances.

---

## 8. Success Metrics

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Document type coverage | 100% of real PKL documents cataloged | Count of enum cases vs regulatory checklist |
| Variable contract completeness | 0 missing variables at generation time | `RenderException` count per 1000 generations |
| Letter numbering uniqueness | 0 duplicate letter numbers per type per year | Database uniqueness check |
| Batch generation throughput | 500 documents in < 10 minutes | Queue processing time |
| Registration document visibility | Admin sees completion % in < 500ms | Registration detail page load time |
| Parent consent tracking | 100% of active registrations with consent status | `document_status` completeness |

---

## 9. Roadmap

### Prerequisites

This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [base-classes.md](base-classes.md) (#2) | `BaseCommandAction`, `BaseReadAction`, `BaseEntity`, `BaseModel` base classes |
| [registration.md](registration.md) (#31) | `Registration` model, registration lifecycle, document upload infrastructure |
| [document-templates.md](document-templates.md) (#43) | `DocumentRenderer`, `DocumentCategory` enum, template CRUD, PDF rendering pipeline |
| [reports.md](reports.md) (#48) | `ReportFinalized` event triggers auto-generation of Completion Letter |

### Build Guide

This spec defines the CONTENT layer on top of the existing template infrastructure. First, define
the `OfficialDocumentType` enum with all 15 document types and their variable contracts. Then
implement `GenerateOfficialDocumentAction` using the existing `DocumentRenderer` from
document-templates.md. Wire event listeners for auto-generated documents (Completion Letter on
ReportFinalized, Registration Form on StudentRegistered). Add `document_status` tracking to
Registration model. Seed the document type registry.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | (No downstream) | This spec is consumed by enrollment, daily ops, and certification modules as needed |

---

## Quick References

- `docs/specs/document-templates.md` — Template infrastructure, `DocumentRenderer`, PDF rendering
- `docs/specs/registration.md` — Registration model, document upload, `document_status` column
- `docs/specs/certification.md` — Certificate generation (separate from official documents)
- `docs/specs/reports.md` — `ReportFinalized` event that triggers Completion Letter
- `docs/specs/partnership-management.md` — MoU document storage (separate initiative)
- `docs/modules/document.md` — Document module overview
- `docs/modules/document-reference.md` — Document module technical reference
- `docs/foundation/project-requirements.md` — High-level feature list
- `docs/conventions.md` — Naming conventions, `LabelEnum` contract
