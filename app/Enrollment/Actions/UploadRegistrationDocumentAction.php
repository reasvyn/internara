<?php

declare(strict_types=1);

namespace App\Enrollment\Actions;

use App\Core\Actions\BaseAction;
use App\Enrollment\Models\Registration;
use App\Enrollment\Models\RegistrationDocument;
use App\Program\Internship\Models\InternshipDocumentRequirement;
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
