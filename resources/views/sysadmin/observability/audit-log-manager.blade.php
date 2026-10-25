<x-ui::components.record-manager :title="__('sysadmin.activity_title')" :subtitle="__('sysadmin.activity_subtitle')">
    <x-slot:filters>
        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('sysadmin.activity_filter_user_label') }}</label>
        <x-ts-select.native
            wire:model.live="filters.user"
            :options="ts_options($users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]), __('sysadmin.activity_filter_user'))"
        />

        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('sysadmin.activity_filter_module_label') }}</label>
        <x-ts-select.native
            wire:model.live="filters.module"
            :options="ts_options($modules->map(fn ($m) => ['id' => $m, 'name' => ucfirst($m)]), __('sysadmin.activity_filter_module'))"
        />

        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('sysadmin.activity_filter_action_label') }}</label>
        <x-ts-select.native
            wire:model.live="filters.action"
            :options="ts_options($actions->map(fn ($a) => ['id' => $a, 'name' => ucfirst($a)]), __('sysadmin.activity_filter_action'))"
        />
    </x-slot:filters>

    <x-ts-table :headers="$this->headers()" :rows="$this->rows()" :sort-by="$sortBy" with-pagination class="table-sm">
        @interact('column_created_at', $log)
            <span class="text-sm whitespace-nowrap">{{ $log->created_at->format('d M Y H:i:s') }}</span>
        @endinteract

        @interact('column_causer_name', $log)
            @if ($log->causer)
                <div class="flex flex-col py-1">
                    <span class="text-sm font-medium">{{ $log->causer->name }}</span>
                    <span class="font-mono text-xs opacity-50">{{ $log->causer->email }}</span>
                </div>
            @else
                <span class="text-xs opacity-50">{{ __('sysadmin.activity_system') }}</span>
            @endif
        @endinteract

        @interact('column_description', $log)
            <x-ts-badge :text="ucfirst($log->description)" xs color="gray" />
        @endinteract

        @interact('column_log_name', $log)
            <span class="text-sm">{{ $log->log_name ? ucfirst($log->log_name) : '-' }}</span>
        @endinteract

        @interact('column_subject', $log)
            @if ($log->subject)
                <div class="text-xs">
                    <span class="opacity-50">{{ class_basename($log->subject_type) }}</span>
                    <span class="ml-1 font-mono">{{ \Illuminate\Support\Str::limit((string) $log->subject_id, 8) }}</span>
                </div>
            @else
                -
            @endif
        @endinteract

        @interact('column_ip', $log)
            <span class="font-mono text-xs">{{ $log->properties->get('ip_address', '-') ?? '-' }}</span>
        @endinteract
    </x-ts-table>

    <x-slot:emptyState>
        <x-ui::widgets.empty-state
            icon="shield-check"
            :title="__('sysadmin.activity_no_entries')"
            :description="__('sysadmin.activity_adjust_filters')"
        />
    </x-slot:emptyState>

    <x-slot:auxiliary>
        @include('sysadmin.observability.components.audit-log-guide')
    </x-slot:auxiliary>
</x-ui::components.record-manager>
