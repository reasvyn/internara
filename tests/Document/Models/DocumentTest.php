<?php

declare(strict_types=1);

use App\Document\Models\Document;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('document belongs to creator', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create(['created_by' => $user->id]);

    expect($document->createdBy)->toBeInstanceOf(User::class);
});

test('scope active returns only active documents', function () {
    Document::factory()->create(['is_active' => true]);
    Document::factory()->create(['is_active' => false]);

    $active = Document::active()->get();

    expect($active)->toHaveCount(1);
});

test('scope ofType filters by type', function () {
    Document::factory()->create(['type' => 'report']);
    Document::factory()->create(['type' => 'template']);

    expect(Document::ofType('report')->get())->toHaveCount(1);
});

test('casts is_active as boolean', function () {
    $document = Document::factory()->create(['is_active' => true]);

    expect($document->is_active)->toBeTrue();
});

test('download name accessor returns original name or title with pdf', function () {
    $document = Document::factory()->create(['title' => 'My Document']);

    expect($document->download_name)->toBe('My Document.pdf');
});
