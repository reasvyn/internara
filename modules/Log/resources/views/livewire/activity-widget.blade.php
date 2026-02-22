<div>
    <x-ui::card :title="__('log::ui.activity_feed')" shadow separator>
        <x-slot:menu>
            <x-ui::button 
                :label="__('ui::common.view_all')" 
                variant="metadata" 
                link="{{ route('admin.activities') }}" 
            />
        </x-slot:menu>

        <div class="space-y-4" role="log" aria-label="{{ __('log::ui.activity_feed') }}">
            @forelse($activities as $activity)
                <div class="flex flex-col gap-1 hover:bg-base-200/50 p-1 rounded transition-colors">
                    <div class="flex justify-between items-start">
                        <span class="font-bold text-xs">{{ $activity->causer?->name ?? __('log::ui.system') }}</span>
                        <span class="text-[9px] opacity-40 uppercase">
                            {{ $activity->created_at->diffForHumans(null, true) }}
                        </span>
                    </div>
                    <div class="text-[11px] leading-snug">
                        <span class="opacity-50 italic">[{{ $activity->log_name }}]</span>
                        {{ $activity->description }}
                    </div>
                </div>
            @empty
                <div class="text-center py-4 opacity-50 italic text-xs">
                    {{ __('log::ui.no_activities') }}
                </div>
            @endforelse
        </div>
    </x-ui::card>
</div>
