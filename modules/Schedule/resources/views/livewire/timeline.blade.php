<div>
    <x-ui::card title="{{ __('schedule::ui.timeline_title') }}" shadow separator>
        @if($schedules->isEmpty())
            <div class="text-center py-8 opacity-50" role="status">
                <x-ui::icon name="tabler.calendar-off" class="w-12 h-12 mx-auto mb-2" aria-hidden="true" />
                <p>{{ __('schedule::ui.empty_timeline') }}</p>
            </div>
        @else
            <div class="relative" role="list" aria-label="{{ __('schedule::ui.timeline_title') }}">
                {{-- Vertical Line --}}
                <div class="absolute left-3 top-0 bottom-0 w-0.5 bg-base-300" aria-hidden="true"></div>

                <div class="space-y-8">
                    @foreach($schedules as $schedule)
                        <div class="relative pl-10" role="listitem">
                            {{-- Bullet --}}
                            <div @class([
                                'absolute left-0 top-1 w-6 h-6 rounded-full border-4 border-base-100 flex items-center justify-center',
                                'bg-primary' => $schedule->type === 'briefing',
                                'bg-info' => $schedule->type === 'event',
                                'bg-error' => $schedule->type === 'deadline',
                            ]) aria-hidden="true">
                                <x-ui::icon name="tabler.point-filled" class="w-2 h-2 text-white" />
                            </div>

                            <div>
                                <div class="text-xs font-bold uppercase tracking-wider opacity-60">
                                    <time datetime="{{ $schedule->start_at->toIso8601String() }}">
                                        {{ $schedule->start_at->translatedFormat('d M Y') }}
                                    </time>
                                    @if($schedule->start_at->isToday())
                                        <span class="badge badge-primary badge-xs ml-1">{{ __('schedule::ui.today') }}</span>
                                    @endif
                                </div>
                                <h3 class="font-black text-base leading-tight">{{ $schedule->title }}</h3>
                                @if($schedule->description)
                                    <p class="text-sm opacity-70 mt-1">{{ $schedule->description }}</p>
                                @endif
                                @if($schedule->location)
                                    <div class="flex items-center gap-1 mt-2 text-xs opacity-60">
                                        <x-ui::icon name="tabler.map-pin" class="w-3 h-3" aria-hidden="true" />
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