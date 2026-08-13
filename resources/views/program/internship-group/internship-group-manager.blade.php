<x-core::ui.record-manager
    :title="__('internship.groups')"
    :subtitle="__('internship.groups_subtitle')"
>
    <x-slot:headerActions>
        <x-mary-button :label="__('internship.create_group')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
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
                    <x-mary-button icon="o-users" class="btn-ghost btn-sm" wire:click="manageMembers('{{ $group->id }}')" :aria-label="__('internship.manage_members')" />
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit('{{ $group->id }}')" :aria-label="__('common.actions.edit')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="askDelete('{{ $group->id }}')" :aria-label="__('common.actions.delete')" />
                </div>
            @endscope
        </x-mary-table>
    </div>

    {{-- Confirm Dialog --}}
    <x-core::ui.confirm
        wire:model="showConfirm"
        :message="$confirmMessage"
        confirmText="{{ __('common.actions.confirm') }}"
        cancelText="{{ __('common.actions.cancel') }}"
        confirmClass="btn-error"
    />

    <x-slot:modal>
        {{-- Group Form --}}
        <x-mary-modal wire:model="showModal" :title="$editingId ? __('internship.edit_group') : __('internship.create_group')" separator class="backdrop-blur-sm">
            <x-mary-form wire:submit="save" class="space-y-5">
                <div class="bg-base-200/30 border border-base-content/10 rounded-xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-4">{{ __('internship.identity') }}</p>
                    <x-mary-input :label="__('internship.group_name')" wire:model="form.name" icon="o-user-group" />
                    <x-mary-select :label="__('internship.title')" wire:model="form.internship_id" :options="$this->internships" icon="o-briefcase" class="mt-4" />
                    <x-mary-textarea :label="__('internship.description')" wire:model="form.description" rows="2" icon="o-document-text" class="mt-4" />
                </div>

                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('common.actions.save')" class="btn-primary btn-sm" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>

        {{-- Add Members --}}
        <x-mary-modal wire:model="showMemberModal" :title="__('internship.manage_members')" separator class="backdrop-blur-sm">
            <div class="space-y-4">
                <div class="space-y-3">
                    @foreach ($memberFormData as $index => $memberRow)
                        <div class="bg-base-200/30 border border-base-content/10 rounded-xl p-5 relative">
                            @if (count($memberFormData) > 1)
                                <button type="button" wire:click="removeMemberRow({{ $index }})" class="absolute top-3 right-3 text-error btn btn-ghost btn-sm btn-square" :aria-label="__('internship.remove_member_row')">
                                    <x-mary-icon name="o-x-mark" class="size-4" />
                                </button>
                            @endif
                            <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-4">{{ __('internship.member') }} #{{ $index + 1 }}</p>
                            <x-mary-select :label="__('internship.member_role')" wire:model="memberFormData.{{ $index }}.role" :options="$this->roleOptions" icon="o-user" />
                            <x-mary-input :label="__('internship.registration_id')" wire:model="memberFormData.{{ $index }}.registration_id" :placeholder="__('internship.registration_id_placeholder')" icon="o-document-text" class="mt-4" />
                            <x-mary-input :label="__('internship.mentor_id')" wire:model="memberFormData.{{ $index }}.mentor_id" :placeholder="__('internship.mentor_id_placeholder')" icon="o-identification" class="mt-4" />
                        </div>
                    @endforeach

                    <x-mary-button :label="__('internship.add_member_row')" icon="o-plus" wire:click="addMemberRow" class="btn-ghost btn-sm w-full border border-dashed" />
                </div>

                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showMemberModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('internship.add_members')" wire:click="addMembers" class="btn-primary btn-sm" spinner="addMembers" />
                </x-slot:actions>
            </div>
        </x-mary-modal>
    </x-slot:modal>
    @include('program.internship-group.components.internship-group-guide')
</x-core::ui.record-manager>
