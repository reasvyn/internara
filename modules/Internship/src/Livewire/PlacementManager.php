<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Internship\Livewire\Forms\PlacementForm;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\Shared\Livewire\Concerns\ManagesRecords;
use Modules\User\Livewire\Forms\UserForm;
use Modules\User\Services\Contracts\UserService;

class PlacementManager extends Component
{
    use ManagesRecords;

    public PlacementForm $form;

    public UserForm $mentorForm;

    public bool $mentorModal = false;

    /**
     * Initialize the component.
     */
    public function boot(InternshipPlacementService $placementService): void
    {
        $this->service = $placementService;
    }

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('internship.update');
    }

    /**
     * Get companies for the dropdown.
     */
    public function getCompaniesProperty(): \Illuminate\Support\Collection
    {
        return \Modules\Internship\Models\Company::all(['id', 'name']);
    }

    /**
     * Get internships for the dropdown.
     */
    public function getInternshipsProperty(): \Illuminate\Support\Collection
    {
        return app(InternshipService::class)->all(['id', 'title']);
    }

    /**
     * Get mentors for the dropdown.
     */
    public function getMentorsProperty(): \Illuminate\Support\Collection
    {
        return app(UserService::class)->get(['roles.name' => 'mentor'], ['id', 'name']);
    }

    /**
     * Open the mentor creation modal.
     */
    public function addMentor(): void
    {
        $this->mentorForm->reset();
        $this->mentorForm->roles = ['mentor'];
        $this->mentorModal = true;
    }

    /**
     * Save the new mentor and auto-select them.
     */
    public function saveMentor(): void
    {
        $this->mentorForm->validate();

        try {
            $mentor = app(UserService::class)->create($this->mentorForm->all());

            $this->form->mentor_id = $mentor->id;
            $this->mentorModal = false;

            notify(__('Mentor created and assigned successfully.'), 'success');
        } catch (\Throwable $e) {
            notify($e->getMessage(), 'error');
        }
    }

    public function render(): View
    {
        return view('internship::livewire.placement-manager');
    }
}
