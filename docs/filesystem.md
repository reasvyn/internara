# Filesystem Documentation: Internara

## 1. Overview

Internara uses Laravel's filesystem abstraction (Flysystem) for managing file storage across
multiple disks. The system supports local storage, public assets, and cloud storage (S3-compatible).

### Configuration

- **Config File**: `config/filesystems.php`
- **Environment Variables**: `FILESYSEM_DISK`, `AWS_*`, `AZURE_*`
- **Default Disk**: Local (`env('FILESYSEM_DISK', 'local')`)

## 2. Filesystem Disks

### Available Disks

| Disk     | Driver | Root Path             | Visibility   | Use Case                  |
| -------- | ------ | --------------------- | ------------ | ------------------------- |
| `local`  | local  | `storage/app/private` | Private      | Private files, temp files |
| `public` | local  | `storage/app/public`  | Public       | Public assets, uploads    |
| `s3`     | s3     | AWS S3 bucket         | Configurable | Cloud storage, backups    |

### Default Configuration

```env
FILESYSEM_DISK=local

# S3 (Optional)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_URL=
AWS_ENDPOINT=
AWS_USE_PATH_STYLE_ENDPOINT=false
```

## 3. Disk Details

### Local Disk (Default)

```php
// config/filesystems.php
'local' => [
    'driver' => 'local',
    'root' => storage_path('app/private'),
    'serve' => true,  // Allow serving private files via PHP
    'throw' => false,
    'report' => false,
],
```

**Paths**:

- Root: `storage/app/private/`
- Example file: `storage/app/private/documents/report.pdf`

**Access**:

```php
use Illuminate\Support\Facades\Storage;

// Store
Storage::disk('local')->put('documents/report.pdf', $content);

// Retrieve
$content = Storage::disk('local')->get('documents/report.pdf');

// Check existence
if (Storage::disk('local')->exists('documents/report.pdf')) {
}

// URL (if serve=true)
$url = Storage::disk('local')->url('documents/report.pdf');
// Returns: /storage/app/private/documents/report.pdf (via symlink)
```

### Public Disk

```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL') . '/storage',
    'visibility' => 'public',
    'throw' => false,
    'report' => false,
],
```

**Paths**:

- Root: `storage/app/public/`
- Public URL: `https://your-app.com/storage/branding/logo.png`

**Symbolic Link**:

```bash
# Create symbolic link (required for public disk)
php artisan storage:link

# Creates: public/storage → storage/app/public
```

**Usage**:

```php
// Store public file
Storage::disk('public')->put('branding/logo.png', $imageData);

// Get public URL
$url = Storage::disk('public')->url('branding/logo.png');
// Returns: https://your-app.com/storage/branding/logo.png
```

### S3 Disk (Cloud Storage)

```php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    'throw' => false,
    'report' => false,
],
```

**Usage**:

```php
// Store on S3
Storage::disk('s3')->put('backups/database.sql', $sqlContent);

// Get temporary URL (signed)
$url = Storage::disk('s3')->temporaryUrl('backups/database.sql', now()->addMinutes(5));
```

## 4. File Operations

### Basic CRUD Operations

#### Create/Update (Put)

```php
use Illuminate\Support\Facades\Storage;

// Simple put
Storage::put('file.txt', 'Hello World');

// Put with visibility
Storage::put('file.txt', 'Hello', 'public');

// PutFile (from uploaded file)
$path = Storage::putFile('documents', $request->file('document'));

// PutFileAs (with custom name)
$path = Storage::putFileAs('documents', $request->file('document'), 'contract.pdf');

// Prepend/Append
Storage::prepend('log.txt', 'First line');
Storage::append('log.txt', 'Last line');
```

#### Read (Get)

```php
// Get file content
$content = Storage::get('file.txt');

// Download response
return Storage::download('documents/contract.pdf', 'new-name.pdf');

// Stream download (large files)
return Storage::response('documents/large-file.zip');
```

#### Delete

```php
// Delete single file
Storage::delete('file.txt');

// Delete multiple files
Storage::delete(['file1.txt', 'file2.txt']);

// Delete directory (recursive)
Storage::deleteDirectory('temp');
```

#### Check Existence & Metadata

