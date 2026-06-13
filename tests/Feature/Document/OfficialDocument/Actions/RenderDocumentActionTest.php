<?php

declare(strict_types=1);

use App\Document\Models\Document;
use App\Document\OfficialDocument\Actions\RenderDocumentAction;
use App\Enrollment\Registration\Models\Registration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('renders document for registration', function () {
    $document = Document::factory()->create();
    $registration = Registration::factory()->create();

    $rendered = app(RenderDocumentAction::class)->execute($document, $registration);

    expect($rendered)->toBeInstanceOf(Document::class);
    expect($rendered->template_id)->toBe($document->id);
});

test('download returns file path for existing document', function () {
    $document = Document::factory()->create(['file_path' => 'reports/test.pdf']);

    Storage::fake('local');
    Storage::disk('local')->put('reports/test.pdf', 'content');

    $path = app(RenderDocumentAction::class)->download($document);

    expect($path)->toBe('reports/test.pdf');
});
