# Media Collections — Definition & Validation

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

Every media collection is declared on the Model that owns it and carries its own validation rules.
A collection that is not registered, or has no size/MIME guard, accepts anything — then what should
be an "avatar" quietly stores executables or multi-GB files.

---

## Intent

Define media collections on the Model via `registerMediaCollections()`, give each collection its own
validation rules (max file size, MIME types), validate MIME type server-side (not just the file
extension), and sanitize generated filenames with `Str::slug()`. All file storage goes through Spatie
MediaLibrary.

## Rationale — What Fails Without It

- **An unregistered collection** has no contract: MediaLibrary stores the file but nothing
  constrains what it is, where it goes, or how it is processed (no conversions). Callers that assume
  `'avatar'` is a single file get a broken invariant.
- **No per-collection size/MIME rules** lets any upload land on a model that only wants photos — a
  2GB video into an `avatar` collection thrashes disk and risks the request, and a malicious file
  type bypasses the intent of the feature.
- **Trusting the file extension over the MIME type** is spoofable — an attacker renames a PHP file
  to `photo.jpg` and the extension-`validate()` pass. MediaLibrary checks the real content MIME type
  server-side; validating by extension only defeats that.
- **Raw filenames** (user-provided, possibly `../../evil.php`, spaces, or non-ASCII) leak into the
  storage path and headers — `Str::slug()` produces a predictable, storage-safe basename.

## How to Apply

Declare collections on the Model:

```php
public function registerMediaCollections(): void
{
    $this->addMediaCollection('avatar')
        ->singleFile()
        ->acceptsFile(function (File $file) {
            return in_array($file->mimeType, ['image/jpeg', 'image/png']);
        });
}
```

Rules for every collection:

- **`singleFile()`** when the feature expects exactly one file of that kind (avatar, signature,
  certificate) — it replaces the previous file on re-upload instead of accumulating duplicates.
- **`acceptsFile()` closure** is the primary server-side MIME guard — check `$file->mimeType`, not
  `$file->getClientOriginalExtension()`. Validate MIME **server-side inside the closure**, never
  only in a Livewire/browser rule that a crafted request can skip.
- **Unit-test the closure** — assert which MIME types the collection accepts and rejects, so the
  guard is a spec-visible behavior (see `pest-testing`).

### Filename sanitization

Do not trust the original name:

```php
$user->addMedia($uploadedFile)
    ->usingFileName(Str::slug(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $uploadedFile->getClientOriginalExtension())
    ->toMediaCollection('avatar');
```

## Anti-Patterns & Pitfalls

- `$this->addMediaCollection('avatar')` with no `acceptsFile` — unguarded collection.
- Validating `'image' => ['mimes:jpg,png']` only in the Livewire component — a hand-crafted request
  bypasses it; the collection closure is the enforcement point.
- Using `singleFile()` for a "documents" collection where retention expects many files.
- `acceptsFile()` checking `pathinfo($name, PATHINFO_EXTENSION)` — extension is spoofable; MIME only.
- Hardcoding a collection name in two places — reuse the same string literal the Model registers
  (ideally a constant) so renames don't orphan media.

## Verification

- Every Model that owns files has `registerMediaCollections()` with one `addMediaCollection()` per
  feature collection.
- Each collection has `acceptsFile()` (MIME check) and a size guard (saved elsewhere in MediaLibrary
  config or an upload rule).
- `python3 scripts/scan_security.py` does not flag the upload path; filename uses `Str::slug()`.
- Reference: `docs/infrastructure/media-library.md` §Collections and `docs/conventions.md`
  §File Upload Security.