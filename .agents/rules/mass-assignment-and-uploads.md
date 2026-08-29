# Mass Assignment & File Upload Security — Input Integrity

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

## Intent

Mass assignment and file upload are the two places attacker-controlled input meets the persistence
layer directly. This rule fixes the model-write surface: every model declares its fillable surface
with the `#[Fillable]` attribute and no raw request array ever reaches `create()`. Independently, it
fixes the file surface: every upload goes through Spatie MediaLibrary, is validated by MIME
server-side, and gets a sanitized, size-limited filename.

## Rationale

**Mass assignment** fails when an attacker slips an extra parameter into a request and the model
silently persists it: `User::create($request->all())` with an unexpected `role=admin` field escalates
privilege in one request. The project's model wire-up (`#[Fillable]` attribute + typed entities)
turns the guard on per model; every bypass — `$fillable`, `$guarded`, or a raw `$request->all()` into
`create()` — is a finding. In Livewire, `$this->all()` is the same hole: the whole component state,
including fields no form ever renders, becomes the write input.

**File upload** fails in four independent ways, any of which is exploitable on its own:

- **Wrong pipeline** — `Storage::put()` outside MediaLibrary skips the conversions, MIME checks, and
  random naming the library manages.
- **Extension-only validation** — an attacker renames `shell.php` to `shell.jpg`; the extension looks
  safe and the server still executes it depending on deployment.
- **Unsanitized filenames** — a user-supplied filename with path separators (`../`) or control
  characters can escape the intended directory.
- **No size limits** — unlimited upload size is a denial-of-service and a storage-exhaustion vector.

## How to Apply — Mass Assignment

- **Every model uses the `#[Fillable([...])]` attribute** — never `$fillable` or `$guarded`.
- **No `Model::create($request->all())` anywhere.**
- **No `Model::create($this->all())` in Livewire.**
- Writes take validated, explicit shape: a DTO or explicitly-listed keys (`$request->only([...])` /
  `$validated`).

## How to Apply — File Upload Security

- **ALL uploads go through Spatie MediaLibrary** — never `Storage::put()` for user uploads. Register
  a collection on the model with the library's validation.
- **MIME type validated server-side** — not just the extension. MediaLibrary validates the actual
  file MIME; verify the collection's `image/*`, `mimes:pdf` etc. rules.
- **Filenames sanitized with `Str::slug()`** — strips path separators and control characters.
- **File size limits defined per collection** — max size configured on each collection the app
  actually accepts.

## Examples

```php
// GOOD — fillable surface via attribute
#[Fillable(['identifier', 'full_name', 'email', 'status'])]
final class Intern extends Model
{
    // ...
}

// BAD — the canonical mass-assignment hole
Intern::create($request->all());

// GOOD — validated, explicit writes
app(CreateInternAction::class)->execute(StoreInternData::from($validated));
```

```php
// GOOD — uploads through MediaLibrary with explicit rules
$intern->addMediaFromRequest('photo')
    ->toMediaCollection('photos');   // MIME + size enforced by collection rules

// BAD — raw storage write outside the library
Storage::put('uploads/' . $request->file('photo')->getClientOriginalName(), ...);
```

## Anti-Patterns & Pitfalls

- `$guarded = ['*']` in a Livewire component's model to "override" the attribute — the model surface
  is owned by `#[Fillable]`.
- Trusting the client-reported extension and skipping server MIME inspection.
- Accepting the user's filename verbatim — even within MediaLibrary, sanitize before it reaches the
  collection.
- Setting one generous max on the default collection and missing per-collection limits for the risky
  ones (PDFs, archives).
- Forgetting `Storage::put` uploads added "temporarily" — each is a finding regardless of intent.

## Verification & Detection

```bash
# Every model must carry #[Fillable]
rg -L "\#\[Fillable\(" app/ --include="*.php" --glob "*/Models/*.php"

# The two mass-assignment holes
rg -n "create\(\$request|create\(\$this->all\(\)\)" app/ --include="*.php"

# Uploads that bypass the media library
rg -n "Storage::put\(|->store\(" app/ --include="*.php" | rg -v "MediaLibrary|addMedia|toMedia"

# Filesystem writes on user-controlled paths
rg -n "getClientOriginalName|getClientOriginalExtension" app/ --include="*.php"
```