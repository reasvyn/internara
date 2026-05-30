<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire;

use App\Domain\Admin\Actions\BatchDeleteUserAction;
use App\Domain\Admin\Actions\CreateUserAction;
use App\Domain\Admin\Actions\DeleteUserAction;
use App\Domain\Admin\Actions\GetUserManagerStatsAction;
use App\Domain\Admin\Actions\UpdateUserAction;
use App\Domain\Admin\Livewire\Concerns\DownloadsAccountSlips;
use App\Domain\Admin\Livewire\Forms\UserForm;
use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Models\ActivationToken;
use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\Shared\Enums\CsvRowResult;
use App\Domain\Shared\Support\CsvHandler;
use App\Domain\User\Models\User;
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

    public bool $showStatusModal = false;

    public ?string $statusTarget = null;

    public string $selectedStatus = '';

    public string $statusReason = '';

    public $importFile = null;

    public UserForm $form;

    public function boot(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('user.manager.name'), 'sortable' => true],
            ['key' => 'email', 'label' => __('user.manager.email')],
            ['key' => 'roles_list', 'label' => __('user.manager.roles')],
            ['key' => 'status', 'label' => __('user.manager.status')],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return User::query()->with(['roles', 'statuses']);
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('username', 'like', "%{$this->search}%");
        });
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['role'] ?? null, function ($q, $role) {
                $q->role($role);
            })
            ->when($this->filters['status'] ?? null, function ($q, $status) {
                $q->whereHas('statuses', fn ($qs) => $qs->where('name', $status)->latest('id'));
            })
            ->when($this->filters['created_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($this->filters['created_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
    }

    #[Computed]
    public function roles()
    {
        return Role::whereNotIn('name', ['super_admin', 'admin'])->get();
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
        return app(GetUserManagerStatsAction::class)->execute();
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
            $updateAction->execute(
                $user,
                ['name' => $this->form->name, 'email' => $this->form->email],
                [
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
                $this->form->roles,
            );
            flash()->success(__('user.manager.success_updated'));
        } else {
            $user = $createAction->execute(['name' => $this->form->name, 'email' => $this->form->email], [], $this->form->roles);
            $this->userModal = false;
            $this->redirect(route('admin.users.account-slip', $user));

            return;
        }

        $this->userModal = false;
    }

    public function resetPassword(string $id): void
    {
        $user = User::findOrFail($id);

        ActivationToken::revokeFor($user);
        flash()->success(__('user.manager.password_reset'));
    }

    public function deleteUser(string $id, DeleteUserAction $deleteAction): void
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('super_admin')) {
            flash()->error(__('user.manager.cannot_delete_super_admin'));

            return;
        }

        try {
            $deleteAction->execute($user);
            flash()->success(__('user.manager.success_deleted'));
        } catch (\RuntimeException $e) {
            flash()->error($e->getMessage());
        }
    }

    public function deleteSelected(BatchDeleteUserAction $batchDelete): void
    {
        $result = $batchDelete->execute($this->selectedIds);

        if ($result['deleted'] > 0) {
            flash()->success(__('common.actions.bulk_action_done', [
                'count' => $result['deleted'],
                'action' => __('common.actions.delete'),
            ]));
        }

        $this->clearSelection();
    }

    // --- Import / Export ---

    public function updatedImportFile(): void
    {
        if ($this->importFile) {
            $this->import(app(CsvHandler::class), app(CreateUserAction::class));
        }
    }

    public function import(CsvHandler $csv, CreateUserAction $create): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $result = $csv->import($this->importFile->getRealPath(), function (array $row) use ($create) {
            $name = trim($row[0] ?? '');

            if ($name === '') {
                return null;
            }

            if (User::where('email', trim($row[1] ?? ''))->exists()) {
                return CsvRowResult::SKIPPED;
            }

            $create->execute([
                'name' => $name,
                'email' => trim($row[1] ?? ''),
            ], [], []);

            return CsvRowResult::CREATED;
        });

        $this->importFile = null;

        if ($result['invalid']) {
            flash()->error(__('common.actions.import_invalid'));

            return;
        }

        flash()->success(__('common.actions.import_summary', [
            'created' => $result['created'],
            'skipped' => $result['skipped'],
        ]));
    }

    public function export(CsvHandler $csv): StreamedResponse
    {
        $users = User::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->get();

        return $csv->export(
            $users,
            [__('user.fields.full_name'), __('user.fields.email'), __('user.fields.username')],
            fn ($u) => [$u->name, $u->email, $u->username],
            'users.csv',
        );
    }

    public function exportSelected(CsvHandler $csv): ?StreamedResponse
    {
        if ($this->selectedIds === []) {
            flash()->warning(__('common.actions.no_records_selected'));

            return null;
        }

        $users = User::whereIn('id', $this->selectedIds)->orderBy('name')->get();

        return $csv->export(
            $users,
            [__('user.fields.full_name'), __('user.fields.email'), __('user.fields.username')],
            fn ($u) => [$u->name, $u->email, $u->username],
            'users-selected.csv',
        );
    }

    public function downloadTemplate(CsvHandler $csv): StreamedResponse
    {
        return $csv->downloadTemplate(
            [__('user.fields.full_name'), __('user.fields.email')],
            [__('user.manager.name_placeholder'), __('user.manager.email_placeholder')],
            'users-template.csv',
        );
    }

    public function render(): View
    {
        return view('admin.manager');
    }
}
