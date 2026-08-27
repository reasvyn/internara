<x-ui::components.record-manager :title="__('handbook.title')" :subtitle="__('handbook.subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('handbook.create')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <div class="overflow-x-auto">
        <x-ts-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            class="table-sm"
        >
            @interact('column_title', $h)
                <div class="flex flex-col">
                    <span class="text-sm font-medium">{{ $h->title }}</span>
                    <span class="text-base-content/50 text-xs">v{{ $h->version }}</span>
                </div>
            @endinteract

            @interact('column_audience', $h)
                <span class="text-sm">{{ $h->metadata['target_audience'] ?? __('handbook.audience_all') }}</span>
            @endinteract

            @interact('column_is_active', $h)
                <x-ts-badge
                    :text="$h->is_active ? __('handbook.active') : __('handbook.inactive')"
                    :class="$h->is_active ? 'badge-success' : 'badge-ghost'"
                />
            @endinteract

            @interact('column_action', $h)
                <div class="flex justify-end gap-1">
                    <x-ts-button
                        icon="pencil"
                        color="white"
                        sm
                        wire:click="edit('{{ $h->id }}')"
                        :aria-label="__('common.actions.edit')"
                    />
                    <x-ts-button
                        icon="trash"
                        class="text-error"
                        color="white"
                        sm
                        wire:click="askDelete('{{ $h->id }}')"
                        :aria-label="__('common.actions.delete')"
                    />
                </div>
            @endinteract
        </x-ts-table>
    </div>

    <x-slot:modal>
        <x-ts-modal wire="showModal" :title="$form->id ? __('handbook.edit') : __('handbook.create')" separator blur>
            <form wire:submit="save" class="space-y-5">
                <x-ts-input :label="__('handbook.title_field')" wire:model="form.title" icon="document-text" />
                <x-ts-select.native
                    :label="__('handbook.target_audience')"
                    wire:model="form.audience"
                    :options="$this->audienceOptions"
                />
                <x-ts-textarea :label="__('handbook.content_field')" wire:model="form.description" rows="3" />
                <x-ts-toggle :label="__('handbook.active')" wire:model="form.isActive" />
                <x-ts-upload :label="__('handbook.file')" wire:model="uploadFile" />

                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showModal', false)"
                        color="white"
                        sm
                    />
                    <x-ts-button :text="__('common.actions.save')" color="primary" sm type="submit" loading="save" />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>
    @include('document.handbook.components.handbook-guide')
</x-ui::components.record-manager>
