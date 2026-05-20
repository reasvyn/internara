# Laravel Media Library Reference

## What It Covers

Complete reference for `spatie/laravel-medialibrary` features: adding media from various sources (uploaded files, URLs, strings, base64, streams, disk), media collections (single-file, multi-file, MIME-restricted, disk-specific), media conversions (image manipulation, responsive images, queued/sync), retrieving media (URLs, paths, temporary S3 URLs), managing media (clear, delete, reorder, move/copy), custom properties, events, and configuration.

## Model Integration

Models implement `HasMedia` and use `InteractsWithMedia`. Collections are defined in `registerMediaCollections()` with options for single-file replacement, MIME type restrictions, fallback URLs, and custom disks. Conversions are defined in `registerMediaConversions()` with image manipulation (fit, crop, resize, sharpen, blur, greyscale) and configuration (queued/sync, scoped to collections, responsive images).

## Adding Media

Media can be added from uploaded files, HTTP requests, URLs, string content, base64, streams, or existing disk files. The `FileAdder` (returned by `addMedia()`) supports chaining: `usingName()`, `usingFileName()`, `withCustomProperties()`, `withResponsiveImages()`, `storingConversionsOnDisk()`. All chains end with `toMediaCollection('collection')`.

## Retrieving and Displaying

`getFirstMediaUrl('collection')` for original URL, `getFirstMediaUrl('collection', 'conversion')` for conversion URL. `getMedia('collection')` returns all items. `hasMedia('collection')` checks existence. Temporary URLs support S3 with expiry. Responsive images use `$media->toHtml()` or `$media->getSrcset()` for `<img srcset>`.

## Configuration

Key config options: default disk, max file size, queue settings, image driver (gd/imagick/vips), media model class, file namer, path generator, URL generator. Custom path generators and file namers allow custom storage structures. The Media model can be extended for custom scopes and methods.

## Testing

Use `Http::fake()` for URL-based media. Queue conversions in tests: `Queue::fake()`. Prevent stray disk writes with `Storage::fake('public')`.
