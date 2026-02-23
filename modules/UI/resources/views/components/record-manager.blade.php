<div>
    <x-ui::header 
        wire:key="{{ $this->getEventPrefix() }}-header"
        :title="$this->title" 
        :subtitle="$this->subtitle"
        :context="$this->context"
    >
        <x-slot:actions wire:key="{{ $this->getEventPrefix() }}-actions">
            <div 
                class="flex items-center gap-3 relative z-50"
                x-data="{ selectedIds: $wire.entangle('selectedIds') }"
            >
                {{-- 1. Standard Actions (Dropdown) --}}
                <x-ui::dropdown icon="tabler.dots" variant="tertiary" right>
                    <x-ui::menu-item title="ui::common.print" icon="tabler.printer" wire:click="printPdf" />
                    <x-ui::menu-item title="ui::common.export" icon="tabler.download" wire:click="exportCsv" />
                    <x-ui::menu-item title="ui::common.import" icon="tabler.upload" wire:click="$set('importModal', true)" />
                </x-ui::dropdown>

                {{-- 2. Bulk Actions --}}
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

                {{-- 3. Add Button --}}
                @if($this->can('create'))
                    <x-ui::button :label="$this->addLabel" icon="tabler.plus" variant="primary" wire:click="add" />
                @endif
            </div>
        </x-slot:actions>
    </x-ui::header>

    <x-ui::card wire:key="{{ $this->getEventPrefix() }}-card">
        {{-- Search & Filters --}}
        <div 
            x-data="{ 
                search: $wire.entangle('search', true),
                applyFilter() {
                    let term = this.search.toLowerCase();
                    let rows = this.$el.querySelectorAll('table tbody tr:not(.mary-table-empty)');
                    rows.forEach(row => {
                        let text = row.innerText.toLowerCase();
                        row.style.display = text.includes(term) ? '' : 'none';
                    });
                }
            }" 
            x-init="$watch('search', () => applyFilter())"
        >
            <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full md:w-1/3">
                    <x-ui::input 
                        :placeholder="__('ui::common.search_placeholder')" 
                        icon="tabler.search" 
                        wire:model.live.debounce.250ms="search" 
                        x-model="search"
                        clearable 
                    />
                </div>
                {{ $filters ?? '' }}
            </div>

            {{-- Table Wrapper --}}
            <div class="w-full overflow-auto rounded-xl border border-base-200 bg-base-100 shadow-sm max-h-[60vh] relative">
                <div wire:loading.flex wire:target="search" class="absolute inset-0 bg-base-100/60 backdrop-blur-[1px] z-20 items-center justify-center">
                    <span class="loading loading-spinner loading-md text-base-content/20"></span>
                </div>

                <x-mary-table 
                    class="table-zebra table-md w-full"
                    :headers="$this->headers" 
                    :rows="$this->records" 
                    wire:model="selectedIds"
                    :sort-by="$this->sortBy"
                    selectable
                    with-pagination
                >
                    {{-- Dynamically inject specific cells from the child component --}}
                    {{ $tableCells ?? '' }}

                    {{-- Default Actions Column (if not overridden) --}}
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
        </div>
    </x-ui::card>

    {{-- 1. Main Form Modal --}}
    <x-ui::modal 
        id="{{ $this->getEventPrefix() }}-form-modal" 
        wire:model="formModal" 
        :title="$this->form->id ? __('ui::common.edit') : __('ui::common.add')"
    >
        <x-ui::form wire:submit.prevent="save">
            {{ $formFields ?? '' }}

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('formModal', false)" />
                <x-ui::button :label="__('ui::common.save')" type="submit" variant="primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- 2. Bulk Confirm Delete Modal --}}
    <x-ui::modal 
        id="{{ $this->getEventPrefix() }}-confirm-modal" 
        wire:model="confirmModal" 
        :title="__('ui::common.confirm')"
    >
        <p>{{ $this->deleteConfirmMessage }}</p>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('confirmModal', false)" />
            <x-ui::button :label="__('ui::common.delete')" class="btn-error" wire:click="remove('{{ $this->recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>

    {{-- 3. Generic Import Modal --}}
    <x-ui::modal 
        id="{{ $this->getEventPrefix() }}-import-modal" 
        wire:model="importModal" 
        :title="__('ui::common.import')"
    >
        <x-ui::form wire:submit.prevent="importCsv">
            <div class="mb-4 flex items-center justify-between px-1">
                <span class="text-xs font-bold uppercase tracking-widest text-base-content/40">{{ __('ui::common.select_file') }}</span>
                <x-ui::button :label="__('ui::common.download_template')" icon="tabler.file-download" variant="tertiary" class="btn-xs" wire:click="downloadTemplate" />
            </div>

            <x-ui::file 
                wire:model="csvFile" 
                accept=".csv"
                :crop="false"
                required
            />
            
            @isset($importInstructions)
                <p class="text-xs text-base-content/50 mt-2 italic">
                    {{ $importInstructions }}
                </p>
            @endisset

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('importModal', false)" />
                <x-ui::button :label="__('ui::common.import')" type="submit" variant="primary" spinner="importCsv" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>
</div>
