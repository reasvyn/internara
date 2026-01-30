<div>
    <x-ui::card title="{{ __('log::ui.activity_feed') }}" shadow separator>
        <div class="space-y-4">
            @forelse($activities as $activity)
                <div class="flex flex-col gap-1 p-2 hover:bg-base-200 rounded-lg transition-colors border-b border-base-300 last:border-0">
                    <div class="flex justify-between items-start">
                        <span class="font-bold text-sm">{{ $activity->causer?->name ?? 'System' }}</span>
                        <span class="text-[10px] opacity-50">{{ $activity->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="text-xs">
                        <x-ui::badge :label="$activity->log_name" class="badge-xs badge-outline mr-1" />
                        {{ $activity->description }}
                    </div>
                </div>
            @empty
                <div class="text-center py-4 opacity-50 italic text-sm">
                    {{ __('log::ui.no_activities') }}
                </div>
            @endforelse
        </div>
        
        @if($activities->hasPages())
            <div class="mt-4">
                {{ $activities->links() }}
            </div>
        @endif
    </x-ui::card>
</div>
