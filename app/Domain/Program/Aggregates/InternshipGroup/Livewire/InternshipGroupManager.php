<?php

declare(strict_types=1);

namespace App\Domain\Program\Aggregates\InternshipGroup\Livewire;

use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\Program\Aggregates\InternshipGroup\Actions\AddMemberToGroupAction;
use App\Domain\Program\Aggregates\InternshipGroup\Actions\CreateInternshipGroupAction;
use App\Domain\Program\Aggregates\InternshipGroup\Actions\DeleteInternshipGroupAction;
use App\Domain\Program\Aggregates\InternshipGroup\Actions\RemoveMemberFromGroupAction;
use App\Domain\Program\Aggregates\InternshipGroup\Actions\UpdateInternshipGroupAction;
use App\Domain\Program\Aggregates\InternshipGroup\Enums\InternshipGroupRole;
use App\Domain\Program\Aggregates\InternshipGroup\Livewire\Forms\InternshipGroupForm;
use App\Domain\Program\Aggregates\Internship\Models\Internship;
use App\Domain\Program\Aggregates\Internship\Models\InternshipGroup;
use App\Domain\Program\Aggregates\Internship\Models\InternshipGroupMember;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;

class InternshipGroupManager extends BaseRecordManager
{
    use AuthorizesRequests;

    public bool $showModal = false;

    public bool $showMemberModal = false;

    public bool $showConfirm = false;

    public string $confirmMessage = '';

    public string $confirmType = '';

    public ?string $confirmTarget = null;

    public ?string $editingId = null;

    public ?string $internshipId = null;

    public InternshipGroupForm $form;

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
            ['key' => 'name', 'label' => __('internship.group_name'), 'sortable' => true],
            ['key' => 'internship', 'label' => __('internship.title'), 'sortable' => false],
            ['key' => 'member_count', 'label' => __('internship.members'), 'sortable' => false],
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
        $this->form->reset();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $group = InternshipGroup::findOrFail($id);

        $this->resetErrorBag();
        $this->editingId = $group->id;
        $this->form->fill([
            'name' => $group->name,
            'internship_id' => $group->internship_id,
            'placement_id' => $group->placement_id ?? '',
            'description' => $group->description ?? '',
        ]);
        $this->showModal = true;
    }

    public function save(CreateInternshipGroupAction $create, UpdateInternshipGroupAction $update): void
    {
        $this->form->validate();

        if ($this->editingId) {
            $group = InternshipGroup::findOrFail($this->editingId);
            $update->execute($group, $this->form->all());
            flash()->success(__('internship.group_updated'));
        } else {
            $create->execute($this->form->all());
            flash()->success(__('internship.group_created'));
        }

        $this->showModal = false;
        $this->editingId = null;
    }

    // --- Delete ---

    public function askDelete(string $id): void
    {
        $group = InternshipGroup::findOrFail($id);

        $this->confirmTarget = $id;
        $this->confirmType = 'delete';
        $this->confirmMessage = __('internship.confirm_delete_group', ['name' => $group->name]);
        $this->showConfirm = true;
    }

    public function confirmAction(DeleteInternshipGroupAction $deleteAction): void
    {
        if ($this->confirmTarget === null) {
            return;
        }

        try {
            $group = InternshipGroup::findOrFail($this->confirmTarget);
            $this->authorize('delete', $group);
            $deleteAction->execute($group);
            flash()->success(__('internship.group_deleted'));
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
        $allowedRoles = implode(',', array_map(fn ($r) => $r->value, InternshipGroupRole::cases()));

        $this->validate([
            'memberFormData.role' => ['required', "in:{$allowedRoles}"],
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

        flash()->success(__('internship.member_added'));
    }

    public function removeMember(string $memberId, RemoveMemberFromGroupAction $action): void
    {
        $member = InternshipGroupMember::findOrFail($memberId);
        $action->execute($member);

        flash()->success(__('internship.member_removed'));
    }

    // ---

    #[Computed]
    public function internships(): array
    {
        return Internship::pluck('name', 'id')->toArray();
    }

    #[Computed]
    public function roleOptions(): array
    {
        return collect(InternshipGroupRole::cases())
            ->map(fn ($r) => ['id' => $r->value, 'name' => $r->label()])
            ->toArray();
    }

    public function render(): View
    {
        return view('internship.internship-group-manager');
    }
}
