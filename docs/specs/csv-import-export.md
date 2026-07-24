# CSV Import & Export — Bulk Data Operations

> **Last updated:** 2026-07-22 **Changes:** feat — consolidated cross-module CSV import/export from
> `user-crud-and-status.md`, `company-management.md`, and `department-management.md` into standalone spec

## Description

Specification of Internara's cross-module CSV import and export subsystem. Defines bulk data
operations for users, departments, and companies — covering file upload, row-by-row validation,
duplicate detection, credential generation, per-row result reporting, template downloads, and
filtered export. The shared `CsvHandler` service and `CsvRowResult` enum provide consistent
behavior across all three modules.

---

## 1. Problem Statements

### PS-1 — Bulk Onboarding at Scale

Schools may have 500+ students, dozens of departments, and numerous partner companies to onboard
at the start of an academic year. Manual one-by-one creation through forms is impractical and
error-prone at this volume. CSV import must handle bulk data entry with consistent validation,
deduplication, and clear feedback on what was created versus skipped.

### PS-2 — Duplicate Detection Across Modules

Each module has a natural uniqueness constraint: users by email, departments by name, companies
by name. Without duplicate detection during import, admins could create redundant records that
break referential integrity (e.g., duplicate user emails causing login ambiguity). The import
process must detect existing records and skip duplicates transparently.

### PS-3 — Credential Generation for User Imports

When importing users (especially students), each new account requires a unique username and
temporary password. Manual credential generation for 500+ students is untenable. The import
pipeline must auto-generate credentials for every valid row and make them available for
distribution via account slips.

### PS-4 — Per-Row Error Reporting

CSV files from external systems often contain malformed rows — missing required fields, invalid
email formats, or encoding issues. Rather than failing the entire import on the first bad row,
the system must process all rows, report per-row results (created, skipped, error), and present
a clear summary to the admin.

### PS-5 — Filtered Export for Reporting

Administrators need to export subsets of data for offline reporting, migration to other systems,
or spreadsheet analysis. Exports must respect the current search and filter state applied in the
management UI, so admins can narrow results before downloading.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide CSV import with row-by-row validation and duplicate detection across users, departments, and companies |
| G2  | Auto-generate credentials (username, password) for each valid user row during CSV import |
| G3  | Report per-row results using `CsvRowResult` enum (created, skipped) with summary flash message |
| G4  | Provide CSV export that respects current search and filter state in the management UI |
| G5  | Support per-selection export (`exportSelected`) for downloading only checked rows |
| G6  | Provide downloadable CSV templates with correct headers and placeholder example rows |
| G7  | Validate CSV header row against expected columns and reject mismatched files |
| G8  | Limit import file size to 2048KB and restrict MIME types to csv/txt |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | CSV import with update-on-duplicate (merge or overwrite existing records) |
| NG2  | Real-time streaming import for files exceeding memory limits |
| NG3  | Import scheduling or queue-based async processing |
| NG4  | Export to formats other than CSV (Excel, PDF, JSON) |
| NG5  | Import of related/nested data (e.g., importing users with their departments in one file) |
| NG6  | Column mapping UI (admin must prepare CSV with correct column order) |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Imports Users via CSV

**Actor:** Admin
**Preconditions:** CSV file prepared with columns: name, email, phone
**Flow:**
1. Admin navigates to User Management, clicks "Import"
2. Uploads CSV file (max 2048KB, mimes: csv, txt)
3. `UserManager::import()` validates file constraints
4. `CsvHandler::import()` reads header row, validates against expected headers
5. For each data row:
   - Empty name → skip (return null)
   - Email already exists in database → skip (`CsvRowResult::SKIPPED`)
   - Valid row → `CreateUserAction::execute()` creates user with auto-generated credentials
   - Return `CsvRowResult::CREATED`
6. Flash message shows: "Imported: X, Skipped: Y"
**Postconditions:** Users created from CSV, duplicates skipped, summary displayed

### UC-2 — Admin Imports Departments via CSV

**Actor:** Admin
**Preconditions:** CSV file prepared with columns: name, description
**Flow:**
1. Admin navigates to Academics → Departments, clicks "Import"
2. Uploads CSV file (max 2048KB, mimes: csv, txt)
3. `DepartmentManager::import()` validates file and checks admin create permission
4. `CsvHandler::import()` processes each row:
   - Empty name → skip
   - Name already exists → skip (`CsvRowResult::SKIPPED`)
   - Valid row → `CreateDepartmentAction::execute()` creates department
   - Return `CsvRowResult::CREATED`
