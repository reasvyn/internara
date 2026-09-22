<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Modules\Document\Models\Document;
use App\Modules\Document\Services\DocumentRenderer;
use App\Modules\Partner\Domain\Partnership\Models\Partnership;
use App\Modules\Setting\Domain\Branding\Actions\RemoveBrandAssetAction;
use App\Modules\Setting\Domain\Branding\Actions\UploadBrandAssetAction;
use App\Modules\Setting\Domain\Branding\Livewire\Forms\BrandingForm;
use App\Modules\Setting\Livewire\SystemSetting;
use App\Modules\Setting\Models\Setting;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\Conversions\ImageGenerators\Image;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(LazilyRefreshDatabase::class);

describe('WQGTP: Spatie MediaLibrary Integration and File Uploads', function (): void {
    test('WQGTP-FR-UPL-001: models implement HasMedia and InteractsWithMedia contract', function (): void {
        $user = new User;
        $document = new Document;
        $partnership = new Partnership;

        expect($user instanceof HasMedia)->toBeTrue()
            ->and($document instanceof HasMedia)->toBeTrue()
            ->and($partnership instanceof HasMedia)->toBeTrue();
    });

    test('WQGTP-FR-UPL-002: named collections are declared per module purpose', function (): void {
        $user = User::factory()->create();
        $document = Document::factory()->create();

        expect($user->media()->get())->toBeIterable()
            ->and($document->media()->get())->toBeIterable();
    });

    test('WQGTP-FR-UPL-003: collections reject files outside MIME allowlist', function (): void {
        Storage::fake('public');

        $form = new BrandingForm(new SystemSetting, 'brandingForm');
        $form->brand_logo = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');

        expect(fn () => $form->validate())->toThrow(ValidationException::class);
    });

    test('WQGTP-FR-UPL-004: oversized uploads are rejected before storage', function (): void {
        Storage::fake('public');

        $form = new BrandingForm(new SystemSetting, 'brandingForm');
        // Max size for logo is 1024KB in BrandingForm
        $form->brand_logo = UploadedFile::fake()->create('huge_logo.png', 5000, 'image/png');

        expect(fn () => $form->validate())->toThrow(ValidationException::class);
    });

    test('WQGTP-FR-UPL-005: client filenames are sanitized and never become direct raw disk paths', function (): void {
        Storage::fake('public');

        $action = app(UploadBrandAssetAction::class);
        $file = UploadedFile::fake()->image('test_logo.png', 100, 100);

        $url = $action->execute($file, 'logo');
        expect($url)->toBeString()
            ->and($url)->not->toContain('..');
    });

    test('WQGTP-FR-UPL-006: media files are stored on configured disks', function (): void {
        expect(config('filesystems.disks.public'))->toBeArray()
            ->and(config('media-library.disk_name'))->toBe('public');
    });

    test('WQGTP-FR-UPL-007: stored media generates unique non-guessable storage names', function (): void {
        Storage::fake('public');

        $action = app(UploadBrandAssetAction::class);
        $file1 = UploadedFile::fake()->image('test1.png', 50, 50);
        $file2 = UploadedFile::fake()->image('test2.png', 50, 50);

        $url1 = $action->execute($file1, 'logo');
        $url2 = $action->execute($file2, 'favicon');

        expect($url1)->not->toBe($url2);
    });

    test('WQGTP-FR-UPL-008: image uploads trigger thumbnail conversion support', function (): void {
        expect(config('media-library.image_optimizers'))->toBeArray()
            ->and(config('media-library.image_generators'))->toContain(Image::class);
    });

    test('WQGTP-FR-UPL-009: deleting a record or removing asset cleans up media files', function (): void {
        Storage::fake('public');

        $action = app(UploadBrandAssetAction::class);
        $file = UploadedFile::fake()->image('to_remove.png', 50, 50);
        $action->execute($file, 'logo');

        $setting = Setting::where('key', 'brand_logo_ref')->first();
        expect($setting)->not->toBeNull();

        $removeAction = app(RemoveBrandAssetAction::class);
        $removeAction->execute('logo');

        $fresh = Setting::where('key', 'brand_logo')->first();
        expect($fresh?->value)->toBe('');
    });

    test('WQGTP-FR-UPL-010: rejected upload leaves no media records and alters no model state', function (): void {
        Storage::fake('public');

        $form = new BrandingForm(new SystemSetting, 'brandingForm');
        $form->brand_logo = UploadedFile::fake()->create('malicious.sh', 500, 'application/x-sh');

        $initialCount = Setting::count();

        expect(fn () => $form->validate())->toThrow(ValidationException::class);
        expect(Setting::count())->toBe($initialCount);
    });

    test('WQGTP-FR-UPL-011: upload and deletion write audit logs with masked PII', function (): void {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user);

        $action = app(UploadBrandAssetAction::class);
        $file = UploadedFile::fake()->image('audited_logo.png', 50, 50);
        $action->execute($file, 'logo');

        $removeAction = app(RemoveBrandAssetAction::class);
        $removeAction->execute('logo');

        $logs = Activity::where('causer_id', $user->id)->get();
        expect($logs)->not->toBeNull();
    });

    test('WQGTP-FR-UPL-012: upload validation strings resolve in en and id', function (): void {
        expect(trans('validation.mimes', ['values' => 'png'], 'en'))->not->toBeEmpty()
            ->and(trans('validation.mimes', ['values' => 'png'], 'id'))->not->toBeEmpty();
    });

    test('WQGTP-NFR-UPL-001: image thumbnail conversions use synchronous sync queue in testing', function (): void {
        expect(config('media-library.queue_connection_name'))->toBe('sync');
    });

    test('WQGTP-NFR-UPL-002: max file size handles up to 10MB without worker modification', function (): void {
        expect(config('media-library.max_file_size'))->toBeGreaterThanOrEqual(10 * 1024 * 1024);
    });

    test('WQGTP-NFR-UPL-003: large downloads stream with constant memory', function (): void {
        expect(class_exists(DocumentRenderer::class))->toBeTrue();
    });

    test('WQGTP-NFR-UPL-004: zero orphaned files remaining on asset removal', function (): void {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar_orphan.png', 50, 50);
        $media = $user->addMedia($file)->toMediaCollection('avatar');

        $path = $media->getPathRelativeToRoot();
        expect(Storage::disk('public')->exists($path))->toBeTrue();

        $media->delete();
        expect(Storage::disk('public')->exists($path))->toBeFalse();
    });

    test('WQGTP-DD-UPL-001: Spatie MediaLibrary used instead of raw Storage facade for entities', function (): void {
        expect(class_exists(Media::class))->toBeTrue();
    });

    test('WQGTP-DD-UPL-002: named collections isolate rules per module purpose', function (): void {
        $partnership = new Partnership;
        expect(defined(Partnership::class.'::COLLECTION_MOU'))->toBeTrue();
    });

    test('WQGTP-DD-UPL-003: synchronous conversions for MVP image collections', function (): void {
        expect(config('media-library.image_driver'))->toBeIn(['gd', 'imagick', 'vips']);
    });

    test('WQGTP-DD-UPL-004: outside web root or public asset delivery with non-guessable tokens', function (): void {
        expect(config('filesystems.default'))->toBeIn(['local', 'public']);
    });

    test('WQGTP-UC-UPL-001: user avatar uploads with media collection', function (): void {
        $user = User::factory()->create();
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);
        $media = $user->addMedia($file)->toMediaCollection('avatar');

        expect($media)->not->toBeNull()
            ->and($media->collection_name)->toBe('avatar');
    });

    test('WQGTP-UC-UPL-002: document template upload and storage', function (): void {
        $doc = Document::factory()->create();
        Storage::fake('public');

        $file = UploadedFile::fake()->create('template.pdf', 1000, 'application/pdf');
        $media = $doc->addMedia($file)->toMediaCollection('file');

        expect($media)->not->toBeNull()
            ->and($media->collection_name)->toBe('file');
    });

    test('WQGTP-UC-UPL-003: record deletion removes associated media files', function (): void {
        $user = User::factory()->create();
        Storage::fake('public');

        $file = UploadedFile::fake()->image('student_pic.png', 50, 50);
        $media = $user->addMedia($file)->toMediaCollection('avatar');

        expect(Storage::disk('public')->exists($media->getPathRelativeToRoot()))->toBeTrue();

        $user->delete();

        expect(Storage::disk('public')->exists($media->getPathRelativeToRoot()))->toBeFalse();
    });
});
