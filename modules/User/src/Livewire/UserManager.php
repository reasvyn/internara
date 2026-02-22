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

    public array $selectedIds = [];

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
     * {@inheritdoc}
     */
    protected function getExportHeaders(): array
    {
        return [
            'name' => __('user::ui.manager.table.name'),
            'email' => __('user::ui.manager.table.email'),
            'username' => __('user::ui.manager.table.username'),
            'roles' => __('user::ui.manager.table.roles'),
            'created_at' => __('ui::common.created_at'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapRecordForExport($record, array $keys): array
    {
        return [
            $record->name,
            $record->email,
            $record->username,
            $record->roles->pluck('name')->implode(', '),
            $record->created_at->format('Y-m-d H:i'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getPdfView(): ?string
    {
        return 'user::pdf.users';
    }

    /**
     * Mount the component.
     */
    public function mount(?string $targetRole = null): void
    {
        if ($targetRole !== null) {
            $this->targetRole = $targetRole;
        }

        // Security: Only SuperAdmins can manage 'admin' role
        if ($this->targetRole === 'admin' && ! auth()->user()->hasRole('super-admin')) {
            abort(403, __('user::exceptions.super_admin_unauthorized'));
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

        return $this->service
            ->query(array_filter($filters), ['id', 'name', 'email', 'username', 'created_at'])
            ->with(['roles:id,name'])
            ->paginate($this->perPage);
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
     * Remove all selected users.
     */
    public function removeSelected(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        try {
            // Filter out super-admins before deletion for safety
            $targets = \Modules\User\Models\User::whereIn('id', $this->selectedIds)
                ->get()
                ->reject(fn ($u) => $u->hasRole('super-admin'))
                ->pluck('id')
                ->toArray();

            $count = $this->service->destroy($targets);
            $this->selectedIds = [];
            flash()->success(__(':count data pengguna berhasil dihapus.', ['count' => $count]));
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
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
            if ($user->hasRole('super-admin')) {
                flash()->error(__('user::exceptions.super_admin_readonly'));
                return;
            }

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
        $roleKey = $this->targetRole ?: 'user';
        $title = $this->targetRole ? __("user::ui.{$roleKey}_management") : __('User Management');

        return view('user::livewire.user-manager', [
            'title' => $title,
            'roleKey' => $roleKey,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => $title . ' | ' . setting('brand_name', setting('app_name')),
        ]);
    }
}
