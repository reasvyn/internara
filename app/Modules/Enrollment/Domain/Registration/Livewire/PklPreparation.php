<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Livewire;

use App\Modules\Document\Models\Document;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Actions\SubmitPlacementPreferenceAction;
use App\Modules\Enrollment\Domain\Registration\Actions\UploadRegistrationDocumentAction;
use App\Modules\Enrollment\Domain\Registration\Data\PlacementPreferenceData;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Enrollment\Domain\Registration\Models\RegistrationDocument;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

class PklPreparation extends Component
{
    use AuthorizesRequests;
    use Interactions;
    use WithFileUploads;

    public ?Registration $registration = null;

    public int $step = 1;

    public string $placementType = 'partner';

    public ?string $placementId = null;

    public ?string $proposedCompanyName = null;

    public ?string $proposedCompanyAddress = null;

    /** @var array<string, mixed> */
    public array $uploads = [];

    public function mount(?Registration $registration = null): void
    {
        if ($registration) {
            $this->registration = $registration;
        } else {
            $user = auth()->user();
            if ($user) {
                $this->registration = $user->registrations()
                    ->with(['internship', 'placement', 'documents'])
                    ->latest()
                    ->first();
            }
        }

        if ($this->registration) {
            $this->placementId = $this->registration->placement_id;
            if ($this->registration->proposed_company_details) {
                $this->placementType = 'proposed';
                $this->proposedCompanyName = $this->registration->proposed_company_details['company_name'] ?? null;
                $this->proposedCompanyAddress = $this->registration->proposed_company_details['company_address'] ?? null;
            }
        }
    }

    #[Computed]
    public function availablePlacements(): Collection
    {
        if (! $this->registration || ! $this->registration->internship_id) {
            return new Collection;
        }

        return Placement::where('internship_id', $this->registration->internship_id)
            ->with('company')
            ->get()
            ->filter(fn (Placement $p) => ! $p->asPlacementCapacity()->isFull())
            ->values();
    }

    #[Computed]
    public function requiredDocuments(): Collection
    {
        if (! $this->registration || ! $this->registration->internship) {
            return new Collection;
        }

        $documentIds = $this->registration->internship->required_document_ids ?? [];

        return Document::whereIn('id', $documentIds)->get();
    }

    #[Computed]
    public function submittedDocuments(): Collection
    {
        if (! $this->registration) {
            return new Collection;
        }

        return RegistrationDocument::where('registration_id', $this->registration->id)
            ->with('document')
            ->get();
    }

    public function setPlacementType(string $type): void
    {
        $this->placementType = $type;
    }

    public function savePlacementPreference(SubmitPlacementPreferenceAction $action): void
    {
        if (! $this->registration) {
            return;
        }

        $this->authorize('update', $this->registration);

        if ($this->placementType === 'partner') {
            $this->validate([
                'placementId' => 'required|exists:placements,id',
            ]);

            $action->execute($this->registration, new PlacementPreferenceData(
                placementId: $this->placementId,
            ));
        } else {
            $this->validate([
                'proposedCompanyName' => 'required|string|min:3|max:255',
                'proposedCompanyAddress' => 'required|string|min:5|max:500',
            ]);

            $action->execute($this->registration, new PlacementPreferenceData(
                proposedCompanyName: $this->proposedCompanyName,
                proposedCompanyAddress: $this->proposedCompanyAddress,
            ));
        }

        $this->registration->refresh();
        $this->toast()->success(__('registration.wizard.success'))->send();
        $this->step = 2;
    }

    public function uploadDocuments(UploadRegistrationDocumentAction $action): void
    {
        if (! $this->registration) {
            return;
        }

        $this->authorize('update', $this->registration);

        $activeUploads = array_filter($this->uploads, fn ($val) => ! empty($val));

        if (empty($activeUploads)) {
            $this->toast()->error(__('registration.document_upload.no_files_selected'))->send();

            return;
        }

        $rules = [];
        $documentIds = $this->registration->internship->required_document_ids ?? [];
        foreach ($activeUploads as $docId => $file) {
            if (in_array($docId, $documentIds, true)) {
                $rules["uploads.{$docId}"] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
            }
        }

        $this->validate($rules);

        $action->execute($this->registration, $activeUploads);
        $this->uploads = [];
        $this->registration->refresh();
        $this->toast()->success(__('registration.document_upload.success'))->send();
        $this->step = 3;
    }

    public function goToStep(int $targetStep): void
    {
        $this->step = $targetStep;
    }

    public function render(): View
    {
        return view('enrollment.registration.pkl-preparation');
    }
}
