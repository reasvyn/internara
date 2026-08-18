<div>
    <x-slot:title>{{ __('journals.attendance.title') }}</x-slot:title>

    <x-core::ui.page-header
        :title="__('journals.attendance.title')"
        :description="__('journals.attendance.clock_in_out_subtitle')"
    />

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-mary-card>
            <h3 class="mb-2 text-sm font-bold">{{ __('journals.attendance.today_status') }}</h3>
            @if ($todayAttendance)
                <p class="text-sm">
                    {{ __('journals.attendance.clocked_in_at') }}
                    <strong>{{ $todayAttendance->clock_in ? \Carbon\Carbon::parse($todayAttendance->clock_in)->format('H:i') : '' }}</strong>
                </p>
                @if ($todayAttendance->clock_out)
                    <p class="text-sm">
                        {{ __('journals.attendance.clocked_out_at') }}
                        <strong>{{ $todayAttendance->clock_out ? \Carbon\Carbon::parse($todayAttendance->clock_out)->format('H:i') : '' }}</strong>
                    </p>
                    <x-mary-badge :value="__('journals.attendance.completed')" class="badge-success mt-2" />
                @else
                    <x-mary-button
                        wire:click="clockOut"
                        :label="__('journals.attendance.clock_out')"
                        icon="o-arrow-right-end-on-rectangle"
                        class="btn-warning btn-sm mt-3"
                    />
                @endif
            @else
                <p class="text-base-content/60 mb-3 text-sm">{{ __('journals.attendance.not_clocked_in') }}</p>
                <x-mary-button
                    wire:click="clockIn"
                    :label="__('journals.attendance.clock_in')"
                    icon="o-arrow-left-start-on-rectangle"
                    class="btn-primary btn-sm"
                />
            @endif
        </x-mary-card>
    </div>
</div>