5. Flash message shows: "Imported: X, Skipped: Y"
**Postconditions:** Departments created, duplicates skipped, summary displayed

### UC-3 — Admin Imports Companies via CSV

**Actor:** Admin
**Preconditions:** CSV file prepared with columns: name, address, phone, email, website, description, industry_sector
**Flow:**
1. Admin navigates to Partners → Companies, clicks "Import"
2. Uploads CSV file (max 2048KB, mimes: csv, txt)
3. `CompanyManager::import()` validates file constraints
4. `CsvHandler::import()` processes each row:
   - Empty name → skip
   - Name already exists → skip (`CsvRowResult::SKIPPED`)
   - Valid row → `CreateCompanyAction::execute(CompanyData)` creates company
   - Return `CsvRowResult::CREATED`
5. Flash message shows: "Imported: X, Skipped: Y"
**Postconditions:** Companies created, duplicates skipped, summary displayed

### UC-4 — Admin Exports Users with Filters

**Actor:** Admin
**Preconditions:** Users exist in the system; admin has applied search or filters
**Flow:**
1. Admin navigates to User Management, applies search term or role/status filters
2. Clicks "Export" button
3. `UserManager::export()` builds query with current search/filter state
4. `CsvHandler::export()` streams CSV with columns: full_name, email, username, phone, address
5. File downloads as `users.csv`
**Postconditions:** CSV file contains only filtered users, respects current UI state

### UC-5 — Admin Exports Selected Users

**Actor:** Admin
**Preconditions:** Users selected via checkboxes in the management table
**Flow:**
1. Admin selects specific users via row checkboxes
2. Clicks "Export Selected"
3. `UserManager::exportSelected()` queries only selected IDs
4. `CsvHandler::export()` streams CSV with same columns as full export
5. File downloads as `users-selected.csv`
**Postconditions:** CSV contains only the selected users

### UC-6 — Admin Downloads CSV Template

**Actor:** Admin
**Preconditions:** None
**Flow:**
1. Admin navigates to any management UI (Users, Departments, or Companies)
2. Clicks "Download Template"
3. `downloadTemplate()` calls `CsvHandler::downloadTemplate()` with headers and example row
4. File downloads with correct column headers and a placeholder row
**Postconditions:** Template CSV downloaded with correct format

---

## 4. Functional Requirements

### User CSV Import

| ID   | Requirement |
| ---- | ----------- |
| FR-IE1 | CSV import must accept files up to 2048KB with MIME types `csv` or `txt` |
| FR-IE2 | Import must validate header row against expected columns (`name`, `email`, `phone`) and reject mismatched files with `invalid: true` |
| FR-IE3 | Import must detect duplicate emails — rows where email matches an existing user must be skipped (`CsvRowResult::SKIPPED`) |
| FR-IE4 | Import must auto-generate credentials for each valid row via `CreateUserAction` (username from email, random 12-char password) |

### User CSV Export

| ID   | Requirement |
| ---- | ----------- |
| FR-IE5 | CSV export must respect current search and filter state from `UserManager` query builder |
| FR-IE6 | Export columns must be: `full_name`, `email`, `username`, `phone`, `address` |
| FR-IE7 | `downloadTemplate()` must provide a CSV template with headers and one placeholder example row |
| FR-IE8 | `exportSelected()` must export only the rows selected via checkboxes in the UI |

### Department CSV Import/Export

| ID   | Requirement |
| ---- | ----------- |
| FR-IE9 | Department CSV import must validate file constraints (2048KB, csv/txt MIME) |
| FR-IE10 | Department import must deduplicate by name — skip rows where name matches existing department |
| FR-IE11 | Department export columns must be: `name`, `description` |
| FR-IE12 | Department template must include headers and a placeholder example row |

### Company CSV Import/Export

| ID   | Requirement |
| ---- | ----------- |
| FR-IE13 | Company CSV import must validate file constraints (2048KB, csv/txt MIME) |
| FR-IE14 | Company import must deduplicate by name — skip rows where name matches existing company |
| FR-IE15 | Company import must pass data through `CompanyData` DTO for validation |
| FR-IE16 | Company export columns must be: `name`, `address`, `phone`, `email`, `website`, `description`, `industry_sector` |
| FR-IE17 | Company template must include all seven column headers and a placeholder example row |

