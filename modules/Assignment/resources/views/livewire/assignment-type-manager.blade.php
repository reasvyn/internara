<div>
    <x-ui::header 
        :title="__('assignment::ui.menu.assignment_types')" 
        :subtitle="__('assignment::ui.type_subtitle')"
        :context="'assignment::ui.menu.assignments'"
    >
        <x-slot:actions>
            <x-ui::button :label="__('assignment::ui.add_type')" icon="tabler.plus" wire:click="add" variant="primary" />
        </x-slot:actions>
    </x-ui::header>

    <x-ui::card>
        <div class="w-full overflow-auto rounded-xl border border-base-200 bg-base-100 shadow-sm max-h-[60vh]">
            <x-mary-table 
                class="table-zebra table-md"
                :headers="[
                    ['key' => 'name', 'label' => __('assignment::ui.title')],
                    ['key' => 'slug', 'label' => __('assignment::ui.slug')],
                    ['key' => 'group', 'label' => __('assignment::ui.group')],
                    ['key' => 'description', 'label' => __('assignment::ui.description')],
                    ['key' => 'actions', 'label' => ''],
                ]" 
                :rows="$types" 
                with-pagination
            >
                @scope('cell_group', $type)
                    <x-ui::badge :value="Str::upper($type->group)" variant="secondary" class="badge-sm font-bold" />
                @endscope

                @scope('actions', $type)
                    <div class="flex justify-end gap-2">
                        <x-ui::button icon="tabler.edit" variant="tertiary" wire:click="edit('{{ $type->id }}')" class="text-info" tooltip="{{ __('assignment::ui.edit_type') }}" />
                        <x-ui::button 
                            icon="tabler.trash" 
                            variant="tertiary" 
                            wire:click="remove('{{ $type->id }}')"
                            wire:confirm="{{ __('assignment::ui.delete_confirm') }}"
                            class="text-error" 
                            tooltip="{{ __('ui::common.delete') }}" 
                        />
                    </div>
                @endscope
            </x-mary-table>
        </div>
    </x-ui::card>

    <x-ui::modal wire:model="formModal" :title="$recordId ? __('assignment::ui.edit_type') : __('assignment::ui.add_type')">
        <x-ui::form wire:submit="save">
            <x-ui::input :label="__('assignment::ui.title')" wire:model.blur="name" required />
            <x-ui::input :label="__('assignment::ui.slug')" wire:model="slug" required :readonly="$recordId !== null" />
            
            <x-ui::select :label="__('assignment::ui.group')" wire:model="group" :options="[
                ['id' => 'report', 'name' => __('assignment::ui.group_report')],
                ['id' => 'presentation', 'name' => __('assignment::ui.group_presentation')],
                ['id' => 'certification', 'name' => __('assignment::ui.group_certification')],
                ['id' => 'other', 'name' => __('assignment::ui.group_other')],
            ]" required />

            <x-ui::textarea :label="__('assignment::ui.description')" wire:model="description" rows="3" />

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" x-on:click="$wire.formModal = false" />
                <x-ui::button :label="__('ui::common.save')" type="submit" variant="primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>
</div>
