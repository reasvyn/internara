<?php

declare(strict_types=1);

use App\Modules\Document\Domain\OfficialDocument\Actions\GenerateDocumentAction;
use App\Modules\Document\Jobs\GenerateDocumentJob;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Services\DocumentRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

test('8FVZA-FR-JOB1/JOB2/JOB3: GenerateDocumentJob is a queued job with tries and backoff', function () {
    $job = new GenerateDocumentJob(documentId: 'doc-id');

    expect($job)->toBeInstanceOf(ShouldQueue::class)
        ->and($job->tries)->toBe(3)
        ->and($job->backoff)->toBe([2, 10, 30])
        ->and($job->queue)->toBe('documents');
});

test('7UB7S-FR-PDF1: handle renders and stores the PDF for the document', function () {
    $document = Document::factory()->create(['file_path' => null]);

    $renderer = Mockery::mock(DocumentRenderer::class);
    $renderer->shouldReceive('storePdf')
        ->once()
        ->with(
            Mockery::on(fn ($d) => $d instanceof Document && $d->id === $document->id),
            Mockery::on(fn ($d) => $d instanceof Document && $d->id === $document->id),
        )
        ->andReturn('generated-documents/test.pdf');

    $this->app->instance(DocumentRenderer::class, $renderer);

    $job = new GenerateDocumentJob(documentId: $document->id);
    $job->handle(app(GenerateDocumentAction::class));

    $document->refresh();
    expect($document->file_path)->toBe('generated-documents/test.pdf')
        ->and($document->metadata['generated_at'])->not->toBeNull();
});

test('8FVZA-UC-1: missing document throws during handle', function () {
    $job = new GenerateDocumentJob(documentId: 'missing-doc');

    expect(fn () => $job->handle(app(GenerateDocumentAction::class)))
        ->toThrow(ModelNotFoundException::class);
});

test('8FVZA-NFR-JOB4: failed() logs the generation error with document id context', function () {
    $logs = captureLogs();

    $job = new GenerateDocumentJob(documentId: 'doc-123');

    $job->failed(new RuntimeException('pdf rendering failed'));

    $log = $logs->last();
    expect($log->level)->toBe('error')
        ->and($log->message)->toBe('Document generation failed')
        ->and($log->context['document_id'])->toBe('doc-123')
        ->and($log->context['error'])->toBe('pdf rendering failed');
});

test('8FVZA-FR-JOB5: dispatch pushes the job to the documents queue', function () {
    $document = Document::factory()->create();

    GenerateDocumentJob::dispatch(documentId: $document->id);

    Queue::assertPushedOn('documents', GenerateDocumentJob::class);
});
