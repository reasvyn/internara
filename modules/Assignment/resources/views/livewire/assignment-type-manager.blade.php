<div>
    <x-ui::card>
        <div class="w-full overflow-auto rounded-xl border border-base-200 bg-base-100 shadow-sm max-h-[60vh]">
            <x-mary-table 
                class="table-zebra table-md"
                :headers="$this->headers" 
                :rows="$this->records" 
                with-pagination
            >
                @scope('cell_actions', $type)
                    <div class="flex justify-end gap-2">
                        @if($this->can('update', $type->id))
                            <x-ui::button icon="tabler.edit" variant="tertiary" wire:click="edit('{{ $type->id }}')" class="text-info" tooltip="{{ __('assignment::ui.edit_type') }}" />
                        @endif
                        
                        @if($this->can('delete', $type->id))
                            <x-ui::button 
                                icon="tabler.trash" 
                                variant="tertiary" 
                                wire:click="discard('{{ $type->id }}')"
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
    <x-ui::modal wire:model="formModal" :title="$form->id ? __('assignment::ui.edit_type') : __('assignment::ui.add_type')">
        <x-ui::form wire:submit="save">
            <x-ui::input :label="__('assignment::ui.name')" wire:model.live="form.name" required />
            <x-ui::input :label="__('assignment::ui.slug')" wire:model="form.slug" required />
            
            <x-ui::select 
                :label="__('assignment::ui.group')" 
                wire:model="form.group" 
                :options="$this->groups" 
                required 
            />

            <x-ui::textarea :label="__('assignment::ui.description')" wire:model="form.description" rows="3" />
            
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
