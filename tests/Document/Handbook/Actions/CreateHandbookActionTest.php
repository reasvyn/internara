<?php

declare(strict_types=1);

use App\Document\Enums\DocumentCategory;
use App\Document\Handbook\Actions\CreateHandbookAction;
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

test('creates handbook with type handbook', function () {
    $file = UploadedFile::fake()->create('guidelines.pdf', 100);

    $data = new HandbookData(
        title: 'PKL Guidelines',
        audience: HandbookAudience::STUDENT,
        description: 'Student guidelines',
        isActive: true,
        file: $file,
    );

    $handbook = app(CreateHandbookAction::class)->execute($data);

    expect($handbook)->toBeInstanceOf(Document::class);
    expect($handbook->type)->toBe(DocumentCategory::HANDBOOK->value);
    expect($handbook->title)->toBe('PKL Guidelines');
    expect($handbook->version)->toBe(1);
    expect($handbook->metadata['target_audience'])->toBe('student');
});

test('creates handbook without file when not provided', function () {
    $data = new HandbookData(
        title: 'General Policy',
        audience: HandbookAudience::ALL,
    );

    $handbook = app(CreateHandbookAction::class)->execute($data);

    expect($handbook->version)->toBe(1);
    expect($handbook->getFirstMedia('handbook_file'))->toBeNull();
});
