<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UserManagement\Livewire;

use App\Modules\Auth\Domain\Permissions\Enums\Role as RoleEnum;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Core\Livewire\BaseRecordManager;
use App\Modules\Core\Support\CsvHandler;
use App\Modules\Partners\Domain\Company\Models\Company;
use App\Modules\User\Domain\UserManagement\Actions\CreateUserAction;
use App\Modules\User\Domain\UserManagement\Actions\DeleteUserAction;
use App\Modules\User\Domain\UserManagement\Actions\UpdateUserAction;
use App\Modules\User\Domain\UserManagement\Data\CreateUserData;
use App\Modules\User\Domain\UserManagement\Data\UpdateUserData;
use App\Modules\User\Domain\UserManagement\Livewire\Concerns\DownloadsAccountSlips;
use App\Modules\User\Domain\UserManagement\Livewire\Forms\SupervisorForm;
use App\Modules\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TallStackUi\Traits\Interactions;

class SupervisorManager extends BaseRecordManager
{
    use AuthorizesRequests, DownloadsAccountSlips;
    use Interactions;

    public bool $userModal = false;

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
            ['index' => 'name', 'label' => __('user.supervisor.name'), 'sortable' => true],
            [
                'index' => 'username',
                'label' => __('user.fields.username'),
                'class' => 'font-mono text-xs',
            ],
            ['index' => 'email', 'label' => __('user.fields.email'), 'sortable' => true],
            ['index' => 'profile.company_id', 'label' => __('user.supervisor.company')],
            ['index' => 'created_at', 'label' => __('user.student.joined'), 'sortable' => true],
            ['index' => 'actions', 'label' => '', 'sortable' => false],
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

    #[Computed]
    public function companies(): array
    {
        return Company::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($company) => ['id' => $company->id, 'name' => $company->name])
            ->toArray();
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
            $updateAction->execute(new UpdateUserData(
                userId: $user->id,
                user: ['name' => $this->form->name, 'email' => $this->form->email],
                profile: $profileData,
            ));
            $this->toast()->success(__('user.supervisor.success_updated'))->send();
        } else {
            $user = $createAction->execute(new CreateUserData(
                user: ['name' => $this->form->name, 'email' => $this->form->email],
                profile: $profileData,
                roles: [RoleEnum::SUPERVISOR->value],
                sendNotification: false,
            ));
            $this->userModal = false;
            $this->redirect(route('admin.users.account-slip', $user));

            return;
        }

        $this->userModal = false;
    }

    public function askDelete(string $id): void
    {
        $this->confirmActionType = 'delete';
        $this->confirmTarget = $id;
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function askDeleteSelected(): void
    {
        $this->confirmActionType = 'deleteSelected';
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function confirmAction(DeleteUserAction $deleteAction): void
    {
        try {
            if ($this->confirmActionType === 'delete') {
                $deleteAction->execute(User::findOrFail($this->confirmTarget));
                $this->toast()->success(__('user.supervisor.success_deleted'))->send();
            } elseif ($this->confirmActionType === 'deleteSelected') {
                $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
                    $user = User::find($id);
                    if ($user) {
                        $deleteAction->execute($user);
                    }
                });
            }
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }
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
