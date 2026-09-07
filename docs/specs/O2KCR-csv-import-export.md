# Bulk Import & Export — Cross-Module Tabular Data Operations

> **Spec ID:** O2KCR

## Description

Specification of Internara's cross-module bulk import and export subsystem for tabular data
files. Defines the shared `CsvHandler` service and `CsvRowResult` enum that power bulk operations
across all record managers in the system — covering file upload, row-by-row validation, duplicate
detection, per-row result reporting, template download, and filtered export. The infrastructure
currently powers **9 managers** (Users, Departments, Companies, Internships, Internship Groups,
Partnerships, Announcements, Certificate Templates, Academic Years) and is the canonical place to
add bulk import/export to additional managers as new bulk operations are introduced.

The system stores data in tabular form, supports CSV import/export today, and is designed to
accommodate Excel/TSV via format-agnostic column-mapping without changing the manager-side API.

---

## 1. Problem Statements

### PS-1 — Bulk Onboarding at Scale

Schools may have 500+ students, dozens of departments, hundreds of partner companies, and many
internships to onboard at the start of an academic year. Manual one-by-one creation through forms
is impractical and error-prone at this volume. Bulk import must handle large datasets with
consistent validation, deduplication, and clear feedback on what was created versus skipped.

### PS-2 — Duplicate Detection Across Modules

Each module has a natural uniqueness constraint: users by email, departments by name, companies
by name, internships by code, partnerships by company+year. Without duplicate detection during
import, admins could create redundant records that break referential integrity (e.g., duplicate
user emails causing login ambiguity, duplicate internships confusing students). The import
process must detect existing records and skip duplicates transparently.

### PS-3 — Credential Generation for User Imports

When importing users (especially students), each new account requires a unique username and
temporary password. Manual credential generation for 500+ students is untenable. The import
pipeline must auto-generate credentials for every valid row and make them available for
distribution via account slips.

### PS-4 — Per-Row Error Reporting

CSV files from external systems often contain malformed rows — missing required fields, invalid
email formats, encoding issues, or rows referencing unknown foreign keys. Rather than failing
the entire import on the first bad row, the system must process all rows, report per-row
results (created, skipped, error), and present a clear summary to the admin.

### PS-5 — Filtered Export for Reporting and Migration

Administrators need to export subsets of data for offline reporting, migration to other systems,
spreadsheet analysis, and audit. Exports must respect the current search and filter state in the
management UI so admins can narrow results before downloading.

### PS-6 — Cross-Cutting Pattern for New Managers

Adding a new manager (e.g., `EventManager` for an event module) shouldn't require re-inventing
the import/export pipeline. The bulk import/export infrastructure must be reusable as a
convention: any manager that extends `BaseRecordManager` can opt in by implementing 4 methods
(`import`, `export`, `exportSelected`, `downloadTemplate`) using the shared `CsvHandler`.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide a shared `CsvHandler` service that every record manager can call for import/export/template |
| G2  | Provide a type-safe `CsvRowResult` enum (`CREATED`, `SKIPPED`) for per-row status reporting |
| G3  | Auto-generate credentials (username, password) for each valid user row during import |
| G4  | Report per-row results with summary flash message (created count + skipped count) |
| G5  | Provide filtered export that respects current search and filter state in the management UI |
| G6  | Support per-selection export (`exportSelected`) for downloading only checked rows |
| G7  | Provide downloadable templates with correct headers and placeholder example rows |
| G8  | Validate header row against expected columns and reject mismatched files |
| G9  | Limit import file size to 2048KB and restrict MIME types to csv/txt |
| G10 | Cover **9 managers** in the system: Users, Departments, Companies, Internships, Internship Groups, Partnerships, Announcements, Certificate Templates, Academic Years |
| G11 | Provide a clear convention for future managers to opt into bulk import/export |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Bulk import with update-on-duplicate (merge or overwrite existing records) |
| NG2  | Real-time streaming import for files exceeding memory limits |
| NG3  | Import scheduling or queue-based async processing |
| NG4  | Native Excel/TSV rendering (must be reformatted to CSV; future enhancement) |
| NG5  | Import of related/nested data in one file (e.g., users with their department FK) — must be split into separate imports |
| NG6  | Column mapping UI (admin must prepare CSV with correct column order) |
| NG7  | Cross-table referential validation at import time (FKs must already exist in the database) |

---

## 3. User Stories / Use Cases

