# O2KCR — Bulk Import & Export

> **Spec ID:** O2KCR
> **Status:** Full
> **Owner:** Core
> **Depends on:** 95EVB, XI3LB, 4HWSB, 7C5WM, IT0OE, NTHQA, 3S55V, J0M04, XW6F5

## Description

Cross-cutting tabular bulk operations for Internara: the shared `CsvHandler` service and
`CsvRowResult` enum powering import, filtered export, selected-rows export, and template
download across 9 record managers — Users, Departments, Companies, Internships, Internship
Groups, Partnerships, Announcements, Certificate Templates, Academic Years. Any future
manager opts into the same four-method convention instead of reinventing the pipeline.
Single CSV satisfies MVP; multi-format matrices are post-MVP depth and explicitly out of
scope.

---

## 1. Problem Statements

### PS-1 — Bulk Onboarding at Scale

Schools may have 500+ students, dozens of departments, hundreds of partner companies, and many
internships to onboard at the start of an academic year. Manual one-by-one creation through forms
is impractical and error-prone at this volume. Bulk import must handle large datasets with
consistent validation, deduplication, and clear feedback on what was created versus skipped.
**→ Requirement:** FR-CSV-001 (shared import), FR-CSV-006 (dedup), UC-CSV-001–009.

### PS-2 — Duplicate Detection Across Modules

Each module has a natural uniqueness constraint: users by email, departments by name, companies
by name, internships by code, partnerships by company+year. Without duplicate detection during
import, admins could create redundant records that break referential integrity (e.g., duplicate
user emails causing login ambiguity, duplicate internships confusing students). The import
process must detect existing records and skip duplicates transparently.
**→ Requirement:** FR-CSV-006 (natural-key dedup), per-manager duplicate rows.

### PS-3 — Credential Generation for User Imports

When importing users (especially students), each new account requires a unique username and
temporary password. Manual credential generation for 500+ students is untenable. The import
pipeline must auto-generate credentials for every valid row and make them available for
distribution via account slips.
**→ Requirement:** FR-CSV-018 (credential minting), UC-CSV-001.

### PS-4 — Per-Row Error Reporting

CSV files from external systems often contain malformed rows — missing required fields, invalid
email formats, encoding issues, or rows referencing unknown foreign keys. Rather than failing
the entire import on the first bad row, the system must process all rows, report per-row
results (created, skipped, failed with reason), and present a clear summary to the admin.
**→ Requirement:** FR-CSV-007/008 (row contract + summary), NFR-CSV-006/009.

### PS-5 — Filtered Export for Reporting and Migration

Administrators need to export subsets of data for offline reporting, migration to other systems,
spreadsheet analysis, and audit. Exports must respect the current search and filter state in the
management UI so admins can narrow results before downloading.
**→ Requirement:** FR-CSV-011 (filtered export), UC-CSV-010/011.

### PS-6 — Cross-Cutting Pattern for New Managers

Adding a new manager should not require re-inventing the import/export pipeline. The bulk
infrastructure must be reusable as a convention: any manager extending `BaseRecordManager`
opts in by implementing 4 methods (`import`, `export`, `exportSelected`, `downloadTemplate`)
using the shared `CsvHandler`.
**→ Requirement:** FR-CSV-001/002/003 (shared service), DD-CSV-007 (opt-in convention).

---

## 2. Goals & Non-Goals

### Goals

- **One shared `CsvHandler` for import, export, and templates** — every manager calls the same service. *Why:* identical file handling everywhere, zero duplicated streaming code.
- **Type-safe `CsvRowResult` outcome per row** — created, skipped, or failed with reason. *Why:* summaries stay honest and machine-readable instead of stringly-typed.
- **Minted credentials on every valid user row** — username plus random password through `CreateUserAction`. *Why:* five hundred students cannot wait on hand-made logins.
- **Per-row reporting with an honest summary** — created, skipped, and failed counts plus reasons. *Why:* admins must know exactly which rows need attention.
- **Filtered and selected-rows export** — current search state or checked rows decide the file. *Why:* reporting is always about a subset, never the whole table.
- **Templates with headers plus one example row** — downloadable correctness. *Why:* a correct starting file prevents most import failures before they happen.
- **Header validation before any row runs** — mismatched files refused up front. *Why:* garbage columns must never produce garbage records.
- **Size and type limits on uploads** — 2048KB, csv/txt only. *Why:* bounding input bounds the failure.
- **Nine managers covered, clear path for the tenth** — Users, Departments, Companies, Internships, Groups, Partnerships, Announcements, Templates, Academic Years. *Why:* the convention must already be proven before the next manager needs it.

### Non-Goals

- **Update-on-duplicate (merge/overwrite)**. *Why:* conflict resolution and audit complexity exceed MVP; duplicates skip.
- **Real-time streaming import beyond memory-safe chunking**. *Why:* bounded files plus row-at-a-time reading cover school scale.
- **Scheduled or queue-based async import**. *Why:* scheduling daemons are post-MVP depth; imports run interactively.
- **Native Excel/TSV/XLSX/PDF matrices**. *Why:* single CSV satisfies MVP per the trim decision; other formats reformat first.
- **Nested/related data in one file**. *Why:* foreign keys must pre-exist; imports stay single-entity.
- **Column-mapping UI**. *Why:* templates fix the column order; mapping screens are post-MVP comfort.
- **Multi-format export**. *Why:* same single-format reasoning as import.

---

## 3. User Stories / Use Cases

Nine manager imports plus the three export-side stories. Each row is verified by a Feature
test with a real file upload or streamed download.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-CSV-001 | Admin imports users from a file and credentials mint per valid row | P0 | F | Full |
| UC-CSV-002 | Admin imports departments with name-dedup | P1 | F | Full |
| UC-CSV-003 | Admin imports companies with DTO validation | P1 | F | Full |
| UC-CSV-004 | Admin imports internships with FK resolution from existing records | P0 | F | Full |
| UC-CSV-005 | Admin imports internship groups with semicolon-list resolution | P1 | F | Full |
| UC-CSV-006 | Admin imports partnerships as drafts with company resolution | P1 | F | Full |
| UC-CSV-007 | Admin imports announcements as drafts with later batch publish | P2 | F | Full |
| UC-CSV-008 | Admin imports certificate templates as inactive rows awaiting activation | P1 | F | Full |
| UC-CSV-009 | Admin imports academic years as inactive rows awaiting activation | P1 | F | Full |
| UC-CSV-010 | Admin exports the currently filtered view to a file | P0 | F | Full |
| UC-CSV-011 | Admin exports only the checked rows to a file | P0 | F | Full |
| UC-CSV-012 | Admin downloads the import template with headers and an example row | P0 | F | Full |

