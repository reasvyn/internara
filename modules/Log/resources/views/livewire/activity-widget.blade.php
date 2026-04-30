<div>
    <x-ui::card :title="__('log::ui.activity_feed')" shadow separator class="bg-base-100/50">
        <x-slot:menu>
            <x-ui::button 
                :label="__('ui::common.view_all')" 
                variant="metadata" 
                class="btn-xs"
                link="{{ route('admin.activities') }}" 
            />
        </x-slot:menu>

        <div class="space-y-3" role="log" aria-label="{{ __('log::ui.activity_feed') }}">
            @forelse($activities as $activity)
                <div class="group flex items-start gap-3 p-2 rounded-xl hover:bg-base-200/50 transition-all duration-200">
                    <div class="mt-1">
                        <x-ui::avatar :image="$activity->causer?->avatar_url" :title="$activity->causer?->name" size="w-8 h-8" />
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-center mb-0.5">
                            <span class="font-bold text-xs truncate">{{ $activity->causer?->name ?? __('log::ui.system') }}</span>
                            <span class="text-[9px] opacity-40 whitespace-nowrap ml-2">
                                {{ $activity->created_at->diffForHumans(null, true) }}
                            </span>
                        </div>
                        
                        <div class="text-[11px] leading-relaxed text-base-content/70 line-clamp-2">
                            <span class="text-[9px] font-black uppercase opacity-30 mr-1 tracking-tighter">
                                {{ $activity->log_name }}
                            </span>
                            {{ $activity->description }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center py-8 opacity-30 italic text-xs">
                    <x-ui::icon name="tabler.history-off" class="w-8 h-8 mb-2" />
                    {{ __('log::ui.no_activities') }}
                </div>
            @endforelse
        </div>
    </x-ui::card>
</div>
