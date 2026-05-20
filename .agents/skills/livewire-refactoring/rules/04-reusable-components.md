# Reusable Components (Extraction)

## What It Enforces

Repeated UI patterns are extracted into shared Livewire or Blade components. CRUD tables extend `BaseRecordManager`. Confirm dialogs use `<x-ui::confirm>`. Static utilities (CSV handling, language checking) move to `Support/` classes. Concerns (selection, sorting) are extracted into traits.

## Why It Matters

The rule of three: when you see the same pattern three or more times, it's time to extract. Shared components reduce duplication, ensure consistent behavior, and centralize bug fixes. BaseRecordManager provides pagination, search, sort, and selection — every CRUD table gets these for free.

## When It Applies

Extract at these thresholds:
- Table with search + pagination: BaseRecordManager (abstract class in Core/Livewire)
- Create/Edit modal: BaseRecordManager's modal slot pattern
- Confirmation dialog: `<x-ui::confirm>` component with title, message, confirmText, cancelText
- Bulk action bar: `<x-ui::selection-bar>` component
- CSV import/export: `CsvHandler` support class in Domain/Support
- Missing translation detection: `LangChecker` support class (debug mode)
- Record selection: `WithRecordSelection` concern
- Sorting: `WithSorting` concern

BaseRecordManager is the primary extraction point. It defines `headers()` and `query()` as abstract methods, provides `rows()` with automatic search/filter/sort/pagination, and offers `performBulkAction()` for batch operations.

Exceptions: Patterns used only once or twice don't need extraction.
