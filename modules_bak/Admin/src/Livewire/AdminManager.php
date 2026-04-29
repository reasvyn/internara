<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Modules\Admin\Livewire\Forms\AdminForm;
use Modules\Admin\Services\Contracts\AdminService;
use Modules\Exception\Concerns\HandlesAppException;
use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\UI\Livewire\RecordManager;
use Modules\User\Models\AccountToken;
use Modules\User\Models\User;

class AdminManager extends RecordManager
{
    use HandlesAppException;

    public AdminForm $form;

    /**
     * Get summary statistics for admin distribution.
     */
    #[Computed]
    public function stats(): array
    {
        return $this->service->getStats();
    }

    /**
     * Initialize the component.
     */
    public function boot(AdminService $adminService): void
    {
        $this->service = $adminService;
        $this->eventPrefix = 'admin';
        $this->modelClass = User::class;
    }

    /**
     * Configure the component's basic properties.
     */
    public function initialize(): void
    {
        $this->title = __('admin::ui.menu.administrators');
        $this->subtitle = __('admin::ui.manager.subtitle');
        $this->context = 'admin::ui.menu.administrators';
        $this->addLabel = __('admin::ui.manager.add');
        $this->deleteConfirmMessage = __('admin::ui.manager.delete_confirm');

        $this->viewPermission = Permission::ADMIN_MANAGE;
        $this->createPermission = Permission::ADMIN_MANAGE;
        $this->updatePermission = Permission::ADMIN_MANAGE;
        $this->deletePermission = Permission::ADMIN_MANAGE;

        $this->searchable = ['name', 'email'];
        $this->sortable = ['name', 'email', 'created_at'];
    }

    /**
     * Mount the component with security gate.
     */
    public function mount(): void
    {
        abort_unless(
            auth()->user()->hasRole(Role::SUPER_ADMIN->value),
            403,
            __('user::exceptions.super_admin_unauthorized'),
        );

        parent::mount();
    }

    /**
     * Define the table structure.
     */
    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'name', 'label' => __('user::ui.manager.table.name'), 'sortable' => true],
            ['key' => 'email', 'label' => __('user::ui.manager.table.email'), 'sortable' => true],
            ['key' => 'invitation_status', 'label' => __('admin::ui.manager.invitation_status')],
            ['key' => 'created_at', 'label' => __('ui::common.created_at'), 'sortable' => true],
            ['key' => 'actions', 'label' => __('ui::common.actions'), 'class' => 'w-1 text-right'],
        ];
    }

    /**
     * Transform raw admin record for UI display.
     */
    protected function mapRecord(mixed $record): array
    {
        return array_merge($record->toArray(), [
            'avatar_url' => $record->avatar_url,
            'invitation_status' => $this->resolveInvitationStatus($record),
            'is_super_admin' => $record->hasRole(Role::SUPER_ADMIN->value),
        ]);
    }

    /**
     * Fetch and transform records for the table.
     */
    #[Computed]
    public function records(): LengthAwarePaginator
    {
        return $this->service
            ->query($this->filters)
            ->with(['roles:id,name', 'accountTokens'])
            ->paginate($this->perPage)
            ->through(fn($user) => $this->mapRecord($user));
    }

    public function reinvite(mixed $id): void
    {
        try {
            $admin = $this->service->findOrFail($id);

            if (!$admin->requiresSetup()) {
                flash()->warning(__('admin::ui.manager.already_accepted'));

                return;
            }

            $this->service->invite($admin, auth()->user());
            flash()->success(__('admin::ui.manager.reinvited', ['email' => $admin->email]));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    public function save(): void
    {
        $this->form->validate();

        try {
            $isNew = !$this->form->id;

            if ($isNew) {
                $admin = $this->service->create($this->form->all());
                $this->service->invite($admin, auth()->user());
                flash()->success(__('admin::ui.manager.invited', ['email' => $admin->email]));
            } else {
                $this->service->update($this->form->id, $this->form->all());
                flash()->success(__('shared::messages.record_saved'));
            }

            $this->toggleModal(self::MODAL_FORM, false);
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    public function statusBadgeVariant(string $status): string
    {
        return match ($status) {
            'accepted' => 'success',
            'pending' => 'warning',
            'expired' => 'error',
            'not_invited' => 'neutral',
            default => 'neutral',
        };
    }

    public function render(): View
    {
        return view('admin::livewire.admin-manager');
    }

    private function resolveInvitationStatus(User $admin): string
    {
        if (!$admin->requiresSetup()) {
            return 'accepted';
        }

        $tokens = $admin->accountTokens->where('type', AccountToken::TYPE_INVITATION);

        if ($tokens->isEmpty()) {
            return 'not_invited';
        }

        $hasActive = $tokens->contains(fn(AccountToken $t) => $t->isActive());

        return $hasActive ? 'pending' : 'expired';
    }
}
