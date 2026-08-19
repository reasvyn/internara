<div>
    <x-mary-header :title="__('document.templates')" :subtitle="__('document.templates_subtitle')" separator>
        <x-slot:actions>
            <x-mary-button
                :label="__('document.create_template')"
                icon="o-plus"
                wire:click="createTemplate"
                class="btn-primary"
            />
        </x-slot:actions>
    </x-mary-header>

    <div class="mb-4">
        <x-mary-input
            :label="__('document.search')"
            wire:model.live.debounce="search"
            icon="o-magnifying-glass"
            :placeholder="__('document.search_templates')"
        />
    </div>

    <x-mary-card>
        <x-mary-table :headers="$headers" :rows="$templates" with-pagination>
            @scope('cell_is_active', $template)
                <x-mary-badge
                    :label="$template->is_active ? __('document.active') : __('document.inactive')"
                    :class="$template->is_active ? 'badge-success' : 'badge-neutral'"
                />
            @endscope

            @scope('cell_type', $template)
                {{ \App\Document\Enums\DocumentCategory::tryFrom($template->type)?->label() ?? $template->type }}
            @endscope

            @scope('actions', $template)
                <x-mary-button
                    icon="o-pencil"
                    wire:click="editTemplate('{{ $template->id }}')"
                    class="btn-sm btn-ghost"
                />
            @endscope
        </x-mary-table>
    </x-mary-card>

    <x-mary-modal
        wire:model="templateModal"
        :title="$templateData['id'] ? __('document.edit_template') : __('document.create_template')"
        separator
        class="backdrop-blur"
    >
        <x-mary-form wire:submit="saveTemplate">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-mary-input :label="__('document.template_name')" wire:model="templateData.title" />
                <x-mary-select
                    :label="__('document.category')"
                    wire:model="templateData.type"
                    :options="$this->categories()"
                />

                <x-mary-textarea
                    :label="__('document.content')"
                    wire:model="templateData.content"
                    class="min-h-[300px] font-mono text-sm md:col-span-2"
                    :hint="__('document.content_hint')"
                />

                <x-mary-textarea
                    :label="__('document.description')"
                    wire:model="templateData.description"
                    class="md:col-span-2"
                />
                <x-mary-checkbox :label="__('document.active')" wire:model="templateData.is_active" />
            </div>

            <x-slot:actions>
                <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('templateModal', false)" />
                <x-mary-button :label="__('document.save_template')" type="submit" icon="o-check" class="btn-primary" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
