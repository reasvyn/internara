<div>
    <x-slot:title>GDPR Deletion Logs</x-slot:title>

    <x-shared::ui.page-header title="GDPR Deletion Logs" />

    <x-mary-card>
        <div class="flex gap-4 mb-4">
            <x-mary-input wire:model.live.debounce.300ms="search" placeholder="Search by email..." class="w-72" />
            <x-mary-select wire:model.live="filterType" placeholder="All types" class="w-48">
                <option value="">All types</option>
                <option value="anonymization">Anonymization</option>
                <option value="permanent_deletion">Permanent Deletion</option>
            </x-mary-select>
        </div>

        <x-mary-table :headers="$headers" :rows="$logs" :sort-by="$sortBy" link="/admin/gdpr-logs/{id}" with-pagination>
            @scope('cell_deletion_type', $log)
                <x-mary-badge :value="ucfirst(str_replace('_', ' ', $log->deletion_type))" class="badge-{{ $log->deletion_type === 'permanent_deletion' ? 'error' : 'warning' }}" />
            @endscope
            @scope('cell_deleted_at', $log)
                {{ $log->deleted_at?->format('Y-m-d H:i') }}
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
