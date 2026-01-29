<div>
    <x-ui::main title="{{ __('log::ui.activity_feed') }}" subtitle="{{ __('log::ui.activity_feed_subtitle') }}">
        <x-ui::card shadow separator>
            <x-ui::table :headers="[
                ['key' => 'created_at', 'label' => __('shared::ui.time')],
                ['key' => 'causer.name', 'label' => __('log::ui.user')],
                ['key' => 'log_name', 'label' => __('log::ui.category')],
                ['key' => 'description', 'label' => __('log::ui.activity')],
            ]" :rows="$activities" with-pagination>
                @scope('cell_created_at', $activity)
                    {{ $activity->created_at->diffForHumans() }}
                @endscope
                
                @scope('cell_log_name', $activity)
                    <x-ui::badge :label="$activity->log_name" class="badge-outline" />
                @endscope

                @scope('cell_causer.name', $activity)
                    {{ $activity->causer?->name ?? 'System' }}
                @endscope
            </x-ui::table>
        </x-ui::card>
    </x-ui::main>
</div>
