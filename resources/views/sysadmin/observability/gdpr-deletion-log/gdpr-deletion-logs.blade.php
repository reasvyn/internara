<div>
    <x-slot:title>GDPR Deletion Logs</x-slot:title>

    <x-core::ui.page-header :title="__('sysadmin.gdpr_logs.title')" />

    <x-mary-card>
        <div class="mb-4 flex gap-4">
            <x-ts-input
                wire:model.live.debounce.300ms="search"
                :placeholder="__('sysadmin.gdpr_logs.search_placeholder')"
                class="w-72"
            />
            <x-mary-select
                wire:model.live="filterType"
                :placeholder="__('sysadmin.gdpr_logs.type_placeholder')"
                class="w-48"
            >
                <option value="">All types</option>
                <option value="anonymization">Anonymization</option>
                <option value="permanent_deletion">Permanent Deletion</option>
            </x-mary-select>
        </div>

        <x-mary-table :headers="$headers" :rows="$logs" :sort-by="$sortBy" link="/admin/gdpr-logs/{id}" with-pagination>
            @scope('cell_deletion_type', $log)
                <x-ts-badge
                    :text="ucfirst(str_replace('_', ' ', $log->deletion_type))"
                    class="badge-{{ $log->deletion_type === 'permanent_deletion' ? 'error' : 'warning' }}"
                />
            @endscope
            @scope('cell_deleted_at', $log)
                {{ $log->deleted_at?->format('Y-m-d H:i') }}
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
