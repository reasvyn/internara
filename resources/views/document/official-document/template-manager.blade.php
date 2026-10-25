<x-ui::components.record-manager :title="__('document.templates')" :subtitle="__('document.templates_subtitle')">
    <x-slot:headerActions>
        <x-ts-button
            :text="__('document.create_template')"
            icon="plus"
            wire:click="createTemplate"
            color="primary"
            sm
        />
    </x-slot:headerActions>

    <x-ts-table :headers="$this->headers()" :rows="$templates" :sort-by="$sortBy" with-pagination class="table-sm">
        @interact('column_is_active', $template)
            <x-ts-badge
                :text="$template->is_active ? __('document.active') : __('document.inactive')"
                :color="$template->is_active ? 'green' : 'gray'"
                xs
            />
        @endinteract

        @interact('column_type', $template)
            {{ \App\Modules\Document\Enums\DocumentCategory::tryFrom($template->type)?->label() ?? $template->type }}
        @endinteract

        @interact('column_created_at', $template)
            <span class="text-sm">{{ $template->created_at?->format('d M Y') ?? '—' }}</span>
        @endinteract

        @interact('column_actions', $template)
            <x-ui::components.table-actions>
                <x-ui::components.table-action
                    action="edit"
                    wire:click="editTemplate('{{ $template->id }}')"
                    :label="__('common.actions.edit').' '.$template->title"
                />
            </x-ui::components.table-actions>
        @endinteract
    </x-ts-table>

    <x-slot:modal>
        <x-ts-modal
            wire="templateModal"
            :title="$templateData['id'] ? __('document.edit_template') : __('document.create_template')"
            separator
            class="backdrop-blur"
        >
            <form wire:submit="saveTemplate">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-ts-input :label="__('document.template_name')" wire:model="templateData.title" />
                    <x-ts-select.native
                        :label="__('document.category')"
                        wire:model="templateData.type"
                        :options="ts_options($this->categories())"
                    />
                </div>
                <div class="mt-4">
                    <x-ts-textarea :label="__('document.description')" wire:model="templateData.description" />
                </div>
                <div class="mt-4">
                    <x-ts-textarea
                        :label="__('document.template_content')"
                        wire:model="templateData.content"
                        rows="8"
                        :hint="__('document.template_hint')"
                    />
                </div>
                <div class="mt-4">
                    <x-ts-checkbox :label="__('document.active')" wire:model="templateData.is_active" />
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        color="slate"
                        outline
                        sm
                        wire:click="$set('templateModal', false)"
                    />
                    <x-ts-button :text="__('common.actions.save')" type="submit" color="primary" sm />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>
</x-ui::components.record-manager>
