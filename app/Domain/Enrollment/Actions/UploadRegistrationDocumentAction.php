<?php

declare(strict_types=1);

namespace App\Domain\Enrollment\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Enrollment\Models\Registration;
use App\Domain\Enrollment\Models\RegistrationDocument;
use App\Domain\Program\Aggregates\Internship\Models\InternshipDocumentRequirement;
use Illuminate\Http\UploadedFile;

final class UploadRegistrationDocumentAction extends BaseAction
{
    /**
     * @param array<string, UploadedFile> $uploads requirement_id => UploadedFile
     */
    public function execute(Registration $registration, array $uploads): void
    {
        $requirements = InternshipDocumentRequirement::where('internship_id', $registration->internship_id)
            ->whereIn('id', array_keys($uploads))
            ->get();

        foreach ($requirements as $req) {
            if (! isset($uploads[$req->id])) {
                continue;
            }

            $registrationDoc = RegistrationDocument::create([
                'registration_id' => $registration->id,
                'internship_document_requirement_id' => $req->id,
                'status' => 'pending',
            ]);

            $registrationDoc->addMedia($uploads[$req->id])->toMediaCollection('file');
        }
    }
}