### UC-O2KCR-1 — Admin Imports Users via Tabular File

**Actor:** Admin
**Preconditions:** File prepared with columns: full_name, email, phone
**Flow:** Same as O2KCR import flow — file upload, header validation, per-row processing, summary flash.
**Postconditions:** Users created, credentials auto-generated, duplicates skipped, summary displayed

### UC-O2KCR-2 — Admin Imports Departments

**Actor:** Admin
**Preconditions:** File with columns: name, description
**Flow:** Standard import flow
**Postconditions:** Departments created, duplicates skipped

### UC-O2KCR-3 — Admin Imports Companies

**Actor:** Admin
**Preconditions:** File with columns: name, address, phone, email, website, description, industry_sector
**Flow:** Standard import flow with DTO validation
**Postconditions:** Companies created, duplicates skipped

### UC-O2KCR-4 — Admin Imports Internships

**Actor:** Admin
**Preconditions:** File with columns: code, title, academic_year, department_name, company_name
**Flow:**
1. Admin uploads file at Program → Internships → Import
2. For each row, lookup existing `AcademicYear` by name, `Department` by name, `Company` by name
3. If any lookup misses → skip the row with reason "Referenced {entity} not found"
4. If all lookups succeed → create internship via `CreateInternshipAction`
**Postconditions:** Internships created with FKs resolved from existing data; orphan rows skipped

### UC-O2KCR-5 — Admin Imports Internship Groups

**Actor:** Admin
**Preconditions:** File with columns: name, internship_codes, student_emails
**Flow:** Each row's `internship_codes` (semicolon-separated) and `student_emails` (semicolon-separated) are split and looked up
**Postconditions:** Groups created with FK relationships resolved

### UC-O2KCR-6 — Admin Imports Partnerships

**Actor:** Admin
**Preconditions:** File with columns: company_name, partner_school, start_date, end_date, mou_number
**Flow:** Lookup `Company` by name; skip if not found. Create partnership with status `DRAFT`.
**Postconditions:** Partnership drafts created, duplicates skipped by company_name + start_date

### UC-O2KCR-7 — Admin Bulk-Imports Announcements

**Actor:** Admin
**Preconditions:** File with columns: title, body, target_role, publish_at
**Flow:** Each row creates an announcement with auto-generated `target_role` parsing
**Postconditions:** Announcements created in `DRAFT` status; published via separate batch action

### UC-O2KCR-8 — Admin Imports Certificate Templates

**Actor:** Admin
**Preconditions:** File with columns: name, layout, content_template, is_active
**Flow:** Each row creates a template with `is_active=0` (requires manual activation)
**Postconditions:** Templates created in inactive state, can be edited before activation

### UC-O2KCR-9 — Admin Imports Academic Years

**Actor:** Admin
**Preconditions:** File with columns: name, start_date, end_date
**Flow:** Standard import flow
**Postconditions:** Academic years created, duplicates skipped

### UC-O2KCR-10 — Admin Exports Filtered Data

**Actor:** Admin
**Preconditions:** Admin has applied search/filter on the management UI
**Flow:** Clicks "Export" → query builder applies current filters → CsvHandler streams file
**Postconditions:** File downloaded with only matching rows

### UC-O2KCR-11 — Admin Exports Selected Rows

**Actor:** Admin
**Preconditions:** Admin has selected specific rows via checkboxes
**Flow:** Clicks "Export Selected" → query filters by `whereIn('id', $this->selectedIds)`
**Postconditions:** File downloaded with only selected rows

### UC-O2KCR-12 — Admin Downloads Template

**Actor:** Admin
**Preconditions:** None
**Flow:** Clicks "Download Template" → file with headers + one example row
**Postconditions:** Template downloaded with correct column format

---

## 4. Functional Requirements

### Cross-Module Infrastructure

