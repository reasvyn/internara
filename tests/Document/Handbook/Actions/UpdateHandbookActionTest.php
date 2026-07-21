<?php

declare(strict_types=1);

use App\Document\Enums\DocumentCategory;
use App\Document\Handbook\Actions\UpdateHandbookAction;
use App\Document\Handbook\Data\HandbookData;
use App\Document\Handbook\Enums\HandbookAudience;
use App\Document\Models\Document;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('updates handbook metadata without version bump', function () {
    $handbook = Document::factory()->create([
        'type' => DocumentCategory::HANDBOOK->value,
        'title' => 'Old Title',
        'version' => 1,
        'metadata' => ['target_audience' => 'student'],
    ]);

    $data = new HandbookData(
        title: 'New Title',
        audience: HandbookAudience::TEACHER,
        description: 'Updated description',
        isActive: false,
    );

    $updated = app(UpdateHandbookAction::class)->execute($handbook, $data);

    expect($updated->title)->toBe('New Title');
    expect($updated->version)->toBe(1);
    expect($updated->is_active)->toBeFalse();
});

test('updates handbook with file bumps version', function () {
    $handbook = Document::factory()->create([
        'type' => DocumentCategory::HANDBOOK->value,
        'title' => 'Original',
        'version' => 1,
    ]);

    $data = new HandbookData(
        title: 'Original',
        audience: HandbookAudience::ALL,
        file: UploadedFile::fake()->create('v2.pdf', 100),
    );

    $updated = app(UpdateHandbookAction::class)->execute($handbook, $data);

    expect($updated->version)->toBe(2);
});
