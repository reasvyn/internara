<div
    x-data="{
        search: $wire.entangle('search', true),
        selectedIds: $wire.entangle('selectedIds'),
        items: $wire.entangle('items'),
        
        get filteredCount() {
            if (!this.search) return this.items.length;
            let term = this.search.toLowerCase();
            return this.items.filter(item => 
                Object.values(item).some(val => 
                    String(val).toLowerCase().includes(term)
                )
            ).length;
        },

        applyLocalFilter() {
            let term = this.search.toLowerCase();
            this.$el.querySelectorAll('tbody tr:not(.mary-table-empty)').forEach(tr => {
                let text = tr.innerText.toLowerCase();
                tr.style.display = !term || text.includes(term) ? '' : 'none';
            });
        }
    }"
    x-init="$watch('search', () => applyLocalFilter())"
>
    <x-ui::header 
        wire:key="{{ $this->getEventPrefix() }}-header"
        :title="$this->title" 
        :subtitle="$this->subtitle"
        :context="$this->context"
    >
        <x-slot:actions wire:key="{{ $this->getEventPrefix() }}-actions">
            <div class="flex items-center gap-3 relative z-50">
                {{-- Standard Actions --}}
                <x-ui::dropdown icon="tabler.dots" variant="tertiary" right>
                    <x-ui::menu-item title="ui::common.print" icon="tabler.printer" wire:click="printPdf" />
                    <x-ui::menu-item title="ui::common.export" icon="tabler.download" wire:click="exportCsv" />
                    <x-ui::menu-item title="ui::common.import" icon="tabler.upload" x-on:click="$wire.importModal = true" />
                </x-ui::dropdown>

                {{-- Bulk Actions --}}
                @if($this->can('delete'))
                    <x-ui::dropdown 
                        :label="__('ui::common.bulk_actions')" 
                        icon="tabler.layers-intersect" 
                        variant="secondary" 
                        x-bind:disabled="selectedIds.length === 0"
                        x-bind:class="{ 'opacity-50 pointer-events-none': selectedIds.length === 0 }"
                    >
                        {{ $bulkActions ?? '' }}
                        <x-ui::menu-item 
                            title="ui::common.delete_selected" 
                            icon="tabler.trash" 
                            class="text-error" 
                            wire:click="removeSelected" 
                            wire:confirm="{{ $this->deleteConfirmMessage }}"
                        />
                    </x-ui::dropdown>
                @endif

                {{-- Add Button --}}
                @if($this->can('create'))
                    <x-ui::button :label="$this->addLabel" icon="tabler.plus" variant="primary" wire:click="add" />
                @endif
            </div>
        </x-slot:actions>
    </x-ui::header>

    <x-ui::card wire:key="{{ $this->getEventPrefix() }}-card">
        {{-- Instant Client-side Search Input --}}
        <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="w-full md:w-1/3">
                <x-ui::input 
                    :placeholder="__('ui::common.search_placeholder')" 
                    icon="tabler.search" 
                    wire:model.live.debounce.500ms="search" 
                    x-model="search"
                    x-on:input="applyLocalFilter()"
                    clearable 
                />
            </div>
            {{ $filters ?? '' }}
        </div>

        {{-- Table Wrapper --}}
        <div class="w-full overflow-auto rounded-xl border border-base-200 bg-base-100 shadow-sm max-h-[60vh] relative">
            <div 
                wire:loading.class="opacity-40 pointer-events-none" 
                wire:target="search, sortBy, perPage"
                class="transition-opacity duration-200"
            >
                <x-mary-table 
                    class="table-zebra table-md w-full"
                    :headers="$this->headers" 
                    :rows="$this->records" 
                    wire:model="selectedIds"
                    :sort-by="$this->sortBy"
                    selectable
                    with-pagination
                >
                    @isset($tableCells) {{ $tableCells }} @endisset

                    @scope('actions', $record)
                        <div class="flex items-center justify-end gap-1">
                            {{ $rowActions ?? '' }}
                            @if($this->can('update', $record))
                                <x-ui::button icon="tabler.edit" variant="tertiary" class="text-info btn-xs" wire:click="edit('{{ $record->id }}')" tooltip="{{ __('ui::common.edit') }}" />
                            @endif
                            @if($this->can('delete', $record))
                                <x-ui::button icon="tabler.trash" variant="tertiary" class="text-error btn-xs" wire:click="discard('{{ $record->id }}')" tooltip="{{ __('ui::common.delete') }}" />
                            @endif
                        </div>
                    @endscope
                </x-mary-table>
            </div>

            {{-- Instant Empty State Feedback (Client-side) --}}
            <div 
                x-show="filteredCount === 0" 
                wire:loading.remove 
                wire:target="search"
                x-cloak 
                class="p-12 text-center"
            >
                <x-ui::icon name="tabler.search-off" class="mx-auto size-12 opacity-20" />
                <p class="mt-4 text-base-content/50">{{ __('ui::common.no_results') }}</p>
            </div>
        </div>
    </x-ui::card>

    {{-- Modals --}}
    <x-ui::modal wire:model="formModal" :title="$this->form->id ? __('ui::common.edit') : __('ui::common.add')">
        <x-ui::form wire:submit.prevent="save">
            {{ $formFields ?? '' }}
            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" x-on:click="$wire.formModal = false" />
                <x-ui::button :label="__('ui::common.save')" type="submit" variant="primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    <x-ui::modal wire:model="confirmModal" :title="__('ui::common.confirm')">
        <p>{{ $this->deleteConfirmMessage }}</p>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.cancel')" x-on:click="$wire.confirmModal = false" />
            <x-ui::button :label="__('ui::common.delete')" class="btn-error" wire:click="remove('{{ $this->recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>

    <x-ui::modal wire:model="importModal" :title="__('ui::common.import')">
        <x-ui::form wire:submit.prevent="importCsv">
            <div class="mb-4 flex items-center justify-between px-1">
                <span class="text-xs font-bold uppercase tracking-widest text-base-content/40">{{ __('ui::common.select_file') }}</span>
                <x-ui::button :label="__('ui::common.download_template')" icon="tabler.file-download" variant="tertiary" class="btn-xs" wire:click="downloadTemplate" />
            </div>
            <x-ui::file wire:model="csvFile" accept=".csv" required />
            @if(!empty($this->importInstructions))
                <p class="text-xs text-base-content/50 mt-2 italic">{{ $this->importInstructions }}</p>
            @endif
            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" x-on:click="$wire.importModal = false" />
                <x-ui::button :label="__('ui::common.import')" type="submit" variant="primary" spinner="importCsv" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>
</div>
