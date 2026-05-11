<div>
    <x-slot:title>Attendance</x-slot:title>

    <x-ui::page-header title="Attendance" description="Clock in and out for your internship." />

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-mary-card>
            <h3 class="font-bold text-sm mb-2">Today's Status</h3>
            @if($todayAttendance)
                <p class="text-sm">Clocked in at: <strong>{{ $todayAttendance->clock_in?->format('H:i') }}</strong></p>
                @if($todayAttendance->clock_out)
                    <p class="text-sm">Clocked out at: <strong>{{ $todayAttendance->clock_out?->format('H:i') }}</strong></p>
                    <x-mary-badge value="Completed" class="badge-success mt-2" />
                @else
                    <x-mary-button wire:click="clockOut" label="Clock Out" icon="o-arrow-right-end-on-rectangle" class="btn-warning btn-sm mt-3" />
                @endif
            @else
                <p class="text-sm text-base-content/60 mb-3">Not clocked in today.</p>
                <x-mary-button wire:click="clockIn" label="Clock In" icon="o-arrow-left-start-on-rectangle" class="btn-primary btn-sm" />
            @endif
        </x-mary-card>
    </div>
</div>
