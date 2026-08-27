<div>
    <x-slot:title>{{ __('journals.absence.title') }}</x-slot:title>

    <x-ui::ui.page-header :title="__('journals.absence.title')" :description="__('journals.absence.subtitle')" />

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <x-ts-card shadowless>
            <form wire:submit="submit">
                <div class="grid grid-cols-2 gap-4">
                    <x-ts-input wire:model="startDate" type="date" :label="__('journals.absence.start_date')" />
                    <x-ts-input wire:model="endDate" type="date" :label="__('journals.absence.end_date')" />
                </div>

                <x-ts-select.native wire:model="reasonType" :label="__('journals.absence.reason_type')" class="mt-4">
                    <option value="">{{ __('journals.absence.select_reason') }}</option>
                    @foreach ($reasonTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </x-ts-select.native>

                <x-ts-textarea
                    wire:model="reasonDescription"
                    :label="__('journals.absence.description')"
                    rows="4"
                    class="mt-4"
                />

                <x-ts-button
                    type="submit"
                    :text="__('journals.absence.submit_request')"
                    icon-right="o-paper-airplane"
                    class="mt-4"
                    color="primary"
                />
            </form>

            <x-ts-card shadowless>
                <h3 class="mb-4 text-sm font-bold">{{ __('journals.absence.my_requests') }}</h3>
                @forelse ($existingRequests as $req)
                    <div class="border-base-200 flex items-center justify-between border-b py-2 last:border-0">
                        <div>
                            <p class="text-sm font-medium">
                                {{ $req->start_date?->format('d M') }} - {{ $req->end_date?->format('d M Y') }}
                            </p>
                            <p class="text-base-content/60 text-xs">{{ $req->reason_type?->label() }}</p>
                        </div>
                        <x-ts-badge
                            :text="$req->status->label() ?? __('journals.absence.pending')"
                            :class="$req->status === 'approved' ? 'badge-success' : ($req->status === 'rejected' ? 'badge-error' : 'badge-warning')"
                        />
                    </div>
                @empty
                    <p class="text-base-content/60 text-sm">{{ __('journals.absence.no_requests') }}</p>
                @endforelse

                <div class="mt-4">{{ $existingRequests->links() }}</div>
    </div>
</div>
