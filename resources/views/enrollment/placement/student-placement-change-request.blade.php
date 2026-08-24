<div class="mx-auto mt-6 max-w-2xl">
    <x-core::ui.page-header
        :title="__('placement_change.request_title')"
        :subtitle="__('placement_change.request_subtitle')"
    />

    @if (! $registration)
        <x-mary-card>
            <div class="p-6 text-center">
                <x-ts-icon name="information-circle" class="text-base-content/40 mx-auto mb-3 h-12 w-12" />
                <p class="text-base-content/60">{{ __('placement_change.no_active_registration') }}</p>
            </div>
        </x-mary-card>
    @elseif ($pendingRequest)
        <x-mary-card>
            <div class="p-6 text-center">
                <x-ts-icon name="clock" class="text-warning mx-auto mb-3 h-12 w-12" />
                <h3 class="mb-1 text-lg font-semibold">{{ __('placement_change.pending_title') }}</h3>
                <p class="text-base-content/60">{{ __('placement_change.pending_message') }}</p>
                <div class="bg-base-200 mt-3 rounded-lg p-3 text-left text-sm">
                    <p><strong>{{ __('placement_change.reason') }}:</strong> {{ $pendingRequest->reason }}</p>
                    <p class="mt-1">
                        <strong>{{ __('placement_change.status') }}:</strong>
                        <x-ts-badge :text="__('placement_change.status_pending')" class="badge-warning" />
                    </p>
                </div>
            </div>
        </x-mary-card>
    @else
        <x-mary-card>
            <div class="bg-base-200 mb-4 rounded-lg p-4">
                <h3 class="mb-2 font-semibold">{{ __('placement_change.current_placement') }}</h3>
                <p class="text-sm">{{ $registration->placement?->company?->name ?? '—' }}</p>
                <p class="text-base-content/50 text-xs">{{ $registration->internship?->name }}</p>
            </div>

            <form wire:submit="submit">
                <div class="space-y-5">
                    <x-ts-select.native
                        :label="__('placement_change.target_placement')"
                        wire:model="form.to_placement_id"
                        :options="[null => __('placement_change.target_placeholder')] + ($availablePlacements)"
                        option-label="company.name"
                        option-value="id"
                    />
                    <x-ts-textarea
                        :label="__('placement_change.reason')"
                        wire:model="form.reason"
                        :placeholder="__('placement_change.reason_placeholder')"
                        rows="4"
                    />
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button :text="__('placement_change.submit')" color="primary" type="submit" loading="submit" />
                </div>
            </form>
        </x-mary-card>
    @endif
</div>
