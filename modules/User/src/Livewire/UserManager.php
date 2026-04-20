<?php

declare(strict_types=1);

namespace Modules\User\Livewire;

use Illuminate\Support\Arr;
use Illuminate\View\View;
use Modules\Permission\Enums\Role;
use Modules\Department\Livewire\Concerns\HasDepartmentOptions;
use Modules\UI\Livewire\RecordManager;
use Modules\User\Livewire\Forms\UserManagerForm;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;

/**
 * Class UserManager
 *
 * Provides a unified interface for managing system users, supporting role-based filtering
 * and standard CRUD operations via the RecordManager abstraction.
 */
class UserManager extends RecordManager
{
    use HasDepartmentOptions;

    public UserManagerForm $form;

    /**
     * The specific role being managed (optional).
     */
    public ?string $targetRole = null;

    /**
     * Initialize the component metadata and services.
     */
    public function boot(UserService $userService): void
    {
        $this->service = $userService;
        $this->eventPrefix = 'user';
        $this->modelClass = User::class;
    }

    /**
     * Configure the component's basic properties.
     */
    public function initialize(): void
    {
        $roleKey = $this->targetRole ?: 'user';
        $this->title = $this->targetRole
            ? __("user::ui.{$roleKey}_management")
            : __('user::ui.manager.title');
        $this->subtitle = __('user::ui.manager.subtitle');
        $this->context = 'admin::ui.menu.users';
        $this->addLabel = __('user::ui.manager.add_'.$roleKey);
        $this->deleteConfirmMessage = __('user::ui.manager.delete.message');

        $this->viewPermission = 'user.view';
        $this->createPermission = 'user.manage';
        $this->updatePermission = 'user.manage';
        $this->deletePermission = 'user.manage';
    }

    /**
     * Define the table structure.
     */
    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'name', 'label' => __('user::ui.manager.table.name'), 'sortable' => true],
            ['key' => 'email', 'label' => __('user::ui.manager.table.email'), 'sortable' => true],
            [
                'key' => 'username',
                'label' => __('user::ui.manager.table.username'),
                'sortable' => true,
            ],
            ['key' => 'role_labels', 'label' => __('user::ui.manager.table.roles')],
            ['key' => 'display_status', 'label' => __('user::ui.manager.table.status')],
            ['key' => 'actions', 'label' => '', 'class' => 'w-1'],
        ];
    }

    /**
     * Customize the query to include roles and profiles.
     */
    #[\Livewire\Attributes\Computed]
    public function records(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $appliedFilters = array_filter(
            array_merge($this->filters, [
                'search' => $this->search,
                'sort_by' => $this->sortBy['column'] ?? 'created_at',
                'sort_dir' => $this->sortBy['direction'] ?? 'desc',
            ]),
            fn ($value) => $value !== null && $value !== '' && $value !== [],
        );

        return $this->service
            ->query($appliedFilters)
            ->with(['roles:id,name', 'profile.department', 'statuses'])
            ->paginate($this->perPage)
            ->through(function (User $user): User {
                $roleNames = $user->roles->pluck('name')->values()->all();
                $displayStatus = $user->hasAnyRole([Role::SUPER_ADMIN->value, Role::ADMIN->value])
                    ? 'verified'
                    : ($user->latestStatus()?->name ?? User::STATUS_ACTIVE);

                $user->setAttribute('role_labels', implode(', ', $roleNames));
                $user->setAttribute('display_status', $displayStatus);

                return $user;
            });
    }

    /**
     * Remove all selected users with safety checks.
     */
    public function removeSelected(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        try {
            $targets = $this->service
                ->query()
                ->whereIn('id', $this->selectedIds)
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

    public function sendPasswordResetLink(mixed $id): void
    {
        try {
            $this->service->sendPasswordResetLink($id);
            flash()->success(__('auth::ui.forgot_password.sent'));
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    public function save(): void
    {
        $this->form->validate();
        $payload = Arr::except($this->form->all(), ['password', 'password_confirmation']);
        $isSetupAuthorized = session(\Modules\Setup\Services\Contracts\SetupService::SESSION_SETUP_AUTHORIZED) === true;

        try {
            if ($isSetupAuthorized) {
                $this->service->withoutAuthorization();
            }

            if ($this->form->id) {
                $record = $this->service->find($this->form->id);
                if (! $isSetupAuthorized && $record && $this->updatePermission) {
                    \Illuminate\Support\Facades\Gate::authorize($this->updatePermission, $record);
                }
                $this->service->update($this->form->id, $payload);
            } else {
                if (! $isSetupAuthorized && $this->createPermission) {
                    $roles = $this->form->roles;
                    $authModel = $this->modelClass ?: config('auth.providers.users.model');
                    \Illuminate\Support\Facades\Gate::authorize($this->createPermission, [$authModel, $roles]);
                }
                $this->service->create($payload);
            }

            $this->toggleModal(self::MODAL_FORM, false);
            flash()->success('shared::messages.record_saved');
            $this->dispatch($this->getEventPrefix().':saved', exists: true);
        } catch (\Throwable $e) {
            if (is_debug_mode()) {
                throw $e;
            }

            flash()->error($e->getMessage());
        }
    }

    /**
     * Prepare form for a new user, pre-assigning target role if applicable.
     */
    public function add(): void
    {
        $this->form->reset();

        if ($this->targetRole) {
            $this->form->roles = [$this->targetRole];
        }

        $this->toggleModal(self::MODAL_FORM, true);
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        $roleKey = $this->targetRole ?: 'user';

        return view('user::livewire.user-manager', [
            'roleKey' => $roleKey,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => $this->title.' | '.setting('brand_name', setting('app_name')),
        ]);
    }
}
