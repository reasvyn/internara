# Department Bulk Import — File-Based Bulk Operations

> **Spec ID:** 4CSV1

## Description

Specification for bulk importing and exporting departments via tabular files (CSV today, extensible
to Excel/TSV). Covers the import pipeline with duplicate detection, template download, and
export streaming. The Department entity lifecycle (create, read, update, delete) is defined in
[department-management.md](4HWSB-department-management.md). The shared `CsvHandler` service and
`CsvRowResult` enum are documented in [csv-import-export.md](O2KCR-csv-import-export.md).

---

## 1. Problem Statements

### PS-1 — Bulk Department Setup via File Import

During initial system setup, schools typically have 10–50 departments already defined in
spreadsheets or other systems. Manual entry of each department is tedious and error-prone.
The system must support file import for bulk creation, with duplicate detection and error
reporting. Export is equally important for reporting, migration, and backup.

### PS-2 — Template Download for Import Guidance

Admins who want to bulk-import departments need a starting point — a template file with the
correct column headers and an example row. Without a downloadable template, admins must guess
the column order and field names, leading to import failures.

### PS-3 — Filtered Export for Reporting and Migration

Administrators need to export subsets of departments (filtered by search) for reporting,
migration to other systems, or spreadsheet analysis. Exports must respect the current search
state in the management UI.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Support bulk import of departments from tabular files (CSV/TSV/Excel) with duplicate detection |
| G2  | Support export of departments to tabular files for reporting and migration |
| G3  | Provide a downloadable template with headers and an example row for import guidance |
| G4  | Stream export without buffering the entire dataset in memory |
| G5  | Report per-row results (created, skipped, error) and a flash summary after import |
| G6  | Respect the active search filter when exporting |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Department merge or transfer operations (reassign profiles individually instead) |
| NG2  | Background job processing for import (synchronous within the request) |
| NG3  | Column mapping UI (template defines the expected column order) |
| NG4  | Differential / incremental import (always full snapshot) |

---

## 3. User Stories / Use Cases

### UC-4CSV1-1 — Admin Imports Departments from File

**Actor:** Admin / Super Admin
**Preconditions:** Admin has a tabular file (CSV/TSV) with department names and descriptions
**Flow:**
1. Admin navigates to Academics → Departments
2. Admin clicks "Import" or drags a file onto the import zone
3. `DepartmentManager` validates file: required `mimes:csv,txt`, max 2MB
4. `import()` method calls `CsvHandler::import()` with row processor:
   - Row format: `[name, description]` (2 columns)
   - Empty name rows → skipped (null return)
   - Duplicate name rows → `CsvRowResult::SKIPPED`
   - Valid rows → `CreateDepartmentAction::execute()` → `CsvRowResult::CREATED`
5. `CsvHandler` returns summary: `{created, skipped, invalid}`
6. If invalid (wrong headers), flash error: "Import file has invalid format"
7. If valid, flash summary: "{n} created, {m} skipped (duplicates)"
**Postconditions:** Departments created from file, duplicates skipped, summary shown

### UC-4CSV1-2 — Admin Downloads Import Template

**Actor:** Admin / Super Admin
**Preconditions:** None
**Flow:**
1. Admin clicks "Download Template" on department manager
2. `downloadTemplate()` calls `CsvHandler::downloadTemplate()`
3. Returns streamed CSV with headers `name,description` and one example row
**Postconditions:** Template downloaded, ready for editing

### UC-4CSV1-3 — Admin Exports Departments to File

**Actor:** Admin / Super Admin
**Preconditions:** At least one department exists
**Flow:**
1. Admin clicks "Export" on department manager
2. `export()` queries departments (filtered by search if active), ordered by name
3. `CsvHandler::export()` streams file with columns: name, description
4. File downloads as `departments.csv`
**Postconditions:** File downloaded with all matching departments

---

## 4. Functional Requirements

