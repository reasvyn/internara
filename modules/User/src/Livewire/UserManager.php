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
    use Concerns\InteractsWithDepartments;
    use HandlesAppException;
    use ManagesRecords;

    public UserForm $form;

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
            \Modules\Shared\Support\Formatter::date($record->created_at, 'Y-m-d H:i'),
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
    public function mount(): void
    {
        $this->authorize('user.view');
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
            ], ['id', 'name', 'email', 'username', 'created_at'])
            ->with(['roles:id,name'])
            ->paginate($this->perPage);
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
            flash()->success(__('user::ui.manager.deleted_successfully', ['count' => $count]));
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
            $this->authorize('update', $user);
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
                $user = $this->service->find($this->form->id);
                if ($user) {
                    $this->authorize('update', $user);
                }
                $this->service->update($this->form->id, $this->form->all());
            } else {
                $this->authorize('create', [User::class, $this->form->roles]);
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