### 3.1 Imports

#### UC-CSV-001 — Six hundred students, one file, the night before placement

The coordinator receives the enrollment spreadsheet at 9pm: six hundred rows, three with
malformed emails, forty duplicates from last term's partial import. She saves as CSV,
uploads, and watches the summary land — created, skipped, failed with reasons naming the
bad rows. Every valid row minted its username and password through the same Action the
single-create form uses, so slips print in the morning without a second thought. The file
that once meant a week of typing means an evening of review.

#### UC-CSV-002 — Departments in minutes

A new school profile means eight departments, each a name plus description. The admin
downloads the template, fills eight rows, imports — reruns the same file twice to be sure,
and the second run skips all eight as duplicates instead of doubling them. Idempotent by
construction: the import can be retried without fear.

#### UC-CSV-003 — Companies through the DTO gate

Partner data arrives messy — trailing spaces, inconsistent casing, a missing website here
and there. Each row passes through `CompanyData` before the create Action, so validation is
identical to the manual form and the stored records look hand-entered. The file's chaos
stays in the file; the database never sees it.

- Trims and normalizes before comparing for duplicates.
- Rejects the row, not the file, when validation fails.

#### UC-CSV-004 — Internships resolve their world

Each internship row names its academic year, department, and company — all of which must
already exist. The processor looks each up by name; a row naming a company nobody
registered skips with "referenced company not found" while its neighbors succeed. Orphans
never enter: an internship always points at real records or it does not enter at all.

#### UC-CSV-005 — Groups unpack their lists

Group rows pack semicolon-separated internship codes and student emails into single cells.
The processor splits, resolves each reference, and skips the row when any member is
missing — a group with a ghost member is worse than no group. Valid groups land with
relationships intact, ready for the term.

#### UC-CSV-006 — Partnerships start as drafts

Imported partnerships arrive in `DRAFT` because a signature nobody verified must not look
active. Company resolved by name, duplicates caught on company plus start date, activation
left as a deliberate human act. The import proposes; the admin disposes.

#### UC-CSV-007 — Announcements queue quietly

Bulk-imported announcements land as drafts with parsed target roles, invisible to students
until the separate publish action releases them. A mistyped audience in row forty embarrasses
nobody because nothing goes out on import. Draft-first is the apology that never needs
sending.

#### UC-CSV-008 — Templates sleep until reviewed

Certificate templates import with `is_active=0` regardless of what the file claims — a
half-written layout must not become the school's certificate. Activation follows manual
review, one click per template. The file supplies candidates; humans confer authority.

#### UC-CSV-009 — Academic years wait their turn

Year rows import inactive because exactly one year rules the term and the import must not
elect it. Duplicates skip on name; activation stays a conscious decision in the year
manager. Chronology is too important to be decided by file order.

### 3.2 Exports & Templates

#### UC-CSV-010 — The filtered view becomes a spreadsheet

The admin narrows internships to the active year, hits Export, and downloads exactly the
rows on screen — search text and filters applied server-side before streaming. What you see
is what you get, which is the entire point: the export answers the question the filter
asked, in a format the spreadsheet understands.

#### UC-CSV-011 — Checked rows travel alone

Twelve checked rows out of nine hundred become a twelve-row file via `whereIn` on the
selection. Partial exports like this feed targeted follow-ups — the twelve companies
missing MOUs, the twelve students needing slips — without anyone hand-deleting rows from a
full dump.

#### UC-CSV-012 — The template teaches the format

One click downloads headers plus a single example row: the contract, demonstrated. Admins
fill beneath the example, delete it, and import — column order correct by construction.
Most import failures are format failures, and this file prevents most of those.

---

## 4. Functional Requirements

