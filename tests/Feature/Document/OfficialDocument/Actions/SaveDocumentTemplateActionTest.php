<?php

declare(strict_types=1);

use App\Document\Models\Document;
use App\Document\OfficialDocument\Actions\SaveDocumentTemplateAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('saves new document template', function () {
    $document = app(SaveDocumentTemplateAction::class)->execute([
        'name' => 'Surat Pengantar',
        'content' => '<p>Template content</p>',
    ]);

    expect($document)->toBeInstanceOf(Document::class);
    expect($document->slug)->toBe('surat-pengantar');
});

test('updates existing document template', function () {
    $existing = Document::factory()->create();

    $updated = app(SaveDocumentTemplateAction::class)->execute([
        'id' => $existing->id,
        'name' => 'Updated Name',
        'content' => 'Updated content',
    ]);

    expect($updated->id)->toBe($existing->id);
    expect($updated->name)->toBe('Updated Name');
});
