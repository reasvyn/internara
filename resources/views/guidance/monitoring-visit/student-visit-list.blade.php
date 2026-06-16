<div>
    <x-mary-header :title="__('guidance.visit_title')" :subtitle="__('guidance.visit_student_subtitle')" separator />

    <div class="space-y-4">
        @forelse($this->visits as $visit)
            <x-mary-card shadow class="bg-base-100 border border-base-200">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-medium">{{ $visit->visit_date?->format('d M Y') }}</p>
                        <p class="text-sm text-base-content/70">{{ $visit->method->label() }}</p>
                        @if($visit->location)
                            <p class="text-sm text-base-content/50">{{ $visit->location }}</p>
                        @endif
                    </div>
                    <x-mary-badge :value="$visit->is_verified ? __('guidance.verified') : __('guidance.pending')"
                        :class="$visit->is_verified ? 'badge-success' : 'badge-warning'" />
                </div>
            </x-mary-card>
        @empty
            <x-mary-alert :title="__('guidance.no_visits')" icon="o-information-circle" class="bg-base-200" />
        @endforelse
    </div>
</div>
