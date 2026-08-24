<div class="p-8">
    <x-mary-header
        :title="__('sysadmin.activity_title')"
        :subtitle="__('sysadmin.activity_subtitle')"
        separator
        progress-indicator
    />

    <x-ts-card class="bg-base-100 border-base-200 mb-6 border">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-ts-select.native
                :label="__('sysadmin.activity_filter_user_label')"
                wire:model="filterUser"
                :options="[null => __('sysadmin.activity_filter_user')] + ($users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]))"
                option-value="id"
                option-label="name"
                clearable
            />

            <x-ts-select.native
                :label="__('sysadmin.activity_filter_module_label')"
                wire:model="filterModule"
                :options="[null => __('sysadmin.activity_filter_module')] + ($modules->map(fn ($m) => ['id' => $m, 'name' => ucfirst($m)]))"
                option-value="id"
                option-label="name"
                clearable
            />

            <x-ts-select.native
                :label="__('sysadmin.activity_filter_action_label')"
                wire:model="filterAction"
                :options="[null => __('sysadmin.activity_filter_action')] + ($actions->map(fn ($a) => ['id' => $a, 'name' => ucfirst($a)]))"
                option-value="id"
                option-label="name"
                clearable
            />
        </div>

        <div class="mt-4 flex justify-end">
            <x-ts-button
                :text="__('sysadmin.activity_reset_filters')"
                icon="x-mark"
                color="white"
                wire:click="resetFilters"
            />
        </div>

        <x-ts-card class="bg-base-100 border-base-200 border">
            @if ($logs->isEmpty())
                <div class="py-8 text-center opacity-60">
                    <x-ts-icon name="shield-check" class="mx-auto mb-3 h-12 w-12" />
                    <p class="text-lg">{{ __('sysadmin.activity_no_entries') }}</p>
                    @if ($filterUser || $filterModule || $filterAction)
                        <p class="text-sm">{{ __('sysadmin.activity_adjust_filters') }}</p>
                    @endif
                </div>
            @else
                @php
                    $headers = [
                        ['key' => 'timestamp', 'label' => __('sysadmin.activity_timestamp')],
                        ['key' => 'user', 'label' => __('sysadmin.activity_user')],
                        ['key' => 'action', 'label' => __('sysadmin.activity_action')],
                        ['key' => 'module', 'label' => __('sysadmin.activity_module')],
                        ['key' => 'subject', 'label' => __('sysadmin.activity_subject')],
                        ['key' => 'ip', 'label' => __('sysadmin.activity_ip')],
                    ];
                @endphp

                <x-mary-table :headers="$headers" :rows="$logs" with-pagination>
                    @scope('cell_timestamp', $log)
                        <div class="text-sm whitespace-nowrap">{{ $log->created_at->format('d M Y H:i:s') }}</div>
                    @endscope

                    @scope('cell_user', $log)
                        @if ($log->causer)
                            <div>
                                <div class="font-medium">{{ $log->causer->name }}</div>
                                <div class="text-xs opacity-50">{{ $log->causer->email }}</div>
                            </div>
                        @else
                            <span class="text-xs opacity-50">{{ __('sysadmin.activity_system') }}</span>
                        @endif
                    @endscope

                    @scope('cell_action', $log)
                        <x-ts-badge :text="ucfirst($log->description)" class="badge-ghost" />
                    @endscope

                    @scope('cell_module', $log)
                        {{ $log->log_name ? ucfirst($log->log_name) : '-' }}
                    @endscope

                    @scope('cell_subject', $log)
                        @if ($log->subject)
                            <div class="text-xs">
                                <span class="opacity-50">{{ class_basename($log->subject_type) }}</span>
                                <span class="ml-1 font-mono">{{ Str::limit($log->subject_id, 8) }}</span>
                            </div>
                        @else
                            -
                        @endif
                    @endscope

                    @scope('cell_ip', $log)
                        <span class="font-mono text-xs">{{ $log->properties->get('ip_address', '-') ?? '-' }}</span>
                    @endscope
                </x-mary-table>
            @endif

            @include('sysadmin.observability.components.audit-log-guide')
</div>
