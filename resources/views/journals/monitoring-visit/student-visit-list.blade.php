<div>
    <x-ui::components.page-header
        :title="__('journals.visit_title')"
        :description="__('journals.visit_student_subtitle')"
    />

    <div class="space-y-4">
        @forelse ($this->visits as $visit)
            <x-ts-card class="bg-base-100 border-base-200 border">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-medium">{{ $visit->visit_date?->format('d M Y') }}</p>
                        <p class="text-base-content/70 text-sm">{{ $visit->method->label() }}</p>
                        @if ($visit->location)
                            <p class="text-base-content/50 text-sm">{{ $visit->location }}</p>
                        @endif
                    </div>
                    <x-ts-badge
                        :text="$visit->is_verified ? __('journals.verified') : __('journals.pending')"
                        :class="$visit->is_verified ? 'badge-success' : 'badge-warning'"
                    />
                </div>
            </x-ts-card>
        @empty
            <x-ts-alert :title="__('journals.no_visits')" icon="information-circle" class="bg-base-200" />
        @endforelse
    </div>
</div>