| ID   | Requirement |
| ---- | ----------- |
| FR-O2KCR-IE1 | All import operations must use the shared `CsvHandler::import()` service |
| FR-O2KCR-IE2 | All export operations must use the shared `CsvHandler::export()` service returning `StreamedResponse` |
| FR-O2KCR-IE3 | All template downloads must use `CsvHandler::downloadTemplate()` |
| FR-O2KCR-IE4 | Import must validate file: max 2048KB, MIME types `csv` or `txt` |
| FR-O2KCR-IE5 | Import must validate header row against expected columns and reject mismatched files with `invalid: true` |
| FR-O2KCR-IE6 | All imports must deduplicate by the module's natural uniqueness key (email for users, name for departments/companies, code for internships, etc.) |
| FR-O2KCR-IE7 | Row processor callback returns `CsvRowResult::CREATED`, `CsvRowResult::SKIPPED`, or `null` (silent skip) |
| FR-O2KCR-IE8 | Import summary flash must use `common.actions.import_summary` with `created` and `skipped` counts |
| FR-O2KCR-IE9 | Invalid header files must flash `common.actions.import_invalid` error message |
| FR-O2KCR-IE10 | Export must respect current search and filter state from the manager's `applySearch()` and `applyFilters()` |
| FR-O2KCR-IE11 | `exportSelected()` must export only the rows selected via checkboxes in the UI |
| FR-O2KCR-IE12 | `downloadTemplate()` must provide a template with headers and one placeholder example row |
| FR-O2KCR-IE13 | All imports must null out the `importFile` property after processing |
| FR-O2KCR-IE14 | All user-facing strings in CSV operations must use `__()` translation helper |

### UserManager

| ID   | Requirement |
| ---- | ----------- |
| FR-O2KCR-UM1 | Import columns: `full_name`, `email`, `phone` |
| FR-O2KCR-UM2 | Duplicate detection: skip rows where `email` matches an existing user |
| FR-O2KCR-UM3 | Auto-generate credentials (username from email, random 12-char password) via `CreateUserAction` |
| FR-O2KCR-UM4 | Export columns: `full_name`, `email`, `username`, `phone`, `address` |
| FR-O2KCR-UM5 | Filenames: `users.csv`, `users-selected.csv`, `users-template.csv` |

### DepartmentManager

| ID   | Requirement |
| ---- | ----------- |
| FR-O2KCR-DM1 | Import columns: `name`, `description` |
| FR-O2KCR-DM2 | Duplicate detection: skip rows where `name` matches an existing department |
| FR-O2KCR-DM3 | Authorize `create` on Department model before import |
| FR-O2KCR-DM4 | Export columns: `name`, `description` |
| FR-O2KCR-DM5 | Filenames: `departments.csv`, `departments-selected.csv`, `departments-template.csv` |

### CompanyManager

| ID   | Requirement |
| ---- | ----------- |
| FR-O2KCR-CM1 | Import columns: `name`, `address`, `phone`, `email`, `website`, `description`, `industry_sector` |
| FR-O2KCR-CM2 | Duplicate detection: skip rows where `name` matches an existing company |
| FR-O2KCR-CM3 | Data must pass through `CompanyData` DTO for validation before `CreateCompanyAction` |
| FR-O2KCR-CM4 | Export columns: same 7 import columns |
| FR-O2KCR-CM5 | Filenames: `companies.csv`, `companies-selected.csv`, `companies-template.csv` |

### InternshipManager

| ID   | Requirement |
| ---- | ----------- |
| FR-O2KCR-IM1 | Import columns: `code`, `title`, `academic_year_name`, `department_name`, `company_name` |
| FR-O2KCR-IM2 | Each row must resolve `AcademicYear` by name, `Department` by name, `Company` by name — rows with missing FKs must be skipped with reason |
| FR-O2KCR-IM3 | Duplicate detection: skip rows where `code` matches an existing internship |
| FR-O2KCR-IM4 | Export columns: `code`, `title`, `academic_year`, `department`, `company`, `start_date`, `end_date`, `status` |
| FR-O2KCR-IM5 | Filenames: `internships.csv`, `internships-selected.csv`, `internships-template.csv` |

### InternshipGroupManager

| ID   | Requirement |
| ---- | ----------- |
| FR-O2KCR-IG1 | Import columns: `name`, `internship_codes` (semicolon-separated), `student_emails` (semicolon-separated) |
| FR-O2KCR-IG2 | Each cell value must be split and resolved; missing FKs skip the row |
| FR-O2KCR-IG3 | Duplicate detection: skip rows where `name` matches an existing group |
| FR-O2KCR-IG4 | Export columns: `name`, `internship_count`, `student_count`, `created_at` |
| FR-O2KCR-IG5 | Filenames: `internship-groups.csv`, `internship-groups-selected.csv`, `internship-groups-template.csv` |

### PartnershipManager

