<?php

declare(strict_types=1);

namespace App\Actions\Document;

use App\Actions\Audit\LogAuditAction;
use App\Models\DocumentTemplate;
use App\Models\FormalDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * S2 - Sustain: Logic for generating formal documents from templates.
 * S3 - Scalable: Stateless action.
 */
class GenerateDocumentAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    /**
     * Generate a formal document from a template.
     */
    public function execute(Model $target, DocumentTemplate $template, array $additionalData = []): FormalDocument
    {
        return DB::transaction(function () use ($target, $template, $additionalData) {
            // Prepare merge data (e.g., student name, school name)
            $mergeData = array_merge([
                'target' => $target,
                'now' => now()->format('d F Y'),
            ], $additionalData);

            // Render content using Blade engine
            $renderedContent = Blade::render($template->content, $mergeData);

            /** @var FormalDocument $document */
            $document = FormalDocument::create([
                'documentable_id' => $target->getKey(),
                'documentable_type' => $target->getMorphClass(),
                'template_id' => $template->id,
                'title' => $template->name . ' - ' . ($target->name ?? $target->id),
                'document_number' => $this->generateDocumentNumber($template),
                'issued_at' => now(),
                'metadata' => [
                    'rendered_content' => $renderedContent,
                ],
            ]);

            $document->setStatus('draft', 'Generated from template.');

            $this->logAuditAction->execute(
                action: 'document_generated',
                subjectType: FormalDocument::class,
                subjectId: $document->id,
                payload: [
                    'template_id' => $template->id,
                    'target_id' => $target->getKey()
                ],
                module: 'Document'
            );

            return $document;
        });
    }

    protected function generateDocumentNumber(DocumentTemplate $template): string
    {
        // Simple sequential number for now
        $count = FormalDocument::where('template_id', $template->id)->count() + 1;
        return strtoupper($template->category) . '/' . now()->format('Ymd') . '/' . Str::padLeft((string) $count, 4, '0');
    }
}
