<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire;

use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Modules\Admin\Livewire\Forms\AdminForm;
use Modules\Admin\Services\Contracts\AdminService;
use Modules\Exception\Concerns\HandlesAppException;
use Modules\UI\Livewire\RecordManager;
use Modules\User\Models\AccountToken;
use Modules\User\Models\User;

/**
 * Class AdminManager
 *
 * Manages system administrators with specialized logic and role enforcement.
 * Only SuperAdmins are authorized to manage Admin accounts.
 *
 * Password management: admins are provisioned via email invitation, never by
 * direct password entry. This ensures admins set their own credentials securely.
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
        $this->subtitle = __('admin::ui.manager.subtitle');
        $this->addLabel = __('admin::ui.manager.add');
        $this->deleteConfirmMessage = __('admin::ui.manager.delete_confirm');
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
            ['key' => 'invitation_status', 'label' => __('admin::ui.manager.invitation_status'), 'sortable' => false],
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
        $paginator = $this->service->paginate(
            [
                'search' => $this->search,
                'sort_by' => $this->sortBy['column'] ?? 'created_at',
                'sort_dir' => $this->sortBy['direction'] ?? 'desc',
            ],
            $this->perPage,
            ['*'],
            ['roles:id,name', 'profile', 'statuses', 'accountTokens'],
        );

        $paginator->getCollection()->transform(function (User $admin): User {
            $admin->setAttribute('invitation_status', $this->resolveInvitationStatus($admin));
            return $admin;
        });

        return $paginator;
    }

    /**
     * Open form for adding a new admin.
     */
    public function add(): void
    {
        $this->form->reset();
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
     * Save the admin record and send invitation email if creating new.
     */
    public function save(): void
    {
        $this->form->validate();

        try {
            $isNew = ! $this->form->id;

            if ($isNew) {
                $admin = $this->service->create($this->form->all());
                // Send invitation email for new accounts
                $this->service->invite($admin, auth()->user());
                flash()->success(__('admin::ui.manager.invited', ['email' => $admin->email]));
            } else {
                $this->service->update($this->form->id, $this->form->all());
                flash()->success(__('shared::messages.record_saved'));
            }

            $this->formModal = false;
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    /**
     * Resend the invitation email to an unclaimed admin.
     * Only available while setup_required is true.
     */
    public function reinvite(mixed $id): void
    {
        try {
            /** @var User $admin */
            $admin = $this->service->findOrFail($id);

            if (! $admin->requiresSetup()) {
                flash()->warning(__('admin::ui.manager.already_accepted'));
                return;
            }

            $this->service->invite($admin, auth()->user());
            flash()->success(__('admin::ui.manager.reinvited', ['email' => $admin->email]));
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

    // ─── Internal ────────────────────────────────────────────────────────────────

    /**
     * Derive the invitation status label key for an admin user.
     */
    private function resolveInvitationStatus(User $admin): string
    {
        // Account already activated
        if (! $admin->requiresSetup()) {
            return 'accepted';
        }

        $tokens = $admin->accountTokens
            ->where('type', AccountToken::TYPE_INVITATION);

        if ($tokens->isEmpty()) {
            return 'not_invited';
        }

        // Has at least one non-expired, non-claimed token
        $hasActive = $tokens->contains(
            fn (AccountToken $t): bool => $t->isActive()
        );

        return $hasActive ? 'pending' : 'expired';
    }
}