| ID   | Requirement |
| ---- | ----------- |
| FR-O2KCR-PM1 | Import columns: `company_name`, `partner_school`, `start_date`, `end_date`, `mou_number` |
| FR-O2KCR-PM2 | `Company` must be resolved by name; missing companies skip the row |
| FR-O2KCR-PM3 | New partnerships start in `DRAFT` status; activation requires admin action |
| FR-O2KCR-PM4 | Duplicate detection: skip rows where `(company_name, start_date)` matches existing partnership |
| FR-O2KCR-PM5 | Export columns: `company`, `partner_school`, `start_date`, `end_date`, `mou_number`, `status` |
| FR-O2KCR-PM6 | Filenames: `partnerships.csv`, `partnerships-selected.csv`, `partnerships-template.csv` |

### AnnouncementManager

| ID   | Requirement |
| ---- | ----------- |
| FR-O2KCR-AM1 | Import columns: `title`, `body`, `target_role`, `publish_at` |
| FR-O2KCR-AM2 | Imported announcements start in `DRAFT` status; bulk publish is a separate action |
| FR-O2KCR-AM3 | Duplicate detection: skip rows where `(title, publish_at)` matches existing announcement |
| FR-O2KCR-AM4 | Export columns: `title`, `target_role`, `publish_at`, `status` |
| FR-O2KCR-AM5 | Filenames: `announcements.csv`, `announcements-selected.csv`, `announcements-template.csv` |

### CertificateTemplateManager

| ID   | Requirement |
| ---- | ----------- |
| FR-O2KCR-CT1 | Import columns: `name`, `layout`, `content_template`, `is_active` |
| FR-O2KCR-CT2 | Imported templates are created with `is_active=0` regardless of input; admin must manually activate |
| FR-O2KCR-CT3 | Duplicate detection: skip rows where `name` matches an existing template |
| FR-O2KCR-CT4 | Export columns: `name`, `layout`, `is_active`, `created_at` |
| FR-O2KCR-CT5 | Filenames: `certificate-templates.csv`, `certificate-templates-selected.csv`, `certificate-templates-template.csv` |

### AcademicYearManager

| ID   | Requirement |
| ---- | ----------- |
| FR-O2KCR-AY1 | Import columns: `name`, `start_date`, `end_date` |
| FR-O2KCR-AY2 | Duplicate detection: skip rows where `name` matches an existing academic year |
| FR-O2KCR-AY3 | New academic years start in `INACTIVE` status; activation requires admin action |
| FR-O2KCR-AY4 | Export columns: `name`, `start_date`, `end_date`, `status` |
| FR-O2KCR-AY5 | Filenames: `academic-years.csv`, `academic-years-selected.csv`, `academic-years-template.csv` |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-O2KCR-P1 | CSV import of 100 rows must complete in under 30 seconds |
| NFR-O2KCR-P2 | CSV export must use `StreamedResponse` to avoid loading entire dataset into memory |
| NFR-O2KCR-P3 | Template download must complete instantly (header + one example row) |
| NFR-O2KCR-S1 | All imports must sanitize input fields — trim whitespace, prevent XSS in exported data |
| NFR-O2KCR-S2 | Import file upload must enforce max size and MIME type at the form level |
| NFR-O2KCR-S3 | Export must not include sensitive fields (passwords, tokens, recovery keys) |
| NFR-O2KCR-S4 | Import operations must require admin-level authorization before processing |
| NFR-O2KCR-R1 | CSV import must handle malformed rows gracefully — skip and continue, never halt the entire import |
| NFR-O2KCR-R2 | Header mismatch must be detected before row processing begins and return `invalid: true` |
| NFR-O2KCR-R3 | Export must handle empty datasets without errors (return CSV with headers only) |
| NFR-O2KCR-R4 | When an FK lookup fails (e.g., referenced Company not found), the row must be skipped with a clear reason in the summary |
| NFR-O2KCR-U1 | Import success must display created and skipped counts via flash message |
| NFR-O2KCR-U2 | Import failure (invalid headers) must display a clear error flash message |
| NFR-O2KCR-U3 | Export must trigger file download with a descriptive filename per module |
| NFR-O2KCR-A1 | CSV import form must have associated labels for the file input and accessible error messages |
| NFR-O2KCR-A2 | Import/export buttons must be keyboard-navigable and have accessible labels |
| NFR-O2KCR-A3 | Flash messages must be announced to screen readers via `aria-live` region |
| NFR-O2KCR-L1 | All user-facing strings in CSV operations must use `__()` translation helper |
| NFR-O2KCR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |

---

