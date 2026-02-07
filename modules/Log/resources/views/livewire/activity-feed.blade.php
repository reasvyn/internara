<div>
    <x-ui::card :title="__('log::ui.activity_feed')" :subtitle="__('log::ui.activity_feed_subtitle')" shadow separator>
        <div class="space-y-4" role="log" aria-label="{{ __('log::ui.activity_feed') }}">
            @forelse($activities as $activity)
                <div class="flex flex-col gap-1 p-2 hover:bg-base-200 rounded-lg transition-colors border-b border-base-300 last:border-0">
                    <div class="flex justify-between items-start">
                        <span class="font-bold text-sm">{{ $activity->causer?->name ?? __('log::ui.system') }}</span>
                        <span class="text-[10px] opacity-50" title="{{ $activity->created_at->toDayDateTimeString() }}">
                            {{ $activity->created_at->diffForHumans() }}
                        </span>
                    </div>
                    <div class="text-xs">
                        <x-ui::badge :label="$activity->log_name" priority="secondary" class="badge-xs mr-1" />
                        {{ $activity->description }}
                    </div>
                </div>
            @empty
                <div class="text-center py-8 opacity-50 italic text-sm">
                    {{ __('log::ui.no_activities') }}
                </div>
            @endforelse
        </div>
        
        @if($activities->hasPages())
            <div class="mt-6 border-t border-base-200 pt-4">
                {{ $activities->links() }}
            </div>
        @endif
    </x-ui::card>
</div>
