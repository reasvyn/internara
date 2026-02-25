<?php

declare(strict_types=1);

namespace Modules\Assignment\Livewire;

use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Assignment\Services\Contracts\AssignmentTypeService;

/**
 * Class AssignmentTypeManager
 *
 * Allows administrators to manage dynamic assignment categories/types.
 */
class AssignmentTypeManager extends Component
{
    use WithPagination;

    public bool $formModal = false;

    public ?string $recordId = null;

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|string|max:100')]
    public string $slug = '';

    #[Validate('required|string|max:50')]
    public string $group = 'report';

    #[Validate('nullable|string|max:255')]
    public string $description = '';

    /**
     * Updated hook for name to auto-generate slug.
     */
    public function updatedName(string $value): void
    {
        if (! $this->recordId) {
            $this->slug = Str::slug($value);
        }
    }

    /**
     * Reset the form fields.
     */
    public function resetForm(): void
    {
        $this->reset(['recordId', 'name', 'slug', 'group', 'description']);
    }

    /**
     * Show the form to create a new assignment type.
     */
    public function add(): void
    {
        $this->resetForm();
        $this->formModal = true;
    }

    /**
     * Show the form to edit an existing assignment type.
     */
    public function edit(string $id, AssignmentTypeService $service): void
    {
        $type = $service->find($id);

        if (! $type) {
            return;
        }

        $this->recordId = $id;
        $this->name = $type->name;
        $this->slug = $type->slug;
        $this->group = $type->group;
        $this->description = $type->description ?? '';

        $this->formModal = true;
    }

    /**
     * Save the assignment type record.
     */
    public function save(AssignmentTypeService $service): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'group' => $this->group,
            'description' => $this->description,
        ];

        if ($this->recordId) {
            $service->update($this->recordId, $data);
            flash()->success(__('assignment::ui.type_updated'));
        } else {
            $service->create($data);
            flash()->success(__('assignment::ui.type_created'));
        }

        $this->formModal = false;
    }

    /**
     * Delete an assignment type.
     */
    public function remove(string $id, AssignmentTypeService $service): void
    {
        $service->delete($id);
        flash()->success(__('assignment::ui.type_deleted'));
    }

    /**
     * Render the component.
     */
    public function render(AssignmentTypeService $service)
    {
        return view('assignment::livewire.assignment-type-manager', [
            'types' => $service->paginate([], 10),
            'groups' => [
                'report' => __('Report'),
                'presentation' => __('Presentation'),
                'certification' => __('Certification'),
                'other' => __('Other'),
            ],
        ])->layout('ui::components.layouts.dashboard', [
            'title' => __('assignment::ui.menu.assignment_types').
                ' | '.
                setting('brand_name', setting('app_name')),
        ]);
    }
}
