# Media Conversions & Responsive Images — Processing Config

Image processing is configured declaratively on the collection. Conversions and responsive images
turn one uploaded original into the set of always-optimized variants the UI serves — and if they are
not declared, every view renders the full-resolution original, which is the difference between a
snappy page and a multi-megabyte download.

---

## Intent

Declare media conversions on the collection via `registerMediaConversions()` for image processing —
thumbnails and responsive variants — driven by the collection's own `addMediaConversion()` calls.
Conversion sizes, the storage disk, and the queue driver are configured in `config/media-library.php`.

## Rationale — What Fails Without It

- **No conversions declared** means `getMediaUrl()` variants like `'thumb'` do not exist; the UI must
  use the full original everywhere — heavy images on list pages, thumbnails, and PDF/letter views.
- **Oversized originals streamed in full** waste bandwidth and slow every render; a
  `thumb (150x150)` knows the exact variant list pages need.
- **Conversions re-generated on every request** (or declared where MediaLibrary cannot enqueue them)
  burn CPU; declaring them on the collection lets MediaLibrary generate once and cache the result.
- **Wrong disk/queue** (see the storage/queue rule) means conversions or originals land where they
  cannot be served or processing never runs.

## How to Apply

Define conversions inside the collection registration:

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

- **Default conversion:** `thumb (150x150)` for list/thumbnail contexts.
- Add larger named conversions (e.g. `preview`, `full`) only where the spec names a concrete size —
  each conversion costs storage and generation time (spec-driven: no size = no conversion).
- Declare conversions only for **image** collections; document/PDF collections need no conversions.

### Config in `config/media-library.php`

| Setting   | Purpose                                    | Default     |
| --------- | ------------------------------------------ | ----------- |
| Disk      | Where originals + conversions are stored   | `public`    |
| Queue     | Queue on which conversion jobs run         | `default`   |

Point conversions at the public/local disk so generated variants are servable from web storage; a
private disk breaks URL serving without a signed-route strategy.

## Anti-Patterns & Pitfalls

- Adding a conversion the frontend never requests — dead storage and generation cost.
- Declaring conversions on a non-image collection — wasted files.
- Naming a conversion `'thumbnail'` while views request `'thumb'` — mystery broken images at runtime.
- Forgetting the `public` disk on a box where files must be directly URL-visible — `404` on every
  media URL.
- Turning off the queue for conversion jobs on a large batch — blocking requests during generation.

## Verification

- For each image collection: a `thumb` conversion exists and matches what Blade views request.
- `getFirstMediaUrl($collection, 'thumb')` produces a URL whose served bytes match the declared
  dimensions.
- `docs/guides/infra/media-library.md` §Conversions matches the declared set; MediaLibrary's
  `sync:media-library` regeneration passes.
