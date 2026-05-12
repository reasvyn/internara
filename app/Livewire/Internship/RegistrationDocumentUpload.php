<?php

declare(strict_types=1);

namespace App\Livewire\Internship;

use App\Models\InternshipDocumentRequirement;
use App\Models\Registration;
use App\Models\RegistrationDocument;
use Illuminate\Http\UploadedFile;
use Livewire\Component;
use Livewire\WithFileUploads;

class RegistrationDocumentUpload extends Component
{
    use WithFileUploads;

    public ?Registration $registration = null;

    /** @var array<string, UploadedFile> */
    public array $uploads = [];

    public function mount(): void
    {
        $this->registration = auth()->user()->getActiveRegistration()
            ?? auth()->user()->registrations()
                ->get()
                ->first(fn ($reg) => $reg->hasStatus('pending'));
    }

    public function upload(): void
    {
        if (! $this->registration) {
            flash()->error('No active or pending registration found.');

            return;
        }

        $requirements = InternshipDocumentRequirement::where('internship_id', $this->registration->internship_id)
            ->with('document')
            ->get();

        $rules = [];
        foreach ($requirements as $req) {
            $rules["uploads.{$req->id}"] = $req->is_mandatory ? 'required|file|mimes:pdf,jpg,jpeg,png|max:5120' : 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120';
        }

        $this->validate($rules);

        foreach ($requirements as $req) {
            if (! isset($this->uploads[$req->id])) {
                continue;
            }

            $registrationDoc = RegistrationDocument::create([
                'registration_id' => $this->registration->id,
                'internship_document_requirement_id' => $req->id,
                'status' => 'pending',
            ]);

            $registrationDoc->addMedia($this->uploads[$req->id])->toMediaCollection('file');
        }

        $this->uploads = [];
        flash()->success('Documents uploaded successfully.');
    }

    public function render()
    {
        $requirements = collect();

        if ($this->registration) {
            $requirements = InternshipDocumentRequirement::where('internship_id', $this->registration->internship_id)
                ->with('document')
                ->get();
        }

        return view('livewire.internship.registration-document-upload', [
            'requirements' => $requirements,
            'existingDocs' => $this->registration
                ? RegistrationDocument::where('registration_id', $this->registration->id)->with('requirement.document')->get()
                : collect(),
        ]);
    }
}