### Cross-Module Patterns

| ID   | Requirement |
| ---- | ----------- |
| FR-IE18 | All import operations must use the shared `CsvHandler::import()` service |
| FR-IE19 | All export operations must use the shared `CsvHandler::export()` service returning `StreamedResponse` |
| FR-IE20 | All template downloads must use `CsvHandler::downloadTemplate()` |
| FR-IE21 | Import summary flash messages must use `common.actions.import_summary` with `created` and `skipped` counts |
| FR-IE22 | Invalid header files must flash `common.actions.import_invalid` error message |
| FR-IE23 | All user-facing strings in CSV operations must use `__()` translation helper |
| FR-IE24 | Import operations must null out the `importFile` property after processing |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-P1 | CSV import of 100 rows must complete in under 30 seconds |
| NFR-P2 | CSV export must use `StreamedResponse` to avoid loading entire dataset into memory |
| NFR-P3 | Template download must complete instantly (header + one example row) |
| NFR-S1 | CSV import must sanitize all input fields — trim whitespace, prevent XSS in exported data |
| NFR-S2 | Import file upload must enforce max size (2048KB) and MIME type validation at the form level |
| NFR-S3 | Export must not include sensitive fields (passwords, tokens, recovery keys) |
| NFR-S4 | Import operations must require admin-level authorization before processing |
| NFR-R1 | CSV import must handle malformed rows gracefully — skip and continue, never halt the entire import |
| NFR-R2 | Header mismatch must be detected before row processing begins and return `invalid: true` |
| NFR-R3 | Export must handle empty datasets without errors (return CSV with headers only) |
| NFR-U1 | Import success must display created and skipped counts via flash message |
| NFR-U2 | Import failure (invalid headers) must display a clear error flash message |
| NFR-U3 | Export must trigger file download with a descriptive filename (e.g., `users.csv`, `departments.csv`) |
| NFR-A1 | CSV import form must have associated labels for the file input and accessible error messages |
| NFR-A2 | Import/export buttons must be keyboard-navigable and have accessible labels |
| NFR-A3 | Flash messages must be announced to screen readers via `aria-live` region |
| NFR-L1 | All user-facing strings in CSV operations must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |

---

## 6. API / Data Contracts

### 6.1 CsvHandler Service

```php
// app/Core/Support/CsvHandler.php
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
// app/Core/Enums/CsvRowResult.php
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

### 6.3 UserManager Import/Export Methods

```php
// app/User/UserManagement/Livewire/UserManager.php
class UserManager extends BaseRecordManager
{
    public Property $importFile;

    public function processImport(CsvHandler $csv, CreateUserAction $create): void;
    // Validates file: required, file, mimes:csv,txt, max:2048
    // Delegates to import()

    public function import(CsvHandler $csv, CreateUserAction $create): void;
    // CsvHandler::import() with row processor:
    //   $row[0] = name, $row[1] = email, $row[2] = phone
    //   Empty name → null (skip silently)
    //   Email exists → CsvRowResult::SKIPPED
    //   Valid → CreateUserAction::execute() → CsvRowResult::CREATED

    public function export(CsvHandler $csv): StreamedResponse;
    // Query with search filter → CsvHandler::export()
    // Headers: full_name, email, username, phone, address
    // Filename: users.csv

    public function exportSelected(CsvHandler $csv): ?StreamedResponse;
    // Query selected IDs only → CsvHandler::export()
    // Filename: users-selected.csv

    public function downloadTemplate(CsvHandler $csv): StreamedResponse;
    // Headers: full_name, email, phone
    // Example row: [name_placeholder, email_placeholder, phone_placeholder]
    // Filename: users-template.csv
}
```

### 6.4 DepartmentManager Import/Export Methods

```php
// app/Academics/Department/Livewire/DepartmentManager.php
class DepartmentManager extends BaseRecordManager
{
    public Property $importFile;

    public function import(CsvHandler $csv, CreateDepartmentAction $create): void;
    // Authorizes 'create' on Department model
    // Validates file: required, file, mimes:csv,txt, max:2048
    // Row processor: $row[0] = name, $row[1] = description
    //   Empty name → null, Name exists → SKIPPED, Valid → CREATED

