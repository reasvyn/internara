<?php

declare(strict_types=1);

namespace Modules\Student\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Modules\Department\Livewire\Concerns\HasDepartmentOptions;
use Modules\Exception\Concerns\HandlesAppException;
use Modules\Student\Livewire\Forms\StudentForm;
use Modules\Student\Services\Contracts\StudentService;
use Modules\UI\Livewire\RecordManager;
use Modules\User\Models\User;

/**
 * Class StudentManager
 *
 * Manages student users with specialized logic and role enforcement.
 */
class StudentManager extends RecordManager
{
    use HandlesAppException;
    use HasDepartmentOptions;

    public StudentForm $form;

    /**
     * Initialize the component.
     */
    public function boot(StudentService $studentService): void
    {
        $this->service = $studentService;
        $this->eventPrefix = 'student';
    }

    public function initialize(): void
    {
        $this->title = __('admin::ui.menu.students');
        $this->subtitle = __('user::ui.manager.subtitle');
        $this->addLabel = __('user::ui.manager.add_student');
        $this->deleteConfirmMessage = __('user::ui.manager.delete.message');
        $this->viewPermission = 'student.manage';
        $this->createPermission = 'student.manage';
        $this->updatePermission = 'student.manage';
        $this->deletePermission = 'student.manage';
        $this->modelClass = User::class;
    }

    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'name', 'label' => __('ui::common.name'), 'sortable' => true],
            ['key' => 'email', 'label' => __('ui::common.email'), 'sortable' => false],
            ['key' => 'created_at', 'label' => __('ui::common.created_at'), 'sortable' => true],
        ];
    }

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        parent::mount();
    }

    /**
     * Get records property for the table.
     */
    #[Computed]
    public function records(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->service
            ->paginate([
                'search' => $this->search,
                'sort_by' => $this->sortBy['column'] ?? 'created_at',
                'sort_dir' => $this->sortBy['direction'] ?? 'desc',
            ], $this->perPage, ['*'], ['roles:id,name', 'profile.department', 'statuses']);
    }

    /**
     * Generate a random password for the student.
     */
    public function generatePassword(): void
    {
        $this->form->generatePassword();
    }

    /**
     * Open form for adding a new student.
     */
    public function add(): void
    {
        $this->form->reset();
        $this->formModal = true;
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
            $this->formModal = true;
        }
    }

    /**
     * Save the student record.
     */
    public function save(): void
    {
        $this->form->validate();

        try {
            if ($this->form->id) {
                $this->service->update($this->form->id, $this->form->all());
            } else {
                $this->service->create($this->form->all());
            }

            $this->formModal = false;
            flash()->success(__('shared::messages.record_saved'));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
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
}