Shared infrastructure first, then per-manager columns and rules, closing with the DTO-phase
conformance row. Every row is implemented and verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-CSV-001 | All imports run through the shared `CsvHandler::import()` service | P0 | F | Full |
| FR-CSV-002 | All exports run through `CsvHandler::export()` returning a `StreamedResponse` | P0 | F | Full |
| FR-CSV-003 | All template downloads run through `CsvHandler::downloadTemplate()` | P0 | F | Full |
| FR-CSV-004 | Imports refuse files over 2048KB or outside the csv/txt MIME types | P0 | F | Full |
| FR-CSV-005 | Imports validate the header row against expected columns and report `invalid: true` without processing rows on mismatch | P0 | F | Full |
| FR-CSV-006 | All imports deduplicate on the module's natural key (email, name, code, or compound per manager) | P0 | F | Full |
| FR-CSV-007 | Row processors return `CsvRowResult::CREATED`, `::SKIPPED`, or `::FAILED` with a per-row reason; `null` means silent skip of empty rows | P0 | F | Full |
| FR-CSV-008 | Import summaries report created, skipped, and failed counts with per-row failure reasons surfaced to the admin | P0 | F | Full |
| FR-CSV-009 | Import summary flashes use `common.actions.import_summary` with the counts | P1 | F | Full |
| FR-CSV-010 | Invalid-header files flash the `common.actions.import_invalid` error | P1 | F | Full |
| FR-CSV-011 | Exports apply the manager's current `applySearch()` and `applyFilters()` before streaming | P0 | F | Full |
| FR-CSV-012 | `exportSelected()` exports exactly the checked ids and nothing else | P0 | F | Full |
| FR-CSV-013 | Templates carry headers plus one placeholder example row | P0 | F | Full |
| FR-CSV-014 | Every import nulls its `importFile` property after processing | P1 | F | Full |
| FR-CSV-015 | Every user-facing string in CSV operations passes through `__()` | P0 | A | Full |
| FR-CSV-016 | User import columns are `full_name`, `email`, `phone` | P0 | F | Full |
| FR-CSV-017 | User import skips rows whose email matches an existing user | P0 | F | Full |
| FR-CSV-018 | User import mints username-from-email plus a random 12-character password via `CreateUserAction` | P0 | F | Full |
| FR-CSV-019 | User export columns are `full_name`, `email`, `username`, `phone`, `address` | P0 | F | Full |
| FR-CSV-020 | User filenames are `users.csv`, `users-selected.csv`, `users-template.csv` | P1 | F | Full |
| FR-CSV-021 | Department import columns are `name`, `description` | P1 | F | Full |
| FR-CSV-022 | Department import skips rows whose name matches an existing department | P1 | F | Full |
| FR-CSV-023 | Department import authorizes `create` on the Department model first | P0 | F | Full |
| FR-CSV-024 | Department export columns are `name`, `description` | P1 | F | Full |
| FR-CSV-025 | Department filenames are `departments.csv`, `departments-selected.csv`, `departments-template.csv` | P1 | F | Full |
| FR-CSV-026 | Company import columns are `name`, `address`, `phone`, `email`, `website`, `description`, `industry_sector` | P1 | F | Full |
| FR-CSV-027 | Company import skips rows whose name matches an existing company | P1 | F | Full |
| FR-CSV-028 | Company rows validate through the `CompanyData` DTO before `CreateCompanyAction` | P0 | F | Full |
| FR-CSV-029 | Company export columns repeat the seven import columns | P1 | F | Full |
| FR-CSV-030 | Company filenames are `companies.csv`, `companies-selected.csv`, `companies-template.csv` | P1 | F | Full |
| FR-CSV-031 | Internship import columns are `code`, `title`, `academic_year_name`, `department_name`, `company_name` | P0 | F | Full |
| FR-CSV-032 | Internship rows resolve AcademicYear, Department, and Company by name, skipping rows with any missing reference and a named reason | P0 | F | Full |
| FR-CSV-033 | Internship import skips rows whose code matches an existing internship | P0 | F | Full |
| FR-CSV-034 | Internship export columns are `code`, `title`, `academic_year`, `department`, `company`, `start_date`, `end_date`, `status` | P1 | F | Full |
| FR-CSV-035 | Internship filenames are `internships.csv`, `internships-selected.csv`, `internships-template.csv` | P1 | F | Full |
| FR-CSV-036 | Group import columns are `name`, semicolon-separated `internship_codes`, semicolon-separated `student_emails` | P1 | F | Full |
| FR-CSV-037 | Group rows split and resolve every list member, skipping the row on any missing reference | P1 | F | Full |
| FR-CSV-038 | Group import skips rows whose name matches an existing group | P1 | F | Full |
| FR-CSV-039 | Group export columns are `name`, `internship_count`, `student_count`, `created_at` | P1 | F | Full |
| FR-CSV-040 | Group filenames are `internship-groups.csv`, `internship-groups-selected.csv`, `internship-groups-template.csv` | P1 | F | Full |
| FR-CSV-041 | Partnership import columns are `company_name`, `partner_school`, `start_date`, `end_date`, `mou_number` | P1 | F | Full |
| FR-CSV-042 | Partnership rows resolve Company by name, skipping rows with unknown companies | P1 | F | Full |
| FR-CSV-043 | Imported partnerships start in `DRAFT`; activation is a separate admin act | P1 | F | Full |
| FR-CSV-044 | Partnership import skips rows matching an existing company-plus-start-date pair | P1 | F | Full |
| FR-CSV-045 | Partnership export columns are `company`, `partner_school`, `start_date`, `end_date`, `mou_number`, `status` | P1 | F | Full |
| FR-CSV-046 | Partnership filenames are `partnerships.csv`, `partnerships-selected.csv`, `partnerships-template.csv` | P1 | F | Full |
| FR-CSV-047 | Announcement import columns are `title`, `body`, `target_role`, `publish_at` | P2 | F | Full |
| FR-CSV-048 | Imported announcements start in `DRAFT`; publishing is a separate batch action | P2 | F | Full |
| FR-CSV-049 | Announcement import skips rows matching an existing title-plus-publish-at pair | P2 | F | Full |
| FR-CSV-050 | Announcement export columns are `title`, `target_role`, `publish_at`, `status` | P2 | F | Full |
| FR-CSV-051 | Announcement filenames are `announcements.csv`, `announcements-selected.csv`, `announcements-template.csv` | P2 | F | Full |
| FR-CSV-052 | Template import columns are `name`, `layout`, `content_template`, `is_active` | P1 | F | Full |
| FR-CSV-053 | Imported certificate templates force `is_active=0` regardless of file content pending manual activation | P1 | F | Full |
| FR-CSV-054 | Template import skips rows whose name matches an existing template | P1 | F | Full |
| FR-CSV-055 | Template export columns are `name`, `layout`, `is_active`, `created_at` | P1 | F | Full |
| FR-CSV-056 | Template filenames are `certificate-templates.csv`, `certificate-templates-selected.csv`, `certificate-templates-template.csv` | P1 | F | Full |
| FR-CSV-057 | Academic-year import columns are `name`, `start_date`, `end_date` | P1 | F | Full |
| FR-CSV-058 | Academic-year import skips rows whose name matches an existing year | P1 | F | Full |
| FR-CSV-059 | Imported academic years start `INACTIVE`; activation is a separate admin act | P1 | F | Full |
| FR-CSV-060 | Academic-year export columns are `name`, `start_date`, `end_date`, `status` | P1 | F | Full |
| FR-CSV-061 | Academic-year filenames are `academic-years.csv`, `academic-years-selected.csv`, `academic-years-template.csv` | P1 | F | Full |
| FR-CSV-062 | Row payloads follow the DTO migration phases: `array` today, `Data|array` union with `fromArray()` when stabilizing, `Data` only when settled — per the phase table in §6.5 | P1 | A | Full |

### 4.1 Shared Infrastructure

#### FR-CSV-001 — One door for every import

Nine managers, one `import()` implementation: header check, row loop, summary assembly all
live in `CsvHandler`, with managers supplying only columns and a row callback. A bug fix in
BOM handling lands once and heals nine flows. Shared code is not just thrift — it is nine
guarantees that behave identically under the same malformed file.

