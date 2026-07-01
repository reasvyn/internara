---
name: medialibrary-development
description: SDLC Phase: IMPLEMENTATION (Sub-skill). Specialized file upload and media management — Spatie MediaLibrary collections, conversions, responsive images, and retrieval.
upstream:
  - feature-building
downstream:
  - pest-testing
  - sync-docs
---

> **⚠️ Context Awareness Required:** Before following any instruction in this skill,
> read [context-awareness.md](context-awareness.md). Do NOT trust numbers, paths,
> class names, or method signatures without verifying them in the actual codebase.
> The codebase evolves independently of this document — verify, don't assume.
> **Rule:** If the skill says a number/path/name, verify it in the code first.


# Media Library Development Skill

## When to Activate

Apply this skill when implementing file uploads, managing media collections and conversions, generating responsive images, or retrieving media URLs and paths. Activates whenever a Model needs file attachments.

## SDLC Context

| Role | Skill |
|------|-------|
| **Upstream (input)** | `feature-building` — roadmap task requiring file uploads |
| **This skill** | **IMPLEMENTATION (Media)** — produces MediaLibrary integrations |
| **Downstream (output)** | `pest-testing` — tests for upload functionality |
| | `sync-docs` — documentation after media changes |
| **Phase** | [Planning] → [Analysis] → [Design] → Implementation → [Testing] → [Maintenance] |

## Key References

- **User model (avatar)**: `app/User/Models/User.php` — `avatar` collection (single-file, thumb 200×200 WebP)
- **Settings model (branding)**: `app/Settings/Models/Setting.php` — branding images
- **SchoolEntity (logo)**: `app/Academics/School/Entities/SchoolEntity.php` — institution logo (stored via Settings)
- **Partnership model (MoU)**: `app/Partners/Partnership/Models/Partnership.php` — partnership documents
- **Document model**: `app/Document/Models/Document.php` — official documents
- **Certificate model (output)**: `app/Certification/Certificate/Models/Certificate.php` — generated certificate PDFs
- **Logbook model (photos)**: `app/Journals/Logbook/Models/Logbook.php` — daily activity photos
- **RegistrationDocument model**: `app/Enrollment/Registration/Models/RegistrationDocument.php` — enrollment docs
- **Submission model**: `app/Assignment/Submission/Models/Submission.php` — assignment submissions
- **Media library docs**: `docs/infrastructure/media-library.md`
- **Config**: `config/media-library.php` — global settings (disk, queue, image driver, responsive images)
- **Filesystems**: `config/filesystems.php` — disk definitions (local, public, s3)

## Models with Media Collections (8 total)

| Model | Collection(s) | Type | Conversion |
|-------|---------------|------|------------|
| `User` | `avatar` | Single-file, replaceable | `thumb`: 200×200 WebP, non-queued |
| `Setting` | Branding images | Single/multi | Configurable, queued |
| `Partnership` | `mou_document` | Single | PDF thumbnail |
| `Document` | `file` | Single | Configurable |
| `Certificate` | `output` | Single | — |
| `RegistrationDocument` | `file` | Single | — |
| `Logbook` | `photos` | Multi-file | — |
| `Submission` | `file` | Multi-file | — |

## Model Integration

Models must implement `HasMedia` and use `InteractsWithMedia`. Collections are declared in `registerMediaCollections()` and conversions in `registerMediaConversions(?Media $media = null)`:

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxFileSize(10 * 1024 * 1024);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)->height(200)->format('webp')->nonQueued();
    }
}
```

## Collection Manipulation Patterns

**Basic upload** — `addMedia($file)->toMediaCollection('name')`. Use `preservingOriginal()` to copy instead of move.

**From Livewire** — Livewire accepts the `UploadedFile`, Actions use `addMedia($this->avatar)->toMediaCollection('avatar')`.

**From request** — `$model->addMediaFromRequest('field')->toMediaCollection('name')`

**From disk** — `$model->addMediaFromDisk('path/file.jpg', 'public')->toMediaCollection('name')`

**From base64** — `$model->addMediaFromBase64($base64)->toMediaCollection('name')`

**From URL** — `$model->addMediaFromUrl('https://example.com/file.jpg')->toMediaCollection('name')`

**From stream** (large files) — `$model->addMediaFromStream($stream)->toMediaCollection('name')` then `fclose($stream)`.

## Multi-File Upload Patterns

```php
// Iterate files in an Action
foreach ($data->files as $file) {
    $submission->addMedia($file)->toMediaCollection('files');
}

