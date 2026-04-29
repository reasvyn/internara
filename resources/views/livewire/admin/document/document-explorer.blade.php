<div>
    <x-mary-header title="Official Documents" subtitle="Explore and manage institutional correspondence and certificates" separator />

    <div class="mb-4">
        <x-mary-input label="Search" wire:model.live.debounce="search" icon="o-magnifying-glass" placeholder="Search by title or document number..." />
    </div>

    <x-mary-card>
        <x-mary-table :headers="$headers" :rows="$documents" with-pagination>
            @scope('cell_documentable_type', $doc)
                <div class="text-xs opacity-70">
                    {{ str($doc->documentable_type)->afterLast('\\') }}
                </div>
            @endscope

            @scope('actions', $doc)
                <div class="flex gap-2">
                    <x-mary-button icon="o-eye" label="View" class="btn-sm btn-ghost" />
                    <x-mary-button icon="o-arrow-down-tray" label="Download" class="btn-sm btn-ghost" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
