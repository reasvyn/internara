<?php

declare(strict_types=1);

namespace App\Modules\Program\Domain\InternshipGroup\Livewire;

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Core\Livewire\BaseRecordManager;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\Program\Domain\InternshipGroup\Actions\AddMembersToGroupAction;
use App\Modules\Program\Domain\InternshipGroup\Actions\CreateInternshipGroupAction;
use App\Modules\Program\Domain\InternshipGroup\Actions\DeleteInternshipGroupAction;
use App\Modules\Program\Domain\InternshipGroup\Actions\RemoveMemberFromGroupAction;
use App\Modules\Program\Domain\InternshipGroup\Actions\UpdateInternshipGroupAction;
use App\Modules\Program\Domain\InternshipGroup\Enums\InternshipGroupRole;
use App\Modules\Program\Domain\InternshipGroup\Livewire\Forms\InternshipGroupForm;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use TallStackUi\Traits\Interactions;

class InternshipGroupManager extends BaseRecordManager
{
    use AuthorizesRequests;
    use Interactions;

    public bool $showModal = false;

    public bool $showMemberModal = false;

    public string $confirmMessage = '';

    public string $confirmType = '';

    public ?string $confirmTarget = null;

    public ?string $editingId = null;

    public ?string $internshipId = null;

    public InternshipGroupForm $form;

    /** @var array<int, array{role: string, registration_id: string, mentor_id: string}> */
    public array $memberFormData = [];

    public function boot(): void
    {
        $this->authorize('viewAny', InternshipGroup::class);
    }

    public function headers(): array
    {
        return [
            ['index' => 'name', 'label' => __('internship.group_name'), 'sortable' => true],
            ['index' => 'internship', 'label' => __('internship.title'), 'sortable' => false],
            ['index' => 'member_count', 'label' => __('internship.members'), 'sortable' => false],
            ['index' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return InternshipGroup::query()->with('internship')->withCount('members');
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where('name', 'like', "%{$this->search}%");
    }

    // --- Group CRUD ---

    public function create(): void
    {
        $this->authorize('create', InternshipGroup::class);

        $this->resetErrorBag();
        $this->form->reset();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $group = InternshipGroup::findOrFail($id);
        $this->authorize('update', $group);

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

    public function save(
        CreateInternshipGroupAction $create,
        UpdateInternshipGroupAction $update,
    ): void {
        $this->form->validate();

        if ($this->editingId) {
            $group = InternshipGroup::findOrFail($this->editingId);
            $this->authorize('update', $group);
            $update->execute($group, $this->form->all());
            $this->toast()->success(__('internship.group_updated'))->send();
        } else {
            $this->authorize('create', InternshipGroup::class);
            $create->execute($this->form->all());
            $this->toast()->success(__('internship.group_created'))->send();
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
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
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
            $this->toast()->success(__('internship.group_deleted'))->send();
        } catch (RejectedException|\RuntimeException $e) {
            $this->toast()->error($e->getMessage())->send();
        }
        $this->confirmTarget = null;
        $this->confirmType = '';
    }

    // --- Members ---

    public ?string $memberGroupId = null;

    /** @return array{role: string, registration_id: string, mentor_id: string} */
    private function emptyMemberRow(): array
    {
        return [
            'role' => 'student',
            'registration_id' => '',
            'mentor_id' => '',
        ];
    }

    public function manageMembers(string $groupId): void
    {
        $this->memberGroupId = $groupId;
        $this->resetMemberForm();
        $this->showMemberModal = true;
    }

    public function resetMemberForm(): void
    {
        $this->memberFormData = [$this->emptyMemberRow()];
        $this->resetErrorBag();
    }

    public function addMemberRow(): void
    {
        $this->memberFormData[] = $this->emptyMemberRow();
    }

    public function removeMemberRow(int $index): void
    {
        if (isset($this->memberFormData[$index])) {
            unset($this->memberFormData[$index]);
            $this->memberFormData = array_values($this->memberFormData);
        }
    }

    public function addMembers(AddMembersToGroupAction $action): void
    {
        $allowedRoles = implode(',', array_map(fn ($r) => $r->value, InternshipGroupRole::cases()));

        $this->validate([
            'memberFormData' => ['required', 'array', 'min:1'],
            'memberFormData.*.role' => ['required', "in:{$allowedRoles}"],
            'memberFormData.*.registration_id' => [
                'required_if:memberFormData.*.role,student',
                'nullable',
                'exists:registrations,id',
            ],
            'memberFormData.*.mentor_id' => [
                'required_if:memberFormData.*.role,school_teacher,industry_supervisor',
                'nullable',
                'exists:users,id',
            ],
        ]);

        $group = InternshipGroup::findOrFail($this->memberGroupId);
        $this->authorize('update', $group);

        $action->execute($group, $this->memberFormData);

        $this->showMemberModal = false;
        $this->memberGroupId = null;

        $this->toast()->success(__('internship.members_added', ['count' => count($this->memberFormData)]))->send();
    }

    public function removeMember(string $memberId, RemoveMemberFromGroupAction $action): void
    {
        $member = InternshipGroupMember::findOrFail($memberId);
        $group = $member->group;
        $this->authorize('update', $group);
        $action->execute($member);

        $this->toast()->success(__('internship.member_removed'))->send();
    }

    // ---

    #[Computed]
    public function internships(): array
    {
        return Internship::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($internship) => ['id' => $internship->id, 'name' => $internship->name])
            ->toArray();
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
        return view('program.internship-group.internship-group-manager');
    }
}