#### FR-CSV-002 — Exports stream, never accumulate

`StreamedResponse` writes rows to the wire as they are read, so a ten-thousand-row export
peaks like a ten-row one. The alternative — building the whole file in memory — passes every
test on staging and kills the request in production. Streaming is the only export shape,
not an option managers select.

#### FR-CSV-003 — Templates come from the same hand

`downloadTemplate()` guarantees the example file matches what `import()` expects, because
both read the same column definition. Template/import drift — the classic "but I used your
template!" support ticket — becomes structurally impossible instead of carefully avoided.

#### FR-CSV-004 — Bounded input at the gate

Two megabytes and two MIME types draw a box around what the parser must survive. The limit
is enforced at the form before bytes reach the handler, so abuse and accidents both stop
early. A school-scale roster fits comfortably inside; anything larger is a different job
than interactive import.

- Oversize or mistyped files are refused with a translatable message.
- The bound lives in validation rules, adjustable without touching the handler.

#### FR-CSV-005 — Headers first, rows never on mismatch

The header check runs before the first data row, case-insensitively, and a mismatch returns
`invalid: true` with zero writes. Processing rows under wrong columns would silently plant
names in email fields across hundreds of records — the kind of corruption discovered at
slip-printing time. Early refusal trades one clear error for a hundred quiet ones.

#### FR-CSV-006 — Every module names its sameness

Email for users, name for departments and companies, code for internships, compound pairs
where singles lie — each manager declares the key that makes a row "already here." The
declaration lives beside the processor, visible in review, so the next maintainer sees the
dedup rule before touching the create call it guards.

#### FR-CSV-007 — Three outcomes plus meaningful silence

Created, skipped, failed-with-reason — and `null` reserved for the truly empty line that
deserves no mention. The `FAILED` case closes that gap: validation explosions
and FK misses are failures with explanations, not silent skips that leave admins guessing.
Four returns, each with a distinct fate in the summary.

#### FR-CSV-008 — Summaries tell the whole truth

Created, skipped, failed — counts plus the per-row reasons behind the failures, surfaced
where the admin who uploaded can act on them. A summary reporting only successes is a
progress bar that lies; this one itemizes the rows needing a second pass with their line
numbers attached.

#### FR-CSV-009 — The success message carries numbers

`common.actions.import_summary` interpolates the counts into one translated flash, so the
admin learns "412 created, 38 skipped, 3 failed" in both languages without custom strings
per manager. One key, nine managers, zero hardcoded sentences.

#### FR-CSV-010 — The failure message names the problem

`common.actions.import_invalid` tells the admin the headers did not match — before they
wonder why zero rows imported. Distinct keys for success and invalid-header states keep the
two outcomes unmistakable in both locales.

#### FR-CSV-011 — Exports mirror the screen

Search text and filters compose into the export query, so the file contains the rows under
discussion and nothing else. An export ignoring the filter would be worse than useless at
audit time — a spreadsheet claiming to show "suspended students" while containing everyone.
The mirror is exact because it reuses the manager's own query builders.

#### FR-CSV-012 — Selection exports exactly the selection

`whereIn` on the checked ids, no more, no fewer. The guarantee matters when the selection
is a sensitive subset — twelve records for a disciplinary follow-up must not arrive with
eight hundred fellow travelers. Exactness here is a privacy property, not just UX.

#### FR-CSV-013 — Templates demonstrate, not just declare

Headers plus one plausible example row: types, formats, date shapes, all shown by example.
An admin who has never seen an ISO date writes one correctly by copying the row above.
Demonstration beats documentation for the audience uploading at midnight.

#### FR-CSV-014 — The upload handle dies after use

Nulling `importFile` post-processing prevents double-submit replays from re-running the
import and stops Livewire from holding a stale temp-file reference. One line, two failure
modes removed: accidental double imports and ghost-file errors on the next visit.

#### FR-CSV-015 — No hardcoded words in the pipeline

Flashes, validation messages, summaries — all through `__()`. CSV flows cross every
module, so a hardcoded string here would scatter fourteen untranslatable sentences across
nine managers. The translation rule holds the shared service to the same standard as the
rest of the UI.

### 4.2 Users

#### FR-CSV-016 — Three columns in, nothing exotic

Full name, email, phone: the minimum viable identity a roster spreadsheet already has. No
usernames, no passwords, no roles in the file — those are minted or defaulted, because
asking a spreadsheet for credentials is asking for `password123` four hundred times.

#### FR-CSV-017 — Email sameness skips quietly

A row matching an existing user skips instead of erroring, which makes reruns and
overlapping files safe. The skip is reported, not hidden — the summary's skipped count is
where the admin confirms the overlap they expected.

#### FR-CSV-018 — Every imported user arrives login-ready

Username derived, password random, both through `CreateUserAction` — the same Action, the
same validation, the same notifications as single creation. Import is not a parallel user
system; it is the single system fed faster. Slips print from these rows exactly as from
hand-made accounts.

#### FR-CSV-019 — Exports carry theIdentity, never the secret

Name, email, username, phone, address travel; passwords, tokens, and keys never appear in
any export column list. The omission is the requirement: a spreadsheet emailed to a
department head must contain nothing an attacker can log in with.

#### FR-CSV-020 — Filenames say what they are

`users.csv`, `users-selected.csv`, `users-template.csv` — the trio any admin recognizes in
a downloads folder months later. Convention over configuration extends to filenames: no
timestamps, no hashes, just the entity and the variant.

### 4.3 Departments

#### FR-CSV-021 — Two columns, complete departments

Name plus description covers the entity fully — departments are among the simplest rows in
the system, and their import shape says so. Simplicity here is honest: nothing hidden, no
optional columns that secretly matter.

#### FR-CSV-022 — Name sameness skips

Department names are unique by nature — two "Teknik Komputer" rows would split students
across phantom twins. Name matching keeps the list canonical across repeated imports of
the school's standard structure.

#### FR-CSV-023 — Authorization precedes bytes

The `create` check on Department runs before the file is even read, so an unauthorized
upload fails closed without parsing anything. Permission first, processing second — the
order is the security property.

