<div class="p-8">
    <x-ui::components.page-header
        :title="__('sysadmin.activity_title')"
        :description="__('sysadmin.activity_subtitle')"
    />

    <x-ts-card class="bg-base-100 border-base-200 mb-6 border">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-ts-select.native
                :label="__('sysadmin.activity_filter_user_label')"
                wire:model="filterUser"
                :options="ts_options($users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]), __('sysadmin.activity_filter_user'))"
                clearable
            />

            <x-ts-select.native
                :label="__('sysadmin.activity_filter_module_label')"
                wire:model="filterModule"
                :options="ts_options($modules->map(fn ($m) => ['id' => $m, 'name' => ucfirst($m)]), __('sysadmin.activity_filter_module'))"
                clearable
            />

            <x-ts-select.native
                :label="__('sysadmin.activity_filter_action_label')"
                wire:model="filterAction"
                :options="ts_options($actions->map(fn ($a) => ['id' => $a, 'name' => ucfirst($a)]), __('sysadmin.activity_filter_action'))"
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
                        ['index' => 'timestamp', 'label' => __('sysadmin.activity_timestamp')],
                        ['index' => 'user', 'label' => __('sysadmin.activity_user')],
                        ['index' => 'action', 'label' => __('sysadmin.activity_action')],
                        ['index' => 'module', 'label' => __('sysadmin.activity_module')],
                        ['index' => 'subject', 'label' => __('sysadmin.activity_subject')],
                        ['index' => 'ip', 'label' => __('sysadmin.activity_ip')],
                    ];
                @endphp

                <x-ts-table :headers="$headers" :rows="$logs" paginate>
                    @interact('column_timestamp', $log)
                        <div class="text-sm whitespace-nowrap">{{ $log->created_at->format('d M Y H:i:s') }}</div>
                    @endinteract

                    @interact('column_user', $log)
                        @if ($log->causer)
                            <div>
                                <div class="font-medium">{{ $log->causer->name }}</div>
                                <div class="text-xs opacity-50">{{ $log->causer->email }}</div>
                            </div>
                        @else
                            <span class="text-xs opacity-50">{{ __('sysadmin.activity_system') }}</span>
                        @endif
                    @endinteract

                    @interact('column_action', $log)
                        <x-ts-badge :text="ucfirst($log->description)" class="badge-ghost" />
                    @endinteract

                    @interact('column_module', $log)
                        {{ $log->log_name ? ucfirst($log->log_name) : '-' }}
                    @endinteract

                    @interact('column_subject', $log)
                        @if ($log->subject)
                            <div class="text-xs">
                                <span class="opacity-50">{{ class_basename($log->subject_type) }}</span>
                                <span class="ml-1 font-mono">{{ Str::limit($log->subject_id, 8) }}</span>
                            </div>
                        @else
                            -
                        @endif
                    @endinteract

                    @interact('column_ip', $log)
                        <span class="font-mono text-xs">{{ $log->properties->get('ip_address', '-') ?? '-' }}</span>
                    @endinteract
                </x-ts-table>
            @endif

            @include('sysadmin.observability.components.audit-log-guide')
        </x-ts-card>
    </x-ts-card>
</div>
