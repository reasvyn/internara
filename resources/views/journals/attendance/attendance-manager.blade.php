<div>
    <x-slot:title>Attendance</x-slot:title>

    <x-core::ui.page-header title="Attendance Management" description="Record daily attendance for your supervised students." />

    <x-mary-card>
        <div class="flex gap-4 mb-6 items-end">
            <div>
                <x-mary-input wire:model.live="date" type="date" label="Date" class="w-48" />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Placement</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Existing</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $registration)
                        <tr>
                            <td>{{ $registration->mentee?->user?->name ?? 'N/A' }}</td>
                            <td class="text-sm">{{ $registration->placement?->company?->name ?? 'N/A' }}</td>
                            <td>
                                @if(isset($existing[$registration->id]))
                                    <x-mary-badge :value="$existing[$registration->id]->status?->label() ?? 'N/A'" />
                                @else
                                    <select wire:model="records.{{ $registration->id }}.status" class="select select-bordered select-sm">
                                        <option value="">Select...</option>
                                        @foreach($statuses as $s)
                                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                            <td>
                                @if(!isset($existing[$registration->id]))
                                    <x-mary-input wire:model="records.{{ $registration->id }}.notes" placeholder="Notes..." class="input-sm" />
                                @endif
                            </td>
                            <td>
                                @if(isset($existing[$registration->id]))
                                    @if(!$existing[$registration->id]->is_verified)
                                        <x-mary-button wire:click="verifyAttendance('{{ $existing[$registration->id]->id }}')" icon="o-check" class="btn-xs btn-success" />
                                    @else
                                        <x-mary-icon name="o-check-badge" class="text-success" />
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(count($students) > 0)
            <div class="mt-4">
                <x-mary-button wire:click="markAttendance" label="Save Attendance" icon="o-check" class="btn-primary" />
            </div>
        @endif
    </x-mary-card>
</div>
