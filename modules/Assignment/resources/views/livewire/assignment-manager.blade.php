<div>
    <x-ui::header :title="__('assignment::ui.manage_assignments')" :subtitle="__('assignment::ui.subtitle')">
        <x-slot:actions>
            <x-ui::button :label="__('assignment::ui.add_assignment')" icon="tabler.plus" variant="primary" wire:click="add" />
        </x-slot:actions>
    </x-ui::header>

    <x-ui::main>
        <x-ui::card>
            <x-ui::table :headers="[
                ['key' => 'title', 'label' => __('assignment::ui.title')],
                ['key' => 'type.name', 'label' => __('assignment::ui.type')],
                ['key' => 'is_mandatory', 'label' => __('assignment::ui.is_mandatory')],
                ['key' => 'due_date', 'label' => __('assignment::ui.due_date')],
            ]" :rows="$assignments" with-pagination>
                
                @scope('cell_is_mandatory', $assignment)
                    <x-ui::badge 
                        :value="$assignment->is_mandatory ? __('ui::common.success') : __('ui::common.cancel')" 
                        :variant="$assignment->is_mandatory ? 'primary' : 'secondary'" 
                        class="badge-sm" 
                    />
                @endscope

                @scope('cell_due_date', $assignment)
                    <span class="text-xs opacity-70">{{ $assignment->due_date?->translatedFormat('d M Y H:i') ?? '-' }}</span>
                @endscope

                @scope('actions', $assignment)
                    <div class="flex gap-1">
                        <x-ui::button icon="tabler.edit" variant="tertiary" class="text-info btn-sm" wire:click="edit('{{ $assignment->id }}')" />
                        <x-ui::button icon="tabler.trash" variant="tertiary" class="text-error btn-sm" wire:click="remove('{{ $assignment->id }}')" wire:confirm="__('assignment::ui.delete_confirm')" />
                    </div>
                @endscope
            </x-ui::table>
        </x-ui::card>
    </x-ui::main>

    {{-- Form Modal --}}
    <x-ui::modal wire:model="formModal" :title="$recordId ? __('assignment::ui.edit_assignment') : __('assignment::ui.add_assignment')">
        <x-ui::form wire:submit="save">
            <x-ui::input :label="__('assignment::ui.title')" wire:model="title" required />
            
            <x-ui::select 
                :label="__('assignment::ui.assignment_type')" 
                wire:model="assignment_type_id" 
                :options="$types" 
                required 
            />

            <x-ui::textarea :label="__('ui::common.description')" wire:model="description" />
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui::checkbox :label="__('assignment::ui.is_mandatory')" wire:model="is_mandatory" />
                <x-ui::input :label="__('assignment::ui.due_date')" type="datetime-local" wire:model="due_date" />
            </div>

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('formModal', false)" />
                <x-ui::button :label="__('ui::common.save')" type="submit" variant="primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>
</div>