#### FR-CSV-024 — Exports round-trip the import

Name and description out, matching name and description in: exports double as re-importable
backups. Symmetry between the shapes means an exported file is always a valid future
import, which makes migrations boring in the best way.

#### FR-CSV-025 — Filenames follow the trio

Same three-variant convention as users, with the entity swapped. An admin managing nine
managers learns the pattern once and predicts all twenty-seven filenames correctly.

### 4.4 Companies

#### FR-CSV-026 — Seven columns of partner truth

Name, address, phone, email, website, description, industry sector: the full partner record
as the spreadsheet already holds it. Seven columns is the widest import in the system, and
the template's example row earns its keep here most of all.

#### FR-CSV-027 — Company twins refused

Name matching prevents the "PT Maju Jaya" / "PT. Maju Jaya" split that would scatter
placements across duplicates — with trimming applied before comparison so punctuation
spacing stops forging twins. The dedup rule is where data hygiene actually happens.

#### FR-CSV-028 — The DTO stands at the gate

`CompanyData` validates every row exactly as the manual form does, because two validation
paths for one entity always diverge. This row is the DTO-phase pattern in miniature: typed
boundary today, with the §6.5 migration path ready when the shape stabilizes further.

#### FR-CSV-029 — Exports mirror the seven

Same columns out as in, so the partner list round-trips cleanly between systems. A DUDI
coordinator reconciling our records against theirs works from identical shapes on both
sides of the exchange.

#### FR-CSV-030 — Filenames follow the trio

Companies variant of the same convention. Predictability compounds: the ninth manager's
filenames surprise nobody.

### 4.5 Internships

#### FR-CSV-031 — Five columns naming three parents

Code, title, plus the names of the year, department, and company it belongs to — human
names, not UUIDs, because the spreadsheet author knows names. The processor translates
names to keys; the file stays readable by the humans who maintain it.

#### FR-CSV-032 — Missing parents skip with names attached

Each row looks up its three references; any miss skips the row and says which — "referenced
company not found" beats a silent drop when the admin is hunting row 213. Valid rows still
land, so one bad reference costs one row, never the file.

- Lookups run per row against existing records only; imports never create parents.
- The reason names the missing entity type for fast correction.

#### FR-CSV-033 — Code sameness skips

Internship codes are the stable identifiers students see, so code matching keeps them
unique across imports. A reissued spreadsheet for the new term skips last term's codes
instead of forking them.

#### FR-CSV-034 — Exports enrich beyond imports

Exports add dates and status the import never supplied — the file outgrows the file that
came in, because reporting needs the internship's life story, not just its birth record.
Import shape stays minimal; export shape stays useful.

#### FR-CSV-035 — Filenames follow the trio

Internships variant of the convention. The pattern holds at nine for nine.

### 4.6 Internship Groups

#### FR-CSV-036 — Lists inside cells

Semicolon-separated codes and emails pack many-to-many membership into flat rows — the
only sane way to express groups in CSV. The separator is documented in the template's
example row, where it cannot be missed.

#### FR-CSV-037 — Every member must exist

Split, resolve each, skip the row on any ghost — a group pointing at a graduated student's
dead email is corruption wearing a group's name. All-or-nothing per row keeps membership
honest; partial groups never persist.

#### FR-CSV-038 — Group twins refused

Name matching keeps groups canonical: rerunning the grouping file updates nothing and
duplicates nothing. Group identity is its name, and names do not repeat.

#### FR-CSV-039 — Exports count instead of listing

Name plus member counts plus creation date: the export summarizes groups rather than
exploding them back into email lists. Counts answer the oversight question — "is every
student grouped?" — without re-parsing membership.

#### FR-CSV-040 — Filenames follow the trio

Groups variant, hyphenated per convention. Twenty-seven filenames, one rule.

### 4.7 Partnerships

#### FR-CSV-041 — Five columns of agreement

Company name, partner school, dates, MOU number: the administrative facts of a
partnership, exactly as the filing cabinet holds them. The file mirrors paperwork, which
is why admins fill it correctly the first time.

#### FR-CSV-042 — Unknown companies stop the row

Company resolved by name; absence skips with reason. A partnership with a phantom company
would dangle over nothing — the skip protects referential honesty while the rest of the
file proceeds.

#### FR-CSV-043 — Draft first, active later

Imports propose, humans approve: every imported partnership waits in draft for the
verification that only a person can do. Automatic activation from a file would launder
unverified claims into official-looking records.

#### FR-CSV-044 — Company-plus-date twins refused

The compound key catches the real duplicate — same company, same start — while allowing
genuine renewals with new dates. Single-column matching would either miss twins or block
renewals; the pair gets it right.

#### FR-CSV-045 — Exports add the verdict

The export appends status to the five import columns, showing which drafts became real.
The added column turns the file from an input log into an oversight report.

#### FR-CSV-046 — Filenames follow the trio

Partnerships variant of the convention. The last manager to learn it is the last manager
to need teaching.

### 4.8 Announcements

#### FR-CSV-047 — Four columns of broadcast

Title, body, target role, publish time: a complete announcement in one row. Bulk creation
serves term-start broadcast schedules, where a dozen notices go out in a week and typing
them one by one wastes the communications officer's mornings.

#### FR-CSV-048 — Drafts until deliberately released

Import never publishes — the separate batch action does, after human review. A mistyped
target role reaching every student is the nightmare this ordering prevents. The queue fills
silently; release stays loud and intentional.

#### FR-CSV-049 — Title-plus-time twins refused

The compound key distinguishes genuine repeats (same notice, new date) from accidental
double-rows. Announcements legitimately recur; the dedup rule respects that while still
catching the paste-twice error.

#### FR-CSV-050 — Exports show the broadcast record

Title, audience, schedule, status: what went out, to whom, when, and whether it did. The
export is the communications audit, not a re-import file — its shape serves oversight.

#### FR-CSV-051 — Filenames follow the trio

Announcements variant. Convention complete at nine managers.

### 4.9 Certificate Templates

#### FR-CSV-052 — Four columns of layout

Name, layout, content template, active flag: everything a certificate template needs, with
the flag honored only in the breach — see the next row. Bulk import serves schools
migrating a library of existing designs.

