<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Exception\Concerns\HandlesAppException;
use Modules\UI\Livewire\Concerns\ManagesRecords;
use Modules\User\Livewire\Forms\UserForm;
use Modules\Admin\Services\Contracts\AdminService;

/**
 * Class AdminManager
 * 
 * Manages system administrators with specialized logic and role enforcement.
 * Only SuperAdmins are authorized to manage Admin accounts.
 */
class AdminManager extends Component
{
    use HandlesAppException;
    use ManagesRecords;

    public UserForm $form;

    /**
     * Initialize the component.
     */
    public function boot(AdminService $adminService): void
    {
        $this->service = $adminService;
        $this->eventPrefix = 'admin';
    }

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        // Security: Only SuperAdmins can manage 'admin' role
        if (! auth()->user()->hasRole(\Modules\Permission\Enums\Role::SUPER_ADMIN->value)) {
            abort(403, __('user::exceptions.super_admin_unauthorized'));
        }

        $this->authorize('admin.manage');
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
     * Open form for adding a new admin.
     */
    public function add(): void
    {
        $this->form->reset();
        $this->form->roles = ['admin'];
        $this->formModal = true;
    }

    /**
     * Open form for editing an admin.
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
     * Save the admin record.
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
                $this->authorize('create', [User::class, ['admin']]);
                $this->service->create($this->form->all());
            }

            $this->formModal = false;
            flash()->success(__('shared::messages.record_saved'));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    /**
     * Render the admin manager view.
     */
    public function render(): View
    {
        $title = __('admin::ui.menu.administrators');

        return view('admin::livewire.admin-manager', [
            'title' => $title,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => $title . ' | ' . setting('brand_name', setting('app_name')),
            'context' => 'admin::ui.menu.administrators',
        ]);
    }
}
