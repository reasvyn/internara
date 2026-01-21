<?php

declare(strict_types=1);

namespace Modules\User\Livewire;

use Livewire\Component;
use Modules\Shared\Livewire\Concerns\ManagesRecords;
use Modules\User\Livewire\Forms\UserForm;
use Modules\User\Services\Contracts\UserService;

class UserManager extends Component
{
    use ManagesRecords;

    public UserForm $form;

    public string $targetRole = '';

    /**
     * Initialize the component.
     */
    public function boot(UserService $userService): void
    {
        $this->service = $userService;
        $this->eventPrefix = 'user';
    }

    /**
     * Mount the component.
     */
    public function mount(string $targetRole = ''): void
    {
        $this->targetRole = $targetRole;

        // Security: Only SuperAdmins can manage 'admin' role
        if ($this->targetRole === 'admin' && ! auth()->user()->hasRole('super-admin')) {
            abort(403, 'Only SuperAdmins can manage administrator accounts.');
        }

        $this->authorize('user.view');
    }

    /**
     * Override records to filter by role.
     */
    public function records(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $filters = [
            'search' => $this->search,
            'sort_by' => $this->sortBy,
            'sort_dir' => $this->sortDir,
        ];

        if ($this->targetRole) {
            $filters['roles.name'] = $this->targetRole;
        }

        return $this->service->paginate(
            array_filter($filters),
            $this->perPage,
        );
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

    public function getDepartmentsProperty(): \Illuminate\Support\Collection
    {
        if (class_exists(\Modules\Department\Services\Contracts\DepartmentService::class)) {
            return app(\Modules\Department\Services\Contracts\DepartmentService::class)->all(['id', 'name']);
        }
        return collect();
    }

    /**
     * Open the form modal for editing a record.
     */
    public function edit(mixed $id): void
    {
        $user = $this->service->find($id);

        if ($user) {
            $this->form->fill($user->toArray());
            
            // Populate profile data based on role
            if ($user->hasRole('student') && $user->studentProfile) {
                $this->form->profile = [
                    'identity_number' => $user->studentProfile->nisn,
                    'department_id' => $user->studentProfile->department_id,
                    'phone' => $user->studentProfile->phone,
                ];
            } elseif ($user->hasRole('teacher') && $user->teacherProfile) {
                $this->form->profile = [
                    'identity_number' => $user->teacherProfile->nip,
                    'department_id' => $user->teacherProfile->department_id,
                    'phone' => $user->teacherProfile->phone,
                ];
            }

            $this->formModal = true;
        }
    }

    public function render()
    {
        return view('user::livewire.user-manager', [
            'title' => $this->targetRole ? ucfirst($this->targetRole).' Management' : 'User Management',
        ]);
    }
}
