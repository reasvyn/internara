---
name: medialibrary-development
description: "SDLC Phase: IMPLEMENTATION (Sub-skill). Specialized file upload and media management — Spatie MediaLibrary collections, conversions, responsive images, and retrieval."
upstream:
  - feature-building
downstream:
  - pest-testing
  - sync-docs
---

# MediaLibrary Development

> **Prerequisite:** Load `context-awareness` for project orientation.

## When to Activate

Use this skill when implementing file uploads, image handling, document storage, or any
media-related feature. All file storage must go through Spatie MediaLibrary.

## Agent Workflow

Using this skill follows 4 phases:

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- Read relevant docs: module docs, pattern docs, reference docs
- Understand task scope: what needs to be done, which files are affected
- Verify paths, class names, signatures against actual code (don't trust docs blindly)
- Determine approach: at least 2 options before deciding

### 2. Execute — Implement MediaLibrary Feature

- Define media collection on Model via registerMediaCollections()
- Set validation rules: max file size, MIME types
- Handle upload in Command Action (not Livewire)
- Retrieve media via getFirstMediaUrl() or getMedia()
- Sanitize filename with Str::slug()
- Output: media collection definitions, upload handling in Command Actions, and retrieval logic

### 3. Verify — Quality Gates

- Run linter: `vendor/bin/pint --dirty --format agent`
- Run static analysis: `vendor/bin/phpstan analyse --no-progress`
- Run unit/feature tests: `php artisan test --compact --filter={TestName}`
- Ensure pre-commit checklist is satisfied
- Check no debug calls (`dd/dump/ray`) were left behind

### 4. Report & Commit

- Deliver a comprehensive report to the user:
    - Summary of media work done
    - Collections defined and their validation rules
    - Files created or modified
- Feeds into: pest-testing (upload tests), sync-docs (doc updates)
- Commit using format: `type(scope): description`
- Push if requested

## Phase Context

| Role           | Skill                                           |
| -------------- | ----------------------------------------------- |
| **Upstream**   | `feature-building` (implementation flow)        |
| **This skill** | **IMPLEMENTATION (Sub-skill)** — media-specific |
| **Downstream** | `pest-testing` (upload tests), `sync-docs`      |

## Core Rules

1. ALL file uploads go through Spatie MediaLibrary — never `Storage::put()`
2. Each media collection defines its own validation rules (max size, MIME types)
3. Validate MIME type server-side (not just extension)
4. Generated filenames must be sanitized with `Str::slug()`
5. Media conversions defined on the collection for image processing

## Collection Definition

Define collections on the Model using `registerMediaCollections()`:

```php
public function registerMediaCollections(): void
{
    $this->addMediaCollection('avatar')
        ->singleFile()
        ->acceptsFile(function (File $file) {
            return in_array($file->mimeType, ['image/jpeg', 'image/png']);
        })
        ->registerMediaConversions(function (Media $media) {
            $this->addMediaConversion('thumb')
                ->width(150)
                ->height(150)
                ->sharpen(10);
        });
}
```

## Usage Patterns

### Upload (in Command Action)

```php
$user->addMedia($uploadedFile)->toMediaCollection('avatar');
```

### Retrieve

```php
// Single file
$url = $model->getFirstMediaUrl('collection');
$thumb = $model->getFirstMediaUrl('collection', 'thumb');

// Multiple files
$mediaItems = $model->getMedia('documents');
```

### Delete

```php
$model->clearMediaCollection('avatar'); // all
$model->deleteMedia($mediaId); // specific
```

## Key Configurations

| Setting             | Location                     | Default         |
| ------------------- | ---------------------------- | --------------- |
| Max file size       | Per collection               | 10MB            |
| Accepted MIME types | Per collection               | model-specific  |
| Image conversions   | `registerMediaConversions()` | thumb (150x150) |
| Storage disk        | `config/media-library.php`   | `public`        |
| Queue               | `config/media-library.php`   | `default`       |

## Verification Checklist

- [ ] Upload goes through MediaLibrary, not `Storage::put()`
- [ ] MIME type validated server-side
- [ ] Filename sanitized with `Str::slug()`
- [ ] Collection registered on the model
- [ ] Max file size and accepted types defined on collection
- [ ] Conversions defined if image processing needed
- [ ] Upload handled in Command Action, not Livewire
- [ ] Test covers upload and retrieval

## References

| Topic                | Doc                                              |
| -------------------- | ------------------------------------------------ |
| Media library setup  | `docs/infrastructure/media-library.md`           |
| File upload security | `docs/conventions.md` (§3.6)                     |
| Spatie docs          | `search-docs` with `spatie/laravel-medialibrary` |
