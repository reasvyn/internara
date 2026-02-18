<?php

declare(strict_types=1);

namespace Modules\User\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Exception\Concerns\HandlesAppException;
use Modules\Shared\Livewire\Concerns\ManagesRecords;
use Modules\User\Livewire\Forms\UserForm;
use Modules\User\Services\Contracts\UserService;

class UserManager extends Component
{
    use HandlesAppException;
    use ManagesRecords;

    public UserForm $form;

    public string $targetRole = '';

    /**
     * Initialize the component.
     */
    public function boot(UserService $userService): void
    {
        $this->service = $userService;
        $this->eventPrefix = 'user';
    }

    /**
     * Mount the component.
     */
    public function mount(string $targetRole = ''): void
    {
        $this->targetRole = $targetRole;

        // Security: Only SuperAdmins can manage 'admin' role
        if ($this->targetRole === 'admin' && ! auth()->user()->hasRole('super-admin')) {
            abort(403, 'Only SuperAdmins can manage administrator accounts.');
        }

        $this->authorize('user.view');
    }

    /**
     * Get records property for the table.
     */
    public function getRecordsProperty(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $filters = [
            'search' => $this->search,
            'sort_by' => $this->sortBy,
            'sort_dir' => $this->sortDir,
        ];

        if ($this->targetRole) {
            $filters['roles.name'] = $this->targetRole;
        }

        return $this->service->paginate(array_filter($filters), $this->perPage);
    }

    /**
     * Get departments for the select input.
     */
    public function getDepartmentsProperty(): \Illuminate\Support\Collection
    {
        if (class_exists(\Modules\Department\Services\Contracts\DepartmentService::class)) {
            return app(\Modules\Department\Services\Contracts\DepartmentService::class)->all([
                'id',
                'name',
            ]);
        }

        return collect();
    }

    /**
     * Open form for adding a new user.
     */
    public function add(): void
    {
        $this->form->reset();

        if ($this->targetRole) {
            $this->form->roles = [$this->targetRole];
        }

        $this->formModal = true;
    }

    /**
     * Open the form modal for editing a record.
     */
    public function edit(string $id): void
    {
        $user = $this->service->find($id);

        if ($user) {
            $this->form->setUser($user);
            $this->formModal = true;
        }
    }

    /**
     * Save the user record.
     */
    public function save(): void
    {
        $this->form->validate();

        try {
            if ($this->form->id) {
                $this->service->update($this->form->id, $this->form->all());
            } else {
                $this->service->create($this->form->all());
            }

            $this->formModal = false;
            flash()->success(__('shared::messages.record_saved'));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    /**
     * Render the user manager view.
     */
    public function render(): View
    {
        $title = $this->targetRole
            ? ucfirst($this->targetRole).' Management'
            : __('user::ui.user_management');

        return view('user::livewire.user-manager', [
            'title' => $title,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => $title,
        ]);
    }
}
