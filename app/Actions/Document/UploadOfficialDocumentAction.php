<?php

declare(strict_types=1);

namespace App\Actions\Document;

use App\Actions\Audit\LogAuditAction;
use App\Models\OfficialDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * S1 - Secure: Audited document upload.
 * S3 - Scalable: Stateless action.
 */
class UploadOfficialDocumentAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    /**
     * Upload an official document.
     */
    public function execute(Model $target, UploadedFile $file, array $data): OfficialDocument
    {
        return DB::transaction(function () use ($target, $file, $data) {
            /** @var OfficialDocument $document */
            $document = OfficialDocument::create([
                'documentable_id' => $target->getKey(),
                'documentable_type' => $target->getMorphClass(),
                'template_id' => $data['template_id'] ?? null,
                'title' => $data['title'],
                'document_number' => $data['document_number'] ?? null,
                'issued_at' => $data['issued_at'] ?? now(),
                'expires_at' => $data['expires_at'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);

            $document->addMedia($file)->toMediaCollection('file');
            
            $document->setStatus('active', 'Uploaded by user.');

            $this->logAuditAction->execute(
                action: 'document_uploaded',
                subjectType: OfficialDocument::class,
                subjectId: $document->id,
                payload: array_merge($data, ['file_name' => $file->getClientOriginalName()]),
                module: 'Document'
            );

            return $document;
        });
    }
}
