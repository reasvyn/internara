<?php

declare(strict_types=1);

namespace App\User\UserManagement\Livewire;

use App\Auth\Permissions\Enums\Role as RoleEnum;
use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\Core\Support\CsvHandler;
use App\User\Models\User;
use App\User\UserManagement\Actions\CreateUserAction;
use App\User\UserManagement\Actions\DeleteUserAction;
use App\User\UserManagement\Actions\UpdateUserAction;
use App\User\UserManagement\Livewire\Concerns\DownloadsAccountSlips;
use App\User\UserManagement\Livewire\Forms\SupervisorForm;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupervisorManager extends BaseRecordManager
{
    use AuthorizesRequests, DownloadsAccountSlips;

    public bool $userModal = false;

    public bool $showConfirm = false;

    public string $confirmActionType = '';

    public ?string $confirmTarget = null;

    public SupervisorForm $form;

    public function boot(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('user.supervisor.name'), 'sortable' => true],
            [
                'key' => 'username',
                'label' => __('user.fields.username'),
                'class' => 'font-mono text-xs',
            ],
            ['key' => 'email', 'label' => __('user.fields.email'), 'sortable' => true],
            ['key' => 'profile.company_id', 'label' => __('user.supervisor.company')],
            ['key' => 'created_at', 'label' => __('user.student.joined'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return User::query()
            ->role(RoleEnum::SUPERVISOR->value)
            ->with(['profile.company']);
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
            ->when(
                $this->filters['created_from'] ?? null,
                fn ($q, $v) => $q->whereDate('created_at', '>=', $v),
            )
            ->when(
                $this->filters['created_to'] ?? null,
                fn ($q, $v) => $q->whereDate('created_at', '<=', $v),
            );
    }

    // --- Record Actions ---

    public function create(): void
    {
        $this->resetErrorBag();
        $this->form->reset();
        $this->userModal = true;
    }

    public function edit(string $id): void
    {
        $user = User::with('profile')->findOrFail($id);

        $this->resetErrorBag();
        $this->form->fill([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->profile?->phone ?? '',
            'company_id' => $user->profile?->company_id ?? null,
        ]);
        $this->userModal = true;
    }

    public function save(CreateUserAction $createAction, UpdateUserAction $updateAction): void
    {
        $this->form->validate();

        $profileData = [
            'phone' => $this->form->phone,
            'company_id' => $this->form->company_id,
        ];

        if ($this->form->id) {
            $user = User::findOrFail($this->form->id);
            $updateAction->execute(
                $user,
                ['name' => $this->form->name, 'email' => $this->form->email],
                $profileData,
            );
            flash()->success(__('user.supervisor.success_updated'));
        } else {
            $user = $createAction->execute(
                ['name' => $this->form->name, 'email' => $this->form->email],
                $profileData,
                [RoleEnum::SUPERVISOR->value],
                false,
            );
            $this->userModal = false;
            $this->redirect(route('sysadmin.users.account-slip', $user));

            return;
        }

        $this->userModal = false;
    }

    public function askDelete(string $id): void
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

    public function confirmAction(DeleteUserAction $deleteAction): void
    {
        try {
            if ($this->confirmActionType === 'delete') {
                $deleteAction->execute(User::findOrFail($this->confirmTarget));
                flash()->success(__('user.supervisor.success_deleted'));
            } elseif ($this->confirmActionType === 'deleteSelected') {
                $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
                    $user = User::find($id);
                    if ($user) {
                        $deleteAction->execute($user);
                    }
                });
            }
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }

        $this->showConfirm = false;
        $this->confirmTarget = null;
        $this->confirmActionType = '';
    }

    public function export(CsvHandler $csv): StreamedResponse
    {
        $users = User::query()
            ->role(RoleEnum::SUPERVISOR->value)
            ->with('profile.company')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->get();

        return $csv->export(
            $users,
            [
                __('user.fields.full_name'),
                __('user.fields.email'),
                __('user.supervisor.company'),
            ],
            fn ($u) => [
                $u->name,
                $u->email,
                $u->profile?->company?->name ?? '',
            ],
            'supervisors.csv',
        );
    }

    public function render(): View
    {
        return view('user.user-management.supervisor-manager');
    }
}
