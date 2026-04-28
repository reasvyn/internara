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
            <div class="flex items-center gap-2 sm:gap-3">
                {{-- [S3 - Scalable] Responsive Action Group --}}
                
                {{-- 1. Secondary Actions (Consolidated on all screens for neatness) --}}
                <x-ui::dropdown icon="tabler.dots-vertical" variant="tertiary" class="btn-circle btn-sm sm:btn-md" right>
                    <x-ui::menu-item title="ui::common.refresh" icon="tabler.refresh" wire:click="refreshRecords" />
                    <x-ui::menu-separator />
                    <x-ui::menu-item title="ui::common.print" icon="tabler.printer" wire:click="printPdf" />
                    <x-ui::menu-item title="ui::common.export" icon="tabler.download" wire:click="exportCsv" />
                    <x-ui::menu-item title="ui::common.import" icon="tabler.upload" x-on:click="$wire.importModal = true" />
                </x-ui::dropdown>

                {{-- 2. Bulk Actions --}}
                @if($this->can('delete'))
                    <div 
                        x-show="selectedIds.length > 0" 
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="relative"
                    >
                        <x-ui::dropdown 
                            icon="tabler.layers-intersect" 
                            variant="secondary"
                            class="btn-sm sm:btn-md"
                            :label="__('ui::common.bulk_actions')"
                            label-class="hidden sm:inline"
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
                        {{-- Counter Badge --}}
                        <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-[8px] font-black text-primary-content shadow-sm">
                            <span x-text="selectedIds.length"></span>
                        </span>
                    </div>
                @endif

                {{-- 3. Primary Action (Add) --}}
                @if($this->can('create'))
                    <x-ui::button 
                        :label="$this->addLabel" 
                        icon="tabler.plus" 
                        variant="primary" 
                        wire:click="add" 
                        class="btn-sm sm:btn-md shadow-lg shadow-primary/20 transition-all hover:scale-[1.02] active:scale-95"
                    />
                @endif
            </div>
        </x-slot:actions>
    </x-ui::header>

    <div class="space-y-6">
        {{-- Search & Filters Container --}}
        <div class="rounded-[2rem] bg-base-200/40 p-4 sm:p-6 border border-base-content/5 backdrop-blur-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                {{-- Search Input --}}
                <div class="w-full lg:max-w-md">
                    <x-ui::input 
                        :placeholder="__('ui::common.search_placeholder')" 
                        icon="tabler.search" 
                        wire:model.live.debounce.500ms="search" 
                        x-model="search"
                        x-on:input="applyLocalFilter()"
                        class="bg-base-100/50 border-none shadow-inner focus:bg-base-100"
                        clearable 
                    />
                </div>
                
                {{-- Custom Filters Slot --}}
                @if(isset($filters))
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="hidden lg:block h-8 w-px bg-base-content/10 mx-2"></div>
                        {{ $filters }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Table Content Card --}}
        <x-ui::card wire:key="{{ $this->getEventPrefix() }}-card" class="overflow-hidden border-none shadow-xl bg-base-100/60 ring-1 ring-base-content/5 rounded-[2.5rem]">
            <div class="table-enterprise relative">
                <div 
                    wire:loading.class="opacity-40 pointer-events-none" 
                    wire:target="search, sortBy, perPage, refreshRecords"
                    class="transition-opacity duration-300"
                >
                    <x-mary-table 
                        class="table-md w-full"
                        :headers="$this->headers" 
                        :rows="$this->records" 
                        wire:model="selectedIds"
                        :sort-by="$this->sortBy"
                        selectable
                        with-pagination
                    >
                        @isset($tableCells) {{ $tableCells }} @endisset

                        @if(isset($rowActions))
                            {{ $rowActions }}
                        @else
                            @scope('actions', $record)
                                <div class="flex items-center justify-end gap-1 px-2">
                                    @if($this->can('update', $record))
                                        <x-ui::button icon="tabler.edit" variant="tertiary" class="text-info btn-circle btn-xs hover:bg-info/10 transition-colors" wire:click="edit('{{ $record->id }}')" tooltip="{{ __('ui::common.edit') }}" />
                                    @endif
                                    @if($this->can('delete', $record))
                                        <x-ui::button icon="tabler.trash" variant="tertiary" class="text-error btn-circle btn-xs hover:bg-error/10 transition-colors" wire:click="discard('{{ $record->id }}')" tooltip="{{ __('ui::common.delete') }}" />
                                    @endif
                                </div>
                            @endscope
                        @endif
                    </x-mary-table>
                </div>

                {{-- Instant Empty State Feedback (Client-side) --}}
                <div 
                    x-show="filteredCount === 0" 
                    wire:loading.remove 
                    wire:target="search"
                    x-cloak 
                    class="py-20 text-center"
                >
                    <div class="relative inline-flex mb-6">
                        <div class="absolute inset-0 bg-primary/20 blur-3xl rounded-full"></div>
                        <x-ui::icon name="tabler.search-off" class="relative size-16 text-base-content/20" />
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-widest opacity-30">{{ __('ui::common.no_results') }}</p>
                </div>
            </div>
        </x-ui::card>
    </div>

    {{-- Form Modal --}}
    @if(isset($formFields))
    <x-ui::modal wire:model="formModal" :title="(property_exists($this, 'form') && $this->form->id) ? __('ui::common.edit') : __('ui::common.add')" class="backdrop-blur-md">
        <x-ui::form wire:submit.prevent="save" class="space-y-6">
            {{ $formFields }}
            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" x-on:click="$wire.formModal = false" variant="tertiary" />
                <x-ui::button :label="__('ui::common.save')" type="submit" variant="primary" spinner="save" class="px-10 shadow-lg shadow-primary/20" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>
    @endif

    {{-- Confirm Modal --}}
    <x-ui::modal wire:model="confirmModal" :title="__('ui::common.confirm')" class="backdrop-blur-md">
        <div class="flex flex-col items-center py-6 text-center">
            <div class="size-16 rounded-full bg-error/10 text-error flex items-center justify-center mb-4">
                <x-ui::icon name="tabler.alert-triangle" class="size-8" />
            </div>
            <p class="text-base-content/70 leading-relaxed max-w-xs">{{ $this->deleteConfirmMessage }}</p>
        </div>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.cancel')" x-on:click="$wire.confirmModal = false" variant="tertiary" />
            <x-ui::button :label="__('ui::common.delete')" class="btn-error px-10 shadow-lg shadow-error/20" wire:click="remove('{{ $this->recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>

    {{-- Import Modal --}}
    <x-ui::modal wire:model="importModal" :title="__('ui::common.import')" class="backdrop-blur-md">
        <x-ui::form wire:submit.prevent="importCsv" class="space-y-6">
            <div class="rounded-2xl bg-base-200/50 p-4 border border-base-content/5">
                <div class="mb-4 flex items-center justify-between px-1">
                    <span class="text-xs font-bold uppercase tracking-widest text-base-content/40">{{ __('ui::common.select_file') }}</span>
                    <x-ui::button :label="__('ui::common.download_template')" icon="tabler.file-download" variant="tertiary" class="btn-xs hover:bg-base-100" wire:click="downloadTemplate" />
                </div>
                <x-ui::file wire:model="csvFile" accept=".csv" required />
                @if(!empty($this->importInstructions))
                    <div class="mt-4 p-3 rounded-xl bg-info/5 border border-info/10">
                        <p class="text-[11px] text-info/70 leading-relaxed italic">{{ $this->importInstructions }}</p>
                    </div>
                @endif
            </div>
            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" x-on:click="$wire.importModal = false" variant="tertiary" />
                <x-ui::button :label="__('ui::common.import')" type="submit" variant="primary" spinner="importCsv" class="px-10 shadow-lg shadow-primary/20" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>
</div>
