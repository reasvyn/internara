<div>
    <x-ui::components.page-header
        :title="__('registration.verification.title')"
        :description="__('registration.verification.subtitle')"
    />

    <x-ts-card shadowless>
        @if ($this->pendingRegistrations->isEmpty())
            <x-ts-alert
                :title="__('registration.verification.empty')"
                :text="__('registration.verification.empty_desc')"
                icon="check-circle"
            />
        @else
            <div class="overflow-x-auto">
                <table class="table-zebra table">
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
                        @foreach ($this->pendingRegistrations as $reg)
                            @php
                                $total = $reg->documents->count();
                                $verified = $reg->documents->where('status', 'verified')->count();
                                $pending = $reg->documents->where('status', 'pending')->count();
                                $rejected = $reg->documents->where('status', 'rejected')->count();
                            @endphp
                            <tr>
                                <td>
                                    <div class="font-medium">
                                        {{ $reg->mentee?->user?->name ?? __('common.unknown') }}
                                    </div>
                                    <div class="text-base-content/50 text-xs">{{ $reg->mentee?->user?->email }}</div>
                                </td>
                                <td>{{ $reg->internship?->name ?? '-' }}</td>
                                <td>
                                    @if ($total > 0)
                                        <div class="flex gap-2 text-xs">
                                            <x-ts-badge
                                                :text="$verified.' '.__('registration.verification.verified')"
                                                color="green"
                                                xs
                                            />
                                            <x-ts-badge
                                                :text="$pending.' '.__('registration.verification.pending')"
                                                color="yellow"
                                                xs
                                            />
                                            @if ($rejected > 0)
                                                <x-ts-badge
                                                    :text="$rejected.' '.__('registration.verification.rejected')"
                                                    color="red"
                                                    xs
                                                />
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-base-content/40 text-xs">{{ __('registration.verification.no_docs') }}</span>
                                    @endif
                                </td>
                                <td>{{ $reg->created_at->diffForHumans() }}</td>
                                <td>
                                    <x-ts-button
                                        :text="__('registration.verification.process')"
                                        wire:click="process('{{ $reg->id }}')"
                                        icon="chevron-right"
                                        class="btn-primary btn-sm"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ts-card>
    <x-ts-modal wire="showProcessModal" :title="__('registration.verification.process_title')">
        @if ($this->selectedRegistration)
            <div class="bg-base-200 rounded-box mb-4 p-3">
                <p class="font-medium">{{ $this->selectedRegistration->mentee?->user?->name }}</p>
                <p class="text-base-content/50 text-sm">{{ $this->selectedRegistration->internship?->name }}</p>
            </div>

            <form wire:submit="confirmProcess">
                <x-ts-select.native
                    :label="__('registration.verification.placement')"
                    wire:model="placement_id"
                    :options="ts_options($this->availablePlacements, __('registration.verification.select_placement'))"
                    icon="briefcase"
                />

                <x-ts-select.native
                    :label="__('registration.verification.assigned_mentors')"
                    wire:model="mentor_ids"
                    :options="ts_options($this->mentors, __('registration.verification.select_mentors'))"
                    multiple
                    icon="user-group"
                />

                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('registration.verification.cancel')"
                        wire:click="$set('showProcessModal', false)"
                    />
                    <x-ts-button
                        :text="__('registration.verification.verify_place')"
                        type="submit"
                        icon="check"
                        color="primary"
                    />
                </div>
            </form>
        @endif
    </x-ts-modal>
</div>
