<?php

declare(strict_types=1);

namespace App\Domain\Document\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\Document\Models\DocumentTemplate;
use App\Domain\Document\Models\OfficialDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * S2 - Sustain: Logic for generating official documents from templates.
 * S3 - Scalable: Stateless action.
 */
class GenerateDocumentAction
{
    public function __construct(
        protected readonly LogAuditAction $logAuditAction,
        protected readonly GeneratePdfAction $generatePdfAction,
    ) {}

    /**
     * Generate an official document from a template and save as PDF.
     */
    public function execute(
        Model $target,
        DocumentTemplate $template,
        array $additionalData = [],
    ): OfficialDocument {
        return DB::transaction(function () use ($target, $template, $additionalData) {
            $mergeData = array_merge(
                [
                    'target' => $target,
                    'now' => now()->format('d F Y'),
                ],
                $additionalData,
            );

            $renderedContent = Blade::render($template->content, $mergeData);

            // Generate PDF file
            $pdfPath = $this->generatePdfAction->execute($renderedContent, $template->slug);

            /** @var OfficialDocument $document */
            $document = OfficialDocument::create([
                'documentable_id' => $target->getKey(),
                'documentable_type' => $target->getMorphClass(),
                'template_id' => $template->id,
                'title' => $template->name.' - '.($target->name ?? $target->id),
                'document_number' => $this->generateDocumentNumber($template),
                'issued_at' => now(),
                'metadata' => [
                    'rendered_content' => $renderedContent,
                ],
            ]);

            // Attach the generated PDF file via Media Library
            $document->addMedia($pdfPath)->toMediaCollection('file');

            $document->setStatus('active', 'Generated automatically from template.');

            $this->logAuditAction->execute(
                action: 'document_generated',
                subjectType: OfficialDocument::class,
                subjectId: $document->id,
                payload: [
                    'template_id' => $template->id,
                    'target_id' => $target->getKey(),
                ],
                module: 'Document',
            );

            return $document;
        });
    }

    protected function generateDocumentNumber(DocumentTemplate $template): string
    {
        $count = OfficialDocument::where('template_id', $template->id)->count() + 1;

        return strtoupper($template->category).
            '/'.
            now()->format('Ymd').
            '/'.
            Str::padLeft((string) $count, 4, '0');
    }
}