## 6. API / Data Contracts

### 6.1 CsvHandler Service

```php
// app/Modules/Core/Support/CsvHandler.php
final class CsvHandler
{
    /**
     * Stream a CSV file from a collection of items.
     */
    public function export(
        Collection $items,
        array $headers,
        callable $rowMapper,
        string $filename = 'export.csv',
    ): StreamedResponse;

    /**
     * Download a CSV template with headers and one example row.
     */
    public function downloadTemplate(
        array $headers,
        array $exampleRow,
        string $filename = 'template.csv',
    ): StreamedResponse;

    /**
     * Import a CSV file, processing each row via callback.
     * Validates expected headers if provided.
     */
    public function import(
        string $filePath,
        callable $rowProcessor,
        ?array $expectedHeaders = null,
    ): array; // ['created' => int, 'skipped' => int, 'invalid' => bool]
}
```

### 6.2 CsvRowResult Enum

```php
// app/Modules/Core/Enums/CsvRowResult.php
enum CsvRowResult: string implements LabelEnum
{
    case CREATED = 'created';
    case SKIPPED = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => __('core.csv.created'),
            self::SKIPPED => __('core.csv.skipped'),
        };
    }
}
```

### 6.3 Manager Implementation Convention

Every record manager that opts in to bulk import/export must implement four methods using
`CsvHandler`:

```php
// app/Modules/{Module}/Domain/{Domain}/Livewire/{Name}Manager.php
class XxxManager extends BaseRecordManager
{
    public Property $importFile;

    /** Authorize + delegate to CsvHandler::import() */
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
        $this->flashSummary($summary);
    }

    /** Apply current query + search + filters, stream via CsvHandler::export() */
    public function export(CsvHandler $csv): StreamedResponse
    {
        $items = $this->applySearch($this->applyFilters($this->query()->get()));
        return $csv->export($items, $this->exportColumns(), $this->exportRowMapper(), 'xxxs.csv');
    }

    /** Export only checked rows */
    public function exportSelected(CsvHandler $csv): ?StreamedResponse
    {
        if (empty($this->selectedIds)) return null;
        $items = $this->query()->whereIn('id', $this->selectedIds)->get();
        return $csv->export($items, $this->exportColumns(), $this->exportRowMapper(), 'xxxs-selected.csv');
    }

    /** Stream template with headers + one example row */
    public function downloadTemplate(CsvHandler $csv): StreamedResponse
    {
        return $csv->downloadTemplate(
            $this->exportColumns(),
            $this->exportExampleRow(),
            'xxxs-template.csv'
        );
    }
}
```

### 6.4 File Upload Property

```php
// Shared across all managers with bulk import
public Property $importFile;

// Livewire file upload with validation:
// ['required', 'file', 'mimes:csv,txt', 'max:2048']
```

---

## 7. Design Decisions

### DD-1 — Skip-on-Duplicate (Not Update)

**Decision:** CSV import skips rows with existing uniqueness keys (email for users, name for
departments/companies, code for internships) and does not update or merge existing records.

**Rationale:** Import is for onboarding new records, not updating existing ones. Update-via-CSV
would require conflict resolution logic and audit trail complexity. Skip-on-duplicate is
predictable, safe, and matches admin expectations during initial bulk onboarding.

**Trade-off:** Admin must manually update existing records outside the import flow. Acceptable
because updates are infrequent compared to initial onboarding.

### DD-2 — Streaming I/O for Memory Efficiency

**Decision:** `CsvHandler::import()` processes rows one at a time via `fgetcsv()`; export streams
via `fputcsv()` to `php://output`.

**Rationale:** CSV files with 500+ rows could exhaust PHP memory if loaded into an array.
Streaming keeps memory usage constant regardless of file size.

**Trade-off:** Cannot pre-validate all rows before processing. Mitigated by the `invalid` flag
catching header mismatches before row processing, and individual row errors being caught by
the callback returning null or SKIPPED.

### DD-3 — Type-Safe CsvRowResult Enum

**Decision:** Row processing status is a `CsvRowResult` enum (`CREATED`, `SKIPPED`), not string
constants or integers.

**Rationale:** Enums provide type safety, IDE autocompletion, and self-documenting code. The
`null` return value is reserved for silent skip (empty rows), while `SKIPPED` counts in the
summary — this distinction makes empty rows invisible while duplicate skips are reported.

### DD-4 — Shared CsvHandler Service Across All Managers

