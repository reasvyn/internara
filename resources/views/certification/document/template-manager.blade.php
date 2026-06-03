<div>
    <x-mary-header title="Document Templates" subtitle="Manage templates for formal letters and certificates" separator>
        <x-slot:actions>
            <x-mary-button label="Create Template" icon="o-plus" wire:click="createTemplate" class="btn-primary" />
        </x-slot:actions>
    </x-mary-header>

    <div class="mb-4">
        <x-mary-input label="Search" wire:model.live.debounce="search" icon="o-magnifying-glass" placeholder="Search templates..." />
    </div>

    <x-mary-card>
        <x-mary-table :headers="$headers" :rows="$templates" with-pagination>
            @scope('cell_is_active', $template)
                <x-mary-badge :label="$template->is_active ? 'Active' : 'Inactive'" :class="$template->is_active ? 'badge-success' : 'badge-neutral'" />
            @endscope

            @scope('actions', $template)
                <x-mary-button icon="o-pencil" wire:click="editTemplate('{{ $template->id }}')" class="btn-sm btn-ghost" />
            @endscope
        </x-mary-table>
    </x-mary-card>

    <x-mary-modal wire:model="templateModal" title="{{ $templateData['id'] ? 'Edit Template' : 'Create Template' }}" separator class="backdrop-blur">
        <x-mary-form wire:submit="saveTemplate">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input label="Template Name" wire:model="templateData.name" />
                <x-mary-select label="Category" wire:model="templateData.category" :options="[['id' => 'application', 'name' => 'Application'], ['id' => 'permit', 'name' => 'Permit'], ['id' => 'certificate', 'name' => 'Certificate']]" />
                
                <x-mary-textarea label="Content (Blade Supported)" wire:model="templateData.content" class="md:col-span-2 min-h-[300px] font-mono text-sm" hint="Use {{ '$target->name' }} for dynamic values." />
                
                <x-mary-textarea label="Description" wire:model="templateData.description" class="md:col-span-2" />
                <x-mary-checkbox label="Active" wire:model="templateData.is_active" />
            </div>

            <x-slot:actions>
                <x-mary-button label="Cancel" wire:click="$set('templateModal', false)" />
                <x-mary-button label="Save Template" type="submit" icon="o-check" class="btn-primary" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
