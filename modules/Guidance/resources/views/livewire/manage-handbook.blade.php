<div>
    <x-ui::card>
        <div class="w-full overflow-auto rounded-xl border border-base-200 bg-base-100 shadow-sm max-h-[60vh]">
            <x-mary-table 
                class="table-zebra table-md"
                :headers="$this->headers" 
                :rows="$this->records" 
                with-pagination
            >
                @scope('cell_is_mandatory', $handbook)
                    @if($handbook->is_mandatory)
                        <x-ui::badge :value="__('guidance::ui.mandatory')" variant="warning" class="badge-sm font-bold" />
                    @else
                        <x-ui::badge :value="__('guidance::ui.optional')" variant="secondary" class="badge-sm" />
                    @endif
                @endscope

                @scope('cell_is_active', $handbook)
                    @if($handbook->is_active)
                        <x-ui::badge :value="__('guidance::ui.active')" variant="success" class="badge-sm" />
                    @else
                        <x-ui::badge :value="__('guidance::ui.inactive')" variant="error" class="badge-sm" />
                    @endif
                @endscope

                @scope('actions', $handbook)
                    <div class="flex justify-end gap-2">
                        @if($this->can('update', $handbook['id']))
                            <x-ui::button icon="tabler.edit" variant="tertiary" wire:click="edit('{{ $handbook['id'] }}')" class="text-info" tooltip="{{ __('guidance::ui.edit_handbook') }}" />
                        @endif
                        
                        @if($this->can('delete', $handbook['id']))
                            <x-ui::button 
                                icon="tabler.trash" 
                                variant="tertiary" 
                                wire:click="discard('{{ $handbook['id'] }}')"
                                class="text-error" 
                                tooltip="{{ __('ui::common.delete') }}" 
                            />
                        @endif
                    </div>
                @endscope
            </x-mary-table>
        </div>
    </x-ui::card>

    <div class="mt-12">
        <h3 class="text-xl font-bold mb-6 flex items-center gap-3">
            <x-ui::icon name="tabler.users-check" class="size-6 text-primary" />
            {{ __('guidance::ui.acknowledgement_status') }}
        </h3>
        <livewire:guidance::tables.handbook-acknowledgement-table />
    </div>

    {{-- Form Modal --}}
    <x-ui::modal wire:model="formModal" :title="$form->id ? __('guidance::ui.edit_handbook') : __('guidance::ui.new_handbook')">
        <x-ui::form wire:submit="save">
            <x-ui::input :label="__('guidance::ui.handbook_title')" wire:model="form.title" required />
            <x-ui::input :label="__('guidance::ui.version_label')" wire:model="form.version" required />
            
            <x-ui::textarea :label="__('guidance::ui.description')" wire:model="form.description" rows="3" />
            
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <x-ui::checkbox :label="__('guidance::ui.mandatory')" wire:model="form.is_mandatory" />
                <x-ui::checkbox :label="__('guidance::ui.active')" wire:model="form.is_active" />
            </div>

            <x-ui::file :label="__('guidance::ui.pdf_file')" wire:model="form.file" accept="application/pdf" />

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