```php
// Existence
if (Storage::exists('file.txt')) {
}
if (Storage::missing('file.txt')) {
} // Opposite

// Size & MIME
$size = Storage::size('file.txt'); // In bytes
$mime = Storage::mimeType('file.pdf'); // e.g., "application/pdf"

// Last modified
$timestamp = Storage::lastModified('file.txt');
```

### Directory Operations

```php
// Create directory
Storage::makeDirectory('documents/2026');

// List files
$files = Storage::files('documents'); // Files only
$allFiles = Storage::allFiles('documents'); // Recursive

// List directories
$dirs = Storage::directories('documents');
$allDirs = Storage::allDirectories('documents'); // Recursive

// Copy/Move
Storage::copy('old.txt', 'new.txt');
Storage::move('old.txt', 'new.txt');
```

## 5. Real-World Usage in Internara

### Example 1: Brand Logo Upload (`app/Domain/System/Livewire/Admin/SystemSetting.php`)

```php
class SystemSetting extends Component
{
    public $brand_logo;
    public $site_favicon;

    public function saveBranding(): void
    {
        // Validate
        $this->validate([
            'brand_logo' => 'nullable|image|max:2048',
            'site_favicon' => 'nullable|image|max:512',
        ]);

        // Store logo
        if ($this->brand_logo) {
            $path = $this->brand_logo->store('branding', 'public');
            $settings['brand_logo'] = Storage::url($path);
        }

        // Store favicon
        if ($this->site_favicon) {
            $path = $this->site_favicon->store('branding', 'public');
            $settings['site_favicon'] = Storage::url($path);
        }

        // Save settings...
    }
}
```

### Example 2: PDF Generation (`app/Domain/Document/Actions/GeneratePdfAction.php`)

```php
class GeneratePdfAction
{
    public function execute(string $view, array $data): string
    {
        $pdf = Pdf::loadView($view, $data);
        $tempPath = 'temp/' . uniqid() . '.pdf';

        // Store temporarily on local disk
        Storage::disk('local')->put($tempPath, $pdf->output());

        return Storage::disk('local')->path($tempPath); // Return full path
    }
}
```

### Example 3: Spatie Media Library Integration

Internara uses Spatie Media Library for file attachments:

```php
// Attaching media to models
$internship->addMedia($uploadedFile)->toMediaCollection('documents');

// Retrieving media
$url = $internship->getFirstMediaUrl('documents');
$path = $internship->getFirstMediaPath('documents');
```

## 6. File Security (S1)

### S1 - Secure: File Upload Validation

```php
// Always validate uploads
$request->validate([
    'document' => [
        'required',
        'file', // Must be file
        'mimes:pdf,doc,docx', // Allowed extensions
        'max:10240', // Max 10MB
    ],
]);
```

### S1 - Secure: Prevent Path Traversal

```php
// ❌ DON'T: Allow user-controlled paths
$filename = $request->input('filename'); // Dangerous!
Storage::delete($filename);

// ✅ DO: Use basename() and validate
$filename = basename($request->input('filename')); // Strip path
if (!preg_match('/^[a-zA-Z0-9_\.-]+$/', $filename)) {
    abort(400, 'Invalid filename');
}
Storage::delete('uploads/' . $filename);
```

### S1 - Secure: Private Files

```php
// Private files on 'local' disk (not 'public')
Storage::disk('local')->put('private/report.pdf', $content);

// Serve with authorization check
public function downloadReport(string $filename)
{
    $this->authorize('view', Report::class);

    if (! Storage::disk('local')->exists('private/' . $filename)) {
        abort(404);
    }

    return Storage::disk('local')->download('private/' . $filename);
}
```

### S1 - Secure: Sensitive Data in Files

- Never store passwords, API keys, or tokens in files
- Use `.gitignore` to prevent committing sensitive files
- Encrypt sensitive files: `Storage::put('secret.txt', encrypt($data))`

## 7. File Visibility & URLs

### Visibility Types

| Visibility | Description            | Use Case                 |
| ---------- | ---------------------- | ------------------------ |
| `private`  | Not accessible via URL | User uploads, temp files |
| `public`   | Accessible via URL     | Images, public documents |

```php
// Set visibility on upload
Storage::put('file.txt', $content, 'public');

// Change visibility
Storage::setVisibility('file.txt', 'private');

// Get visibility
$visibility = Storage::visibility('file.txt'); // 'public' or 'private'
```

### Generating URLs

