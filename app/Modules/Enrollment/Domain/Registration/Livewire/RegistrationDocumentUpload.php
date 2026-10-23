<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Livewire;

use App\Modules\Document\Models\Document;
use App\Modules\Enrollment\Domain\Registration\Actions\UploadRegistrationDocumentAction;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Enrollment\Domain\Registration\Models\RegistrationDocument;
use App\Modules\UI\Livewire\BaseFormView;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

class RegistrationDocumentUpload extends BaseFormView
{
    use AuthorizesRequests, WithFileUploads;
    use Interactions;

    public ?Registration $registration = null;

    protected UploadRegistrationDocumentAction $action;

    public function boot(UploadRegistrationDocumentAction $action): void
    {
        $this->action = $action;
        $this->authorize('create', RegistrationDocument::class);
    }

    /** @var array<string, mixed> */
    public array $uploads = [];

    public function mount(?Registration $registration = null): void
    {
        if ($registration) {
            $this->registration = $registration;

            return;
        }

        $user = auth()->user();
        if ($user) {
            $this->registration =
                $user->getActiveRegistration() ??
                $user->registrations()->get()->first(fn ($reg) => $reg->hasStatus('pending'));
        }
    }

    public function upload(?UploadRegistrationDocumentAction $action = null): void
    {
        if (! $this->registration) {
            $this->toast()->error(__('registration.document_upload.no_registration'))->send();

            return;
        }

        $this->authorize('update', $this->registration);

        $documentIds = $this->registration->internship->required_document_ids ?? [];
        $documents = Document::whereIn('id', $documentIds)->get();

        $existingDocIds = RegistrationDocument::where('registration_id', $this->registration->id)
            ->pluck('document_id')
            ->all();

        $activeUploads = array_filter($this->uploads, fn ($val) => ! empty($val));

        if (empty($activeUploads)) {
            $this->toast()->error(__('registration.document_upload.no_files_selected'))->send();

            return;
        }

        $rules = [];
        foreach ($activeUploads as $docId => $file) {
            if (in_array($docId, $documentIds, true)) {
                $rules["uploads.{$docId}"] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            }
        }

        $this->validate($rules);

        $uploader = $action ?? $this->action;

        $this->handleSave(function () use ($uploader, $activeUploads) {
            $uploader->execute($this->registration, $activeUploads);
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
