<div>
    <x-slot:title>{{ __('journals.absence.title') }}</x-slot:title>

    <x-ui::components.page-header
        :title="__('journals.absence.title')"
        :description="__('journals.absence.subtitle')"
    />

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <x-ts-card shadowless>
            <form wire:submit="submit">
                <x-ts-input wire:model="startDate" type="date" :label="__('journals.absence.date')" />

                <x-ts-select.native
                    wire:model="reasonType"
                    :label="__('journals.absence.reason_type')"
                    :options="ts_options($reasonTypes, __('journals.absence.select_reason'))"
                    class="mt-4"
                />

                <x-ts-textarea
                    wire:model="reasonDescription"
                    :label="__('journals.absence.description')"
                    rows="4"
                    class="mt-4"
                />

                <x-ts-button
                    type="submit"
                    :text="__('journals.absence.submit_request')"
                    icon-right="paper-airplane"
                    class="mt-4"
                    color="primary"
                    loading="submit"
                />
            </form>
        </x-ts-card>

        <x-ts-card shadowless>
            <h3 class="mb-4 text-sm font-bold">{{ __('journals.absence.my_requests') }}</h3>
            @forelse ($existingRequests as $req)
                <div class="border-base-200 flex items-center justify-between border-b py-2 last:border-0">
                    <div>
                        <p class="text-sm font-medium">{{ $req->date?->format('d M Y') }}</p>
                        <p class="text-base-content/60 text-xs">{{ $req->absence_type?->label() }}</p>
                    </div>
                    <x-ts-badge
                        :text="$req->absence_status?->label() ?? __('journals.absence.pending')"
                        :color="match ($req->absence_status?->value) {
                            'approved' => 'green',
                            'rejected' => 'red',
                            default => 'yellow',
                        }"
                    />
                </div>
            @empty
                <p class="text-base-content/60 text-sm">{{ __('journals.absence.no_requests') }}</p>
            @endforelse

            <div class="mt-4">{{ $existingRequests->links() }}</div>
        </x-ts-card>
    </div>
</div>