// Bulk from disk
$submission->addMultipleMediaFromDisk(['f1.pdf', 'f2.pdf'], 's3')
    ->each(fn (FileAdder $adder) => $adder->toMediaCollection('files'));

// Clear a collection
$model->clearMediaCollection('photos');
$model->clearMediaCollectionExcept('photos', $keepIds);
```

Collections are ordered by `order_column` ascending. Sort retrieved results: `$model->getMedia('photos')->sortBy('order_column')`.

## Delete & Replace Patterns

```php
// Delete specific media
$model->getFirstMedia('avatar')?->delete();

// Clear entire collection
$model->clearMediaCollection('documents');

// Force delete (soft-deleted models)
$model->forceDelete();     // cascades to media

// Delete model but keep files
$model->deletePreservingMedia();
```

On `singleFile()` collections, re-uploading (`addMedia()->toMediaCollection('avatar')`) automatically removes the prior file.

## Temporary URLs & S3

S3 objects are private by default. Use time-limited URLs:

```php
$url = $user->getFirstTemporaryUrl('avatar', 'thumb', now()->addMinutes(30));

// getUrl() vs getFullUrl()
$url  = $user->getFirstMediaUrl('avatar');                       // Relative: /storage/...
$full = $user->getFirstMedia('avatar')?->getFullUrl();           // Absolute: https://...
```

For local disks, sign routes manually for protected downloads. Default temporary URL lifetime is 5 minutes (`config/media-library.php` `temporary_url_default_lifetime`).

## Custom Properties

```php
// Attach on upload
$user->addMedia($file)->withCustomProperties([
    'uploaded_by' => auth()->id(),
    'source' => 'registration',
])->toMediaCollection('documents');

// Read
$media->getCustomProperty('uploaded_by');           // null if missing
$media->getCustomProperty('category', 'general');    // with default
$media->hasCustomProperty('source');                 // bool

// Query
use Spatie\MediaLibrary\MediaCollections\Models\Media;
$filtered = Media::where('collection_name', 'documents')->get()
    ->filter(fn (Media $m) => $m->getCustomProperty('source') === 'onboarding');
```

## Responsive Images

Enable with `->withResponsiveImages()` on a conversion. Config in `config/media-library.php` under `responsive_images` (width calculator, tiny placeholders, blurred placeholder generator).

```blade
<img
    src="{{ $user->getFirstMediaUrl('avatar', 'thumb') }}"
    srcset="{{ $user->getFirstMedia('avatar')?->srcset('thumb') }}"
    sizes="(max-width: 600px) 200px, 400px"
    alt="" loading="lazy"
/>

{{-- <picture> for format switching --}}
<picture>
    <source srcset="{{ $user->getFirstMedia('avatar')?->srcset('thumb') }}" type="image/webp" />
    <img src="{{ $user->getFirstMediaUrl('avatar', 'thumb') }}" alt="" loading="lazy" />
</picture>
```

## Manipulations

```php
// Pre-defined conversion manipulations
$this->addMediaConversion('thumb')
    ->width(200)->height(200)->format('webp')->greyscale();

$this->addMediaConversion('watermarked')
    ->width(800)->format('jpg')
    ->watermark(public_path('images/watermark.png'))
    ->watermarkPosition('bottom-right')->watermarkOpacity(0.5);

// Manual manipulation on existing media
$media = $model->getFirstMedia('avatar');
$media->manipulate(fn (Image $image) => $image->greyscale());
```

Available: `fit()`, `orientation('auto')`, `border()`, `sepia()`, `greyscale()`, `watermark()`. Uses `spatie/image` under the hood.

## Queued Conversions

| Approach | When Conversions Run | Queue Required | URL Ready |
|----------|---------------------|----------------|-----------|
| `->queued()` (default) | Async via worker | Yes | Delayed |
| `->nonQueued()` | Synchronously on upload | No | Immediate |

```php
$this->addMediaConversion('thumb')->width(200)->nonQueued();     // Instant
$this->addMediaConversion('preview')->width(1200)->queued();      // Async

