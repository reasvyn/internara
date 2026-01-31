<x-ui::main title="{{ __('Assignment Types') }}" subtitle="{{ __('Define dynamic categories for student assignments.') }}">
    <x-slot:actions>
        <x-ui::button label="{{ __('Add Type') }}" icon="tabler.plus" wire:click="add" class="btn-primary" />
    </x-slot:actions>

    <x-ui::card>
        <x-ui::table :headers="[
            ['key' => 'name', 'label' => __('Name')],
            ['key' => 'slug', 'label' => __('Slug')],
            ['key' => 'group', 'label' => __('Group')],
            ['key' => 'description', 'label' => __('Description')],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ]" :rows="$types" with-pagination>
            @scope('cell_group', $type)
                <x-ui::badge :label="Str::upper($type->group)" class="badge-outline" />
            @endscope

            @scope('cell_actions', $type)
                <div class="flex justify-end gap-2">
                    <x-ui::button icon="tabler.edit" wire:click="edit('{{ $type->id }}')" class="btn-ghost btn-sm" />
                    <x-ui::button icon="tabler.trash" wire:click="remove('{{ $type->id }}')"
                        wire:confirm="{{ __('Are you sure you want to delete this type?') }}"
                        class="btn-ghost btn-sm text-error" />
                </div>
            @endscope
        </x-ui::table>
    </x-ui::card>

    <x-ui::modal wire:model="formModal" title="{{ $recordId ? __('Edit Type') : __('Add Type') }}">
        <x-ui::form wire:submit="save">
            <x-ui::input label="{{ __('Name') }}" wire:model.blur="name" required />
            <x-ui::input label="{{ __('Slug') }}" wire:model="slug" required :readonly="$recordId !== null" />
            
            <x-ui::select label="{{ __('Group') }}" wire:model="group" :options="[
                ['id' => 'report', 'name' => __('Report')],
                ['id' => 'presentation', 'name' => __('Presentation')],
                ['id' => 'certification', 'name' => __('Certification')],
                ['id' => 'other', 'name' => __('Other')],
            ]" required />

            <x-ui::textarea label="{{ __('Description') }}" wire:model="description" rows="3" />

            <x-slot:actions>
                <x-ui::button label="{{ __('Cancel') }}" x-on:click="$wire.formModal = false" />
                <x-ui::button label="{{ __('Save') }}" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>
</x-ui::main>
