<?php

declare(strict_types=1);

namespace App\Enrollment\Actions;

use App\Core\Actions\BaseAction;
use App\Document\Models\Document;
use App\Enrollment\Models\Registration;
use App\Enrollment\Models\RegistrationDocument;
use Illuminate\Http\UploadedFile;

final class UploadRegistrationDocumentAction extends BaseAction
{
    /**
     * @param array<string, UploadedFile> $uploads document_id => UploadedFile
     */
    public function execute(Registration $registration, array $uploads): void
    {
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
                'status' => 'pending',
            ]);

            $registrationDoc->addMedia($uploads[$document->id])->toMediaCollection('file');
        }
    }
}
