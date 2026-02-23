<?php

declare(strict_types=1);

namespace Modules\Teacher\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Exception\Concerns\HandlesAppException;
use Modules\Shared\Livewire\Concerns\ManagesRecords;
use Modules\User\Livewire\Forms\UserForm;
use Modules\Teacher\Services\Contracts\TeacherService;

/**
 * Class TeacherManager
 * 
 * Manages academic teachers with specialized logic and role enforcement.
 */
class TeacherManager extends Component
{
    use \Modules\User\Livewire\Concerns\InteractsWithDepartments;
    use HandlesAppException;
    use ManagesRecords;

    public UserForm $form;

    /**
     * Initialize the component.
     */
    public function boot(TeacherService $teacherService): void
    {
        $this->service = $teacherService;
        $this->eventPrefix = 'teacher';
    }

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('teacher.manage');
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
     * Open form for adding a new teacher.
     */
    public function add(): void
    {
        $this->form->reset();
        $this->form->roles = ['teacher'];
        $this->formModal = true;
    }

    /**
     * Open form for editing a teacher.
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
     * Save the teacher record.
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
                $this->authorize('create', [User::class, ['teacher']]);
                $this->service->create($this->form->all());
            }

            $this->formModal = false;
            flash()->success(__('shared::messages.record_saved'));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    /**
     * Render the teacher manager view.
     */
    public function render(): View
    {
        $title = __('admin::ui.menu.teachers');

        return view('teacher::livewire.teacher-manager', [
            'title' => $title,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => $title . ' | ' . setting('brand_name', setting('app_name')),
            'context' => 'admin::ui.menu.teachers',
        ]);
    }
}
