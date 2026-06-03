<?php

declare(strict_types=1);

namespace App\Domain\Enrollment\Livewire;

use App\Domain\Program\Aggregates\Internship\Models\InternshipDocumentRequirement;
use App\Domain\Enrollment\Actions\UploadRegistrationDocumentAction;
use App\Domain\Enrollment\Models\Registration;
use App\Domain\Enrollment\Models\RegistrationDocument;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\UploadedFile;
use Livewire\Component;
use Livewire\WithFileUploads;

class RegistrationDocumentUpload extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public ?Registration $registration = null;

    public function boot(): void
    {
        $this->authorize('create', RegistrationDocument::class);
    }

    /** @var array<string, UploadedFile> */
    public array $uploads = [];

    public function mount(): void
    {
        $this->registration = auth()->user()->getActiveRegistration()
            ?? auth()->user()->registrations()
                ->get()
                ->first(fn ($reg) => $reg->hasStatus('pending'));
    }

    public function upload(UploadRegistrationDocumentAction $action): void
    {
        if (! $this->registration) {
            flash()->error(__('registration.document_upload.no_registration'));

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

        $action->execute($this->registration, $this->uploads);

        $this->uploads = [];
        flash()->success(__('registration.document_upload.success'));
    }

    public function render(): View
    {
        $requirements = collect();

        if ($this->registration) {
            $requirements = InternshipDocumentRequirement::where('internship_id', $this->registration->internship_id)
                ->with('document')
                ->get();
        }

        return view('enrollment.registration-document-upload', [
            'requirements' => $requirements,
            'existingDocs' => $this->registration
                ? RegistrationDocument::where('registration_id', $this->registration->id)->with('requirement.document')->get()
                : collect(),
        ]);
    }
}
