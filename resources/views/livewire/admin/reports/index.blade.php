<div class="p-8">
    <x-layouts.manager 
        title="Report Management" 
        subtitle="View and generate system reports" 
        :rows="$this->rows()" 
        :headers="$this->headers()"
        :selected-count="$this->selected_count"
        :sort-by="$sortBy"
    >
        {{-- Top Actions --}}
        <x-slot:actions>
            <x-mary-button label="Generate New Report" icon="o-plus" class="btn-primary" wire:click="openGenerateModal" />
        </x-slot:actions>

        {{-- Filters --}}
        <x-slot:filters>
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
        </x-slot:filters>

        {{-- Bulk Actions --}}
        <x-slot:bulkActions>
            <x-mary-button 
                label="Delete Selected" 
                icon="o-trash" 
                class="btn-sm btn-error text-white font-bold rounded-lg" 
                wire:confirm="Delete selected report records? This will not delete physical files."
                wire:click="deleteSelected" 
            />
        </x-slot:bulkActions>

        {{-- Mass Actions --}}
        <x-slot:massActions>
            <x-mary-button 
                label="Clean All Failed Reports" 
                icon="o-no-symbol" 
                class="btn-sm btn-outline border-base-300 rounded-xl" 
                wire:confirm="This will delete ALL report records with 'Failed' status matching current filters. Continue?"
                wire:click="cleanFailedReports"
            />
        </x-slot:massActions>

        {{-- Table Cell Overrides --}}
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
    </x-layouts.manager>

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
