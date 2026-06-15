<?php

declare(strict_types=1);

namespace App\Document\OfficialDocument\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Document\Models\Document;
use App\Document\Support\DocumentRenderer;

final class GenerateDocumentAction extends BaseCommandAction
{
    public function __construct(protected readonly DocumentRenderer $renderer) {}

    public function execute(Document $document, object $target): Document
    {
        return $this->transaction(function () use ($document, $target) {
            $path = $this->renderer->storePdf($document, $target);

            $document->update([
                'file_path' => $path,
                'metadata' => array_merge($document->metadata ?? [], [
                    'generated_at' => now()->toIso8601String(),
                ]),
            ]);

            $this->log('document_generated', $document, [
                'document_id' => $document->id,
                'type' => $document->type,
            ]);

            return $document->fresh();
        });
    }
}