**Decision:** All managers share a single `CsvHandler` service in `App\Core\Support`, not
module-specific CSV handlers.

**Rationale:** CSV operations (read rows, write rows, stream response, validate headers) are
identical across modules. The only variation is the row processor callback and column headers,
which are passed as parameters. A shared service eliminates code duplication and ensures
consistent behavior (file handling, error formatting, streaming).

### DD-5 — Header Validation Before Row Processing

**Decision:** `CsvHandler::import()` validates the header row against expected column names
before processing any data rows.

**Rationale:** Processing rows with wrong columns would produce garbage data. Early header
validation prevents silent data corruption. Case-insensitive comparison accommodates minor
capitalization differences.

### DD-6 — FK Resolution at Row Level, Not Pre-Import

**Decision:** For managers with foreign keys (Internship, InternshipGroup, Partnership), the
row processor resolves FKs from existing data and skips rows with missing references.

**Rationale:** Pre-import validation would require loading all related tables, which is
expensive and not reusable. Per-row resolution lets the import proceed with valid rows even when
some references are missing.

**Trade-off:** Imports may create partial datasets (valid rows succeed, missing-FK rows skip).
Mitigated by clear summary reporting and the row processor returning `SKIPPED` with a reason.

### DD-7 — Cross-Module Manager Convention (Not Force)

**Decision:** Bulk import/export is a convention, not a hard inheritance requirement. New
managers opt in by implementing the 4-method contract using `CsvHandler`.

**Rationale:** Some entities (Assignment, Logbook, Incident) are event-driven and have no
natural tabular source. Forcing bulk import on them would create empty templates.

**Trade-off:** New managers may forget to opt in. Mitigated by the convention being documented
in this spec and the `BaseRecordManager` providing shared helpers.

---

## 8. Success Metrics

### 8.1 Coverage

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Managers with bulk import/export | 9 (Users, Departments, Companies, Internships, Groups, Partnerships, Announcements, Templates, Academic Years) | Count of `import()` methods in managers |
| Convention adoption | New managers follow the 4-method contract | Code review of new manager PRs |

### 8.2 Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| CSV import 100 rows (any manager) | < 30 seconds | `import()` end-to-end time |
| CSV export | Streaming, constant memory | `StreamedResponse` for any size dataset |
| Template download | < 100ms | `downloadTemplate()` end-to-end time |

### 8.3 Data Integrity

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Duplicate detection | 100% skip existing uniqueness keys | `CsvRowResult::SKIPPED` for matching rows |
| FK resolution | Orphan rows skipped with reason | Summary flash shows FK-skip count |
| Empty dataset export | Headers-only CSV, no error | `StreamedResponse` with empty collection |

### 8.4 User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Import success feedback | Created + skipped counts | Flash message with `import_summary` key |
| Import failure feedback | Clear invalid-header error | Flash message with `import_invalid` key |
| Template accuracy | Correct headers and example row | `downloadTemplate()` matches import expectations |
| Filename convention | Descriptive per module | `{entity}.csv`, `{entity}-selected.csv`, `{entity}-template.csv` |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [base-classes.md](SE5Q9-base-classes.md) | `BaseRecordManager` shared infrastructure |
| [user-crud-and-status.md](95EVB-user-crud-and-status.md) | `User` model, `CreateUserAction` |
| [department-management.md](4HWSB-department-management.md) | `Department` model, `CreateDepartmentAction` |
| [company-management.md](XI3LB-company-management.md) | `Company` model, `CreateCompanyAction`, `CompanyData` |
| [internship-lifecycle.md](7C5WM-internship-lifecycle.md) | `Internship` model, `CreateInternshipAction` |
| [internship-groups.md](IT0OE-internship-groups.md) | `InternshipGroup` model |
| [partnership-management.md](NTHQA-partnership-management.md) | `Partnership` model |
| [announcement-system.md](3S55V-announcement-system.md) | `Announcement` model |
| [certification.md](J0M04-certification.md) | `CertificateTemplate` model |
| [academic-year-management.md](XW6F5-academic-year-management.md) | `AcademicYear` model |

### Build Guide
After implementing this spec, the system has reusable bulk import/export with template download,
header validation, error reporting, and FK resolution. The shared `CsvHandler` and `CsvRowResult`
are the canonical place to extend with new bulk operations.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [account-slips.md](EWCZ0-account-slips.md) | Account slips reference user data that CSV import manages |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References
