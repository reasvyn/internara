<x-core::ui.record-manager :title="__('internship.groups')" :subtitle="__('internship.groups_subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('internship.create_group')" icon="plus" color="primary" sm wire:click="create" />
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
                    <x-ts-button
                        icon="users"
                        color="white"
                        sm
                        wire:click="manageMembers('{{ $group->id }}')"
                        :aria-label="__('internship.manage_members')"
                    />
                    <x-ts-button
                        icon="pencil"
                        color="white"
                        sm
                        wire:click="edit('{{ $group->id }}')"
                        :aria-label="__('common.actions.edit')"
                    />
                    <x-ts-button
                        icon="trash"
                        class="text-error"
                        color="white"
                        sm
                        wire:click="askDelete('{{ $group->id }}')"
                        :aria-label="__('common.actions.delete')"
                    />
                </div>
            @endscope
        </x-mary-table>
    </div>

    {{-- Confirm Dialog --}}
    <x-slot:modal>
        {{-- Group Form --}}
        <x-ts-modal
            wire="showModal"
            :title="$editingId ? __('internship.edit_group') : __('internship.create_group')"
            separator
            blur
        >
            <form wire:submit="save" class="space-y-5">
                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('internship.identity') }}
                    </p>
                    <x-ts-input :label="__('internship.group_name')" wire:model="form.name" icon="user-group" />
                    <x-mary-select
                        :label="__('internship.title')"
                        wire:model="form.internship_id"
                        :options="$this->internships"
                        icon="briefcase"
                        class="mt-4"
                    />
                    <x-ts-textarea
                        :label="__('internship.description')"
                        wire:model="form.description"
                        rows="2"
                        class="mt-4"
                    />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showModal', false)"
                        color="white"
                        sm
                    />
                    <x-ts-button :text="__('common.actions.save')" color="primary" sm type="submit" loading="save" />
                </div>
            </form>
        </x-ts-modal>

        {{-- Add Members --}}
        <x-ts-modal wire="showMemberModal" :title="__('internship.manage_members')" separator blur>
            <div class="space-y-4">
                <div class="space-y-3">
                    @foreach ($memberFormData as $index => $memberRow)
                        <div class="bg-base-200/30 border-base-content/10 relative rounded-xl border p-5">
                            @if (count($memberFormData) > 1)
                                <button
                                    type="button"
                                    wire:click="removeMemberRow({{ $index }})"
                                    class="text-error btn btn-ghost btn-sm btn-square absolute top-3 right-3"
                                    :aria-label="__('internship.remove_member_row')"
                                >
                                    <x-ts-icon name="x-mark" class="size-4" />
                                </button>
                            @endif
                            <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                                {{ __('internship.member') }} #{{ $index + 1 }}
                            </p>
                            <x-mary-select
                                :label="__('internship.member_role')"
                                wire:model="memberFormData.{{ $index }}.role"
                                :options="$this->roleOptions"
                                icon="user"
                            />
                            <x-ts-input
                                :label="__('internship.registration_id')"
                                wire:model="memberFormData.{{ $index }}.registration_id"
                                :placeholder="__('internship.registration_id_placeholder')"
                                icon="document-text"
                                class="mt-4"
                            />
                            <x-ts-input
                                :label="__('internship.mentor_id')"
                                wire:model="memberFormData.{{ $index }}.mentor_id"
                                :placeholder="__('internship.mentor_id_placeholder')"
                                icon="identification"
                                class="mt-4"
                            />
                        </div>
                    @endforeach

                    <x-ts-button
                        :text="__('internship.add_member_row')"
                        icon="plus"
                        wire:click="addMemberRow"
                        class="w-full border border-dashed"
                        color="white"
                        sm
                    />
                </div>

                <x-slot:footer>
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showMemberModal', false)"
                        color="white"
                        sm
                    />
                    <x-ts-button
                        :text="__('internship.add_members')"
                        wire:click="addMembers"
                        color="primary"
                        sm
                        loading="addMembers"
                    />
                </x-slot:footer>
            </div>
        </x-ts-modal>
    </x-slot:modal>
    @include('program.internship-group.components.internship-group-guide')
</x-core::ui.record-manager>
