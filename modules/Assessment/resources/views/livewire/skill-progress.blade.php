<x-ui::card :title="__('assessment::ui.skill_progress.title')" shadow separator>
    <div class="space-y-6">
        @forelse($stats as $stat)
            <div data-aos="fade-up">
                <div class="flex justify-between text-xs font-bold uppercase tracking-wider mb-2 opacity-70">
                    <span>{{ $stat['name'] }}</span>
                    <span>{{ $stat['score'] }}%</span>
                </div>
                <progress class="progress progress-primary w-full h-2.5 rounded-full" value="{{ $stat['score'] }}" max="100"></progress>
            </div>
        @empty
            <div class="text-center py-8 opacity-50 italic text-sm" data-aos="fade-in">
                {{ __('assessment::ui.skill_progress.no_data') }}
            </div>
        @endforelse
    </div>
</x-ui::card>
