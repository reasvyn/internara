<x-ui::page-header :title="__('placement_change.request_title')" :subtitle="__('placement_change.request_subtitle')" />

<div class="max-w-2xl mx-auto mt-6">
    @if(!$registration)
        <x-mary-card>
            <div class="p-6 text-center">
                <x-mary-icon name="o-information-circle" class="text-base-content/40 w-12 h-12 mx-auto mb-3" />
                <p class="text-base-content/60">{{ __('placement_change.no_active_registration') }}</p>
            </div>
        </x-mary-card>
    @elseif($pendingRequest)
        <x-mary-card>
            <div class="p-6 text-center">
                <x-mary-icon name="o-clock" class="text-warning w-12 h-12 mx-auto mb-3" />
                <h3 class="font-semibold text-lg mb-1">{{ __('placement_change.pending_title') }}</h3>
                <p class="text-base-content/60">{{ __('placement_change.pending_message') }}</p>
                <div class="mt-3 p-3 bg-base-200 rounded-lg text-sm text-left">
                    <p><strong>{{ __('placement_change.reason') }}:</strong> {{ $pendingRequest->reason }}</p>
                    <p class="mt-1"><strong>{{ __('placement_change.status') }}:</strong>
                        <x-mary-badge value="Pending" class="badge-warning" />
                    </p>
                </div>
            </div>
        </x-mary-card>
    @else
        <x-mary-card>
            <div class="p-4 mb-4 bg-base-200 rounded-lg">
                <h3 class="font-semibold mb-2">{{ __('placement_change.current_placement') }}</h3>
                <p class="text-sm">{{ $registration->placement?->company?->name ?? '—' }}</p>
                <p class="text-xs text-base-content/50">{{ $registration->internship?->name }}</p>
            </div>

            <x-mary-form wire:submit="submit">
                <div class="space-y-5">
                    <x-mary-select :label="__('placement_change.target_placement')" wire:model="toPlacementId"
                        :placeholder="__('placement_change.target_placeholder')"
                        :options="$availablePlacements"
                        option-label="company.name"
                        option-value="id" />
                    <x-mary-textarea :label="__('placement_change.reason')" wire:model="reason"
                        :placeholder="__('placement_change.reason_placeholder')" rows="4" />
                </div>
                <x-slot:actions>
                    <x-mary-button :label="__('placement_change.submit')" class="btn-primary" type="submit" spinner="submit" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-card>
    @endif
</div>
