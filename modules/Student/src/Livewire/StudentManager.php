<?php

declare(strict_types=1);

namespace Modules\Student\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Modules\Department\Livewire\Concerns\HasDepartmentOptions;
use Modules\Exception\Concerns\HandlesAppException;
use Modules\Permission\Enums\Role;
use Modules\Student\Livewire\Forms\StudentForm;
use Modules\Student\Services\Contracts\StudentService;
use Modules\UI\Livewire\RecordManager;
use Modules\User\Models\AccountToken;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\AccountProvisioningService;

/**
 * Class StudentManager
 *
 * Manages student accounts with explicit role boundaries and student-specific flows.
 */
class StudentManager extends RecordManager
{
    use HandlesAppException;
    use HasDepartmentOptions;

    public StudentForm $form;

    public array $credentialSlips = [];

    public bool $credentialSlipsModal = false;

    /**
     * Initialize the component.
     */
    public function boot(StudentService $studentService): void
    {
        $this->service = $studentService;
        $this->eventPrefix = 'student';
        $this->modelClass = User::class;
    }

    public function initialize(): void
    {
        $this->title = __('admin::ui.menu.students');
        $this->subtitle = __('user::ui.manager.subtitle');
        $this->context = 'admin::ui.menu.students';
        $this->addLabel = __('user::ui.manager.add_student');
        $this->deleteConfirmMessage = __('user::ui.manager.delete.message');
        $this->viewPermission = 'student.manage';
        $this->createPermission = 'student.manage';
        $this->updatePermission = 'student.manage';
        $this->deletePermission = 'student.manage';
    }

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->hasAnyRole([Role::ADMIN->value, Role::SUPER_ADMIN->value]),
            403,
        );

        parent::mount();
    }

    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'name', 'label' => __('user::ui.manager.table.name'), 'sortable' => true],
            ['key' => 'email', 'label' => __('user::ui.manager.table.email'), 'sortable' => true],
            ['key' => 'username', 'label' => __('user::ui.manager.table.username'), 'sortable' => true],
            ['key' => 'registration_number', 'label' => __('student::ui.manager.table.registration_number')],
            ['key' => 'department_name', 'label' => __('student::ui.manager.table.department')],
            ['key' => 'display_status', 'label' => __('user::ui.manager.table.status')],
            ['key' => 'activation_status', 'label' => __('user::ui.manager.table.activation_status')],
            ['key' => 'created_at', 'label' => __('ui::common.created_at'), 'sortable' => true],
            ['key' => 'actions', 'label' => ''],
        ];
    }

    /**
     * Get records property for the table.
     */
    #[Computed]
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

        return $this->managedStudentQuery($appliedFilters)
            ->with(['profile.department', 'statuses'])
            ->paginate($this->perPage)
            ->through(function (User $user): User {
                $user->setAttribute('registration_number', $user->profile?->registration_number ?? '');
                $user->setAttribute('department_name', $user->profile?->department?->name ?? '');
                $user->setAttribute('display_status', $user->latestStatus()?->name ?? User::STATUS_ACTIVE);
                $user->setAttribute('activation_status', $user->setup_required ? 'pending_claim' : 'claimed');

                return $user;
            });
    }

    public function activeFilterCount(): int
    {
        return count(array_filter(
            $this->filters,
            fn ($value) => $value !== null && $value !== '' && $value !== [],
        ));
    }

    public function resetFilters(): void
    {
        $this->filters = [];
        $this->selectedIds = [];
        $this->resetPage();
    }

    public function reissueActivationCode(mixed $id): void
    {
        $user = $this->service->find($id);

        if (! $user) {
            return;
        }

        $this->authorize('update', $user);

        try {
            $plainCode = app(AccountProvisioningService::class)->reissue(
                $user,
                AccountToken::TYPE_ACTIVATION,
                30,
                auth()->user(),
            );

            $this->credentialSlips = [['name' => $user->name, 'username' => $user->username, 'code' => $plainCode]];
            $this->credentialSlipsModal = true;
            flash()->success(__('student::ui.manager.messages.code_reissued'));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    public function reissueSelectedActivationCodes(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        try {
            $students = $this->managedStudentQuery()->whereIn('id', $this->selectedIds)->get();

            $slips = app(AccountProvisioningService::class)->provisionBatch(
                $students,
                AccountToken::TYPE_ACTIVATION,
                30,
                auth()->user(),
            );

            $this->credentialSlips = array_map(
                fn (array $item) => [
                    'name'     => $item['user']->name,
                    'username' => $item['user']->username,
                    'code'     => $item['plain_code'],
                ],
                $slips,
            );

            $this->credentialSlipsModal = true;
            $this->selectedIds = [];
            flash()->success(__('student::ui.manager.messages.codes_reissued', ['count' => count($slips)]));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    public function closeCredentialSlips(): void
    {
        $this->credentialSlipsModal = false;
        $this->credentialSlips = [];
    }

    public function activateSelected(): void
    {
        $this->updateSelectedStatus(User::STATUS_ACTIVE, 'activated');
    }

    public function archiveSelected(): void
    {
        $this->updateSelectedStatus(User::STATUS_INACTIVE, 'archived');
    }

    /**
     * Open form for adding a new student.
     */
    public function add(): void
    {
        $this->form->reset();
        $this->toggleModal(self::MODAL_FORM, true);
    }

    /**
     * Open form for editing a student.
     */
    public function edit(mixed $id): void
    {
        $user = $this->service->find($id);

        if ($user) {
            $this->authorize('update', $user);
            $this->form->fillFromUser($user);
            $this->toggleModal(self::MODAL_FORM, true);
        }
    }

    /**
     * Save the student record.
     */
    public function save(): void
    {
        $this->form->validate();
        $payload = Arr::except($this->form->all(), ['password', 'password_confirmation']);

        try {
            if ($this->form->id) {
                $this->service->update($this->form->id, $payload);
            } else {
                $this->service->create($payload);
            }

            $this->toggleModal(self::MODAL_FORM, false);
            flash()->success(__('shared::messages.record_saved'));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    public function statusBadgeVariant(string $status): string
    {
        return match ($status) {
            User::STATUS_ACTIVE => 'success',
            User::STATUS_PENDING => 'warning',
            User::STATUS_INACTIVE => 'error',
            default => 'secondary',
        };
    }

    public function activationStatusBadgeVariant(string $status): string
    {
        return match ($status) {
            'pending_claim' => 'warning',
            'claimed'       => 'success',
            default         => 'secondary',
        };
    }

    /**
     * Render the student manager view.
     */
    public function render(): View
    {
        return view('student::livewire.student-manager', [
            'title' => $this->title,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => $this->title.' | '.setting('brand_name', setting('app_name')),
            'context' => 'admin::ui.menu.students',
        ]);
    }

    protected function managedStudentQuery(array $filters = []): Builder
    {
        $selectedStatus = $filters['status'] ?? null;
        $departmentId = $filters['department_id'] ?? null;
        $createdFrom = $filters['created_from'] ?? null;
        $createdTo = $filters['created_to'] ?? null;

        $query = $this->service->query(
            Arr::except($filters, ['status', 'department_id', 'created_from', 'created_to']),
        );

        if ($departmentId) {
            $query->whereHas('profile', fn (Builder $profileQuery): Builder => $profileQuery->where('department_id', $departmentId));
        }

        if (in_array($selectedStatus, [User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_PENDING], true)) {
            $this->applyLatestStatusFilter($query, $selectedStatus);
        }

        if ($createdFrom) {
            $query->whereDate((new User)->getTable().'.created_at', '>=', $createdFrom);
        }

        if ($createdTo) {
            $query->whereDate((new User)->getTable().'.created_at', '<=', $createdTo);
        }

        return $query;
    }

    protected function applyLatestStatusFilter(Builder $query, string $status): void
    {
        $statusTable = app(config('model-status.status_model'))->getTable();
        $userTable = (new User)->getTable();

        $query->whereExists(function ($statusQuery) use ($status, $statusTable, $userTable): void {
            $statusQuery
                ->selectRaw('1')
                ->from($statusTable.' as latest_status')
                ->whereColumn('latest_status.model_id', $userTable.'.id')
                ->where('latest_status.model_type', User::class)
                ->where('latest_status.name', $status)
                ->whereRaw(
                    'latest_status.created_at = (select max(status_history.created_at) from '.$statusTable.' as status_history where status_history.model_type = ? and status_history.model_id = '.$userTable.'.id)',
                    [User::class],
                );
        });
    }

    protected function updateSelectedStatus(string $status, string $messageKey): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        try {
            $students = $this->managedStudentQuery()->whereIn('id', $this->selectedIds)->get();

            foreach ($students as $student) {
                $this->service->update($student->id, ['status' => $status]);
            }

            $this->selectedIds = [];
            flash()->success(__('student::ui.manager.messages.'.$messageKey, ['count' => $students->count()]));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }
}
