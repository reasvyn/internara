<?php

declare(strict_types=1);

namespace Modules\User\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Exception\Concerns\HandlesAppException;
use Modules\User\Services\Contracts\UserService;

/**
 * Class Index
 *
 * Provides a paginated list of users with search and sorting capabilities.
 */
class Index extends Component
{
    use HandlesAppException;
    use WithPagination;

    /**
     * Search term for filtering users.
     */
    #[Url(history: true)]
    public string $search = '';

    /**
     * Sort column.
     */
    #[Url(history: true)]
    public string $sortBy = 'created_at';

    /**
     * Sort direction.
     */
    #[Url(history: true)]
    public string $sortDir = 'desc';

    /**
     * The user service instance.
     */
    protected UserService $userService;

    /**
     * Boot the component.
     */
    public function boot(UserService $userService): void
    {
        $this->userService = $userService;
    }

    /**
     * Handle updated search term.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Delete a user by ID.
     */
    public function delete(string $id): void
    {
        try {
            $this->userService->delete($id);
            $this->dispatch('success', message: __('User deleted successfully.'));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    /**
     * Toggle a user's account status.
     */
    public function toggleStatus(string $id): void
    {
        try {
            $this->userService->toggleStatus($id);
            $this->dispatch('success', message: __('User status updated.'));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('user::livewire.users.index', [
            'users' => $this->userService->paginate(
                [
                    'search' => $this->search,
                    'sort_by' => $this->sortBy,
                    'sort_dir' => $this->sortDir,
                ],
                10,
                ['id', 'name', 'email', 'username', 'created_at'],
            ),
            'headers' => $this->getHeaders(),
        ]);
    }

    /**
     * Define table headers.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getHeaders(): array
    {
        return [
            ['key' => 'name', 'label' => __('Name'), 'sortable' => true],
            ['key' => 'email', 'label' => __('Email'), 'sortable' => true],
            ['key' => 'roles', 'label' => __('Roles'), 'sortable' => false],
            ['key' => 'status', 'label' => __('Status'), 'sortable' => false],
            ['key' => 'created_at', 'label' => __('Joined'), 'sortable' => true],
        ];
    }
}
