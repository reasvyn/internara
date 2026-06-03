<x-core::ui.record-manager
    :title="__('certificate.title')"
    :subtitle="__('certificate.subtitle')"
>
    <x-slot:headerActions>
        <x-mary-button :label="__('certificate.add_template')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <div class="overflow-x-auto">
        <x-mary-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            class="table-sm"
        >
            @scope('cell_is_active', $t)
                <x-mary-badge :value="$t->is_active ? __('certificate.active') : __('certificate.inactive')"
                    :class="$t->is_active ? 'badge-success' : 'badge-ghost'" />
            @endscope

            @scope('cell_layout', $t)
                <span class="text-sm">{{ $t->layout }}</span>
            @endscope

            @scope('actions', $t)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit('{{ $t->id }}')" />
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-slot:modal>
        <x-mary-modal wire:model="showModal" :title="__('certificate.template_form')" class="backdrop-blur-sm max-w-2xl">
            <x-mary-form wire:submit="saveTemplate">
                <div class="space-y-5">
                    <x-mary-input :label="__('certificate.template_name')" wire:model="formData.name" />
                    <x-mary-select :label="__('certificate.layout')" wire:model="formData.layout"
                        :options="['portrait' => 'Portrait', 'landscape' => 'Landscape']" />
                    <x-mary-textarea :label="__('certificate.content_template')" wire:model="formData.content_template" rows="10" />
                    <x-mary-checkbox :label="__('certificate.is_active')" wire:model="formData.is_active" />
                </div>
                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('certificate.save_template')" class="btn-primary btn-sm" type="submit" spinner="saveTemplate" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-core::ui.record-manager>
