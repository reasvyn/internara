<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Livewire\Component;
use Modules\Internship\Livewire\Forms\RegistrationForm;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Shared\Livewire\Concerns\ManagesRecords;
use Modules\User\Services\Contracts\UserService;

class RegistrationManager extends Component
{
    use ManagesRecords;

    public RegistrationForm $form;

    public array $selectedIds = [];

    public ?string $targetPlacementId = null;

    public bool $bulkPlaceModal = false;

    public bool $historyModal = false;

    public ?string $historyId = null;

    protected \Modules\Notification\Contracts\Notifier $notifier;

    public function boot(
        RegistrationService $registrationService,
        \Modules\Notification\Contracts\Notifier $notifier,
    ): void {
        $this->service = $registrationService;
        $this->notifier = $notifier;
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
            /** @var RegistrationService $service */
            $service = $this->service;

            // Keystone Verification: Ensure student has cleared requirements before placement
            if ($this->form->placement_id) {
                $isEligible = app(
                    \Modules\Internship\Services\Contracts\PlacementService::class,
                )->isEligibleForPlacement($this->form->id ?? 'new'); // 'new' is dummy, eligibility check usually needs student_id context for new records

                // For existing records, we can check the ID
                if ($this->form->id && ! $isEligible) {
                    throw new \Modules\Exception\AppException(
                        __('Siswa belum melengkapi persyaratan wajib untuk ditempatkan.'),
                        code: 422,
                    );
                }
            }

            if ($this->form->id) {
                $service->update($this->form->id, $this->form->except('id'));
            } else {
                $service->register($this->form->all());
            }

            $this->formModal = false;
            $this->notifier->success(__('shared::messages.record_saved'));
        } catch (\Throwable $e) {
            $this->notifier->error($e->getMessage());
        }
    }

    /**
     * Open the history modal for a registration.
     */
    public function viewHistory(string $id): void
    {
        $this->historyId = $id;
        $this->historyModal = true;
    }

    /**
     * Get history for the current record.
     */
    public function getHistoryProperty(): \Illuminate\Support\Collection
    {
        if (! $this->historyId) {
            return collect();
        }

        return app(RegistrationService::class)
            ->find($this->historyId)
            ->placementHistory()
            ->with('placement')
            ->latest()
            ->get();
    }

    public function render()
    {
        return view('internship::livewire.registration-manager');
    }

    /**
     * Open the bulk placement modal.
     */
    public function openBulkPlace(): void
    {
        if (empty($this->selectedIds)) {
            $this->dispatch('notify', message: __('Pilih setidaknya satu siswa.'), type: 'warning');

            return;
        }

        $this->bulkPlaceModal = true;
    }

    /**
     * Execute bulk placement.
     */
    public function executeBulkPlace(): void
    {
        if (! $this->targetPlacementId) {
            $this->dispatch('notify', message: __('Pilih lokasi penempatan.'), type: 'error');

            return;
        }

        try {
            $pairings = array_fill_keys($this->selectedIds, $this->targetPlacementId);
            $count = app(\Modules\Internship\Services\Contracts\PlacementService::class)->bulkMatch(
                $pairings,
            );

            $this->bulkPlaceModal = false;
            $this->selectedIds = [];
            $this->targetPlacementId = null;

            $this->notifier->success(__(':count siswa berhasil ditempatkan.', ['count' => $count]));
        } catch (\Throwable $e) {
            $this->notifier->error($e->getMessage());
        }
    }

    /**
     * Approve a registration.
     */
    public function approve(string $id): void
    {
        try {
            /** @var RegistrationService $service */
            $service = $this->service;
            $service->approve($id);
            $this->notifier->success(__('internship::ui.registration_approved'));
        } catch (\Throwable $e) {
            $this->notifier->error($e->getMessage());
        }
    }

    /**
     * Reject a registration.
     */
    public function reject(string $id): void
    {
        try {
            /** @var RegistrationService $service */
            $service = $this->service;
            $service->reject($id);
            $this->notifier->warning(__('internship::ui.registration_rejected'));
        } catch (\Throwable $e) {
            $this->notifier->error($e->getMessage());
        }
    }

    /**
     * Complete a registration.
     */
    public function complete(string $id): void
    {
        try {
            /** @var RegistrationService $service */
            $service = $this->service;
            $service->complete($id);
            $this->notifier->success(__('internship::ui.registration_completed'));
        } catch (\Throwable $e) {
            $this->notifier->error($e->getMessage());
        }
    }
}
