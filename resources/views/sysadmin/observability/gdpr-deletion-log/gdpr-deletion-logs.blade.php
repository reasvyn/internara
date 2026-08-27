<div>
    <x-slot:title>GDPR Deletion Logs</x-slot:title>

    <x-ui::ui.page-header :title="__('sysadmin.gdpr_logs.title')" />

    <x-ts-card shadowless>
        <div class="mb-4 flex gap-4">
            <x-ts-input
                wire:model.live.debounce.300ms="search"
                :placeholder="__('sysadmin.gdpr_logs.search_placeholder')"
                class="w-72"
            />
            <x-ts-select.native wire:model.live="filterType" class="w-48">
                <option value="">All types</option>
                <option value="anonymization">Anonymization</option>
                <option value="permanent_deletion">Permanent Deletion</option>
            </x-ts-select.native>
        </div>

        <x-ts-table :headers="$headers" :rows="$logs" :sort="$sortBy" link="/admin/gdpr-logs/{id}" paginate>
            @interact('column_deletion_type', $log)
                <x-ts-badge
                    :text="ucfirst(str_replace('_', ' ', $log->deletion_type))"
                    class="badge-{{ $log->deletion_type === 'permanent_deletion' ? 'error' : 'warning' }}"
                />
            @endinteract
            @interact('column_deleted_at', $log)
                {{ $log->deleted_at?->format('Y-m-d H:i') }}
            @endinteract
        </x-ts-table>
</div>
