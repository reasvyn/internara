<div>
    <x-ui::card>
        <div class="w-full overflow-auto rounded-xl border border-base-200 bg-base-100 shadow-sm max-h-[60vh]">
            <x-mary-table 
                class="table-zebra table-md"
                :headers="$this->headers" 
                :rows="$this->records" 
                with-pagination
            >
                @scope('cell_is_mandatory', $assignment)
                    @if($assignment->is_mandatory)
                        <x-ui::badge :value="__('assignment::ui.mandatory')" variant="warning" class="badge-sm font-bold" />
                    @else
                        <x-ui::badge :value="__('assignment::ui.optional')" variant="secondary" class="badge-sm" />
                    @endif
                @endscope

                @scope('cell_due_date', $assignment)
                    @if($assignment->due_date)
                        <div class="flex flex-col">
                            <span class="text-sm font-medium">{{ \Carbon\Carbon::parse($assignment->due_date)->translatedFormat('d M Y') }}</span>
                            <span class="text-[10px] uppercase opacity-50">{{ \Carbon\Carbon::parse($assignment->due_date)->format('H:i') }}</span>
                        </div>
                    @else
                        <span class="text-xs opacity-30">—</span>
                    @endif
                @endscope

                @scope('actions', $assignment)
                    <div class="flex justify-end gap-2">
                        @if($this->can('update', $assignment['id']))
                            <x-ui::button icon="tabler.edit" variant="tertiary" wire:click="edit('{{ $assignment['id'] }}')" class="text-info" tooltip="{{ __('assignment::ui.edit_assignment') }}" />
                        @endif
                        
                        @if($this->can('delete', $assignment['id']))
                            <x-ui::button 
                                icon="tabler.trash" 
                                variant="tertiary" 
                                wire:click="discard('{{ $assignment['id'] }}')"
                                class="text-error" 
                                tooltip="{{ __('ui::common.delete') }}" 
                            />
                        @endif
                    </div>
                @endscope
            </x-mary-table>
        </div>
    </x-ui::card>

    {{-- Form Modal --}}
    <x-ui::modal wire:model="formModal" :title="$form->id ? __('assignment::ui.edit_assignment') : __('assignment::ui.add_assignment')">
        <x-ui::form wire:submit="save">
            <x-ui::input :label="__('assignment::ui.title')" wire:model="form.title" required />
            
            <x-ui::select 
                :label="__('assignment::ui.assignment_type')" 
                wire:model="form.assignment_type_id" 
                :options="$this->types" 
                required 
            />

            <x-ui::textarea :label="__('assignment::ui.description')" wire:model="form.description" rows="3" />
            
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <x-ui::checkbox :label="__('assignment::ui.is_mandatory')" wire:model="form.is_mandatory" />
                <x-ui::input :label="__('assignment::ui.due_date')" type="datetime-local" wire:model="form.due_date" />
            </div>

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('formModal', false)" />
                <x-ui::button :label="__('ui::common.save')" type="submit" variant="primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Confirm Delete --}}
    <x-ui::modal wire:model="confirmModal" :title="__('ui::common.confirm_delete')">
        <div class="py-4">{{ $this->deleteConfirmMessage }}</div>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('confirmModal', false)" />
            <x-ui::button :label="__('ui::common.delete')" variant="error" wire:click="remove" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>
</div>
