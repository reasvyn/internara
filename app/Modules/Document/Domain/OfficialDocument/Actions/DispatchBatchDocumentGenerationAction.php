<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\OfficialDocument\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Document\Jobs\GenerateDocumentJob;

final class DispatchBatchDocumentGenerationAction extends BaseCommandAction
{
    /**
     * Dispatch document generation jobs to the configured documents queue.
     *
     * @param list<string> $documentIds
     */
    public function execute(array $documentIds): void
    {
        $queue = (string) config('document-official.batch_queue', 'documents');

        foreach ($documentIds as $documentId) {
            GenerateDocumentJob::dispatch($documentId)->onQueue($queue);
        }

        $this->log('document_batch_queued', null, [
            'document_count' => count($documentIds),
            'queue' => $queue,
        ]);
    }
}
