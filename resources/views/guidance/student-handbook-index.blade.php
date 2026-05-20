<div>
    <div class="mb-6">
        <h2 class="text-xl font-bold">{{ __('dashboard.title') }}</h2>
        <p class="text-sm text-base-content/50">{{ __('handbook.student_subtitle') }}</p>
    </div>

    <div class="space-y-4">
        @forelse($handbooks as $handbook)
            <x-mary-card class="bg-base-100 border border-base-content/10">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold truncate">{{ $handbook->title }}</h3>
                        <p class="text-xs text-base-content/50 mt-1">
                            {{ __('handbook.version') }} {{ $handbook->version }} &middot;
                            {{ $handbook->published_at?->format('d M Y') ?? '-' }}
                        </p>
                        @if($handbook->content)
                            <div class="mt-3 text-sm text-base-content/70 prose prose-sm max-w-none">
                                {!! Str::markdown($handbook->content) !!}
                            </div>
                        @endif
                    </div>
                    <div class="shrink-0">
                        @php
                            $acknowledged = $handbook->acknowledgements->isNotEmpty();
                        @endphp
                        @if($acknowledged)
                            <x-mary-badge :value="__('handbook.acknowledged')" class="badge-success" />
                        @else
                            <x-mary-button
                                wire:click="acknowledge('{{ $handbook->id }}')"
                                :label="__('handbook.acknowledge')"
                                class="btn-primary btn-sm"
                            />
                        @endif
                    </div>
                </div>
            </x-mary-card>
        @empty
            <div class="flex flex-col items-center justify-center py-12 text-base-content/20">
                <x-mary-icon name="o-book-open" class="size-12 mb-3" />
                <span class="text-xs font-medium">{{ __('handbook.no_handbooks') }}</span>
            </div>
        @endforelse
    </div>
</div>
