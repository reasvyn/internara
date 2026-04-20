<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Modules\Admin\Livewire\Forms\AdminForm;
use Modules\Admin\Services\Contracts\AdminService;
use Modules\Exception\Concerns\HandlesAppException;
use Modules\UI\Livewire\RecordManager;
use Modules\User\Models\User;

/**
 * Class AdminManager
 *
 * Manages system administrators with specialized logic and role enforcement.
 * Only SuperAdmins are authorized to manage Admin accounts.
 */
class AdminManager extends RecordManager
{
    use HandlesAppException;

    public AdminForm $form;

    /**
     * Initialize the component.
     */
    public function boot(AdminService $adminService): void
    {
        $this->service = $adminService;
        $this->eventPrefix = 'admin';
    }

    public function initialize(): void
    {
        $this->title = __('admin::ui.menu.administrators');
        $this->subtitle = __('user::ui.manager.subtitle');
        $this->addLabel = __('user::ui.manager.add_admin');
        $this->deleteConfirmMessage = __('user::ui.manager.delete.message');
        $this->viewPermission = 'admin.manage';
        $this->createPermission = 'admin.manage';
        $this->updatePermission = 'admin.manage';
        $this->deletePermission = 'admin.manage';
        $this->modelClass = User::class;
    }

    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'name', 'label' => __('ui::common.name'), 'sortable' => true],
            ['key' => 'email', 'label' => __('ui::common.email'), 'sortable' => false],
            ['key' => 'created_at', 'label' => __('ui::common.created_at'), 'sortable' => true],
        ];
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

        parent::mount();
    }

    /**
     * Get records property for the table.
     */
    #[Computed]
    public function records(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->service->paginate(
            [
                'search' => $this->search,
                'sort_by' => $this->sortBy['column'] ?? 'created_at',
                'sort_dir' => $this->sortBy['direction'] ?? 'desc',
            ],
            $this->perPage,
            ['*'],
            ['roles:id,name', 'profile', 'statuses'],
        );
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
    public function edit(mixed $id): void
    {
        $admin = $this->service->find($id);

        if ($admin) {
            $this->authorize('update', $admin);
            $this->form->fillFromUser($admin);
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
     * Render the admin manager view.
     */
    public function render(): View
    {
        return view('admin::livewire.admin-manager', [
            'title' => $this->title,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => $this->title.' | '.setting('brand_name', setting('app_name')),
            'context' => 'admin::ui.menu.administrators',
        ]);
    }
}
