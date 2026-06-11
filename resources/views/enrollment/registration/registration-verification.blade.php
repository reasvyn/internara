<div>
    <x-mary-header :title="__('registration.verification.title')" :subtitle="__('registration.verification.subtitle')" separator />

    <x-mary-card>
        @if($this->pendingRegistrations->isEmpty())
            <x-mary-alert :title="__('registration.verification.empty')" :description="__('registration.verification.empty_desc')" icon="o-check-circle" />
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>{{ __('registration.verification.student') }}</th>
                            <th>{{ __('registration.verification.program') }}</th>
                            <th>{{ __('registration.verification.documents') }}</th>
                            <th>{{ __('registration.verification.submitted') }}</th>
                            <th>{{ __('registration.verification.subtitle') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->pendingRegistrations as $reg)
                            @php
                                $total = $reg->documents->count();
                                $verified = $reg->documents->where('status', 'verified')->count();
                                $pending = $reg->documents->where('status', 'pending')->count();
                                $rejected = $reg->documents->where('status', 'rejected')->count();
                            @endphp
                            <tr>
                                <td>
                                    <div class="font-medium">{{ $reg->mentee?->user?->name ?? 'Unknown' }}</div>
                                    <div class="text-xs text-gray-500">{{ $reg->mentee?->user?->email }}</div>
                                </td>
                                <td>{{ $reg->internship?->name ?? '-' }}</td>
                                <td>
                                    @if($total > 0)
                                        <div class="flex gap-2 text-xs">
                                            <span class="badge badge-success badge-sm">{{ $verified }} {{ __('registration.verification.verified') }}</span>
                                            <span class="badge badge-warning badge-sm">{{ $pending }} {{ __('registration.verification.pending') }}</span>
                                            @if($rejected > 0)
                                                <span class="badge badge-error badge-sm">{{ $rejected }} {{ __('registration.verification.rejected') }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">{{ __('registration.verification.no_docs') }}</span>
                                    @endif
                                </td>
                                <td>{{ $reg->created_at->diffForHumans() }}</td>
                                <td>
                                    <x-mary-button :label="__('registration.verification.process')" wire:click="process('{{ $reg->id }}')" icon="o-chevron-right" class="btn-primary btn-sm" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-mary-card>

    <x-mary-modal wire:model="showProcessModal" :title="__('registration.verification.process_title')">
        @if($this->selectedRegistration)
            <div class="mb-4 p-3 bg-base-200 rounded-box">
                <p class="font-medium">{{ $this->selectedRegistration->mentee?->user?->name }}</p>
                <p class="text-sm text-gray-500">{{ $this->selectedRegistration->internship?->name }}</p>
            </div>

            <x-mary-form wire:submit="confirmProcess">
                <x-mary-select
                    :label="__('registration.verification.placement')"
                    wire:model="placement_id"
                    :options="$this->availablePlacements"
                    :placeholder="__('registration.verification.select_placement')"
                    icon="o-briefcase" />

                <x-mary-select
                    :label="__('registration.verification.assigned_mentors')"
                    wire:model="mentor_ids"
                    :options="$this->mentors"
                    :placeholder="__('registration.verification.select_mentors')"
                    multiple
                    icon="o-user-group" />

                <x-slot:actions>
                    <x-mary-button :label="__('registration.verification.cancel')" wire:click="$set('showProcessModal', false)" />
                    <x-mary-button :label="__('registration.verification.verify_place')" type="submit" icon="o-check" class="btn-primary" />
                </x-slot:actions>
            </x-mary-form>
        @endif
    </x-mary-modal>
</div>
