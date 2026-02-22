<div>
    <x-ui::header 
        :title="__('admin::ui.menu.job_monitor')" 
        :subtitle="__('admin::ui.dashboard.job_monitor_subtitle', 'Monitor and manage background processing tasks.')" 
        :context="'admin::ui.menu.job_monitor'"
    >
        <x-slot:actions>
            @if($tab === 'failed')
                <x-ui::button label="{{ __('Flush All Failed') }}" icon="tabler.trash" class="btn-error" wire:click="flush" confirm="{{ __('Are you sure you want to delete all failed jobs?') }}" />
            @endif
        </x-slot:actions>
    </x-ui::header>

    <x-ui::tabs wire:model="tab">
            <x-ui::tab name="pending" label="{{ __('Pending Jobs') }}" icon="tabler.loader" />
            <x-ui::tab name="failed" label="{{ __('Failed Jobs') }}" icon="tabler.alert-triangle" />
        </x-ui::tabs>

        <x-ui::card class="mt-4">
            @if($tab === 'pending')
                <x-ui::table :headers="[
                    ['key' => 'id', 'label' => 'ID'],
                    ['key' => 'queue', 'label' => 'Queue'],
                    ['key' => 'attempts', 'label' => 'Attempts'],
                    ['key' => 'reserved_at', 'label' => 'Reserved'],
                    ['key' => 'available_at', 'label' => 'Available'],
                ]" :rows="$pendingJobs" with-pagination>
                    @scope('cell_available_at', $job)
                        {{ \Carbon\Carbon::createFromTimestamp($job->available_at)->diffForHumans() }}
                    @endscope
                    @scope('cell_reserved_at', $job)
                        {{ $job->reserved_at ? \Carbon\Carbon::createFromTimestamp($job->reserved_at)->diffForHumans() : '-' }}
                    @endscope
                </x-ui::table>
            @else
                <x-ui::table :headers="[
                    ['key' => 'uuid', 'label' => 'UUID'],
                    ['key' => 'queue', 'label' => 'Queue'],
                    ['key' => 'failed_at', 'label' => 'Failed At'],
                    ['key' => 'exception', 'label' => 'Error'],
                ]" :rows="$failedJobs" with-pagination>
                    @scope('cell_exception', $job)
                        <div class="max-w-xs truncate text-xs" title="{{ $job->exception }}">
                            {{ Str::limit($job->exception, 100) }}
                        </div>
                    @endscope
                    @scope('actions', $job)
                        <div class="flex gap-2">
                            <x-ui::button icon="tabler.refresh" class="btn-ghost btn-sm text-primary" wire:click="retry('{{ $job->uuid }}')" tooltip="{{ __('Retry') }}" />
                            <x-ui::button icon="tabler.trash" class="btn-ghost btn-sm text-error" wire:click="forget('{{ $job->uuid }}')" tooltip="{{ __('Delete') }}" />
                        </div>
                    @endscope
                </x-ui::table>
            @endif
        </x-ui::card>
</div>
