<?php

declare(strict_types=1);

namespace Modules\Mentor\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Exception\Concerns\HandlesAppException;
use Modules\Shared\Livewire\Concerns\ManagesRecords;
use Modules\User\Livewire\Forms\UserForm;
use Modules\Mentor\Services\Contracts\MentorService;

/**
 * Class MentorManager
 * 
 * Manages industry mentors with specialized logic and role enforcement.
 */
class MentorManager extends Component
{
    use HandlesAppException;
    use ManagesRecords;

    public UserForm $form;

    /**
     * Initialize the component.
     */
    public function boot(MentorService $mentorService): void
    {
        $this->service = $mentorService;
        $this->eventPrefix = 'mentor';
    }

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('mentor.manage');
    }

    /**
     * Get records property for the table.
     */
    public function getRecordsProperty(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->service
            ->query([
                'search' => $this->search,
                'sort_by' => $this->sortBy,
                'sort_dir' => $this->sortDir,
            ])
            ->with(['roles:id,name', 'profile'])
            ->paginate($this->perPage);
    }

    /**
     * Open form for adding a new mentor.
     */
    public function add(): void
    {
        $this->form->reset();
        $this->form->roles = ['mentor'];
        $this->formModal = true;
    }

    /**
     * Open form for editing a mentor.
     */
    public function edit(string $id): void
    {
        $user = $this->service->find($id);

        if ($user) {
            $this->authorize('update', $user);
            $this->form->setUser($user);
            $this->formModal = true;
        }
    }

    /**
     * Save the mentor record.
     */
    public function save(): void
    {
        $this->form->validate();

        try {
            if ($this->form->id) {
                $user = $this->service->find($this->form->id);
                if ($user) {
                    $this->authorize('update', $user);
                }
                $this->service->update($this->form->id, $this->form->all());
            } else {
                $this->authorize('create', [User::class, ['mentor']]);
                $this->service->create($this->form->all());
            }

            $this->formModal = false;
            flash()->success(__('shared::messages.record_saved'));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    /**
     * Render the mentor manager view.
     */
    public function render(): View
    {
        $title = __('admin::ui.menu.mentors');

        return view('mentor::livewire.mentor-manager', [
            'title' => $title,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => $title . ' | ' . setting('brand_name', setting('app_name')),
            'context' => 'admin::ui.menu.mentors',
        ]);
    }
}
