<x-ui::record-manager
    :title="__('briefing.title')"
    :subtitle="__('briefing.subtitle')"
>
    <x-slot:headerActions>
        <x-mary-button :label="__('briefing.add')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <div class="overflow-x-auto">
        <x-mary-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            class="table-sm"
        >
            @scope('cell_date', $b)
                <span class="text-sm">{{ $b->date?->format('d M Y H:i') ?? '—' }}</span>
            @endscope

            @scope('cell_is_mandatory', $b)
                <x-mary-badge :value="$b->is_mandatory ? __('briefing.mandatory') : __('briefing.optional')"
                    :class="$b->is_mandatory ? 'badge-warning' : 'badge-ghost'" />
            @endscope

            @scope('actions', $b)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-user-group" class="btn-ghost btn-sm"
                        wire:click="manageAttendance('{{ $b->id }}')"
                        :aria-label="__('briefing.attendance')" />
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm"
                        wire:click="edit('{{ $b->id }}')" />
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-slot:modal>
        <x-mary-modal wire:model="showModal" :title="$formData['id'] ? __('briefing.edit') : __('briefing.new')" class="backdrop-blur-sm">
            <x-mary-form wire:submit="save">
                <div class="space-y-5">
                    <x-mary-input :label="__('briefing.title_field')" wire:model="formData.title" />
                    <x-mary-select :label="__('briefing.internship')" wire:model="formData.internship_id"
                        :options="$this->internships" option-label="name" option-value="id"
                        :placeholder="__('briefing.internship_placeholder')" />
                    <x-mary-input :label="__('briefing.date')" wire:model="formData.date" type="datetime-local" />
                    <x-mary-input :label="__('briefing.location')" wire:model="formData.location" />
                    <x-mary-textarea :label="__('briefing.description')" wire:model="formData.description" rows="3" />
                    <x-mary-checkbox :label="__('briefing.is_mandatory')" wire:model="formData.is_mandatory" />
                </div>
                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('briefing.save')" class="btn-primary btn-sm" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>

        <x-mary-modal wire:model="showAttendanceModal" :title="__('briefing.attendance')" class="backdrop-blur-sm max-w-4xl">
            <x-mary-form wire:submit="saveAttendance">
                <div class="space-y-3">
                    @foreach($attendees as $index => $attendee)
                        <div class="flex items-center gap-3 p-2 border rounded">
                            <span class="flex-1 text-sm">{{ $attendee['user_id'] }}</span>
                            <x-mary-checkbox wire:model="attendees.{{ $index }}.attended" />
                        </div>
                    @endforeach
                    @if(empty($attendees))
                        <p class="text-sm text-base-content/50">{{ __('briefing.no_students') }}</p>
                    @endif
                </div>
                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showAttendanceModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('briefing.save_attendance')" class="btn-primary btn-sm" type="submit" spinner="saveAttendance" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-ui::record-manager>
