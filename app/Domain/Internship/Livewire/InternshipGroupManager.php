<?php

declare(strict_types=1);

namespace App\Domain\Internship\Livewire;

use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\Internship\Actions\AddMemberToGroupAction;
use App\Domain\Internship\Actions\CreateInternshipGroupAction;
use App\Domain\Internship\Actions\DeleteInternshipGroupAction;
use App\Domain\Internship\Actions\RemoveMemberFromGroupAction;
use App\Domain\Internship\Actions\UpdateInternshipGroupAction;
use App\Domain\Internship\Models\Internship;
use App\Domain\Internship\Models\InternshipGroup;
use App\Domain\Internship\Models\InternshipGroupMember;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class InternshipGroupManager extends BaseRecordManager
{
    public bool $showModal = false;

    public bool $showMemberModal = false;

    public bool $showConfirm = false;

    public string $confirmMessage = '';

    public string $confirmType = '';

    public ?string $confirmTarget = null;

    public ?string $internshipId = null;

    public array $formData = [
        'name' => '',
        'internship_id' => '',
        'placement_id' => '',
        'description' => '',
    ];

    public array $memberFormData = [
        'group_id' => '',
        'role' => 'student',
        'registration_id' => '',
        'mentor_id' => '',
    ];

    public function boot(): void
    {
        $this->authorize('viewAny', InternshipGroup::class);
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
            ['key' => 'internship', 'label' => 'Internship', 'sortable' => false],
            ['key' => 'member_count', 'label' => 'Members', 'sortable' => false],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return InternshipGroup::query()->withCount('members');
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where('name', 'like', "%{$this->search}%");
    }

    // --- Group CRUD ---

    public function create(): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'name' => '',
            'internship_id' => '',
            'placement_id' => '',
            'description' => '',
        ];
        $this->showModal = true;
    }

    public function edit(InternshipGroup $group): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'name' => $group->name,
            'internship_id' => $group->internship_id,
            'placement_id' => $group->placement_id ?? '',
            'description' => $group->description ?? '',
        ];
        $this->confirmTarget = $group->id;
        $this->showModal = true;
    }

    public function save(CreateInternshipGroupAction $create, UpdateInternshipGroupAction $update): void
    {
        $this->validate([
            'formData.name' => ['required', 'string', 'max:255'],
            'formData.internship_id' => ['required', 'exists:internships,id'],
            'formData.placement_id' => ['nullable', 'exists:placements,id'],
        ]);

        if ($this->confirmTarget) {
            $group = InternshipGroup::findOrFail($this->confirmTarget);
            $update->execute($group, $this->formData);
            flash()->success('Group updated.');
        } else {
            $create->execute($this->formData);
            flash()->success('Group created.');
        }

        $this->showModal = false;
        $this->confirmTarget = null;
    }

    // --- Delete ---

    public function askDelete(string $id): void
    {
        $group = InternshipGroup::findOrFail($id);

        $this->confirmTarget = $id;
        $this->confirmType = 'delete';
        $this->confirmMessage = __('Delete :name?', ['name' => $group->name]);
        $this->showConfirm = true;
    }

    public function confirmAction(DeleteInternshipGroupAction $deleteAction): void
    {
        if ($this->confirmTarget === null) {
            return;
        }

        try {
            $group = InternshipGroup::findOrFail($this->confirmTarget);
            $deleteAction->execute($group);
            flash()->success('Group deleted.');
        } catch (\RuntimeException $e) {
            flash()->error($e->getMessage());
        }

        $this->showConfirm = false;
        $this->confirmTarget = null;
        $this->confirmType = '';
    }

    // --- Members ---

    public function manageMembers(string $groupId): void
    {
        $this->memberFormData = [
            'group_id' => $groupId,
            'role' => 'student',
            'registration_id' => '',
            'mentor_id' => '',
        ];
        $this->showMemberModal = true;
    }

    public function addMember(AddMemberToGroupAction $action): void
    {
        $this->validate([
            'memberFormData.role' => ['required', 'in:student,school_teacher,industry_supervisor'],
            'memberFormData.registration_id' => ['required_if:memberFormData.role,student', 'nullable', 'exists:registrations,id'],
            'memberFormData.mentor_id' => ['required_if:memberFormData.role,school_teacher,industry_supervisor', 'nullable', 'exists:mentors,id'],
        ]);

        $group = InternshipGroup::findOrFail($this->memberFormData['group_id']);

        $action->execute($group, [
            'registration_id' => $this->memberFormData['registration_id'] ?: null,
            'mentor_id' => $this->memberFormData['mentor_id'] ?: null,
            'role' => $this->memberFormData['role'],
        ]);

        $this->memberFormData = [
            'group_id' => $this->memberFormData['group_id'],
            'role' => 'student',
            'registration_id' => '',
            'mentor_id' => '',
        ];

        flash()->success('Member added.');
    }

    public function removeMember(string $memberId, RemoveMemberFromGroupAction $action): void
    {
        $member = InternshipGroupMember::findOrFail($memberId);
        $action->execute($member);

        flash()->success('Member removed.');
    }

    // ---

    public function internships(): array
    {
        return Internship::pluck('name', 'id')->toArray();
    }

    public function render(): View
    {
        return view('internship.internship-group-manager', [
            'internships' => $this->internships(),
        ]);
    }
}
