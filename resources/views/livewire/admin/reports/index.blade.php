<div class="p-8">
    {{-- Header Section --}}
    <x-mary-header title="Report Management" subtitle="View and generate system reports" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Generate New Report" icon="o-plus" class="btn-primary" wire:click="openGenerateModal" />
        </x-slot:actions>
    </x-mary-header>

    {{-- Controls Section --}}
    <div class="mb-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div class="w-full lg:max-w-md">
            <x-mary-input 
                wire:model.live.debounce.300ms="search" 
                placeholder="{{ __('Search records...') }}" 
                icon="o-magnifying-glass" 
                clearable 
                class="rounded-2xl border-base-300 focus:border-primary transition-all duration-300 shadow-sm"
            />
        </div>
        <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
            <x-mary-select 
                wire:model.live="filters.report_type" 
                :options="$this->reportTypes" 
                placeholder="All Types" 
                icon="o-document-chart-bar" 
                clearable 
                class="rounded-xl border-base-300"
            />
            <x-mary-select 
                wire:model.live="filters.status" 
                :options="$this->statusOptions" 
                placeholder="All Status" 
                icon="o-flag" 
                clearable 
                class="rounded-xl border-base-300"
            />
        </div>
    </div>

    {{-- Selection Bar --}}
    @if($this->selected_count > 0)
        <div class="mb-6 p-4 bg-primary/5 border border-primary/20 rounded-[2rem] flex flex-col sm:flex-row items-center justify-between gap-4 animate-in fade-in slide-in-from-top-2 duration-500 shadow-xl shadow-primary/5">
            <div class="flex items-center gap-4">
                <div class="size-12 rounded-2xl bg-primary text-primary-content flex items-center justify-center font-black shadow-lg shadow-primary/20">
                    {{ $this->selected_count }}
                </div>
                <div class="text-center sm:text-left">
                    <h4 class="font-black text-sm text-primary uppercase tracking-tight">{{ __('Records Selected') }}</h4>
                    <p class="text-[10px] uppercase font-black tracking-widest opacity-40">{{ __('Apply bulk operations') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex gap-2">
                    <x-mary-button 
                        label="Delete Selected" 
                        icon="o-trash" 
                        class="btn-sm btn-error text-white font-bold rounded-lg" 
                        wire:confirm="Delete selected report records? This will not delete physical files."
                        wire:click="deleteSelected" 
                    />
                    <x-mary-button 
                        label="Clean All Failed Reports" 
                        icon="o-no-symbol" 
                        class="btn-sm btn-outline border-base-300 rounded-xl" 
                        wire:confirm="This will delete ALL report records with 'Failed' status matching current filters. Continue?"
                        wire:click="cleanFailedReports"
                    />
                </div>
                <div class="divider divider-horizontal mx-1"></div>
                <x-mary-button 
                    label="{{ __('Cancel') }}" 
                    wire:click="clearSelection" 
                    class="btn-sm btn-ghost rounded-xl font-black uppercase tracking-widest text-[10px]" 
                />
            </div>
        </div>
    @endif

    {{-- Table Section --}}
    <x-mary-card shadow class="card-enterprise">
        <div class="table-enterprise">
            <x-mary-table 
                :headers="$this->headers()" 
                :rows="$this->rows()" 
                :sort-by="$sortBy"
                with-pagination 
                selectable
                wire:model="selectedIds"
                class="table-sm"
            >
                @scope('cell_report_type', $report)
                    <div class="flex flex-col">
                        <span class="font-bold text-sm uppercase tracking-tight">{{ str_replace('_', ' ', $report->report_type) }}</span>
                        <span class="text-[10px] opacity-40 font-mono">{{ $report->id }}</span>
                    </div>
                @endscope

                @scope('cell_status', $report)
                    @php
                        $statusClass = match($report->status) {
                            'completed' => 'badge-success',
                            'failed' => 'badge-error',
                            default => 'badge-warning',
                        };
                    @endphp
                    <x-mary-badge :value="$report->status" class="{{ $statusClass }} font-black text-[10px] uppercase" />
                @endscope

                @scope('cell_file_size', $report)
                    <span class="text-xs font-mono opacity-60">
                        {{ $report->file_size ? number_format($report->file_size / 1024, 2) . ' KB' : '-' }}
                    </span>
                @endscope

                @scope('cell_generated_at', $report)
                    <span class="text-xs">
                        {{ $report->generated_at ? $report->generated_at->diffForHumans() : 'Pending' }}
                    </span>
                @endscope

                @scope('actions', $report)
                    <div class="flex justify-end gap-1">
                        @if($report->status === 'completed')
                            <x-mary-button icon="o-arrow-down-tray" class="btn-ghost btn-sm text-primary" link="{{ route('admin.reports.download', $report) }}" />
                        @endif
                        <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:confirm="Are you sure?" wire:click="delete('{{ $report->id }}')" />
                    </div>
                @endscope
            </x-mary-table>
        </div>
    </x-mary-card>

    {{-- Generate Modal --}}
    <x-mary-modal wire:model="generateModal" title="Generate Report" separator>
        <div class="space-y-6">
            <x-mary-select 
                label="Report Type" 
                wire:model="formData.report_type" 
                :options="$this->reportTypes" 
                placeholder="Select report type" 
                icon="o-document-chart-bar" 
                class="rounded-xl border-base-300"
            />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-datepicker label="From Date (Optional)" wire:model="formData.date_from" icon="o-calendar" class="rounded-xl border-base-300" />
                <x-mary-datepicker label="To Date (Optional)" wire:model="formData.date_to" icon="o-calendar" class="rounded-xl border-base-300" />
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.generateModal = false" class="rounded-xl" />
            <x-mary-button label="Generate" class="btn-primary rounded-xl font-bold uppercase tracking-widest" wire:click="generateReport" spinner="generateReport" />
        </x-slot:actions>
    </x-mary-modal>
</div>
