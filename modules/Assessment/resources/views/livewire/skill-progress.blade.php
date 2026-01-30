<x-ui::card title="{{ __('Perkembangan Kompetensi') }}" shadow separator>
    <div class="space-y-4">
        @forelse($stats as $stat)
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span>{{ $stat['name'] }}</span>
                    <span class="font-bold">{{ $stat['score'] }}%</span>
                </div>
                <progress class="progress progress-primary w-full" value="{{ $stat['score'] }}" max="100"></progress>
            </div>
        @empty
            <div class="text-center py-4 opacity-50 italic text-sm">
                {{ __('Belum ada data kompetensi yang tercatat.') }}
            </div>
        @endforelse
    </div>
</x-ui::card>
