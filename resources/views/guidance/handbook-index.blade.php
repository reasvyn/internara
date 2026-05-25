<div>
    <x-shared::ui.page-header :title="__('handbooks.title')" :subtitle="__('handbooks.subtitle')" />

    <div class="mb-4 flex justify-end">
        <x-mary-button :label="__('handbooks.create')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </div>

    <x-mary-card>
        <x-mary-table :headers="[
            ['key' => 'title', 'label' => __('handbooks.title_field'), 'sortable' => true],
            ['key' => 'version', 'label' => __('handbooks.version_field'), 'sortable' => true],
            ['key' => 'is_active', 'label' => __('handbooks.status')],
            ['key' => 'actions', 'label' => ''],
        ]" :rows="$handbooks" with-pagination>
            @scope('cell_is_active', $handbook)
                <x-mary-badge :value="$handbook->is_active ? __('handbooks.active') : __('handbooks.inactive')"
                    :class="$handbook->is_active ? 'badge-success' : 'badge-ghost'" />
            @endscope
            @scope('actions', $handbook)
                <div class="flex gap-1">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm btn-circle"
                        wire:click="edit('{{ $handbook->id }}')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm btn-circle text-error"
                        wire:click="delete('{{ $handbook->id }}')"
                        wire:confirm="{{ __('common.actions.confirm_action') }}" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    <x-mary-modal wire:model="showModal" :title="$form->id ? __('handbooks.edit') : __('handbooks.create')">
        <div class="space-y-4">
            <x-mary-input :label="__('handbooks.title_field')" wire:model="form.title" />
            <x-mary-textarea :label="__('handbooks.content_field')" wire:model="form.content" rows="8" />
            <x-mary-input :label="__('handbooks.version_field')" type="number" wire:model="form.version" />
            <x-mary-toggle :label="__('handbooks.active')" wire:model="form.is_active" />
        </div>
        <x-slot:actions>
            <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showModal', false)" class="btn-ghost" />
            <x-mary-button :label="__('common.actions.save')" wire:click="store" class="btn-primary" spinner="store" />
        </x-slot:actions>
    </x-mary-modal>
</div>
