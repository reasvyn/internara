<div>
    <x-slot:title>{{ __('journals.attendance.title') }}</x-slot:title>

    <x-core::ui.page-header
        :title="__('journals.attendance.management_title')"
        :description="__('journals.attendance.management_subtitle')"
    />

    <x-ts-card shadowless>
        <div class="mb-6 flex items-end gap-4">
            <div>
                <x-ts-input wire:model.live="date" type="date" :label="__('journals.date')" class="w-48" />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table-zebra table w-full">
                <thead>
                    <tr>
                        <th>{{ __('journals.student') }}</th>
                        <th>{{ __('journals.attendance.placement') }}</th>
                        <th>{{ __('journals.status') }}</th>
                        <th>{{ __('journals.attendance.notes') }}</th>
                        <th>{{ __('journals.attendance.existing') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $registration)
                        <tr>
                            <td>{{ $registration->mentee?->user?->name ?? 'N/A' }}</td>
                            <td class="text-sm">{{ $registration->placement?->company?->name ?? 'N/A' }}</td>
                            <td>
                                @if (isset($existing[$registration->id]))
                                    <x-ts-badge :text="$existing[$registration->id]->status?->label() ?? 'N/A'" />
                                @else
                                    <x-ts-select.native wire:model="records.{{ $registration->id }}.status" sm>
                                        <option value="">{{ __('journals.attendance.select') }}</option>
                                        @foreach ($statuses as $s)
                                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                        @endforeach
                                    </x-ts-select.native>
                                @endif
                            </td>
                            <td>
                                @if (! isset($existing[$registration->id]))
                                    <x-ts-input
                                        wire:model="records.{{ $registration->id }}.notes"
                                        placeholder="Notes..."
                                        class="input-sm"
                                    />
                                @endif
                            </td>
                            <td>
                                @if (isset($existing[$registration->id]))
                                    @php($attendance = $existing[$registration->id])
                                    @if (! $attendance->is_verified)
                                        <x-ts-button
                                            aria-label="{{ __('journals.verify') }}"
                                            wire:click="verifyAttendance('{{ $attendance->id }}')"
                                            icon="check"
                                            class="btn-xs btn-success"
                                        />
                                    @else
                                        <x-ts-icon name="check-badge" class="text-success" />
                                    @endif
                                    @can('update', $attendance)
                                        <x-ts-select.native
                                            class="ml-1 w-28 align-middle"
                                            wire:change="updateAttendance('{{ $attendance->id }}', $event.target.value)"
                                        >
                                            @foreach ($statuses as $s)
                                                <option
                                                    value="{{ $s->value }}"
                                                    @selected($attendance->status?->value === $s->value)
                                                >
                                                    {{ $s->label() }}
                                                </option>
                                            @endforeach
                                        </x-ts-select.native>
                                    @endcan
                                    @can('delete', $attendance)
                                        <x-ts-button
                                            aria-label="{{ __('common.actions.delete') }}"
                                            wire:click="deleteAttendance('{{ $attendance->id }}')"
                                            wire:confirm="{{ __('journals.attendance.confirm_delete') }}"
                                            icon="trash"
                                            class="btn-xs text-error"
                                        />
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if (count($students) > 0)
            <div class="mt-4">
                <x-ts-button
                    wire:click="markAttendance"
                    :text="__('journals.attendance.save')"
                    icon="check"
                    color="primary"
                />
            </div>
        @endif
</div>
