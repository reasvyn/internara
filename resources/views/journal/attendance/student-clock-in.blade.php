<div>
    <x-slot:title>{{ __('journal.attendance.title') }}</x-slot:title>

    <x-ui::components.page-header
        :title="__('journal.attendance.title')"
        :description="__('journal.attendance.clock_in_out_subtitle')"
    />

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        {{-- Hero Clock-In Action Card --}}
        <div class="border-base-content/10 bg-base-100 flex flex-col justify-between rounded-lg border p-5 shadow-2xs sm:p-6">
            <div>
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <div class="bg-primary/10 text-primary flex size-7 items-center justify-center rounded-md">
                            <x-ts-icon name="clock" class="size-4" />
                        </div>
                        <h2 class="text-base-content text-sm font-bold tracking-tight">
                            {{ __('journal.attendance.today_overview') }}
                        </h2>
                    </div>

                    @if (! $todayAttendance)
                        <x-ts-badge :text="__('common.enums.pending')" color="neutral" sm />
                    @elseif (! $todayAttendance->clock_out)
                        <x-ts-badge :text="__('journal.attendance.in_progress')" color="primary" sm />
                    @else
                        <x-ts-badge :text="__('journal.attendance.completed')" color="success" sm />
                    @endif
                </div>

                {{-- Live Digital Clock (Alpine.js) --}}
                <div
                    x-data="{
                        time: new Date().toLocaleTimeString('id-ID', {
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit',
                        }),
                        init() {
                            setInterval(() => {
                                this.time = new Date().toLocaleTimeString('id-ID', {
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    second: '2-digit',
                                });
                            }, 1000);
                        },
                    }"
                    class="my-5 text-center sm:text-left"
                >
                    <span class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">
                        {{ __('journal.attendance.current_time') }}
                    </span>
                    <div
                        class="text-base-content font-mono text-3xl font-extrabold tracking-tight sm:text-4xl"
                        x-text="time"
                    ></div>
                    <p class="text-base-content/60 mt-1 text-xs font-medium">
                        {{ now()->translatedFormat('l, d F Y') }}
                    </p>
                </div>

                {{-- Check-in / Check-out Timestamps --}}
                <div class="mb-5 grid grid-cols-2 gap-3">
                    <div class="border-base-content/10 bg-base-200/40 rounded-lg border p-3">
                        <span class="text-base-content/60 text-xs font-medium">
                            {{ __('journal.attendance.clock_in_title') }}
                        </span>
                        <div class="text-base-content mt-1 flex items-center gap-1.5 font-mono text-sm font-bold">
                            @if ($todayAttendance?->clock_in)
                                <x-ts-icon name="check" class="text-success size-4 shrink-0" />
                                <span>{{ \Carbon\Carbon::parse($todayAttendance->clock_in)->format('H:i') }} WIB</span>
                            @else
                                <span class="text-base-content/40 font-normal">— : —</span>
                            @endif
                        </div>
                    </div>

                    <div class="border-base-content/10 bg-base-200/40 rounded-lg border p-3">
                        <span class="text-base-content/60 text-xs font-medium">
                            {{ __('journal.attendance.clock_out_title') }}
                        </span>
                        <div class="text-base-content mt-1 flex items-center gap-1.5 font-mono text-sm font-bold">
                            @if ($todayAttendance?->clock_out)
                                <x-ts-icon name="check" class="text-success size-4 shrink-0" />
                                <span>{{ \Carbon\Carbon::parse($todayAttendance->clock_out)->format('H:i') }} WIB</span>
                            @else
                                <span class="text-base-content/40 font-normal">— : —</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tactical CTA Button --}}
            <div class="border-base-content/10 border-t pt-4">
                @if (! $todayAttendance)
                    <x-ts-button
                        wire:click="clockIn"
                        :text="__('journal.attendance.clock_in_btn')"
                        icon="arrow-left-start-on-rectangle"
                        color="primary"
                        class="w-full justify-center py-2.5 font-semibold"
                    />
                @elseif (! $todayAttendance->clock_out)
                    <x-ts-button
                        wire:click="clockOut"
                        :text="__('journal.attendance.clock_out_btn')"
                        icon="arrow-right-end-on-rectangle"
                        color="primary"
                        class="w-full justify-center py-2.5 font-semibold"
                    />
                @else
                    <div class="border-success/30 bg-success/10 rounded-md border p-3 text-center">
                        <div class="text-success flex items-center justify-center gap-2 text-xs font-semibold">
                            <x-ts-icon name="check-circle" class="size-4 shrink-0" />
                            <span>{{ __('journal.attendance.completed_badge') }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Placement Context & Information Card --}}
        <div class="border-base-content/10 bg-base-100 flex flex-col justify-between rounded-lg border p-5 shadow-2xs sm:p-6">
            <div>
                <div class="flex items-center gap-2">
                    <div class="bg-secondary/10 text-secondary flex size-7 items-center justify-center rounded-md">
                        <x-ts-icon name="building-office-2" class="size-4" />
                    </div>
                    <h2 class="text-base-content text-sm font-bold tracking-tight">
                        {{ __('journal.attendance.placement_info') }}
                    </h2>
                </div>

                <div class="mt-4 space-y-3">
                    <div>
                        <span class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">
                            {{ __('journal.attendance.work_location') }}
                        </span>
                        <p class="text-base-content text-sm font-bold">
                            {{ $registration?->placement?->company?->name ?? __('common.enums.other') }}
                        </p>
                        @if ($registration?->placement?->company?->address)
                            <p class="text-base-content/60 mt-0.5 text-xs leading-relaxed">
                                {{ $registration->placement->company->address }}
                            </p>
                        @endif
                    </div>

                    <div class="border-base-content/10 border-t pt-3">
                        <span class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">
                            {{ __('registration.wizard.label_program') }}
                        </span>
                        <p class="text-base-content text-xs font-medium">
                            {{ $registration?->internship?->name ?? __('journal.attendance.default_program_name') }}
                        </p>
                    </div>

                    @if ($registration?->internship?->start_date)
                        <div class="border-base-content/10 border-t pt-3">
                            <span class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">
                                {{ __('common.dates') }}
                            </span>
                            <p class="text-base-content/70 font-mono text-xs">
                                {{ $registration->internship->start_date->translatedFormat('d M Y') }} s/d {{ $registration->internship->end_date?->translatedFormat('d M Y') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="border-base-content/10 border-t pt-4">
                <x-ts-button
                    :href="route('student.attendance.absence')"
                    wire:navigate
                    :text="__('journal.attendance.file_absence')"
                    icon="document-text"
                    color="secondary"
                    outline
                    class="w-full justify-center"
                    sm
                />
            </div>
        </div>
    </div>

    {{-- Unaccounted Working Days (Need Clarification) — Calm, Clean Panel (No Red Glare) --}}
    @if ($unaccountedDates->isNotEmpty())
        <div class="border-base-content/10 bg-base-100 mt-6 rounded-lg border p-5 shadow-2xs">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-3.5">
                    <div class="bg-warning/10 text-warning flex size-10 shrink-0 items-center justify-center rounded-md">
                        <x-ts-icon name="calendar-days" class="size-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base-content text-sm font-bold">
                                {{ __('journal.attendance.unaccounted_title') }}
                            </h3>
                            <x-ts-badge
                                :text="__('journal.attendance.unaccounted_badge', ['count' => $unaccountedDates->count()])"
                                color="warning"
                                sm
                            />
                        </div>
                        <p class="text-base-content/70 mt-1 text-xs leading-relaxed">
                            {{ __('journal.attendance.unaccounted_desc') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="border-base-content/10 mt-4 flex flex-wrap gap-2 border-t pt-4">
                @foreach ($unaccountedDates as $missedDate)
                    <x-ts-button
                        :text="$missedDate->translatedFormat('d M Y')"
                        icon="pencil-square"
                        color="secondary"
                        outline
                        sm
                        :href="route('student.attendance.absence', ['date' => $missedDate->toDateString()])"
                        wire:navigate
                    />
                @endforeach
            </div>
        </div>
    @endif

    {{-- Recent Attendance History --}}
    @if ($recentAttendances->isNotEmpty())
        <div class="border-base-content/10 bg-base-100 mt-6 rounded-lg border p-5 shadow-2xs">
            <h3 class="text-base-content mb-4 text-sm font-bold tracking-tight">
                {{ __('journal.attendance.recent_history') }}
            </h3>

            <div class="divide-base-content/10 divide-y overflow-hidden">
                @foreach ($recentAttendances as $record)
                    <div class="flex items-center justify-between py-2.5 text-xs">
                        <div class="flex items-center gap-2.5">
                            <div class="bg-base-200/60 text-base-content/70 flex size-6 items-center justify-center rounded-md">
                                <x-ts-icon name="calendar" class="size-3.5" />
                            </div>
                            <span class="text-base-content font-medium">
                                {{ $record->date?->translatedFormat('l, d M Y') }}
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="text-base-content/60 font-mono">
                                <span>{{ $record->clock_in ? \Carbon\Carbon::parse($record->clock_in)->format('H:i') : '—' }}</span>
                                <span class="text-base-content/30 mx-1">-</span>
                                <span>{{ $record->clock_out ? \Carbon\Carbon::parse($record->clock_out)->format('H:i') : '—' }}</span>
                            </div>

                            @if ($record->clock_in && $record->clock_out)
                                <x-ts-badge :text="__('journal.attendance.completed')" color="success" sm />
                            @elseif ($record->clock_in)
                                <x-ts-badge :text="__('journal.attendance.in_progress')" color="primary" sm />
                            @else
                                <x-ts-badge :text="__('common.enums.absent')" color="warning" sm />
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
