<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Internship\Services\Contracts\DeliverableService;
use Modules\Internship\Services\Contracts\InternshipRegistrationService;

/**
 * Class DeliverableSubmission
 *
 * Allows students to upload mandatory internship artifacts.
 */
class DeliverableSubmission extends Component
{
    use WithFileUploads;

    public $reportFile;
    public $presentationFile;

    public ?string $registrationId = null;

    /**
     * Mount the component.
     */
    public function mount(InternshipRegistrationService $regService): void
    {
        $registration = $regService->first([
            'student_id' => Auth::id(),
            'latest_status' => 'active',
        ]);

        if ($registration) {
            $this->registrationId = $registration->id;
        }
    }

    /**
     * Submit the internship report.
     */
    public function submitReport(DeliverableService $service): void
    {
        $this->validate(['reportFile' => 'required|file|mimes:pdf|max:10240']);

        try {
            $service->submit($this->registrationId, 'report', $this->reportFile);
            $this->dispatch('notify', message: __('Report submitted successfully.'), type: 'success');
            $this->reportFile = null;
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    /**
     * Submit the presentation material.
     */
    public function submitPresentation(DeliverableService $service): void
    {
        $this->validate(['presentationFile' => 'required|file|mimes:pdf,ppt,pptx|max:20480']);

        try {
            $service->submit($this->registrationId, 'presentation', $this->presentationFile);
            $this->dispatch('notify', message: __('Presentation material submitted successfully.'), type: 'success');
            $this->presentationFile = null;
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    /**
     * Get the current deliverables.
     */
    public function getDeliverablesProperty(DeliverableService $service)
    {
        if (! $this->registrationId) {
            return collect();
        }

        return $service->get(['registration_id' => $this->registrationId]);
    }

    public function render(): View
    {
        return view('internship::livewire.deliverable-submission');
    }
}
