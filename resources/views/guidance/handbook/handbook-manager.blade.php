<x-core::ui.record-manager
    :title="__('guidance.title')"
    :subtitle="__('guidance.subtitle')"
>
    <x-slot:headerActions>
        <x-mary-button :label="__('guidance.create')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <x-core::ui.confirm
        wire:model="showConfirm"
        confirmText="{{ __('common.actions.confirm') }}"
        cancelText="{{ __('common.actions.cancel') }}"
    />

    <div class="overflow-x-auto">
        <x-mary-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            class="table-sm"
        >
            @scope('cell_title', $h)
                <div class="flex flex-col">
                    <span class="font-medium text-sm">{{ $h->title }}</span>
                    <span class="text-xs text-base-content/50">v{{ $h->version }}</span>
                </div>
            @endscope

            @scope('cell_audience', $h)
                <span class="text-sm">{{ $h->metadata['target_audience'] ?? __('guidance.audience_all') }}</span>
            @endscope

            @scope('cell_is_active', $h)
                <x-mary-badge :value="$h->is_active ? __('guidance.active') : __('guidance.inactive')"
                    :class="$h->is_active ? 'badge-success' : 'badge-ghost'" />
            @endscope

            @scope('actions', $h)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit('{{ $h->id }}')" :aria-label="__('common.actions.edit')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="askDelete('{{ $h->id }}')" :aria-label="__('common.actions.delete')" />
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-slot:modal>
        <x-mary-modal wire:model="showModal" :title="$form->id ? __('guidance.edit') : __('guidance.create')" separator class="backdrop-blur-sm">
            <x-mary-form wire:submit="save" class="space-y-5">
                <x-mary-input :label="__('guidance.title_field')" wire:model="form.title" icon="o-document-text" />
                <x-mary-select :label="__('guidance.target_audience')" wire:model="form.audience" :options="$this->audienceOptions" />
                <x-mary-textarea :label="__('guidance.content_field')" wire:model="form.description" rows="3" />
                <x-mary-toggle :label="__('guidance.active')" wire:model="form.isActive" />
                <x-mary-file :label="__('guidance.file')" wire:model="uploadFile" accept="application/pdf" />

                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('common.actions.save')" class="btn-primary btn-sm" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
@include('guidance.handbook.components.handbook-guide')
</x-core::ui.record-manager>
