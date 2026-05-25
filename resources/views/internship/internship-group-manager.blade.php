<x-shared::ui.record-manager
    title="Internship Groups"
    subtitle="Manage student groups with school teachers and industry supervisors"
>
    <x-slot:headerActions>
        <x-mary-button label="New Group" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <div class="overflow-x-auto">
        <x-mary-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            selectable
            wire:model="selectedIds"
            class="table-sm"
        >
            @scope('cell_internship', $group)
                <span class="text-sm">{{ $group->internship?->name ?? '—' }}</span>
            @endscope

            @scope('cell_member_count', $group)
                <span class="text-sm">{{ $group->members_count }}</span>
            @endscope

            @scope('actions', $group)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-users" class="btn-ghost btn-sm" wire:click="manageMembers('{{ $group->id }}')" aria-label="Manage members" />
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit('{{ $group->id }}')" aria-label="Edit" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="askDelete('{{ $group->id }}')" aria-label="Delete" />
                </div>
            @endscope
        </x-mary-table>
    </div>

    {{-- Confirm Dialog --}}
    <x-shared::ui.confirm
        wire:model="showConfirm"
        :message="$confirmMessage"
        confirmText="Confirm"
        cancelText="Cancel"
        confirmClass="btn-error"
    />

    <x-slot:modal>
        {{-- Group Form --}}
        <x-mary-modal wire:model="showModal" :title="$confirmTarget ? 'Edit Group' : 'New Group'" class="backdrop-blur-sm">
            <x-mary-form wire:submit="save">
                <div class="space-y-5">
                    <x-mary-input label="Name" wire:model="form.name" required />
                    <x-mary-select label="Internship" wire:model="form.internship_id" :options="$internships" required />
                    <x-mary-textarea label="Description" wire:model="form.description" rows="2" />
                </div>
                <x-slot:actions>
                    <x-mary-button label="Cancel" wire:click="$set('showModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button label="Save" class="btn-primary btn-sm" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>

        {{-- Add Member --}}
        <x-mary-modal wire:model="showMemberModal" title="Manage Members" class="backdrop-blur-sm">
            <div class="space-y-4">
                <x-mary-select label="Role" wire:model="memberFormData.role">
                    <option value="student">Student</option>
                    <option value="school_teacher">School Teacher</option>
                    <option value="industry_supervisor">Industry Supervisor</option>
                </x-mary-select>
                <x-mary-input label="Registration ID" wire:model="memberFormData.registration_id" placeholder="For students" />
                <x-mary-input label="Mentor ID" wire:model="memberFormData.mentor_id" placeholder="For teachers/supervisors" />
                <x-mary-button label="Add Member" wire:click="addMember" class="btn-primary btn-sm" spinner="addMember" />
            </div>
        </x-mary-modal>
    </x-slot:modal>
</x-shared::ui.record-manager>