// Selective collection regeneration
$this->addMediaConversion('thumb')
    ->performOnCollections('avatar', 'logo')->nonQueued();
```

Fallback URL while queued conversion processes: `$model->getFirstMediaUrl('avatar', 'thumb') ?: $model->getFirstMediaUrl('avatar')`.

## Collection Validation

```php
$this->addMediaCollection('documents')
    ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png'])
    ->acceptsFile(fn (File $file) => $file->size < 50 * 1024 * 1024)  // custom
    ->maxFileSize(10 * 1024 * 1024)
    ->maxNumberOfFiles(5);
```

Validate file size in both Livewire (`#[Validate('image|max:10240')]`) and the Action (security boundary).

## Disk Configuration

```php
// Per-collection disk override
$this->addMediaCollection('backups')->disk('s3');

// Environment-based: config/media-library.php
'disk_name' => env('MEDIA_DISK', 'public'),

// Path prefix for multi-tenant isolation
'prefix' => env('MEDIA_PREFIX', ''),
```

```env
MEDIA_DISK=public     # Development
MEDIA_DISK=s3         # Production
```

## Retrieval & Querying

```php
$model->getMedia('photos');                        // All media in collection
$model->getFirstMedia('avatar');                   // First Media model or null
$model->getFirstMediaUrl('avatar', 'thumb');       // URL with conversion
$model->hasMedia('avatar');                        // Bool check
$model->hasMedia();                                // Any media on model
$model->getMedia('*');                             // All collections

// Eager load
User::with('media')->get();
User::withCount('media')->get();                   // $user->media_count
```

## Cleanup & Maintenance

```bash
# Remove expired temporary uploads
php artisan media-library:delete-old-temporary-uploads

# Regenerate conversions
php artisan media-library:regenerate
php artisan media-library:regenerate --only-missing   # Faster
php artisan media-library:regenerate --ids=1,2,3      # Selective

# Schedule cleanup
$schedule->command('media-library:delete-old-temporary-uploads')->daily();
```

Detect orphan media (model deleted without cascade):

```php
$orphans = Media::whereDoesntHaveMorph('model', [User::class, Submission::class])->get();
$orphans->each->delete();
```

## Common Mistakes

1. **Forgetting `singleFile()`** — Without it, re-uploading adds a second file instead of replacing.
2. **Not checking `hasMedia()` before rendering** — `getFirstMediaUrl()` returns `''` when no media exists, creating broken `<img src="">` tags.
3. **Missing queue worker** — Queued conversions silently fail without a running worker. Use `nonQueued()` when the URL must be available immediately.
4. **Default disk in production** — The `public` disk is local. Multi-server deployments need `MEDIA_DISK=s3`.
5. **Incomplete validation** — Validate file size/MIME in both Livewire (UX) and the Action (security). Never trust the client alone.
6. **`addMedia()` outside Command Actions** — File mutations must go through Command Actions, never in Livewire directly.
7. **N+1 on media** — Always eager load: `User::with('media')->get()` when iterating collections in views.

## Verification Checklist

- [ ] Model implements `HasMedia` and uses `InteractsWithMedia`?
- [ ] Collections in `registerMediaCollections()` with validation rules?
- [ ] Conversions in `registerMediaConversions(?Media $media = null)`?
- [ ] `singleFile()` for replaceable collections?
- [ ] `addMedia()` inside a Command Action (not Livewire)?
- [ ] File size/MIME validated in both Livewire and Action?
- [ ] `hasMedia()` check before rendering URLs?
- [ ] Fallback URLs for queued conversions still processing?
- [ ] Eager loading: `with('media')` or `withCount('media')`?
- [ ] For S3: `MEDIA_DISK` env configured, temporary URL expiry set?
- [ ] For soft-delete: `deletePreservingMedia()` considered?
- [ ] Responsive images: `withResponsiveImages()` + `srcset()` in Blade?
- [ ] Custom properties documented and queryable?
- [ ] Storage symlink exists (`php artisan storage:link`)?
- [ ] Queue worker running if queued conversions are used?
- [ ] Orphan cleanup scheduled?
- [ ] Media migration run (`php artisan migrate`)?
