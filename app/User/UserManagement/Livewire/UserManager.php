<?php

declare(strict_types=1);

namespace App\User\UserManagement\Livewire;

use App\Auth\Permissions\Enums\Role as RoleEnum;
use App\Core\Enums\CsvRowResult;
use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\Core\Support\CsvHandler;
use App\User\Enums\AccountStatus;
use App\User\Models\User;
use App\User\UserManagement\Actions\BatchDeleteUserAction;
use App\User\UserManagement\Actions\CreateUserAction;
use App\User\UserManagement\Actions\DeleteUserAction;
use App\User\UserManagement\Actions\ReadUserManagerStatsAction;
use App\User\UserManagement\Actions\RevokeUserActivationTokensAction;
use App\User\UserManagement\Actions\SetUserStatusAction;
use App\User\UserManagement\Actions\UpdateUserAction;
use App\User\UserManagement\Data\CreateUserData;
use App\User\UserManagement\Data\SetUserStatusData;
use App\User\UserManagement\Data\UpdateUserData;
use App\User\UserManagement\Livewire\Concerns\DownloadsAccountSlips;
use App\User\UserManagement\Livewire\Forms\UserForm;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserManager extends BaseRecordManager
{
    use AuthorizesRequests, DownloadsAccountSlips, WithFileUploads;

    public bool $userModal = false;

    public bool $showConfirm = false;

    public string $confirmActionType = '';

    public ?string $confirmTarget = null;

    public bool $showStatusModal = false;

    public ?string $statusTarget = null;

    public string $selectedStatus = '';

    public string $statusReason = '';

    public $importFile = null;

    public UserForm $form;

    private ReadUserManagerStatsAction $statsAction;

    public function boot(ReadUserManagerStatsAction $statsAction): void
    {
        $this->authorize('viewAny', User::class);

        $this->statsAction = $statsAction;
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('user.manager.name'), 'sortable' => true],
            ['key' => 'email', 'label' => __('user.manager.email')],
            ['key' => 'profile.phone', 'label' => __('user.fields.phone')],
            ['key' => 'roles_list', 'label' => __('user.manager.roles')],
            ['key' => 'status', 'label' => __('user.manager.status')],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', RoleEnum::ADMIN->value))
            ->with(['roles', 'profile']);
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('username', 'like', "%{$this->search}%")
                ->orWhereHas('profile', fn ($p) => $p->where('phone', 'like', "%{$this->search}%"));
        });
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['role'] ?? null, function ($q, $role) {
                $q->role($role);
            })
            ->when($this->filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when(
                $this->filters['created_from'] ?? null,
                fn ($q, $v) => $q->whereDate('created_at', '>=', $v),
            )
            ->when(
                $this->filters['created_to'] ?? null,
                fn ($q, $v) => $q->whereDate('created_at', '<=', $v),
            );
    }

    #[Computed]
    public function roles()
    {
        $assignable = array_map(
            fn (RoleEnum $role): string => $role->value,
            RoleEnum::excludeAdmin(),
        );

        return Role::whereIn('name', $assignable)->orderBy('name')->get();
    }

    #[Computed]
    public function statusOptions(): array
    {
        return collect(AccountStatus::cases())
            ->reject(fn ($s) => $s === AccountStatus::PROTECTED || $s === AccountStatus::ARCHIVED)
            ->map(fn ($s) => ['id' => $s->value, 'name' => $s->label()])
            ->values()
            ->toArray();
    }

    #[Computed]
    public function stats(): array
    {
        return $this->statsAction->execute();
    }

    public function createUser(): void
    {
        $this->resetErrorBag();
        $this->form->reset();
        $this->userModal = true;
    }

    public function editUser(string $id): void
    {
        $user = User::with('roles')->findOrFail($id);

        if ($user->hasRole('super_admin')) {
            flash()->error(__('user.manager.cannot_edit_super_admin'));

            return;
        }

        $this->resetErrorBag();
        $user->load('profile');
        $this->form->fill([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->toArray(),
            'phone' => $user->profile?->phone ?? '',
            'address' => $user->profile?->address ?? '',
            'bio' => $user->profile?->bio ?? '',
            'gender' => $user->profile?->gender?->value ?? '',
            'pob' => $user->profile?->pob ?? '',
            'dob' => $user->profile?->dob?->format('Y-m-d') ?? '',
            'emergency_contact_name' => $user->profile?->emergency_contact_name ?? '',
            'emergency_contact_phone' => $user->profile?->emergency_contact_phone ?? '',
            'emergency_contact_address' => $user->profile?->emergency_contact_address ?? '',
        ]);
        $this->userModal = true;
    }

    public function saveUser(CreateUserAction $createAction, UpdateUserAction $updateAction): void
    {
        $this->form->validate();

        if ($this->form->id) {
            $user = User::findOrFail($this->form->id);
            $updateAction->execute(new UpdateUserData(
                userId: $user->id,
                user: ['name' => $this->form->name, 'email' => $this->form->email],
                profile: [
                    'phone' => $this->form->phone ?: null,
                    'address' => $this->form->address ?: null,
                    'bio' => $this->form->bio ?: null,
                    'gender' => $this->form->gender ?: null,
                    'pob' => $this->form->pob ?: null,
                    'dob' => $this->form->dob ?: null,
                    'emergency_contact_name' => $this->form->emergency_contact_name ?: null,
                    'emergency_contact_phone' => $this->form->emergency_contact_phone ?: null,
                    'emergency_contact_address' => $this->form->emergency_contact_address ?: null,
                ],
                roles: $this->form->roles,
            ));
            flash()->success(__('user.manager.success_updated'));
        } else {
            $user = $createAction->execute(new CreateUserData(
                user: ['name' => $this->form->name, 'email' => $this->form->email],
                profile: [],
                roles: $this->form->roles,
            ));
            $this->userModal = false;
            $this->redirect(route('admin.users.account-slip', $user));

            return;
        }

        $this->userModal = false;
    }

    public function resetPassword(string $id, RevokeUserActivationTokensAction $revokeAction): void
    {
        $user = User::findOrFail($id);

        $revokeAction->execute($user);
        flash()->success(__('user.manager.password_reset'));
    }

    public function askDeleteUser(string $id): void
    {
        $this->confirmActionType = 'delete';
        $this->confirmTarget = $id;
        $this->showConfirm = true;
    }

    public function askDeleteSelected(): void
    {
        $this->confirmActionType = 'deleteSelected';
        $this->showConfirm = true;
    }

    public function confirmAction(
        DeleteUserAction $deleteAction,
        BatchDeleteUserAction $batchDelete,
    ): void {
        try {
            if ($this->confirmActionType === 'delete') {
                $user = User::findOrFail($this->confirmTarget);

                if ($user->hasRole('super_admin')) {
                    flash()->error(__('user.manager.cannot_delete_super_admin'));

                    return;
                }

                $deleteAction->execute($user);
                flash()->success(__('user.manager.success_deleted'));
            } elseif ($this->confirmActionType === 'deleteSelected') {
                $result = $batchDelete->execute($this->selectedIds);

                if ($result['deleted'] > 0) {
                    flash()->success(
                        __('common.actions.bulk_action_done', [
                            'count' => $result['deleted'],
                            'action' => __('common.actions.delete'),
                        ]),
                    );
                }

                $this->clearSelection();
            }
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }

        $this->showConfirm = false;
        $this->confirmTarget = null;
        $this->confirmActionType = '';
    }

    public function lockSelected(SetUserStatusAction $setStatus): void
    {
        $this->performBulkAction(__('common.actions.lock'), function (string $id) use (
            $setStatus,
        ): void {
            $user = User::findOrFail($id);
            $setStatus->execute(new SetUserStatusData(
                userId: $user->id,
                newStatus: AccountStatus::SUSPENDED,
                reason: __('user.manager.status_locked_bulk'),
            ));
        });
    }

    public function unlockSelected(SetUserStatusAction $setStatus): void
    {
        $this->performBulkAction(__('common.actions.unlock'), function (string $id) use (
            $setStatus,
        ): void {
            $user = User::findOrFail($id);
            $setStatus->execute(new SetUserStatusData(
                userId: $user->id,
                newStatus: AccountStatus::ACTIVATED,
            ));
        });
    }

    public function askChangeStatus(string $id): void
    {
        $this->resetErrorBag();
        $this->statusTarget = $id;
        $this->selectedStatus = '';
        $this->statusReason = '';
        $this->showStatusModal = true;
    }

    public function changeStatus(SetUserStatusAction $setStatus): void
    {
        $this->validate([
            'selectedStatus' => ['required', 'string'],
            'statusReason' => ['nullable', 'string', 'max:500'],
        ]);

        $status = AccountStatus::tryFrom($this->selectedStatus);

        if (! $status) {
            flash()->error(__('user.manager.status_invalid'));

            return;
        }

        $user = User::findOrFail($this->statusTarget);

        try {
            $setStatus->execute(new SetUserStatusData(
                userId: $user->id,
                newStatus: $status,
                reason: $this->statusReason ?: null,
            ));
            flash()->success(__('user.manager.status_changed'));
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }

        $this->showStatusModal = false;
        $this->statusTarget = null;
        $this->selectedStatus = '';
        $this->statusReason = '';
    }

    // --- Import / Export ---

    public function updatedImportFile(CsvHandler $csv, CreateUserAction $create): void
    {
        if ($this->importFile) {
            $this->import($csv, $create);
        }
    }

    public function import(CsvHandler $csv, CreateUserAction $create): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $result = $csv->import($this->importFile->getRealPath(), function (array $row) use (
            $create,
        ) {
            $name = trim($row[0] ?? '');

            if ($name === '') {
                return null;
            }

            if (User::where('email', trim($row[1] ?? ''))->exists()) {
                return CsvRowResult::SKIPPED;
            }

            $create->execute(new CreateUserData(
                user: [
                    'name' => $name,
                    'email' => trim($row[1] ?? ''),
                ],
                profile: [
                    'phone' => trim($row[2] ?? '') ?: null,
                ],
                roles: [],
            ));

            return CsvRowResult::CREATED;
        });

        $this->importFile = null;

        if ($result['invalid']) {
            flash()->error(__('common.actions.import_invalid'));

            return;
        }

        flash()->success(
            __('common.actions.import_summary', [
                'created' => $result['created'],
                'skipped' => $result['skipped'],
            ]),
        );
    }

    public function export(CsvHandler $csv): StreamedResponse
    {
        $users = User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', RoleEnum::ADMIN->value))
            ->with('profile')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->get();

        return $csv->export(
            $users,
            [
                __('user.fields.full_name'),
                __('user.fields.email'),
                __('user.fields.username'),
                __('user.fields.phone'),
                __('user.fields.address'),
            ],
            fn ($u) => [
                $u->name,
                $u->email,
                $u->username,
                $u->profile?->phone ?? '',
                $u->profile?->address ?? '',
            ],
            'users.csv',
        );
    }

    public function exportSelected(CsvHandler $csv): ?StreamedResponse
    {
        if ($this->selectedIds === []) {
            flash()->warning(__('common.actions.no_records_selected'));

            return null;
        }

        $users = User::with('profile')->whereIn('id', $this->selectedIds)->orderBy('name')->get();

        return $csv->export(
            $users,
            [
                __('user.fields.full_name'),
                __('user.fields.email'),
                __('user.fields.username'),
                __('user.fields.phone'),
                __('user.fields.address'),
            ],
            fn ($u) => [
                $u->name,
                $u->email,
                $u->username,
                $u->profile?->phone ?? '',
                $u->profile?->address ?? '',
            ],
            'users-selected.csv',
        );
    }

    public function downloadTemplate(CsvHandler $csv): StreamedResponse
    {
        return $csv->downloadTemplate(
            [__('user.fields.full_name'), __('user.fields.email'), __('user.fields.phone')],
            [
                __('user.manager.name_placeholder'),
                __('user.manager.email_placeholder'),
                __('user.fields.phone'),
            ],
            'users-template.csv',
        );
    }

    public function render(): View
    {
        return view('user.user-management.user-manager');
    }
}