    public function export(CsvHandler $csv): StreamedResponse;
    // Authorizes 'viewAny' on Department model
    // Query with search filter → Headers: name, description
    // Filename: departments.csv

    public function exportSelected(CsvHandler $csv): ?StreamedResponse;
    // Filename: departments-selected.csv

    public function downloadTemplate(CsvHandler $csv): StreamedResponse;
    // Headers: name, description
    // Filename: departments-template.csv
}
```

### 6.5 CompanyManager Import/Export Methods

```php
// app/Partners/Company/Livewire/CompanyManager.php
class CompanyManager extends BaseRecordManager
{
    public Property $importFile;

    public function import(CsvHandler $csv, CreateCompanyAction $create): void;
    // Validates file: required, file, mimes:csv,txt, max:2048
    // Row processor: $row[0]=name, $row[1]=address, $row[2]=phone, $row[3]=email,
    //   $row[4]=website, $row[5]=description, $row[6]=industry_sector
    //   Empty name → null, Name exists → SKIPPED, Valid → CompanyData DTO → CREATED

    public function export(CsvHandler $csv): StreamedResponse;
    // Query with search filter → Headers: name, address, phone, email,
    //   website, description, industry_sector
    // Filename: companies.csv

    public function exportSelected(CsvHandler $csv): ?StreamedResponse;
    // Filename: companies-selected.csv

    public function downloadTemplate(CsvHandler $csv): StreamedResponse;
    // Headers: all seven company columns
    // Filename: companies-template.csv
}
```

### 6.6 File Upload Property

```php
// Shared across all three managers
public Property $importFile;

// Livewire file upload with validation:
// ['required', 'file', 'mimes:csv,txt', 'max:2048']
```

---

## 7. Design Decisions

### DD-1 — CSV Import with Skip-on-Duplicate (Not Update)

**Decision:** CSV import skips rows with existing uniqueness keys (email for users, name for
departments/companies) and does not update or merge existing records.
**Rationale:** Import is for onboarding new records, not updating existing ones. Update-via-CSV
would require conflict resolution logic (which fields to overwrite? preserve which values?) and
audit trail complexity. Skip-on-duplicate is predictable, safe, and matches admin expectations
during initial bulk onboarding.
**Trade-off:** Admin must manually update existing records outside the import flow. Acceptable
because updates are infrequent compared to initial onboarding and the management UI provides
full CRUD for edits.

### DD-2 — Chunk Processing for Memory Efficiency

**Decision:** `CsvHandler::import()` processes rows one at a time via `fgetcsv()` in a while
loop, not loading the entire file into memory.
**Rationale:** CSV files with 500+ rows could exhaust PHP memory if loaded into an array.
Streaming via `fgetcsv()` keeps memory usage constant regardless of file size. The
`StreamedResponse` for export follows the same pattern — writing rows to `php://output` via
`fputcsv()` without buffering.
**Trade-off:** Cannot pre-validate all rows before processing (partial imports may occur on
fatal errors). Mitigated by the `invalid` flag catching header mismatches before row processing
begins, and individual row errors being caught by the callback returning null or SKIPPED.

### DD-3 — CsvRowResult Enum for Type-Safe Row Status

**Decision:** Row processing status represented as a `CsvRowResult` enum (`CREATED`, `SKIPPED`)
rather than string constants or integers.
**Rationale:** Enums provide type safety, IDE autocompletion, and self-documenting code. The
enum implements `LabelEnum` for translated display labels. Returning null from the row processor
silently skips the row (for empty rows), while `CsvRowResult::SKIPPED` counts the skip in the
summary. This distinction allows empty rows to be invisible while duplicate skips are reported.
**Trade-off:** Adding new row statuses (e.g., `UPDATED`, `FAILED`) requires updating the enum.
Mitigated by the current spec limiting statuses to CREATED and SKIPPED.

### DD-4 — Cross-Module Shared CsvHandler Service

**Decision:** All three modules (Users, Departments, Companies) share a single `CsvHandler`
service class in `App\Core\Support`, not module-specific CSV handlers.
**Rationale:** The CSV operations (read rows, write rows, stream response, validate headers)
are identical across modules. The only variation is the row processor callback and column
headers, which are passed as parameters. A shared service eliminates code duplication and
ensures consistent behavior (file handling, error formatting, streaming).
**Trade-off:** The service is generic — module-specific validation logic lives in the Livewire
manager's callback, not in the handler. This is intentional: CsvHandler is infrastructure,
not business logic.

