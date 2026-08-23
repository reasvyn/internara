<div>
    <x-slot:title>{{ __('journals.attendance.title') }}</x-slot:title>

    <x-core::ui.page-header
        :title="__('journals.attendance.management_title')"
        :description="__('journals.attendance.management_subtitle')"
    />

    <x-mary-card>
        <div class="mb-6 flex items-end gap-4">
            <div>
                <x-mary-input wire:model.live="date" type="date" :label="__('journals.date')" class="w-48" />
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
                                    <x-mary-badge :value="$existing[$registration->id]->status?->label() ?? 'N/A'" />
                                @else
                                    <select
                                        wire:model="records.{{ $registration->id }}.status"
                                        class="select select-bordered select-sm"
                                    >
                                        <option value="">{{ __('journals.attendance.select') }}</option>
                                        @foreach ($statuses as $s)
                                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                            <td>
                                @if (! isset($existing[$registration->id]))
                                    <x-mary-input
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
                                        <x-mary-button
                                            aria-label="{{ __('journals.verify') }}"
                                            wire:click="verifyAttendance('{{ $attendance->id }}')"
                                            icon="o-check"
                                            class="btn-xs btn-success"
                                        />
                                    @else
                                        <x-mary-icon name="o-check-badge" class="text-success" />
                                    @endif
                                    @can('update', $attendance)
                                        <select
                                            class="select select-bordered select-xs ml-1 w-28 align-middle"
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
                                        </select>
                                    @endcan
                                    @can('delete', $attendance)
                                        <x-mary-button
                                            aria-label="{{ __('common.actions.delete') }}"
                                            wire:click="deleteAttendance('{{ $attendance->id }}')"
                                            wire:confirm="{{ __('journals.attendance.confirm_delete') }}"
                                            icon="o-trash"
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
                <x-mary-button
                    wire:click="markAttendance"
                    :label="__('journals.attendance.save')"
                    icon="o-check"
                    class="btn-primary"
                />
            </div>
        @endif
    </x-mary-card>
</div>
