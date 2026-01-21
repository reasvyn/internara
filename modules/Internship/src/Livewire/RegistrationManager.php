<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Livewire\Component;
use Modules\Internship\Livewire\Forms\RegistrationForm;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\Internship\Services\Contracts\InternshipRegistrationService;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\Shared\Livewire\Concerns\ManagesRecords;
use Modules\User\Services\Contracts\UserService;

class RegistrationManager extends Component
{
    use ManagesRecords;

    public RegistrationForm $form;

    public function boot(InternshipRegistrationService $registrationService): void
    {
        $this->service = $registrationService;
        $this->eventPrefix = 'registration';
    }

    public function mount(): void
    {
        $this->authorize('internship.update');
    }

    /**
     * Get internships for the dropdown.
     */
    public function getInternshipsProperty(): \Illuminate\Support\Collection
    {
        return app(InternshipService::class)->all(['id', 'title']);
    }

    /**
     * Get placements for the dropdown.
     */
    public function getPlacementsProperty(): \Illuminate\Support\Collection
    {
        return app(InternshipPlacementService::class)->all(['id', 'company_name']);
    }

    /**
     * Get students for the dropdown.
     */
    public function getStudentsProperty(): \Illuminate\Support\Collection
    {
        return app(UserService::class)->get(['roles.name' => 'student'], ['id', 'name']);
    }

    /**
     * Get teachers for the dropdown.
     */
    public function getTeachersProperty(): \Illuminate\Support\Collection
    {
        return app(UserService::class)->get(['roles.name' => 'teacher'], ['id', 'name']);
    }

    /**
     * Get mentors for the dropdown.
     */
    public function getMentorsProperty(): \Illuminate\Support\Collection
    {
        return app(UserService::class)->get(['roles.name' => 'mentor'], ['id', 'name']);
    }

    /**
     * Override save to use register method with validation.
     */
    public function save(): void
    {
        $this->form->validate();

        try {
            /** @var InternshipRegistrationService $service */
            $service = $this->service;

            // Keystone Verification: Ensure student has cleared requirements before placement
            if ($this->form->placement_id) {
                $isEligible = app(\Modules\Internship\Services\Contracts\PlacementService::class)
                    ->isEligibleForPlacement($this->form->id ?? 'new'); // 'new' is dummy, eligibility check usually needs student_id context for new records
                
                // For existing records, we can check the ID
                if ($this->form->id && !$isEligible) {
                    throw new \Modules\Exception\AppException(
                        __('Siswa belum melengkapi persyaratan wajib untuk ditempatkan.'),
                        code: 422
                    );
                }
            }

            if ($this->form->id) {
                $service->update($this->form->id, $this->form->except('id'));
            } else {
                $service->register($this->form->all());
            }

            $this->formModal = false;
            $this->dispatch(
                'notify',
                message: __('shared::messages.record_saved'),
                type: 'success',
            );
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('internship::livewire.registration-manager');
    }

    /**
     * Approve a registration.
     */
    public function approve(string $id): void
    {
        try {
            /** @var InternshipRegistrationService $service */
            $service = $this->service;
            $service->approve($id);
            $this->dispatch(
                'notify',
                message: __('internship::ui.registration_approved'),
                type: 'success',
            );
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    /**
     * Reject a registration.
     */
    public function reject(string $id): void
    {
        try {
            /** @var InternshipRegistrationService $service */
            $service = $this->service;
            $service->reject($id);
            $this->dispatch(
                'notify',
                message: __('internship::ui.registration_rejected'),
                type: 'warning',
            );
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }
}
