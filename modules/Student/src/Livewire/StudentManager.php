<?php

declare(strict_types=1);

namespace Modules\Student\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Exception\Concerns\HandlesAppException;
use Modules\UI\Livewire\Concerns\ManagesRecords;
use Modules\User\Livewire\Forms\UserForm;
use Modules\Student\Services\Contracts\StudentService;

/**
 * Class StudentManager
 * 
 * Manages student users with specialized logic and role enforcement.
 */
class StudentManager extends Component
{
    use \Modules\User\Livewire\Concerns\InteractsWithDepartments;
    use HandlesAppException;
    use ManagesRecords;

    public UserForm $form;

    /**
     * Initialize the component.
     */
    public function boot(StudentService $studentService): void
    {
        $this->service = $studentService;
        $this->eventPrefix = 'student';
    }

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('student.manage');
    }

    /**
     * Get records property for the table.
     */
    public function getRecordsProperty(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->service
            ->query([
                'search' => $this->search,
                'sort_by' => $this->sortBy,
                'sort_dir' => $this->sortDir,
            ])
            ->with(['roles:id,name', 'profile'])
            ->paginate($this->perPage);
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
    public function edit(string $id): void
    {
        $user = $this->service->find($id);

        if ($user) {
            $this->authorize('update', $user);
            $this->form->setUser($user);
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
                $user = $this->service->find($this->form->id);
                if ($user) {
                    $this->authorize('update', $user);
                }
                $this->service->update($this->form->id, $this->form->all());
            } else {
                $this->authorize('create', [User::class, ['student']]);
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
        $title = __('admin::ui.menu.students');

        return view('student::livewire.student-manager', [
            'title' => $title,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => $title . ' | ' . setting('brand_name', setting('app_name')),
            'context' => 'admin::ui.menu.students',
        ]);
    }
}
