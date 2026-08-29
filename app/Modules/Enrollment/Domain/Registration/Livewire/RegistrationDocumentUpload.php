<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Livewire;

use App\Modules\Core\Livewire\BaseFormView;
use App\Modules\Document\Models\Document;
use App\Modules\Enrollment\Domain\Registration\Actions\UploadRegistrationDocumentAction;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Enrollment\Domain\Registration\Models\RegistrationDocument;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\UploadedFile;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

class RegistrationDocumentUpload extends BaseFormView
{
    use AuthorizesRequests, WithFileUploads;
    use Interactions;

    public ?Registration $registration = null;

    public function boot(): void
    {
        $this->authorize('create', RegistrationDocument::class);
    }

    /** @var array<string, UploadedFile> */
    public array $uploads = [];

    public function mount(): void
    {
        $this->registration =
            auth()->user()->getActiveRegistration() ??
            auth()->user()->registrations()->get()->first(fn ($reg) => $reg->hasStatus('pending'));
    }

    public function upload(UploadRegistrationDocumentAction $action): void
    {
        if (! $this->registration) {
            $this->toast()->error(__('registration.document_upload.no_registration'))->send();

            return;
        }

        $this->authorize('update', $this->registration);

        $documentIds = $this->registration->internship->required_document_ids ?? [];
        $documents = Document::whereIn('id', $documentIds)->get();

        $rules = [];
        foreach ($documents as $doc) {
            $rules["uploads.{$doc->id}"] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
        }

        $this->validate($rules);

        $this->handleSave(function () use ($action) {
            $action->execute($this->registration, $this->uploads);
            $this->uploads = [];
            $this->toast()->success(__('registration.document_upload.success'))->send();
        });
    }

    public function render(): View
    {
        $documents = collect();

        if ($this->registration) {
            $documentIds = $this->registration->internship->required_document_ids ?? [];
            $documents = Document::whereIn('id', $documentIds)->get();
        }

        return view('enrollment.registration.registration-document-upload', [
            'documents' => $documents,
            'existingDocs' => $this->registration
                ? RegistrationDocument::where('registration_id', $this->registration->id)
                    ->with('document')
                    ->get()
                : collect(),
        ]);
    }
}
