<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Document\Models\Document;
use App\Modules\Enrollment\Domain\Registration\Enums\RegistrationDocumentStatus;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Enrollment\Domain\Registration\Models\RegistrationDocument;
use Illuminate\Http\UploadedFile;

final class UploadRegistrationDocumentAction extends BaseCommandAction
{
    /**
     * @param array<string, UploadedFile> $uploads document_id => UploadedFile
     */
    public function execute(Registration $registration, array $uploads): void
    {
        $this->transaction(function () use ($registration, $uploads) {
            $documentIds = $registration->internship->required_document_ids ?? [];

            $documents = Document::whereIn('id', array_keys($uploads))
                ->whereIn('id', $documentIds)
                ->get();

            foreach ($documents as $document) {
                if (! isset($uploads[$document->id])) {
                    continue;
                }

                $registrationDoc = RegistrationDocument::create([
                    'registration_id' => $registration->id,
                    'document_id' => $document->id,
                    'status' => RegistrationDocumentStatus::PENDING->value,
                ]);

                $registrationDoc->addMedia($uploads[$document->id])->toMediaCollection('file');
            }

            $this->log('registration_documents_uploaded', $registration, [
                'document_count' => $documents->count(),
            ]);
        });
    }
}
