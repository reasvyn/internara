<div>
    <x-ui::card :title="__('schedule::ui.timeline_title')" shadow separator>
        @if($schedules->isEmpty())
            <div class="text-center py-12 opacity-50" role="status" >
                <x-ui::icon name="tabler.calendar-off" class="size-12 mx-auto mb-3" aria-hidden="true" />
                <p class="font-medium">{{ __('schedule::ui.empty_timeline') }}</p>
            </div>
        @else
            <div class="relative" role="list" aria-label="{{ __('schedule::ui.timeline_title') }}">
                {{-- Vertical Line --}}
                <div class="absolute left-3 top-0 bottom-0 w-0.5 bg-base-300" aria-hidden="true"></div>

                <div class="space-y-10">
                    @foreach($schedules as $index => $schedule)
                        <div class="relative pl-10" role="listitem"  >
                            {{-- Bullet --}}
                            <div @class([
                                'absolute left-0 top-1 size-6 rounded-full border-4 border-base-100 flex items-center justify-center shadow-sm',
                                'bg-primary' => $schedule->type === 'briefing',
                                'bg-info' => $schedule->type === 'event',
                                'bg-error' => $schedule->type === 'deadline',
                            ]) aria-hidden="true">
                                <x-ui::icon name="tabler.point-filled" class="size-2 text-white" />
                            </div>

                            <div>
                                <div class="text-[10px] font-black uppercase tracking-widest opacity-60 flex items-center gap-2">
                                    <time datetime="{{ $schedule->start_at->toIso8601String() }}">
                                        {{ $schedule->start_at->translatedFormat('d M Y') }}
                                    </time>
                                    @if($schedule->start_at->isToday())
                                        <x-ui::badge :value="__('schedule::ui.today')" variant="primary" class="badge-xs" />
                                    @endif
                                </div>
                                <h3 class="font-bold text-lg leading-tight mt-1">{{ $schedule->title }}</h3>
                                @if($schedule->description)
                                    <p class="text-sm opacity-70 mt-2 leading-relaxed">{{ $schedule->description }}</p>
                                @endif
                                @if($schedule->location)
                                    <div class="flex items-center gap-1.5 mt-3 text-xs font-semibold opacity-60">
                                        <x-ui::icon name="tabler.map-pin" class="size-3.5" aria-hidden="true" />
                                        <span>{{ $schedule->location }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-ui::card>
</div>