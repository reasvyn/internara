<div class="space-y-4 py-3 sm:space-y-5 sm:py-4">
    <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ __('journals.attendance.management_title') }}</h1>
            <p class="text-base-content/50 mt-1 text-sm">{{ __('journals.attendance.management_subtitle') }}</p>
        </div>
        <div class="flex shrink-0 items-center gap-3">
            <x-ts-input wire:model.live="date" type="date" :label="__('journals.date')" class="w-48" />
        </div>
    </div>

    <div class="border-base-content/10 bg-base-100 overflow-x-auto rounded-2xl border shadow-2xs">
        @if ($students->isNotEmpty())
            <table class="table-zebra table w-full">
                <thead>
                    <tr class="text-base-content/70 text-xs tracking-wider uppercase">
                        <th class="px-4 py-3">{{ __('journals.student') }}</th>
                        <th class="px-4 py-3">{{ __('journals.attendance.placement') }}</th>
                        <th class="px-4 py-3">{{ __('journals.status') }}</th>
                        <th class="px-4 py-3">{{ __('journals.attendance.notes') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('journals.attendance.existing') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-base-content/10 divide-y">
                    @foreach ($students as $registration)
                        <tr class="hover:bg-base-200/50 transition-colors">
                            <td class="px-4 py-3 font-medium">{{ $registration->mentee?->user?->name ?? 'N/A' }}</td>
                            <td class="text-base-content/70 px-4 py-3 text-sm">
                                {{ $registration->placement?->company?->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3">
                                @if (isset($existing[$registration->id]))
                                    <x-ts-badge
                                        :text="$existing[$registration->id]->status?->label() ?? 'N/A'"
                                        color="primary"
                                        xs
                                    />
                                @else
                                    <x-ts-select.native wire:model="records.{{ $registration->id }}.status" sm>
                                        <option value="">{{ __('journals.attendance.select') }}</option>
                                        @foreach ($statuses as $s)
                                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                        @endforeach
                                    </x-ts-select.native>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if (! isset($existing[$registration->id]))
                                    <x-ts-input
                                        wire:model="records.{{ $registration->id }}.notes"
                                        placeholder="{{ __('journals.attendance.notes') }}..."
                                        class="input-sm"
                                    />
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if (isset($existing[$registration->id]))
                                    @php($attendance = $existing[$registration->id])
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if (! $attendance->is_verified)
                                            <x-ui::components.table-action
                                                action="verify"
                                                wire:click="verifyAttendance('{{ $attendance->id }}')"
                                                :label="__('journals.verify')"
                                            />
                                        @else
                                            <x-ts-icon name="check-badge" class="text-success size-5" />
                                        @endif
                                        @can('update', $attendance)
                                            <x-ts-select.native
                                                class="w-28 text-xs"
                                                wire:change="updateAttendance('{{ $attendance->id }}', $event.target.value)"
                                                sm
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
                                            <x-ui::components.table-action
                                                action="delete"
                                                wire:click="askDeleteAttendance('{{ $attendance->id }}')"
                                                :label="__('common.actions.delete')"
                                            />
                                        @endcan
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="border-base-content/10 border-t p-4">
                <x-ts-button
                    wire:click="markAttendance"
                    :text="__('journals.attendance.save')"
                    icon="check"
                    color="primary"
                    sm
                />
            </div>
        @else
            <x-ui::widgets.empty-state
                icon="calendar"
                :title="__('journals.attendance.management_title')"
                :description="__('common.status.no_records')"
            />
        @endif
    </div>
</div>
