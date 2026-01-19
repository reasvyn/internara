# Spatie MediaLibrary: Modular Asset Management

Internara utilizes `spatie/laravel-medialibrary` to handle all file uploads and media processing.
This package is integrated into our `Media` module, providing a unified API for attaching documents,
images, and other assets to domain models.

---

## 1. Architectural Customizations

Our integration is designed to support diverse primary key types and modular isolation.

### 1.1 Polymorphic UUID Support

The default Spatie migration uses incrementing integers for `model_id`. We have modified this to a
string column to support both **UUIDs** and **Big Integers**.

- **Migration**: `modules/Media/database/migrations/..._create_media_table.php`
- **Cast**: The `model_id` attribute is explicitly cast to `string`.

### 1.2 Custom Media Model

We extend the base `Media` model to add a `module` attribute, allowing us to group assets by their
business domain (e.g., "Internship", "User").

---

## 2. Using Media in Your Module

### 2.1 Apply the InteractsWithMedia Concern

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Student extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('certificates')->singleFile();
    }
}
```

### 2.2 Attaching Files

```php
$student->addMedia($file)->toMediaCollection('certificates');
```

---

## 3. UI Integration

For a seamless user experience, use the `x-ui::file` component. It is pre-configured to handle
temporary Livewire uploads and display existing MediaLibrary items.

```blade
<x-ui::file label="Upload Document" wire:model="file" />
```

---

_MediaLibrary is essential for student logbooks and certification. Always define clear **Media
Collections** in your models to keep your assets organized._
