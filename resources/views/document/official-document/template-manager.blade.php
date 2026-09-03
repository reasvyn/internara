<div>
    <x-ui::components.page-header :title="__('document.templates')" :description="__('document.templates_subtitle')">
        <x-slot:actions>
            <x-ts-button
                :text="__('document.create_template')"
                icon="plus"
                wire:click="createTemplate"
                color="primary"
            />
        </x-slot:actions>
    </x-ui::components.page-header>

    <div class="mb-4">
        <x-ts-input
            :label="__('document.search')"
            wire:model.live.debounce="search"
            icon="magnifying-glass"
            :placeholder="__('document.search_templates')"
        />
    </div>

    <x-ts-card shadowless>
        <x-ts-table :headers="$headers" :rows="$templates" paginate>
            @interact('column_is_active', $template)
                <x-ts-badge
                    :label="$template->is_active ? __('document.active') : __('document.inactive')"
                    :class="$template->is_active ? 'badge-success' : 'badge-neutral'"
                />
            @endinteract

            @interact('column_type', $template)
                {{ \App\Modules\Document\Enums\DocumentCategory::tryFrom($template->type)?->label() ?? $template->type }}
            @endinteract

            @interact('column_action', $template)
                <x-ts-button
                    aria-label="{{ __('common.actions.edit') }}"
                    icon="pencil"
                    wire:click="editTemplate('{{ $template->id }}')"
                    class="btn-sm btn-ghost"
                />
            @endinteract
        </x-ts-table>

    </x-ts-card>
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

                <div class="md:col-span-2">
                    <x-ts-textarea
                        :label="__('document.content')"
                        wire:model="templateData.content"
                        class="min-h-[300px] font-mono text-sm"
                        :hint="__('document.content_hint')"
                    />
                </div>

                <div class="md:col-span-2">
                    <x-ts-textarea
                        :label="__('document.description')"
                        wire:model="templateData.description"
                    />
                </div>
                <x-ts-checkbox :label="__('document.active')" wire:model="templateData.is_active" />
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-ts-button :text="__('common.actions.cancel')" wire:click="$set('templateModal', false)" />
                <x-ts-button :text="__('document.save_template')" type="submit" icon="check" color="primary" />
            </div>
        </form>
    </x-ts-modal>

</div>
