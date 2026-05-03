declare(strict_types=1);

namespace App\Livewire\User\Admin;

use App\Domain\User\Actions\CreateUserAction;
use App\Domain\User\Actions\DeleteUserAction;
use App\Domain\User\Actions\UpdateUserAction;
use App\Domain\Auth\Enums\Role as RoleEnum;
use App\Livewire\BaseRecordManager;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modernized Admin Manager using BaseRecordManager pattern.
 */
class AdminManager extends BaseRecordManager
{
    public bool $userModal = false;

    public array $userData = [
        'id' => null,
        'name' => '',
        'email' => '',
        'roles' => [RoleEnum::ADMIN->value],
    ];

    public function boot(): void
    {
        if (
            ! auth()
                ->user()
                ?->hasAnyRole(['super_admin', 'admin'])
        ) {
            abort(403, 'Unauthorized access.');
        }
    }

    /**
     * Define columns and sorting.
     */
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => __('user.admin.name'), 'sortable' => true],
            ['key' => 'email', 'label' => __('user.fields.email'), 'sortable' => true],
            [
                'key' => 'username',
                'label' => __('user.fields.username'),
                'class' => 'font-mono text-xs',
            ],
            ['key' => 'created_at', 'label' => __('user.student.joined'), 'sortable' => true],
            ['key' => 'actions', 'label' => ''],
        ];
    }

    /**
     * Base query for admins.
     */
    protected function query(): Builder
    {
        return User::query()->role([RoleEnum::ADMIN->value, RoleEnum::SUPER_ADMIN->value]);
    }

    /**
     * Search implementation.
     */
    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('username', 'like', "%{$this->search}%");
        });
    }

    // --- Record Actions ---

    public function create(): void
    {
        $this->resetErrorBag();
        $this->userData = [
            'id' => null,
            'name' => '',
            'email' => '',
            'roles' => [RoleEnum::ADMIN->value],
        ];
        $this->userModal = true;
    }

    public function edit(User $user): void
    {
        $this->resetErrorBag();
        $this->userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->toArray(),
        ];
        $this->userModal = true;
    }

    public function save(CreateUserAction $createAction, UpdateUserAction $updateAction): void
    {
        $this->validate([
            'userData.name' => 'required|string|max:255',
            'userData.email' => 'required|email|unique:users,email,'.($this->userData['id'] ?? 'NULL'),
        ]);

        if ($this->userData['id']) {
            $user = User::findOrFail($this->userData['id']);
            $updateAction->execute($user, $this->userData);
            $this->success(__('user.admin.success_updated', default: 'Admin updated.'));
        } else {
            $createAction->execute($this->userData, [], $this->userData['roles']);
            $this->success(__('user.admin.success_created'));
        }

        $this->userModal = false;
    }

    public function delete(User $user, DeleteUserAction $deleteAction): void
    {
        if ($user->id === auth()->id()) {
            $this->error('Cannot delete yourself.');

            return;
        }

        $deleteAction->execute($user);
        $this->success(__('user.admin.success_deleted', default: 'Admin deleted.'));
    }

    // --- Bulk Actions ---

    public function deleteSelected(DeleteUserAction $deleteAction): void
    {
        $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
            if ($id === auth()->id()) {
                return;
            }
            $user = User::find($id);
            if ($user) {
                $deleteAction->execute($user);
            }
        });
    }

    public function render()
    {
        return view('livewire.admin.user.admin-manager');
    }
}