| ID   | Requirement |
| ---- | ----------- |
| FR-4CSV1-BI1 | `DepartmentManager::import()` must authorize `create` policy before processing |
| FR-4CSV1-BI2 | `DepartmentManager::import()` must validate file MIME type (`mimes:csv,txt`) and max 2MB size |
| FR-4CSV1-BI3 | `CsvHandler::import()` must parse the file with columns `[name, description]` |
| FR-4CSV1-BI4 | Rows with empty name must be skipped (return null) |
| FR-4CSV1-BI5 | Rows with duplicate names must return `CsvRowResult::SKIPPED` |
| FR-4CSV1-BI6 | Valid rows must call `CreateDepartmentAction::execute()` and return `CsvRowResult::CREATED` |
| FR-4CSV1-BI7 | Import summary must report created count and skipped count via flash message |
| FR-4CSV1-BI8 | Invalid file format (wrong headers) must flash error: `import_invalid` |
| FR-4CSV1-BI9 | `DepartmentManager::export()` must stream the file with headers `[name, description]` |
| FR-4CSV1-BI10 | `DepartmentManager::export()` must apply the active search filter when present |
| FR-4CSV1-BI11 | `DepartmentManager::exportSelected()` must export only selected department IDs |
| FR-4CSV1-BI12 | `DepartmentManager::downloadTemplate()` must stream a file with headers and one example row |

---

## 5. Non-Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| NFR-4CSV1-BI1 | File export must stream without buffering the entire dataset in memory |
| NFR-4CSV1-BI2 | Import file must be validated for MIME type and max 2MB size before parsing |
| NFR-4CSV1-BI3 | Import must be idempotent — duplicate names are skipped, not errored |
| NFR-4CSV1-BI4 | Import summary must show created and skipped counts via flash message |

---

## 6. API / Data Contracts

The shared `CsvHandler` service, `CsvRowResult` enum, and import/export contracts are defined
in [csv-import-export.md](O2KCR-csv-import-export.md). The Department bulk-import flow plugs
into that infrastructure via the row-processor callback (FR-4CSV1-BI3–FR-4CSV1-BI6).

```php
// app/Modules/Academics/Department/Livewire/DepartmentManager.php — relevant methods

public function import(): void
{
    $this->authorize('create', Department::class);
    $this->validate(['file' => 'required|mimes:csv,txt|max:2048']);
    $summary = app(CsvHandler::class)->import(
        $this->file->getRealPath(),
        fn (array $row) => $this->processRow($row),
        expectedHeaders: ['name', 'description'],
    );
    // Flash $summary
}

public function exportSelected(): StreamedResponse
{
    return app(CsvHandler::class)->export(
        Department::query()->whereIn('id', $this->selected)->orderBy('name')->get(),
        headers: ['name', 'description'],
        rowMapper: fn (Department $d) => [$d->name, $d->description],
        filename: 'departments.csv',
    );
}
```

---

## 7. Design Decisions

### DD-1 — Bulk Import via Shared CsvHandler Service

**Decision:** Department bulk import/export uses the shared `App\Core\Support\CsvHandler`
service, not a module-specific implementation.

**Rationale:** File parsing, streaming, and template generation are cross-cutting concerns.
`CsvHandler` already handles file opening, header validation, row iteration, and streamed
responses. Duplicating this for departments would violate DRY. The department module only
provides the row processor callback (column mapping and `CreateDepartmentAction` invocation).

**Trade-off:** The shared service is generic — department-specific validation (e.g., column
count, name format) happens in the row processor, not the handler. Mitigated by clear error
reporting via `CsvRowResult` enum and flash message summaries.

### DD-2 — Template Format Drives Column Order

**Decision:** The import format is fixed by the downloadable template — `[name, description]`
with no column-mapping UI.

**Rationale:** Schools need a simple, predictable workflow. A column-mapping UI adds significant
complexity (preview, validation, user errors) for a feature that handles at most 10–50 rows
per typical school. The downloadable template provides the contract.

**Trade-off:** Less flexible than column mapping. Acceptable — the typical use case is
single-format import during initial setup, not data migration from arbitrary systems.

---

## 8. Success Metrics

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Import feedback | Shows created/skipped counts | Flash summary after import |
| Template download | Available in one click | `downloadTemplate()` method |
| Duplicate handling | 0 duplicate departments after import | `CsvRowResult::SKIPPED` for duplicates |
| Export filter applied | Search filter respected | `export()` applies `applySearch()` first |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [department-management.md](4HWSB-department-management.md) | `Department` model, `CreateDepartmentAction`, `DepartmentManager` Livewire |
| [csv-import-export.md](O2KCR-csv-import-export.md) | `CsvHandler` service, `CsvRowResult` enum, shared import/export contracts |

### Build Guide
After implementing this spec, admins can bulk-import departments from a file with duplicate
detection, download an import template, and export departments (filtered or selected) to a file.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [csv-import-export.md](O2KCR-csv-import-export.md) | Cross-module bulk operations on users, companies share the same infrastructure |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References
