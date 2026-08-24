<div>
    <x-mary-header :title="__('document.templates')" :subtitle="__('document.templates_subtitle')" separator>
        <x-slot:actions>
            <x-ts-button
                :text="__('document.create_template')"
                icon="plus"
                wire:click="createTemplate"
                color="primary"
            />
        </x-slot:actions>
    </x-mary-header>

    <div class="mb-4">
        <x-ts-input
            :label="__('document.search')"
            wire:model.live.debounce="search"
            icon="magnifying-glass"
            :placeholder="__('document.search_templates')"
        />
    </div>

    <x-mary-card>
        <x-mary-table :headers="$headers" :rows="$templates" with-pagination>
            @scope('cell_is_active', $template)
                <x-ts-badge
                    :label="$template->is_active ? __('document.active') : __('document.inactive')"
                    :class="$template->is_active ? 'badge-success' : 'badge-neutral'"
                />
            @endscope

            @scope('cell_type', $template)
                {{ \App\Document\Enums\DocumentCategory::tryFrom($template->type)?->label() ?? $template->type }}
            @endscope

            @scope('actions', $template)
                <x-ts-button
                    aria-label="{{ __('common.actions.edit') }}"
                    icon="pencil"
                    wire:click="editTemplate('{{ $template->id }}')"
                    class="btn-sm btn-ghost"
                />
            @endscope
        </x-mary-table>
    </x-mary-card>

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
                    :options="$this->categories()"
                />

                <x-ts-textarea
                    :label="__('document.content')"
                    wire:model="templateData.content"
                    class="min-h-[300px] font-mono text-sm md:col-span-2"
                    :hint="__('document.content_hint')"
                />

                <x-ts-textarea
                    :label="__('document.description')"
                    wire:model="templateData.description"
                    class="md:col-span-2"
                />
                <x-ts-checkbox :label="__('document.active')" wire:model="templateData.is_active" />
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-ts-button :text="__('common.actions.cancel')" wire:click="$set('templateModal', false)" />
                <x-ts-button :text="__('document.save_template')" type="submit" icon="check" color="primary" />
            </div>
        </form>
    </x-ts-modal>
</div>