#### FR-CSV-053 — Files never activate

`is_active=0` forced regardless of file content: an unreviewed layout must not become the
face of the school's certification. Activation is a click after eyeballs, never a column
in a spreadsheet. Authority stays human.

#### FR-CSV-054 — Template twins refused

Name matching keeps the library canonical — two "Sertifikat PKL 2026" entries would fork
issuance across lookalikes. One name, one template, no ambiguity at print time.

#### FR-CSV-055 — Exports show the library state

Name, layout, active flag, creation date: the catalog view in file form. Which designs
exist and which are live — the two facts a certification officer needs before the term's
print run.

#### FR-CSV-056 — Filenames follow the trio, hyphenated long

The longest trio in the set, and still predictable. Length is fine; surprise is not.

### 4.10 Academic Years

#### FR-CSV-057 — Three columns of chronology

Name, start, end: the academic calendar in its barest form. Years are few — a school
imports a handful, not hundreds — but they gate everything, so even three rows deserve the
validated path.

#### FR-CSV-058 — Year twins refused

Name matching prevents the forked-calendar disaster: two "2026/2027" rows would split
placements across parallel years with no visible difference. Chronology must be singular.

#### FR-CSV-059 — Imports never crown a year

New years wait inactive for the deliberate activation that retires the old one. The
currently-reigning year is load-bearing for every placement query; a file must not
dethrone it as a side effect.

#### FR-CSV-060 — Exports add the reign

Status appended to the three import columns shows which year rules. The file answers "are
we in the right year?" at a glance — the question behind half of all placement confusion.

#### FR-CSV-061 — Filenames follow the trio

Years variant, closing the set. Twenty-seven filenames, one convention, zero surprises.

### 4.11 Contract Evolution

#### FR-CSV-062 — Row payloads grow up in phases

Today rows arrive as plain arrays while shapes still shift weekly; when a shape settles,
the processor widens to accept `Data|array` with `fromArray()` keeping old callers green;
only then does it narrow to the DTO alone. The phase table in §6.5 pins this path per
manager so migration is a checked box, not a folk memory. Velocity now, typing later, the
route written down.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-CSV-001 | Exports stream with constant memory at any dataset size | Flat peak 50 vs 10000 rows | P0 | F | Full |
| NFR-CSV-002 | Import and export fields are trimmed and escaped so spreadsheet content cannot inject markup | Zero unescaped cells rendered | P0 | A | Full |
| NFR-CSV-003 | Uploads enforce the size and MIME bound at the form before parsing | Refused before handler runs | P0 | F | Full |
| NFR-CSV-004 | Exports never include passwords, tokens, or recovery keys | Zero secrets in any export | P0 | A | Full |
| NFR-CSV-005 | Imports require admin-level authorization before any processing | Refused before file read | P0 | F | Full |
| NFR-CSV-006 | Malformed rows skip with reasons; the file never aborts on a bad row | All valid rows land | P0 | F | Full |
| NFR-CSV-007 | Header mismatch is detected before row processing and reported invalid | Zero writes on mismatch | P0 | F | Full |
| NFR-CSV-008 | Empty datasets export as headers-only files without errors | Valid file, headers only | P1 | F | Full |
| NFR-CSV-009 | Failed FK lookups skip the row with the missing entity named in the reason | Entity named per skip | P0 | F | Full |
| NFR-CSV-010 | Import success flashes created, skipped, and failed counts | All three counts shown | P1 | F | Full |
| NFR-CSV-011 | Invalid headers flash a distinct, actionable error | Distinguishable from success | P1 | F | Full |
| NFR-CSV-012 | Exports download under descriptive per-module filenames | Trio convention per manager | P1 | F | Full |
| NFR-CSV-013 | File inputs carry labels and accessible errors; buttons are keyboard-reachable with names | Labels + focus order correct | P1 | B | Full |
| NFR-CSV-014 | Flash messages announce through an `aria-live` region | Screen-reader announced | P1 | B | Full |
| NFR-CSV-015 | Every user-facing string in CSV flows passes through `__()` | Zero hardcoded strings | P0 | A | Full |
| NFR-CSV-016 | Translation keys exist in both `lang/en/` and `lang/id/` | Keys mirrored both locales | P0 | A | Full |
| NFR-CSV-017 | Every import writes a PII-masked SmartLogger activity entry with actor and counts | One entry per import | P0 | F | Full |

### 5.1 Efficiency & Robustness

#### NFR-CSV-001 — Memory that ignores file size

Streaming bounds the working set to the current row, so the export test runs fifty and ten
thousand rows and compares peaks. A future "simplification" into in-memory assembly fails
this row before it reaches production. Constant memory is asserted, not hoped.

#### NFR-CSV-006 — Bad rows cost one row, never the file

The processor catches per-row failures, records reasons, and continues — the valid 597 of
600 still land while 3 wait for correction. Aborting on first error would turn every
external spreadsheet into a lottery; resilience here is throughput with honesty.

#### NFR-CSV-007 — Wrong columns stop everything, cleanly

Header validation precedes all writes, so a mismatched file produces one error and zero
records. The alternative — hundreds of misaligned rows discovered at slip printing — is
exactly the disaster this ordering exists to prevent.

#### NFR-CSV-008 — Emptiness exports gracefully

A filter matching nothing still downloads a valid headers-only file instead of erroring.
Empty is a legitimate answer — "no suspended students, good" — and the export treats it as
one, keeping automated consumers parsing successfully.

#### NFR-CSV-009 — Skips that teach

Every FK skip names its missing entity, turning the summary into a correction checklist:
register the company, fix the spelling, rerun. Reasons are the difference between a failed
import and a guided one — same outcome count, opposite admin experience.

### 5.2 Security

#### NFR-CSV-002 — Spreadsheet content is untrusted input

Cells trim whitespace and escape markup because CSVs arrive from outside the trust
boundary: a formula-injection string or markup payload must die at the border, not execute
in an admin's browser or a student's export. Sanitize on the way in, escape on the way
out, both unconditionally.

#### NFR-CSV-003 — The gate stands before the parser

