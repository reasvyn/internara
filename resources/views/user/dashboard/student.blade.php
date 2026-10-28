<div>
    <x-ui::components.page-header
        :title="__('dashboard.title')"
        :description="__('dashboard.student.welcome', ['name' => auth()->user()->name])"
    />

    {{-- Stats / Empty Row --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        @if ($registration)
            <x-ui::widgets.stat-card
                :title="__('dashboard.student.company')"
                :value="$registration->placement->company->name"
                icon="building-office"
                color="text-primary"
            />
            <x-ui::widgets.stat-card
                :title="__('dashboard.student.position')"
                :value="$registration->placement->name"
                icon="briefcase"
                color="text-secondary"
            />
            <x-ui::widgets.stat-card
                :title="__('dashboard.student.batch')"
                :value="$registration->internship->name"
                icon="academic-cap"
                color="text-accent"
            />
        @else
            <div class="sm:col-span-3">
                <x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
                    <x-ui::widgets.empty-state
                        icon="shield-exclamation"
                        :title="__('dashboard.student.no_registration')"
                        :description="__('dashboard.student.no_registration_hint')"
                    />
                </x-ts-card>
            </div>
        @endif
    </div>

    {{-- Action Buttons + Journal Progress --}}
    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                <x-ui::widgets.action-button
                    :label="__('dashboard.student.write_journal')"
                    icon="pencil-square"
                    link="{{ route('student.logbook') }}"
                    color="primary"
                />
                <x-ui::widgets.action-button
                    :label="__('dashboard.student.clock_in_out')"
                    icon="clock"
                    link="{{ route('student.attendance') }}"
                    color="secondary"
                />
                <x-ui::widgets.action-button
                    :label="__('dashboard.student.my_assignments')"
                    icon="document-check"
                    link="{{ route('student.assignments') }}"
                    color="accent"
                />
                <x-ui::widgets.action-button
                    :label="__('dashboard.student.request_absence')"
                    icon="document-plus"
                    link="{{ route('student.attendance.absence') }}"
                    color="white"
                />
                <x-ui::widgets.action-button
                    :label="__('dashboard.student.my_documents')"
                    icon="document-arrow-up"
                    link="{{ route('registration.documents') }}"
                    color="white"
                />
                <x-ui::widgets.action-button
                    :label="__('dashboard.student.handbooks')"
                    icon="book-open"
                    link="{{ route('student.handbooks') }}"
                    color="white"
                />
            </div>
        </div>

        <div class="space-y-4">
            <x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
                <x-slot:header>
                    <div class="flex items-center gap-2">
                        <div class="bg-primary/10 text-primary flex size-6 items-center justify-center rounded-md">
                            <x-ts-icon name="check-badge" class="size-3.5" />
                        </div>
                        <span class="text-sm font-semibold">{{ __('dashboard.student.journal_verification') }}</span>
                    </div>
                </x-slot:header>
                <div class="py-1 text-center">
                    <span class="text-primary text-2xl font-bold tabular-nums">{{ $verifiedJournals }}/{{ max($totalJournals, 1) }}</span>
                    <div class="bg-base-200 mt-2 h-2 overflow-hidden rounded-full">
                        <div
                            class="bg-success h-full rounded-full transition-all"
                            style="width: {{ $totalJournals > 0 ? ($verifiedJournals / $totalJournals) * 100 : 0 }}%"
                        ></div>
                    </div>
                </div>
            </x-ts-card>

            <x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
                <x-slot:header>
                    <div class="flex items-center gap-2">
                        <div class="bg-secondary/10 text-secondary flex size-6 items-center justify-center rounded-md">
                            <x-ts-icon name="calendar" class="size-3.5" />
                        </div>
                        <span class="text-sm font-semibold">{{ __('dashboard.student.attendance_percentage') }}</span>
                    </div>
                </x-slot:header>
                <div class="py-1 text-center">
                    <span class="text-secondary text-2xl font-bold tabular-nums">{{ $attendancePercent }}%</span>
                    <div class="bg-base-200 mt-2 h-2 overflow-hidden rounded-full">
                        <div
                            class="bg-secondary h-full rounded-full transition-all"
                            style="width: {{ $attendancePercent }}%"
                        ></div>
                    </div>
                </div>
            </x-ts-card>

            <x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
                <x-slot:header>
                    <div class="flex items-center gap-2">
                        <div class="bg-accent/10 text-accent flex size-6 items-center justify-center rounded-md">
                            <x-ts-icon name="clipboard-document-list" class="size-3.5" />
                        </div>
                        <span class="text-sm font-semibold">{{ __('dashboard.student.assignments_completed') }}</span>
                    </div>
                </x-slot:header>
                <div class="py-1 text-center">
                    <span class="text-accent text-2xl font-bold tabular-nums">{{ $assignmentSubmittedCount }}/{{ max($assignmentTotalCount, 1) }}</span>
                    <div class="bg-base-200 mt-2 h-2 overflow-hidden rounded-full">
                        <div
                            class="bg-accent h-full rounded-full transition-all"
                            style="width: {{ $assignmentTotalCount > 0 ? ($assignmentSubmittedCount / $assignmentTotalCount) * 100 : 0 }}%"
                        ></div>
                    </div>
                </div>
            </x-ts-card>

            <x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
                <x-slot:header>
                    <div class="flex items-center gap-2">
                        <div class="bg-info/10 text-info flex size-6 items-center justify-center rounded-md">
                            <x-ts-icon name="book-open" class="size-3.5" />
                        </div>
                        <span class="text-sm font-semibold">{{ __('dashboard.student.handbook_acknowledgements') }}</span>
                    </div>
                </x-slot:header>
                <div class="py-1 text-center">
                    <span class="text-info text-2xl font-bold tabular-nums">{{ $handbookReadCount }}/{{ max($handbookTotalCount, 1) }}</span>
                    <div class="bg-base-200 mt-2 h-2 overflow-hidden rounded-full">
                        <div
                            class="bg-info h-full rounded-full transition-all"
                            style="width: {{ $handbookTotalCount > 0 ? ($handbookReadCount / $handbookTotalCount) * 100 : 0 }}%"
                        ></div>
                    </div>
                </div>
            </x-ts-card>
        </div>
    </div>

    {{-- Bottom Row --}}
    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            @if ($registration)
                <x-ts-card shadowless class="border-base-content/10 bg-base-100 border">
                    <x-slot:header>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="bg-primary/10 text-primary flex size-6 items-center justify-center rounded-md">
                                    <x-ts-icon name="building-office" class="size-3.5" />
                                </div>
                                <span class="text-sm font-semibold">{{ __('dashboard.student.internship_details') }}</span>
                            </div>
                            <span class="badge badge-primary badge-outline text-xs capitalize">
                                {{ $registration->status }}
                            </span>
                        </div>
                    </x-slot:header>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <span class="text-base-content/50 block text-xs font-medium uppercase">
                                {{ __('dashboard.student.company') }}
                            </span>
                            <span class="text-base-content mt-1 block text-sm font-semibold">
                                {{ $registration->placement?->company?->name ?? '—' }}
                            </span>
                            @if ($registration->placement?->company?->industry_sector)
                                <span class="text-base-content/60 text-xs">
                                    {{ $registration->placement->company->industry_sector }}
                                </span>
                            @endif
                        </div>

                        <div>
                            <span class="text-base-content/50 block text-xs font-medium uppercase">
                                {{ __('dashboard.student.position') }}
                            </span>
                            <span class="text-base-content mt-1 block text-sm font-semibold">
                                {{ $registration->placement?->name ?? '—' }}
                            </span>
                            @if ($registration->placement?->address)
                                <span
                                    class="text-base-content/60 block truncate text-xs"
                                    title="{{ $registration->placement->address }}"
                                >
                                    {{ $registration->placement->address }}
                                </span>
                            @endif
                        </div>

                        <div>
                            <span class="text-base-content/50 block text-xs font-medium uppercase">
                                {{ __('dashboard.student.internship_period') }}
                            </span>
                            <span class="text-base-content mt-1 block text-sm font-medium">
                                {{ $registration->start_date?->translatedFormat('d M Y') ?? '—' }} &mdash; {{ $registration->end_date?->translatedFormat('d M Y') ?? '—' }}
                            </span>
                            <span class="text-base-content/60 text-xs">
                                {{ $registration->internship?->name ?? '—' }}
                            </span>
                        </div>

                        <div>
                            <span class="text-base-content/50 block text-xs font-medium uppercase">
                                {{ __('dashboard.student.academic_mentor') }}
                            </span>
                            <span class="text-base-content mt-1 block text-sm font-medium">
                                {{ $registration->mentors?->first()?->name ?? __('dashboard.student.no_mentor_assigned') }}
                            </span>
                            @if ($registration->mentors?->first()?->email)
                                <span class="text-base-content/60 text-xs">
                                    {{ $registration->mentors->first()->email }}
                                </span>
                            @endif
                        </div>
                    </div>
                </x-ts-card>
            @else
                <x-ui::widgets.profile-summary :showEdit="true" />
            @endif
        </div>

        <div class="space-y-4">
            @if ($registration)
                <x-ui::widgets.profile-summary :showEdit="true" />
            @endif
            <x-ts-card shadowless :header="__('dashboard.quick_links')">
                <div class="space-y-1">
                    <x-ui::widgets.quick-link
                        :label="__('dashboard.edit_profile')"
                        icon="user"
                        link="{{ route('profile') }}"
                    />
                    <x-ui::widgets.quick-link
                        :label="__('profile.recovery.title')"
                        icon="key"
                        link="{{ route('profile.recovery') }}"
                    />
                    <x-ui::widgets.quick-link
                        :label="__('dashboard.notifications')"
                        icon="bell"
                        link="{{ route('notifications') }}"
                    />
                    <x-ui::widgets.quick-link
                        :label="__('dashboard.student.view_evaluations')"
                        icon="star"
                        link="{{ route('assessments') }}"
                    />
                </div>
            </x-ts-card>
        </div>
    </div>

    @include('user.components.dashboard-guide')
</div>
