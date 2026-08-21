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

> **Last updated:** 2026-08-17 **Changes:** extracted inline rules (§Core Rules, §Collection Definition, §Usage Patterns, §Key Configurations, §Verification Checklist) into `rules/` rule assets with a `## Skill Rules` mapping section

> **Prerequisite:** Load `context-awareness` for project orientation.

## When to Activate

Use this skill when implementing file uploads, image handling, document storage, or any
media-related feature. All file storage must go through Spatie MediaLibrary.

## Workflow

Follow the `agent-workflow` skill for the canonical 5-step pipeline (Understand → Plan → Implement → Verify → Summarize): spec-first
doctrine (**governing spec** FR/NFR/UC IDs), **Size Triage** (S/M/L session splitting), verification
strategy, and commit format. This skill adds Spatie MediaLibrary guidance — collections,
conversions, uploads in Command Actions, retrieval — nothing else.

### Execute — Implement MediaLibrary Feature

- Define media collection on Model via `registerMediaCollections()`
- Set validation rules: max file size, MIME types
- Handle upload in Command Action (not Livewire)
- Retrieve media via `getFirstMediaUrl()` or `getMedia()`
- Sanitize filename with `Str::slug()`

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

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Media collections (definition, per-collection validation, MIME, filenames) | `rules/media-collections.md` | Declaring collections or guarding uploads |
| Media conversions & responsive images (conversion config, disk, queue) | `rules/media-conversions.md` | Image processing or performance of media views |
| Upload & retrieval (Action ownership, accessors, deletion) | `rules/upload-retrieval-deletion.md` | Writing upload, retrieval, or delete code |
| Storage & governance (disks, queues, verification gate) | `rules/storage-and-governance.md` | Deploying media features or reviewing config |

## References

| Topic                | Doc                                              |
| -------------------- | ------------------------------------------------ |
| Media library setup  | `docs/infrastructure/media-library.md`           |
| File upload security | `docs/conventions.md` (§3.6)                     |
| Spatie docs          | `search-docs` with `spatie/laravel-medialibrary` |