### DD-5 — Header Validation Before Row Processing

**Decision:** `CsvHandler::import()` validates the header row against expected column names
before processing any data rows. Mismatched headers cause immediate return with `invalid: true`.
**Rationale:** Processing rows with wrong columns would produce garbage data (mapping column B
to field A). Early header validation prevents silent data corruption. The case-insensitive
comparison (`strtolower`) accommodates minor capitalization differences.
**Trade-off:** Strict header matching rejects files with extra columns. Acceptable because the
template download provides the exact expected format.

### DD-6 — Per-Module Authorization on Import

**Decision:** Each manager enforces its own authorization before CSV import — `DepartmentManager`
explicitly calls `$this->authorize('create', Department::class)`, while `UserManager` and
`CompanyManager` rely on `BaseRecordManager` policy checks in `boot()`.
**Rationale:** CSV import is a create operation at scale. The same authorization rules that
govern single-record creation must apply to bulk import. Different modules have different policy
structures (DepartmentPolicy separates `create` ability; CompanyPolicy uses `isAdmin()`), so
each manager enforces its own check.
**Trade-off:** Authorization logic is duplicated across managers. Mitigated by policies being
the single source of truth — managers simply delegate to them.

---

## 8. Success Metrics

### 8.1 Import Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| CSV import 100 rows (users) | < 30 seconds | `UserManager::import()` end-to-end time |
| CSV import 100 rows (companies) | < 10 seconds | `CompanyManager::import()` end-to-end time |
| CSV import 50 rows (departments) | < 15 seconds | `DepartmentManager::import()` end-to-end time |
| Memory usage during import | Constant regardless of file size | `fgetcsv()` streaming, no array loading |

### 8.2 Duplicate Detection

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| User duplicate detection | 100% skip existing emails | `CsvRowResult::SKIPPED` returned for matching emails |
| Department duplicate detection | 100% skip existing names | `CsvRowResult::SKIPPED` returned for matching names |
| Company duplicate detection | 100% skip existing names | `CsvRowResult::SKIPPED` returned for matching names |

### 8.3 Export Accuracy

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Export respects search filter | Search term applied to query | `UserManager::export()` query builder |
| Export respects filters | Role/status filters applied | Query builder `when()` conditions |
| Selected export | Only checked row IDs queried | `whereIn('id', $this->selectedIds)` |
| Empty dataset export | Headers-only CSV, no error | `StreamedResponse` with empty collection |

### 8.4 User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Import success feedback | Created + skipped counts shown | Flash message with `import_summary` key |
| Import failure feedback | Clear invalid-header error | Flash message with `import_invalid` key |
| Template accuracy | Correct headers and example row | `downloadTemplate()` output matches import expectations |
| Filename convention | Descriptive names per module | `users.csv`, `departments.csv`, `companies.csv` |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [user-crud-and-status.md](user-crud-and-status.md) | User entities — bulk import/export of students, teachers |
| [company-management.md](company-management.md) | Company entities — bulk import/export of companies |
| [department-management.md](department-management.md) | Department entities — bulk import/export of departments |

### Build Guide
After implementing this spec, the system has reusable CSV import/export with template download, header validation, and error reporting. This utility is used across user, company, and department modules. The next step is to build account slips, which generates credential documents for placed students.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [account-slips.md](account-slips.md) | Account slips reference user data that CSV import manages |

---

## Quick References

- `app/Core/Support/CsvHandler.php` — shared CSV import/export/template service
- `app/Core/Enums/CsvRowResult.php` — row result enum (CREATED, SKIPPED)
- `app/User/UserManagement/Livewire/UserManager.php` — user import/export/template methods
- `app/Academics/Department/Livewire/DepartmentManager.php` — department import/export/template methods
- `app/Partners/Company/Livewire/CompanyManager.php` — company import/export/template methods
- `docs/specs/user-crud-and-status.md` — User CRUD and AccountStatus
- `docs/specs/company-management.md` — Company CRUD
- `docs/specs/partnership-management.md` — Partnership lifecycle
- `docs/specs/department-management.md` — Department CRUD
