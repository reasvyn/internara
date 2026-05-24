<div>
    <x-slot:title>Absence Request</x-slot:title>

    <x-shared::ui.page-header title="Absence Request" description="Submit an absence request for your internship." />

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-mary-card>
            <form wire:submit="submit">
                <div class="grid grid-cols-2 gap-4">
                    <x-mary-input wire:model="startDate" type="date" label="Start Date" />
                    <x-mary-input wire:model="endDate" type="date" label="End Date" />
                </div>

                <x-mary-select wire:model="reasonType" label="Reason Type" class="mt-4">
                    <option value="">Select reason...</option>
                    @foreach($reasonTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </x-mary-select>

                <x-mary-textarea wire:model="reasonDescription" label="Description" rows="4" class="mt-4" />

                <x-mary-button type="submit" label="Submit Request" icon-right="o-paper-airplane" class="btn-primary mt-4" />
            </form>
        </x-mary-card>

        <x-mary-card>
            <h3 class="font-bold text-sm mb-4">My Requests</h3>
            @forelse($existingRequests as $req)
                <div class="flex items-center justify-between py-2 border-b border-base-200 last:border-0">
                    <div>
                        <p class="text-sm font-medium">{{ $req->start_date?->format('d M') }} - {{ $req->end_date?->format('d M Y') }}</p>
                        <p class="text-xs text-base-content/60">{{ $req->reason_type?->label() }}</p>
                    </div>
                    <x-mary-badge :value="$req->status->label() ?? 'Pending'" :class="$req->status === 'approved' ? 'badge-success' : ($req->status === 'rejected' ? 'badge-error' : 'badge-warning')" />
                </div>
            @empty
                <p class="text-sm text-base-content/60">No absence requests yet.</p>
            @endforelse

            <div class="mt-4">
                {{ $existingRequests->links() }}
            </div>
        </x-mary-card>
    </div>
</div>
