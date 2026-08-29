# Media Upload & Retrieval — Action Ownership and Access Patterns

Uploads and retrieval are the two ends of the media lifecycle. Uploads must happen inside Command
Actions (never in Livewire); retrieval must go through MediaLibrary's accessors. Getting either end
wrong breaks the mutation boundary or leaks storage internals into views.

---

## Intent

Handle uploads in a Command Action (never Livewire), call `addMedia()` inside the Action, retrieve via
`getFirstMediaUrl()` / `getMedia()`, and delete via `clearMediaCollection()` / `deleteMedia()`.

## Rationale — What Fails Without It

- **Uploading in Livewire** (C1-adjacent) hits the storage codec directly, skipping the Action's
  transaction + audit log, and couples the component to MediaLibrary. The component should pass the
  `UploadedFile` to an Action whose `execute()` owns the media call.
- **Retrieval scattered with storage internals** (`Storage::url()`, hardcoded paths) breaks the
  moment the disk changes and duplicates MediaLibrary's own URL logic; `getFirstMediaUrl()` returns
  the correct URL for the current disk config.
- **Deleting via raw disk functions** leaves orphaned `media` rows and stale conversions; the
  collection/media methods remove the records MediaLibrary tracks.
- **Forgetting which method fits the cardinality** (`getFirstMediaUrl` vs `getMedia`) means single-file
  collections get ambiguous "first of many" semantics or multi-file collections lose items.

## How to Apply

### Upload — in a Command Action

```php
final readonly class UploadAvatarAction extends BaseCommandAction
{
    public function execute(UploadAvatarData $data): ActionResponse
    {
        return $this->transaction(function () use ($data) {
            $user = User::findOrFail($data->userId);
            $user->addMedia($data->file)
                ->usingFileName(Str::slug($data->file->getClientOriginalName()))
                ->toMediaCollection('avatar');

            return ActionResponse::success();
        });
    }
}
```

The Livewire component stays thin — it validates and calls the Action:

```php
public function save(UploadAvatarAction $action): void
{
    $this->validate();
    $action->execute($this->avatar, ...);
}
```

### Retrieve

```php
// Single file — with optional conversion
$url   = $model->getFirstMediaUrl('avatar');
$thumb = $model->getFirstMediaUrl('avatar', 'thumb');

// Multiple files
$mediaItems = $model->getMedia('documents');
```

- Single-file collections → `getFirstMediaUrl($collection[, $conversion])`.
- Multi-file collections → `getMedia($collection)` returning a collection of `Media` models to loop
  in the view.

### Delete

```php
$model->clearMediaCollection('avatar'); // all media in the collection
$model->deleteMedia($mediaId);          // a specific Media item
```

## Anti-Patterns & Pitfalls

- `$component->addMedia(...)` inside Livewire — model mutation / media call outside an Action.
- `Storage::url($path)` or `asset('storage/' . $path)` to build media URLs — MediaLibrary owns URLs.
- `getFirstMediaUrl('documents')` where documents is multi-file — silently ignores all but the first.
- `DB::table('media')->where('model_id', $id)->delete()` — leaves rows MediaLibrary expects to manage
  and breaks collection bookkeeping.
- Retrieving media and passing raw `Media` objects to views that then call `->getUrl()` inside Blade
  — precompute URLs in the query/Read Action instead.

## Verification

- Grep feature/upload code: `addMedia(` appears only inside Actions.
- Views use `getFirstMediaUrl()`/`getMedia()` — no `Storage` facade in Blade.
- `python3 tools/scan_violations.py` clean (no mutation from Livewire); a test covers upload +
  retrieval (see `pest-testing` — trace to the governing spec's FR).