```php
// Public disk: Direct URL
$url = Storage::disk('public')->url('image.jpg');
// Returns: https://app.com/storage/image.jpg

// Local private disk: Serve via route (with auth)
$url = route('files.download', ['path' => 'private/report.pdf']);

// S3: Temporary signed URL (expires)
$url = Storage::disk('s3')->temporaryUrl('backups/db.sql', now()->addHour());
```

## 8. Testing with Filesystem

### Using Fake Disk (Laravel Testing)

```php
use Illuminate\Support\Facades\Storage;

test('it uploads file', function () {
    // Fake the disk
    Storage::fake('public');

    // Simulate upload
    $response = $this->post('/upload', [
        'document' => UploadedFile::fake()->create('test.pdf', 100),
    ]);

    // Assert file exists
    Storage::disk('public')->assertExists('documents/test.pdf');

    // Assert file missing
    Storage::disk('public')->assertMissing('documents/fake.pdf');
});
```

### In Pest PHP

```php
test('file operations', function () {
    Storage::fake('local');

    // Store
    Storage::put('test.txt', 'Hello');
    expect(Storage::exists('test.txt'))->toBeTrue();

    // Read
    expect(Storage::get('test.txt'))->toBe('Hello');

    // Delete
    Storage::delete('test.txt');
    expect(Storage::missing('test.txt'))->toBeTrue();
});
```

## 9. Spatie Media Library Integration

### Configuration

- **Config File**: `config/media-library.php`
- **Table**: `media` (Spatie package migration)
- **Storage Disk**: Configurable per collection

### Basic Usage

```php
use App\Models\Internship;

// Add media
$internship = Internship::find($id);
$internship
    ->addMedia($uploadedFile)
    ->preservingOriginal() // Optional: keep original
    ->toMediaCollection('documents');

// Get media
$mediaItems = $internship->getMedia('documents');
$firstMedia = $internship->getFirstMedia('documents');

// Get URL
$url = $internship->getFirstMediaUrl('documents');
$path = $internship->getFirstMediaPath('documents');

// Delete media
$mediaItems[0]->delete();
```

### Media Conversions (Optimization)

```php
// In your model
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(368)->height(232)->sharpen();
    }
}

// Usage
$thumbUrl = $user->getFirstMediaUrl('images', 'thumb');
```

## 10. File Maintenance

### Cleanup Temporary Files

```php
// In a scheduled job (app/Console/Kernel.php)
Schedule::call(function () {
    $files = Storage::files('temp');

    foreach ($files as $file) {
        if (Storage::lastModified($file) < now()->subDays(1)->timestamp) {
            Storage::delete($file);
        }
    }
})->daily();
```

### Backup to S3

```php
// Backup database export
Storage::disk('s3')->put(
    'backups/db-' . now()->format('Y-m-d') . '.sql',
    file_get_contents($localPath),
);
```

### Monitor Disk Usage

```bash
# Check storage size
du -sh storage/app/*

# Find large files
find storage/app -type f -size +10M
```

## 11. Performance Considerations

### S3 vs Local Storage

| Factor          | Local           | S3               |
| --------------- | --------------- | ---------------- |
| **Speed**       | ⚡⚡⚡ Fast     | ⚡⚡ Moderate    |
| **Scalability** | ⚠️ Limited      | ✅✅✅ Excellent |
| **Cost**        | Free (disk)     | Pay per GB       |
| **Reliability** | ⚠️ Single point | ✅✅ Redundant   |

### Optimization Tips

1. **Use `public` disk for images** → Direct URL access (no PHP overhead)
2. **Fake disk in tests** → Fast, no real I/O
3. **S3 for large files** → Offload storage burden
4. **Media conversions** → Serve optimized images (thumbnails)
5. **Temporary URLs** → Avoid storing public links for S3

## 12. Troubleshooting

### "File not found" Errors

1. Check disk configuration: `php artisan about`
2. Verify file exists: `Storage::exists('path')`
3. Check permissions: `ls -la storage/app/`
4. For public files: Run `php artisan storage:link`

### Permission Issues

```bash
# Fix storage permissions
chmod -R 755 storage/
chown -R www-data:www-data storage/
```

### Symbolic Link Issues

```bash
# Remove and recreate link
rm public/storage
php artisan storage:link
```

---

**Last Updated**: April 30, 2026  
**Default Disk**: Local  
**Public URL**: `/storage/` (via symlink)  
**Media Table**: `media` (Spatie)