Size and type enforcement at the form means hostile or accidental monster files never reach
parsing code. Validation order is a security decision: cheap checks first, expensive work
only for files that passed them.

#### NFR-CSV-004 — Secrets never leave through exports

No password, token, or recovery key appears in any column list, and the scan asserts the
absence. An export emailed, printed, or left on a shared drive must be safe by
construction — the sensitive fields simply are not in the vocabulary exports speak.

#### NFR-CSV-005 — Authorization precedes processing

Admin-level checks run before the file opens, so unauthorized uploads fail closed with
nothing parsed, nothing written, nothing leaked about expected columns. The file never
teaches an attacker the schema.

#### NFR-CSV-017 — Imports leave a masked audit trail

One SmartLogger activity entry per import — actor, manager, counts — with PII masked
before either channel. When a bad import is discovered weeks later, this entry names the
uploader and the scale; masking lets the trail be shown without leaking the rows
themselves.

### 5.3 Experience & Localization

#### NFR-CSV-010 — Success reports in three numbers

Created, skipped, failed — the flash carries all three, because an admin who sees only
"412 created" cannot know 3 failed. Completeness of the summary is what makes the failed
rows get fixed instead of forgotten.

#### NFR-CSV-011 — Failure looks like failure

The invalid-header flash is visually and textually distinct from success — different key,
different tone, actionable wording. Confusing the two states wastes the exact evening the
coordinator cannot afford to waste.

#### NFR-CSV-012 — Downloads arrive pre-named

Descriptive per-module filenames mean the downloads folder stays navigable across nine
managers and many terms. Naming is the cheapest UX in the system and among the most used.

#### NFR-CSV-013 — Inputs operable by everyone

Labeled file inputs, announced errors, keyboard-reachable named buttons: the upload form
works for screen-reader and keyboard operators alike. These travel as one row because they
ship in one template and regress together.

#### NFR-CSV-014 — Messages that speak up

The `aria-live` region announces flashes to assistive tech, so success and failure reach
every operator, not just sighted ones. A summary nobody hears is a summary that failed its
only job.

#### NFR-CSV-015 — Two languages in the pipeline

Every flash, error, and summary resolves through `__()` — CSV flows touch every module,
so one hardcoded string here multiplies across nine managers. The helper keeps the count
at zero by construction.

#### NFR-CSV-016 — Keys mirrored, both locales

Indonesian and English files carry the same keys, asserted by the locale scan. A missing
key surfaces as a raw identifier to the admin uploading at midnight — unacceptable for the
flow that onboards the whole school.

---

## 6. API / Data Contracts

### 6.1 CsvHandler Service

```php
// app/Modules/Core/Support/CsvHandler.php
final class CsvHandler
{
    public function export(
        Collection $items,
        array $headers,
        callable $rowMapper,
        string $filename = 'export.csv',
    ): StreamedResponse; // fputcsv to php://output, constant memory

    public function downloadTemplate(
        array $headers,
        array $exampleRow,
        string $filename = 'template.csv',
    ): StreamedResponse;

    public function import(
        string $filePath,
        callable $rowProcessor,   // fn(array $row): ?CsvRowResult + reason capture
        ?array $expectedHeaders = null,
    ): array; // ['created' => int, 'skipped' => int, 'failed' => int,
              //  'invalid' => bool, 'errors' => array<int, string>]
}
```

### 6.2 CsvRowResult Enum

```php
// app/Modules/Core/Enums/CsvRowResult.php
enum CsvRowResult: string implements LabelEnum
{
    case CREATED = 'created';
    case SKIPPED = 'skipped';
    case FAILED = 'failed';   // with per-row reason captured by the handler

    public function label(): string; // __('core.csv.{value}')
}
// null return = silent skip (blank lines), uncounted in the summary.
```

### 6.3 Per-Row Failure Reporting Contract

```php
// Each processed row yields: ['line' => int, 'result' => CsvRowResult,
//   'reason' => ?string] — line = 1-based file line incl. header offset.
// Reasons use translation keys with the offending value as placeholder,
// never raw user content unescaped. The summary flash aggregates counts;
// the errors array carries line+reason pairs for admin review.
```

### 6.4 Manager Implementation Convention

```php
// app/Modules/{Module}/Domain/{Domain}/Livewire/{Name}Manager.php
class XxxManager extends BaseRecordManager
{
    public Property $importFile;

    public function import(CsvHandler $csv, CreateXxxAction $create): void
    {
        $this->authorize('create', Xxx::class);
        $this->validate(['importFile' => 'required|file|mimes:csv,txt|max:2048']);
        $summary = $csv->import(
            $this->importFile->getRealPath(),
            fn (array $row) => $this->processImportRow($row, $create),
            expectedHeaders: ['col1', 'col2', 'col3'],
        );
        $this->importFile = null;
        $this->flashSummary($summary); // common.actions.import_summary / import_invalid
    }

    public function export(CsvHandler $csv): StreamedResponse
    {
        $items = $this->applySearch($this->applyFilters($this->query()->get()));
        return $csv->export($items, $this->exportColumns(), $this->exportRowMapper(), 'xxxs.csv');
    }

    public function exportSelected(CsvHandler $csv): ?StreamedResponse
    {
        if (empty($this->selectedIds)) return null;
        $items = $this->query()->whereIn('id', $this->selectedIds)->get();
        return $csv->export($items, $this->exportColumns(), $this->exportRowMapper(), 'xxxs-selected.csv');
    }

    public function downloadTemplate(CsvHandler $csv): StreamedResponse
    {
        return $csv->downloadTemplate($this->exportColumns(), $this->exportExampleRow(), 'xxxs-template.csv');
    }
}
```

### 6.5 DTO Migration Phases (Row Payloads)

| Phase | Convention | Trigger |
|-------|------------|---------|
| Start | `processImportRow(array $row, ...)` | Column shape still shifting |
| Stabilize | `processImportRow(CompanyData\|array $row, ...)` with `fromArray()` | Shape settled; old callers kept green |
| Final | `processImportRow(CompanyData $row, ...)` | DTO the only contract |

Managers migrate independently per FR-CSV-062; `CompanyData` (FR-CSV-028) is the
reference implementation. Mixed phases mid-migration are expected and temporary.

