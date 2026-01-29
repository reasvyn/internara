<div>
    <x-ui::main title="{{ __('assignment::ui.manage_assignments') }}" subtitle="{{ __('Define mandatory tasks and institutional policies for students.') }}">
        <x-slot:actions>
            <x-ui::button label="{{ __('assignment::ui.add_assignment') }}" icon="tabler.plus" class="btn-primary" wire:click="add" />
        </x-slot:actions>

        <x-ui::card>
            <x-ui::table :headers="[
                ['key' => 'title', 'label' => __('Title')],
                ['key' => 'type.name', 'label' => __('Type')],
                ['key' => 'is_mandatory', 'label' => __('assignment::ui.is_mandatory')],
                ['key' => 'due_date', 'label' => __('assignment::ui.due_date')],
            ]" :rows="$assignments" with-pagination>
                
                @scope('cell_is_mandatory', $assignment)
                    <x-ui::badge :label="$assignment->is_mandatory ? __('Yes') : __('No')" :class="$assignment->is_mandatory ? 'badge-error' : 'badge-outline'" />
                @endscope

                @scope('cell_due_date', $assignment)
                    {{ $assignment->due_date?->format('d/m/Y H:i') ?? '-' }}
                @endscope

                @scope('actions', $assignment)
                    <div class="flex gap-2">
                        <x-ui::button icon="tabler.edit" class="btn-ghost btn-sm text-info" wire:click="edit('{{ $assignment->id }}')" />
                        <x-ui::button icon="tabler.trash" class="btn-ghost btn-sm text-error" wire:click="remove('{{ $assignment->id }}')" wire:confirm="{{ __('assignment::ui.delete_confirm') }}" />
                    </div>
                @endscope
            </x-ui::table>
        </x-ui::card>
    </x-ui::main>

    {{-- Form Modal --}}
    <x-ui::modal wire:model="formModal" title="{{ $recordId ? __('assignment::ui.edit_assignment') : __('assignment::ui.add_assignment') }}">
        <x-ui::form wire:submit="save">
            <x-ui::input label="{{ __('Title') }}" wire:model="title" required />
            
            <x-ui::select 
                label="{{ __('assignment::ui.assignment_type') }}" 
                wire:model="assignment_type_id" 
                :options="$types" 
                required 
            />

            <x-ui::textarea label="{{ __('Description') }}" wire:model="description" />
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui::checkbox label="{{ __('assignment::ui.is_mandatory') }}" wire:model="is_mandatory" />
                <x-ui::input label="{{ __('assignment::ui.due_date') }}" type="datetime-local" wire:model="due_date" />
            </div>

            <x-slot:actions>
                <x-ui::button label="{{ __('shared::ui.cancel') }}" wire:click="$set('formModal', false)" />
                <x-ui::button label="{{ __('shared::ui.save') }}" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>
</div>
