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

    /**
     * Configure the component's basic properties.
     */
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

        $this->searchable = ['name', 'email', 'username', 'profile.registration_number', 'profile.national_identifier'];
        $this->sortable = ['name', 'email', 'username', 'created_at'];
    }

    /**
     * Define the table structure.
     */
    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'name', 'label' => __('user::ui.manager.table.name'), 'sortable' => true],
            ['key' => 'email', 'label' => __('user::ui.manager.table.email'), 'sortable' => true],
            ['key' => 'registration_number', 'label' => __('user::ui.manager.form.registration_number')],
            ['key' => 'department_name', 'label' => __('user::ui.manager.form.department')],
            ['key' => 'display_status', 'label' => __('user::ui.manager.table.status')],
            ['key' => 'activation_status', 'label' => __('user::ui.manager.table.activation_status')],
            ['key' => 'actions', 'label' => __('ui::common.actions'), 'class' => 'w-1 text-right'],
        ];
    }

    /**
     * Transform raw student record for UI display.
     */
    protected function mapRecord(mixed $record): array
    {
        return array_merge($record->toArray(), [
            'avatar_url' => $record->avatar_url,
            'registration_number' => $record->profile?->registration_number ?? '-',
            'department_name' => $record->profile?->department?->name ?? '-',
            'display_status' => $record->latestStatus()?->name ?? User::STATUS_ACTIVE,
            'activation_status' => $record->setup_required ? 'pending_claim' : 'claimed',
        ]);
    }

    /**
     * Fetch and transform records for the table.
     */
    #[Computed]
    public function records(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->managedStudentQuery($this->filters)
            ->with(['profile.department', 'statuses'])
            ->paginate($this->perPage)
            ->through(fn ($user) => $this->mapRecord($user));
    }

    public function reissueActivationCode(mixed $id): void
    {
        $user = $this->service->find($id);
        if (!$user) return;

        $this->authorize('update', $user);

        try {
            $plainCode = app(AccountProvisioningService::class)->reissue($user, AccountToken::TYPE_ACTIVATION, 30, auth()->user());
            $this->credentialSlips = [['name' => $user->name, 'username' => $user->username, 'code' => $plainCode]];
            $this->credentialSlipsModal = true;
            flash()->success(__('user::ui.manager.credential_slips.code_reissued'));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    public function closeCredentialSlips(): void
    {
        $this->credentialSlipsModal = false;
        $this->credentialSlips = [];
    }

    public function resetFilters(): void
    {
        $this->filters = [];
        $this->selectedIds = [];
        $this->resetPage();
    }

    public function activeFilterCount(): int
    {
        return count(array_filter($this->filters, fn ($v) => $v !== null && $v !== '' && $v !== []));
    }

    public function statusBadgeVariant(string $status): string
    {
        return match ($status) {
            User::STATUS_ACTIVE => 'success',
            User::STATUS_PENDING => 'warning',
            User::STATUS_INACTIVE => 'error',
            default => 'neutral',
        };
    }

    public function activationStatusBadgeVariant(string $status): string
    {
        return match ($status) {
            'pending_claim' => 'warning',
            'claimed' => 'success',
            default => 'neutral',
        };
    }

    public function render(): View
    {
        return view('student::livewire.student-manager');
    }

    protected function managedStudentQuery(array $filters = []): Builder
    {
        $selectedStatus = $filters['status'] ?? null;
        $departmentId = $filters['department_id'] ?? null;
        $createdFrom = $filters['created_from'] ?? null;
        $createdTo = $filters['created_to'] ?? null;

        $query = $this->service->query(Arr::except($filters, ['status', 'department_id', 'created_from', 'created_to']));

        if ($departmentId) {
            $query->whereHas('profile', fn($q) => $q->where('department_id', $departmentId));
        }

        if (in_array($selectedStatus, [User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_PENDING], true)) {
            $this->applyLatestStatusFilter($query, $selectedStatus);
        }

        if ($createdFrom) $query->whereDate('created_at', '>=', $createdFrom);
        if ($createdTo) $query->whereDate('created_at', '<=', $createdTo);

        return $query;
    }

    protected function applyLatestStatusFilter(Builder $query, string $status): void
    {
        $statusTable = app(config('model-status.status_model'))->getTable();
        $userTable = (new User)->getTable();

        $query->whereExists(function ($q) use ($status, $statusTable, $userTable): void {
            $q->selectRaw('1')
                ->from($statusTable.' as latest_status')
                ->whereColumn('latest_status.model_id', $userTable.'.id')
                ->where('latest_status.model_type', User::class)
                ->where('latest_status.name', $status)
                ->whereRaw('latest_status.created_at = (select max(s2.created_at) from '.$statusTable.' as s2 where s2.model_type = ? and s2.model_id = '.$userTable.'.id)', [User::class]);
        });
    }
}