### 6.6 Per-Manager Column Matrix

| Manager | Import columns | Natural key | Export adds |
|---------|---------------|-------------|-------------|
| Users | full_name, email, phone | email | username, address |
| Departments | name, description | name | — |
| Companies | 7 columns (§4.4) | name | — |
| Internships | code, title, year/dept/company names | code | dates, status |
| Groups | name, codes list, emails list | name | counts, created_at |
| Partnerships | company, school, dates, mou | company+start | status |
| Announcements | title, body, role, publish_at | title+publish_at | status |
| Templates | name, layout, content, active | name | created_at |
| Academic years | name, start, end | name | status |

---

## 7. Design Decisions

Choices behind the shared pipeline; each names the shape it produced.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-CSV-001 | Skip-on-duplicate, never update-on-import | P0 | — | — |
| DD-CSV-002 | Streaming I/O both directions for constant memory | P0 | — | — |
| DD-CSV-003 | `CsvRowResult` enum with created, skipped, and failed outcomes | P0 | — | — |
| DD-CSV-004 | One shared `CsvHandler` across all managers | P0 | — | — |
| DD-CSV-005 | Header validation before any row processing | P0 | — | — |
| DD-CSV-006 | Foreign-key resolution per row, never pre-import | P1 | — | — |
| DD-CSV-007 | Opt-in convention for new managers, not forced inheritance | P1 | — | — |

### 7.1 Correctness Shape

#### DD-CSV-001 — Imports add, they never merge

Update-via-CSV demands conflict answers nobody has — which columns win? who audited the
overwrite? — so duplicates skip and updates stay manual. Onboarding, the actual bulk job,
wants exactly this: new rows in, existing rows untouched, a summary saying which was
which. Predictable beats clever at 11pm before term start.

#### DD-CSV-002 — Rows flow, they never pool

`fgetcsv()` in, `fputcsv()` to `php://output` out: memory flat from fifty rows to fifty
thousand. The price — no whole-file pre-validation — is paid in the currency of header
checks plus per-row reasons, which cover everything pre-validation would have caught that
matters. Scale safety without ceremony.

#### DD-CSV-003 — Outcomes as types, reasons as data

An enum makes the three fates greppable, autocompletable, and unforgeable — no string
typos silently becoming fourth outcomes. The `FAILED`-with-reason case, added for the
per-row reporting contract, separates "known duplicate" from "needs attention" in both
code and summary. `null` keeps its narrow job: blank lines pass without comment.

#### DD-CSV-004 — One handler, nine tenants

CSV mechanics — BOM stripping, header comparison, streaming, summary assembly — are
identical everywhere, so they live once in Core with columns and callbacks injected. The
alternative, nine sibling implementations, had already started disagreeing about header
case-sensitivity before consolidation. Sameness enforced by sharing, not by hoping.

### 7.2 Adoption Shape

#### DD-CSV-005 — Refuse the file before touching the rows

A wrong-column file processed row by row plants hundreds of misaligned records before
anyone notices the headers. Validating first converts that catastrophe into one clear
error and zero writes. Case-insensitive comparison forgives the capitalization differences
spreadsheets love to introduce.

#### DD-CSV-006 — Resolve references where the row is

Per-row FK lookups let valid rows succeed beside broken ones — pre-import validation would
need every related table loaded and would still answer at file granularity. Partial success
with named reasons beats all-or-nothing purity when the coordinator can fix three company
names and rerun. The summary, not the schema, absorbs the incompleteness.

#### DD-CSV-007 — Convention strong enough to follow, light enough to skip

Event-driven entities with no tabular source — logbooks, incidents, assignments — must not
grow empty templates to satisfy an inheritance rule. Opt-in keeps the convention honest:
managers with real bulk needs adopt four methods and inherit the guarantees; the rest stay
clean. Documentation plus the base's helpers do the recruiting that force cannot.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Manager coverage | 9 managers with the 4-method contract | Count of `import()` methods |
| Convention adoption | New tabular managers follow the contract | Review of new manager PRs |
| Import integrity | 100% duplicate skip on natural keys | `SKIPPED` assertions per manager |
| FK honesty | Every orphan row skipped with named reason | Summary reason assertions |
| Empty export | Headers-only file, no error | Empty-collection export test |
| Summary accuracy | Counts match actual outcomes incl. failures | Summary-vs-database reconciliation |
| Template correctness | Template imports cleanly when filled | Round-trip template test |
| Filename convention | Trio per manager, all nine | Filename assertions |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [base-classes](SE5Q9-base-classes.md) | `BaseRecordManager` shared infrastructure |
| [user-crud-and-status](95EVB-user-crud-and-status.md) | `User` model, `CreateUserAction` |
| [department-management](4HWSB-department-management.md) | `Department` model, create Action |
| [company-management](XI3LB-company-management.md) | `Company` model, create Action, `CompanyData` |
| [internship-lifecycle](7C5WM-internship-lifecycle.md) | `Internship` model, create Action |
| [internship-groups](IT0OE-internship-groups.md) | `InternshipGroup` model |
| [partnership-management](NTHQA-partnership-management.md) | `Partnership` model |
| [announcement-system](3S55V-announcement-system.md) | `Announcement` model |
| [certification](J0M04-certification.md) | `CertificateTemplate` model |
| [academic-year-management](XW6F5-academic-year-management.md) | `AcademicYear` model |

### Build Guide

Shared handler plus enum plus convention: bulk capability lands once and serves nine
managers. New tabular managers adopt the four methods; credential-bearing imports flow on
to slips.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [account-slips](EWCZ0-account-slips.md) | Slips distribute credentials for CSV-provisioned users |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References

- [Spec registry](index.md) — Enrollment phase; this spec is `O2KCR` with status Full
- [User CRUD & status](95EVB-user-crud-and-status.md) — `CreateUserAction` reused by user import
- [Company management](XI3LB-company-management.md) — `CompanyData` DTO reference for §6.5
- [Account slips](EWCZ0-account-slips.md) — credential distribution for imported users
- [Architecture](D2FT3-architecture.md) — Action Triad, DTO boundary, Entity rules
- [Project initialization](QLHDO-project-initialization.md) — global FR-GLB/NFR every row inherits
