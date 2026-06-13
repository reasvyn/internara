<?php

declare(strict_types=1);

namespace App\Jobs\Document;

use App\Document\Models\Document;
use App\Document\OfficialDocument\Actions\GenerateDocumentAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [2, 10, 30];

    public function __construct(
        protected readonly string $documentId,
    ) {}

    public function handle(GenerateDocumentAction $generateDocument): void
    {
        $document = Document::findOrFail($this->documentId);

        $generateDocument->execute($document, $document);
    }

    public function failed(\Throwable $e): void
    {
        logger()->error('Document generation failed', [
            'document_id' => $this->documentId,
            'error' => $e->getMessage(),
        ]);
    }
}
