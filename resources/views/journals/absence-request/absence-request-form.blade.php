<div>
    <x-slot:title>{{ __('journals.absence.title') }}</x-slot:title>

    <x-core::ui.page-header :title="__('journals.absence.title')" :description="__('journals.absence.subtitle')" />

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <x-mary-card>
            <form wire:submit="submit">
                <div class="grid grid-cols-2 gap-4">
                    <x-mary-input wire:model="startDate" type="date" :label="__('journals.absence.start_date')" />
                    <x-mary-input wire:model="endDate" type="date" :label="__('journals.absence.end_date')" />
                </div>

                <x-mary-select wire:model="reasonType" :label="__('journals.absence.reason_type')" class="mt-4">
                    <option value="">{{ __('journals.absence.select_reason') }}</option>
                    @foreach ($reasonTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </x-mary-select>

                <x-mary-textarea
                    wire:model="reasonDescription"
                    :label="__('journals.absence.description')"
                    rows="4"
                    class="mt-4"
                />

                <x-mary-button
                    type="submit"
                    :label="__('journals.absence.submit_request')"
                    icon-right="o-paper-airplane"
                    class="btn-primary mt-4"
                />
            </form>
        </x-mary-card>

        <x-mary-card>
            <h3 class="mb-4 text-sm font-bold">{{ __('journals.absence.my_requests') }}</h3>
            @forelse ($existingRequests as $req)
                <div class="border-base-200 flex items-center justify-between border-b py-2 last:border-0">
                    <div>
                        <p class="text-sm font-medium">
                            {{ $req->start_date?->format('d M') }} - {{ $req->end_date?->format('d M Y') }}
                        </p>
                        <p class="text-base-content/60 text-xs">{{ $req->reason_type?->label() }}</p>
                    </div>
                    <x-mary-badge
                        :value="$req->status->label() ?? __('journals.absence.pending')"
                        :class="$req->status === 'approved' ? 'badge-success' : ($req->status === 'rejected' ? 'badge-error' : 'badge-warning')"
                    />
                </div>
            @empty
                <p class="text-base-content/60 text-sm">{{ __('journals.absence.no_requests') }}</p>
            @endforelse

            <div class="mt-4">{{ $existingRequests->links() }}</div>
        </x-mary-card>
    </div>
</div>
