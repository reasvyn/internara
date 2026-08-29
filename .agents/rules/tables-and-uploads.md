# Tables & File Uploads — CRUD Rendering and Media Handling

> **Last updated:** 2026-08-25 **Changes:** sync — x-mary-table → TallstackUI x-ts-table (FB792 0.15.0)

List/CRUD tables and file uploads are the two most mechanically complex Livewire concerns. Each has
a mandated mechanism — TallstackUI's `x-ts-table` (with `BaseRecordManager` for CRUD) and
`WithFileUploads` + Spatie MediaLibrary — and each fails in characteristic ways when the mechanism is
replaced with a hand-rolled approximation: lost sorting/pagination semantics, duplicated upload
plumbing, and unauthorized-ish flows.

---

## Tables Use TallstackUI `x-ts-table`

**What it enforces:** List views use TallstackUI's `x-ts-table` component with sorting and pagination.
CRUD tables extend `BaseRecordManager` where the current implementation provides one (check the
existing module code before writing a table component).

**Why it matters:** TallstackUI's table ships sorting, pagination, selection, and the accessible header
semantics this project requires (see `rules/accessibility-wcag.md`). Hand-rolling an HTML table duplicates
that behavior, loses the accessibility defaults (`scope`, `aria-sort`), and produces markup that the
frontend conventions can't review consistently. `BaseRecordManager` centralizes the CRUD wiring
(columns, search, bulk actions), so per-module divergence is avoided.

**How to apply:** Check `app/` for the current `BaseRecordManager` implementation and reuse it for
CRUD tables. For custom lists, render `x-ts-table` with defined columns wired to a paginated,
eager-loaded query. Ensure sortable columns expose `aria-sort` and the bulk-selection header checkbox
carries `aria-label="Select all rows"`.

**Pitfalls to avoid:**

- Writing a `<table>` by hand "because the data is simple" — sorting/pagination/accessibility are
  re-implemented and drift.
- Copying a foreign module's table markup without checking whether `BaseRecordManager` evolved.
- An un-paginated listing that grows unbounded with the dataset.

**Verification:** The view uses `x-ts-table` (or `BaseRecordManager`); sorting, pagination, and
header accessibility attributes are present; the query is eager-loaded and paginated.

---

## File Uploads Use `WithFileUploads` + Spatie MediaLibrary

**What it enforces:** Upload-capable components use Livewire's `WithFileUploads` trait; the uploaded
file is passed to the Action; the ACTION performs the Spatie MediaLibrary call (collection,
conversions, retrieval). Media specifics come from the `medialibrary-development` skill — load it
before writing upload code.

**Why it matters:** `WithFileUploads` supplies the temporary-upload handling and progress mechanism
that makes browser uploads reliable in Livewire; reimplementing it loses the multi-file/temporary
semantics. The MediaLibrary call belongs in the Action because storing a file is a mutation — it must
run inside the Action's transaction with its logging, not as a component side effect. Raw Livewire
`$image->store()` calls bypass MediaLibrary's collections/conversions entirely.

**How to apply:**

```php
use Livewire\WithFileUploads;
use Livewire\WithFileUploads\UploadedFile;

class ProfileEditor extends Component
{
    use WithFileUploads;

    public UploadedFile $avatar;

    public function save(UpdateProfileAction $action): void
    {
        $action->execute($this->avatar, ...);
    }
}
```

The Action then calls the media library:

```php
// inside UpdateProfileAction
$model->addMedia($data->avatar)
    ->toMediaCollection('avatars');
```

Validate the uploaded file (mime, size) in the component's validation rules for UX, and re-validate
in the Action for enforcement.

**Pitfalls to avoid:**

- Editing a plain `$file` property without `WithFileUploads` — uploads break silently.
- The component calling `addMedia()` directly — a mutation outside the Action's transaction.
- Using raw `store()`/local storage instead of MediaLibrary collections (no conversions, no
  retrieval API).

**Verification:** The component uses `WithFileUploads`; every stored upload enters via an Action that
uses the MediaLibrary; `medialibrary-development` was loaded before writing the upload code.

---

## References

| Topic                     | Asset                                        |
| ------------------------- | -------------------------------------------- |
| File uploads              | `docs/guides/infra/media-library.md`       |
| MediaLibrary skill        | `.agents/skills/medialibrary-development/SKILL.md` |
| Table accessibility       | `docs/guides/arch/livewire-pattern.md` §Accessibility |
| Testing components        | `docs/guides/arch/testing-pattern.md`       |