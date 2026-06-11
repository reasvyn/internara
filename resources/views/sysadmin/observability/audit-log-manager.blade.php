<div class="p-8">
    <x-mary-header :title="__('sysadmin.activity_title')" :subtitle="__('sysadmin.activity_subtitle')" separator progress-indicator />

    <x-mary-card shadow class="bg-base-100 border border-base-200 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-mary-select
                label="Filter by User"
                wire:model="filterUser"
                :options="$users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])"
                option-value="id"
                option-label="name"
                :placeholder="__('sysadmin.activity_filter_user')"
                clearable
            />

            <x-mary-select
                label="Filter by Module"
                wire:model="filterModule"
                :options="$modules->map(fn ($m) => ['id' => $m, 'name' => ucfirst($m)])"
                option-value="id"
                option-label="name"
                :placeholder="__('sysadmin.activity_filter_module')"
                clearable
            />

            <x-mary-select
                label="Filter by Action"
                wire:model="filterAction"
                :options="$actions->map(fn ($a) => ['id' => $a, 'name' => ucfirst($a)])"
                option-value="id"
                option-label="name"
                :placeholder="__('sysadmin.activity_filter_action')"
                clearable
            />
        </div>

        <div class="flex justify-end mt-4">
            <x-mary-button label="Reset Filters" icon="o-x-mark" class="btn-ghost" wire:click="resetFilters" />
        </div>
    </x-mary-card>

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        @if ($logs->isEmpty())
            <div class="text-center py-8 opacity-60">
                <x-mary-icon name="o-shield-check" class="w-12 h-12 mx-auto mb-3" />
                <p class="text-lg">No activity log entries found.</p>
                @if ($filterUser || $filterModule || $filterAction)
                    <p class="text-sm">Try adjusting your filters.</p>
                @endif
            </div>
        @else
            @php
                $headers = [
                    ['key' => 'timestamp', 'label' => 'Timestamp'],
                    ['key' => 'user', 'label' => 'User'],
                    ['key' => 'action', 'label' => 'Action'],
                    ['key' => 'module', 'label' => 'Module'],
                    ['key' => 'subject', 'label' => 'Subject'],
                    ['key' => 'ip', 'label' => 'IP Address'],
                ];
            @endphp

            <x-mary-table :headers="$headers" :rows="$logs" with-pagination>
                @scope('cell_timestamp', $log)
                    <div class="text-sm whitespace-nowrap">
                        {{ $log->created_at->format('d M Y H:i:s') }}
                    </div>
                @endscope

                @scope('cell_user', $log)
                    @if ($log->causer)
                        <div>
                            <div class="font-medium">{{ $log->causer->name }}</div>
                            <div class="text-xs opacity-50">{{ $log->causer->email }}</div>
                        </div>
                    @else
                        <span class="text-xs opacity-50">System</span>
                    @endif
                @endscope

                @scope('cell_action', $log)
                    <x-mary-badge :value="ucfirst($log->description)" class="badge-ghost" />
                @endscope

                @scope('cell_module', $log)
                    {{ $log->log_name ? ucfirst($log->log_name) : '-' }}
                @endscope

                @scope('cell_subject', $log)
                    @if ($log->subject)
                        <div class="text-xs">
                            <span class="opacity-50">{{ class_basename($log->subject_type) }}</span>
                            <span class="font-mono ml-1">{{ Str::limit($log->subject_id, 8) }}</span>
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
    </x-mary-card>
</div>
