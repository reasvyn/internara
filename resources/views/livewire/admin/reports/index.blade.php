<div class="p-8">
    <x-mary-header title="Reports" subtitle="Generate and download system reports" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Generate Report" icon="o-plus" class="btn-primary" wire:click="openGenerateModal" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        <div class="mb-6 flex flex-col md:flex-row justify-between gap-4">
            <div class="w-full max-w-sm">
                <x-mary-input wire:model.live.debounce.300ms="search" placeholder="Search reports..." icon="o-magnifying-glass" clearable />
            </div>
            <div class="flex gap-2">
                <x-mary-select wire:model.live="filters.report_type" :options="$this->reportTypes" placeholder="Filter by type" icon="o-document" clearable />
                <x-mary-select wire:model.live="filters.status" :options="$this->statusOptions" placeholder="Filter by status" icon="o-flag" clearable />
            </div>
        </div>

        <x-mary-table :headers="$headers" :rows="$reports" with-pagination>
            @scope('cell_report_type', $report)
                <div class="capitalize font-medium text-sm">{{ str_replace('_', ' ', $report->report_type) }}</div>
            @endscope

            @scope('cell_status', $report)
                <div class="flex justify-center">
                    @if ($report->status === 'completed')
                        <x-mary-badge value="Completed" class="badge-success" />
                    @elseif ($report->status === 'failed')
                        <x-mary-badge value="Failed" class="badge-error" />
                    @else
                        <x-mary-badge value="Pending" class="badge-warning" />
                    @endif
                </div>
            @endscope

            @scope('cell_file_size', $report)
                @if ($report->file_size)
                    {{ number_format($report->file_size / 1024, 1) }} KB
                @else
                    —
                @endif
            @endscope

            @scope('cell_generated_at', $report)
                @if ($report->generated_at)
                    {{ $report->generated_at->format('M d, Y H:i') }}
                @else
                    —
                @endif
            @endscope

            @scope('actions', $report)
                <div class="flex justify-end gap-1">
                    @if ($report->isCompleted())
                        <x-mary-button icon="o-arrow-down-tray" class="btn-ghost btn-sm text-success" link="{{ route('admin.reports.download', $report) }}" />
                    @endif
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- Generate Report Modal --}}
    <x-mary-modal wire:model="generateModal" title="Generate New Report" separator>
        <div class="space-y-6">
            <x-mary-select label="Report Type" wire:model="reportData.report_type" :options="$this->reportTypes" placeholder="Select report type" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input label="Date From" type="date" wire:model="reportData.date_from" />
                <x-mary-input label="Date To" type="date" wire:model="reportData.date_to" />
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.generateModal = false" />
            <x-mary-button label="Generate" class="btn-primary" wire:click="generateReport" spinner="generateReport" />
        </x-slot:actions>
    </x-mary-modal>
</div>
