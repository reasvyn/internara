<x-ui::components.record-manager :title="__('internship.groups')" :subtitle="__('internship.groups_subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('internship.create_group')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <div class="overflow-x-auto">
        <x-ts-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            selectable
            wire:model.live="selectedIds"
            class="table-sm"
        >
            @interact('column_internship', $group)
                <span class="text-sm">{{ $group->internship?->name ?? '—' }}</span>
            @endinteract

            @interact('column_member_count', $group)
                <span class="text-sm">{{ $group->members_count }}</span>
            @endinteract

            @interact('column_action', $group)
                <x-ui::components.table-actions>
                    <x-ts-button.circle
                        icon="users"
                        color="blue"
                        outline
                        xs
                        wire:click="manageMembers('{{ $group->id }}')"
                        :aria-label="__('internship.manage_members')"
                        tooltip="{{ __('internship.manage_members') }}"
                    />
                    <x-ts-button.circle
                        icon="pencil"
                        color="primary"
                        outline
                        xs
                        wire:click="edit('{{ $group->id }}')"
                        :aria-label="__('common.actions.edit')"
                        tooltip="{{ __('common.actions.edit') }}"
                    />
                    <x-ts-button.circle
                        icon="trash"
                        color="red"
                        outline
                        xs
                        wire:click="askDelete('{{ $group->id }}')"
                        :aria-label="__('common.actions.delete')"
                        tooltip="{{ __('common.actions.delete') }}"
                    />
                </x-ui::components.table-actions>
            @endinteract
        </x-ts-table>
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
                    <x-ts-select.native
                        :label="__('internship.title')"
                        wire:model="form.internship_id"
                        :options="ts_options($this->internships)"
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
                        color="slate"
                        outline
                        sm
                    />
                    <x-ts-button :text="__('common.actions.save')" color="primary" sm type="submit" loading="save" />
                </div>
            </form>
        </x-ts-modal>

        {{-- Manage Members Modal --}}
        <x-ts-modal
            wire="showMemberModal"
            :title="__('internship.manage_members').($this->currentGroup ? ' — '.$this->currentGroup->name : '')"
            separator
            blur
            size="2xl"
        >
            <div class="space-y-6">
                @if ($this->currentGroup)
                    {{-- Section 1: Current Members --}}
                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <h4 class="text-base-content/70 text-xs font-semibold tracking-wider uppercase">
                                {{ __('internship.current_members') }}
                                <span class="badge badge-sm badge-neutral ml-1">{{ $this->currentGroup->members->count() }}</span>
                            </h4>
                        </div>

                        @if ($this->currentGroup->members->isEmpty())
                            <div class="border-base-content/20 text-base-content/60 rounded-xl border border-dashed p-6 text-center text-xs">
                                {{ __('internship.no_members') }}
                            </div>
                        @else
                            <div class="border-base-content/10 bg-base-200/20 overflow-x-auto rounded-xl border">
                                <table class="table-sm table w-full">
                                    <thead>
                                        <tr class="border-base-content/10 text-base-content/60 border-b text-xs">
                                            <th>{{ __('internship.member') }}</th>
                                            <th>{{ __('internship.member_role') }}</th>
                                            <th>{{ __('internship.joined_at') }}</th>
                                            @can('update', $this->currentGroup)
                                                <th class="text-center">{{ __('common.actions.title') }}</th>
                                            @endcan
                                        </tr>
                                    </thead>
                                    <tbody class="divide-base-content/5 divide-y">
                                        @foreach ($this->currentGroup->members as $member)
                                            <tr>
                                                <td>
                                                    <div class="text-xs font-medium">
                                                        @if ($member->registration?->student)
                                                            {{ $member->registration->student->name }}
                                                            <span class="text-base-content/50 block text-[11px] font-normal">
                                                                {{ $member->registration->student->email }}
                                                            </span>
                                                        @elseif ($member->user)
                                                            {{ $member->user->name }}
                                                            <span class="text-base-content/50 block text-[11px] font-normal">
                                                                {{ $member->user->email }}
                                                            </span>
                                                        @else
                                                            <span class="text-base-content/40 italic">{{ __('internship.unassigned_member') }}</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    @php
                                                        $roleEnum = \App\Modules\Program\Domain\InternshipGroup\Enums\InternshipGroupRole::tryFrom($member->role ?? '');
                                                        $badgeColor = match ($member->role) {
                                                            'student' => 'primary',
                                                            'school_teacher' => 'purple',
                                                            'industry_supervisor' => 'teal',
                                                            default => 'gray',
                                                        };
                                                    @endphp
                                                    <x-ts-badge
                                                        :text="$roleEnum?->label() ?? $member->role"
                                                        :color="$badgeColor"
                                                        light
                                                        round
                                                        xs
                                                    />
                                                </td>
                                                <td class="text-base-content/60 text-xs whitespace-nowrap">
                                                    {{ $member->joined_at?->format('d M Y') ?? '—' }}
                                                </td>
                                                @can('update', $this->currentGroup)
                                                    <td class="text-center">
                                                        <x-ts-button.circle
                                                            icon="trash"
                                                            color="red"
                                                            xs
                                                            wire:click="removeMember('{{ $member->id }}')"
                                                            wire:confirm="{{ __('internship.confirm_remove_member') }}"
                                                            :aria-label="__('common.actions.delete')"
                                                        />
                                                    </td>
                                                @endcan
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- Section 2: Add Members Form --}}
                    @can('update', $this->currentGroup)
                        <div class="border-base-content/10 border-t pt-4">
                            <h4 class="text-base-content/70 mb-3 text-xs font-semibold tracking-wider uppercase">
                                {{ __('internship.add_members') }}
                            </h4>

                            <div class="space-y-3">
                                @foreach ($memberFormData as $index => $memberRow)
                                    <div class="bg-base-200/30 border-base-content/10 relative rounded-xl border p-4">
                                        @if (count($memberFormData) > 1)
                                            <x-ts-button.circle
                                                type="button"
                                                wire:click="removeMemberRow({{ $index }})"
                                                icon="x-mark"
                                                color="red"
                                                flat
                                                xs
                                                class="absolute top-3 right-3"
                                                aria-label="{{ __('internship.remove_member_row') }}"
                                            />
                                        @endif
                                        <p class="text-base-content/50 mb-3 text-xs font-semibold tracking-wider uppercase">
                                            {{ __('internship.member') }} #{{ $index + 1 }}
                                        </p>

                                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                            <x-ts-select.native
                                                :label="__('internship.member_role')"
                                                wire:model.live="memberFormData.{{ $index }}.role"
                                                :options="ts_options($this->roleOptions)"
                                                icon="user"
                                            />

                                            @if (($memberRow['role'] ?? 'student') === 'student')
                                                @if (! empty($this->availableRegistrations))
                                                    <x-ts-select.native
                                                        :label="__('internship.select_student')"
                                                        wire:model="memberFormData.{{ $index }}.registration_id"
                                                        :options="ts_options($this->availableRegistrations, __('internship.select_student'))"
                                                        icon="academic-cap"
                                                    />
                                                @else
                                                    <x-ts-input
                                                        :label="__('internship.registration_id')"
                                                        wire:model="memberFormData.{{ $index }}.registration_id"
                                                        :placeholder="__('internship.registration_id_placeholder')"
                                                        icon="document-text"
                                                    />
                                                @endif
                                            @else
                                                @if (! empty($this->availableMentors))
                                                    <x-ts-select.native
                                                        :label="__('internship.select_mentor')"
                                                        wire:model="memberFormData.{{ $index }}.mentor_id"
                                                        :options="ts_options($this->availableMentors, __('internship.select_mentor'))"
                                                        icon="identification"
                                                    />
                                                @else
                                                    <x-ts-input
                                                        :label="__('internship.mentor_id')"
                                                        wire:model="memberFormData.{{ $index }}.mentor_id"
                                                        :placeholder="__('internship.mentor_id_placeholder')"
                                                        icon="identification"
                                                    />
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                                <x-ts-button
                                    :text="__('internship.add_member_row')"
                                    icon="plus"
                                    wire:click="addMemberRow"
                                    class="w-full border border-dashed"
                                    color="slate"
                                    outline
                                    xs
                                />
                            </div>
                        </div>
                    @endcan
                @endif

                <x-slot:footer>
                    <x-ts-button
                        :text="__('common.actions.close')"
                        wire:click="$set('showMemberModal', false)"
                        color="slate"
                        outline
                        sm
                    />
                    @if ($this->currentGroup && auth()->user()?->can('update', $this->currentGroup))
                        <x-ts-button
                            :text="__('internship.add_members')"
                            wire:click="addMembers"
                            color="primary"
                            sm
                            loading="addMembers"
                        />
                    @endif
                </x-slot:footer>
            </div>
        </x-ts-modal>
    </x-slot:modal>
    @include('program.internship-group.components.internship-group-guide')
</x-ui::components.record-manager>
