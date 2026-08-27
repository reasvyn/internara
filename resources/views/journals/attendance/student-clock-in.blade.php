<div>
    <x-slot:title>{{ __('journals.attendance.title') }}</x-slot:title>

    <x-ui::ui.page-header
        :title="__('journals.attendance.title')"
        :description="__('journals.attendance.clock_in_out_subtitle')"
    />

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-ts-card shadowless>
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
                    <x-ts-badge :text="__('journals.attendance.completed')" color="green" class="mt-2" />
                @else
                    <x-ts-button
                        wire:click="clockOut"
                        :text="__('journals.attendance.clock_out')"
                        icon="arrow-right-end-on-rectangle"
                        class="mt-3"
                        color="yellow"
                        sm
                    />
                @endif
            @else
                <p class="text-base-content/60 mb-3 text-sm">{{ __('journals.attendance.not_clocked_in') }}</p>
                <x-ts-button
                    wire:click="clockIn"
                    :text="__('journals.attendance.clock_in')"
                    icon="arrow-left-start-on-rectangle"
                    color="primary"
                    sm
                />
            @endif
        </x-ts-card>
    </div>
</div>
