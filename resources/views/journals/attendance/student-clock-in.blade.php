<div>
    <x-slot:title>Attendance</x-slot:title>

    <x-core::ui.page-header title="Attendance" description="Clock in and out for your internship." />

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-mary-card>
            <h3 class="mb-2 text-sm font-bold">Today's Status</h3>
            @if ($todayAttendance)
                <p class="text-sm">
                    Clocked in at:
                    <strong>{{ $todayAttendance->clock_in ? \Carbon\Carbon::parse($todayAttendance->clock_in)->format('H:i') : '' }}</strong>
                </p>
                @if ($todayAttendance->clock_out)
                    <p class="text-sm">
                        Clocked out at:
                        <strong>{{ $todayAttendance->clock_out ? \Carbon\Carbon::parse($todayAttendance->clock_out)->format('H:i') : '' }}</strong>
                    </p>
                    <x-mary-badge value="Completed" class="badge-success mt-2" />
                @else
                    <x-mary-button
                        wire:click="clockOut"
                        label="Clock Out"
                        icon="o-arrow-right-end-on-rectangle"
                        class="btn-warning btn-sm mt-3"
                    />
                @endif
            @else
                <p class="text-base-content/60 mb-3 text-sm">Not clocked in today.</p>
                <x-mary-button
                    wire:click="clockIn"
                    label="Clock In"
                    icon="o-arrow-left-start-on-rectangle"
                    class="btn-primary btn-sm"
                />
            @endif
        </x-mary-card>
    </div>
</div>
