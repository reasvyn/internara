<?php

declare(strict_types=1);

use App\Document\Models\Document;
use App\Document\OfficialDocument\Actions\GenerateDocumentAction;
use App\Document\Services\DocumentRenderer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('GenerateDocumentAction', function () {
    it('generates a document and updates file path and metadata', function () {
        $document = Document::factory()->create([
            'file_path' => null,
            'metadata' => ['department' => 'Academic'],
        ]);

        $target = new class
        {
            public string $name = 'Test Student';
        };

        $renderer = Mockery::mock(DocumentRenderer::class);
        $renderer->shouldReceive('storePdf')
            ->once()
            ->andReturn('generated-documents/test-doc-12345.pdf');
        app()->instance(DocumentRenderer::class, $renderer);

        $result = app(GenerateDocumentAction::class)->execute($document, $target);

        expect($result)->toBeInstanceOf(Document::class);
        expect($result->file_path)->toBe('generated-documents/test-doc-12345.pdf');
        expect($result->metadata)->toHaveKey('generated_at');
    });

    it('preserves original metadata when generating document', function () {
        $document = Document::factory()->create([
            'file_path' => null,
            'metadata' => ['department' => 'Academic', 'tags' => ['renewal']],
        ]);

        $target = new class
        {
            public string $name = 'Test Student';
        };

        $renderer = Mockery::mock(DocumentRenderer::class);
        $renderer->shouldReceive('storePdf')
            ->once()
            ->andReturn('generated-documents/test-doc-67890.pdf');
        app()->instance(DocumentRenderer::class, $renderer);

        $result = app(GenerateDocumentAction::class)->execute($document, $target);

        expect($result->metadata['department'])->toBe('Academic');
        expect($result->metadata['tags'])->toBe(['renewal']);
        expect($result->metadata)->toHaveKey('generated_at');
    });
});
