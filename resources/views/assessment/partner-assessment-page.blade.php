<div class="mx-auto w-full max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
    {{-- Header / Breadcrumb Notice --}}
    <div class="mb-6">
        <h1 class="text-xl font-bold tracking-tight text-neutral-900 sm:text-2xl dark:text-white">
            {{ __('assessment.partner_assessment_title') }}
        </h1>
        <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
            {{ __('assessment.partner_assessment_subtitle') }}
        </p>
    </div>

    @if ($isNotFound)
        <x-ts-alert color="error" icon="x-circle"> {{ __('assessment.magic_link_not_found') }} </x-ts-alert>
    @elseif ($isExpired)
        <x-ts-alert color="warning" icon="clock"> {{ __('assessment.magic_link_expired') }} </x-ts-alert>
    @else
        @if ($isSubmitted)
            <div class="mb-6">
                <x-ts-alert color="success" icon="check-circle">
                    {{
                        __('assessment.submitted_at_notice', [
                            'date' => $this->magicLink?->submitted_at?->translatedFormat('d F Y H:i') ?? '—',
                            'name' => $evaluatorName ?: ($this->company?->name ?? __('common.partner')),
                        ])
                    }}
                </x-ts-alert>
            </div>
        @endif

        {{-- Student & Company Profile Header Card --}}
        <x-ts-card shadowless class="border-base-content/10 bg-base-100 mb-6 border">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <span class="text-base-content/50 block text-xs font-medium tracking-wider uppercase">
                        {{ __('common.student') }}
                    </span>
                    <span class="text-base-content mt-1 block font-semibold"> {{ $this->student?->name ?? '—' }} </span>
                    <span class="text-base-content/60 text-xs">
                        NISN: {{ $this->student?->profile?->nisn ?? '—' }}
                    </span>
                </div>

                <div>
                    <span class="text-base-content/50 block text-xs font-medium tracking-wider uppercase">
                        {{ __('common.company') }}
                    </span>
                    <span class="text-base-content mt-1 block font-semibold">
                        {{ $this->company?->name ?? ($this->placement?->name ?? '—') }}
                    </span>
                    <span class="text-base-content/60 text-xs">
                        {{ $this->placement?->address ?? ($this->company?->address ?? '—') }}
                    </span>
                </div>

                <div>
                    <span class="text-base-content/50 block text-xs font-medium tracking-wider uppercase">
                        {{ __('internship.title') }}
                    </span>
                    <span class="text-base-content mt-1 block font-semibold">
                        {{ $this->registration?->internship?->title ?? '—' }}
                    </span>
                    <span class="text-base-content/60 text-xs">
                        {{ $this->registration?->start_date?->format('d M Y') ?? '—' }} - {{ $this->registration?->end_date?->format('d M Y') ?? '—' }}
                    </span>
                </div>

                <div>
                    <span class="text-base-content/50 block text-xs font-medium tracking-wider uppercase">
                        {{ __('assessment.validity_days') }}
                    </span>
                    <span class="text-success mt-1 inline-flex items-center gap-1.5 text-xs font-semibold">
                        <x-ts-icon name="clock" class="size-4" />
                        {{ __('assessment.link_expires_at', ['date' => $this->magicLink?->expires_at?->translatedFormat('d M Y') ?? '—']) }}
                    </span>
                </div>
            </div>
        </x-ts-card>

        {{-- Tab Navigation --}}
        <div class="border-base-content/10 mb-6 flex border-b">
            <button
                type="button"
                wire:click="$set('activeTab', 'assessment')"
                class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors {{ $activeTab === 'assessment' ? 'border-primary text-primary' : 'border-transparent text-base-content/60 hover:text-base-content' }}"
            >
                <x-ts-icon name="clipboard-document-check" class="size-4" />
                {{ __('assessment.tab_assessment') }}
            </button>
            <button
                type="button"
                wire:click="$set('activeTab', 'journals')"
                class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors {{ $activeTab === 'journals' ? 'border-primary text-primary' : 'border-transparent text-base-content/60 hover:text-base-content' }}"
            >
                <x-ts-icon name="book-open" class="size-4" />
                {{ __('assessment.tab_journals', ['count' => $this->journals->count()]) }}
            </button>
            <button
                type="button"
                wire:click="$set('activeTab', 'attendance')"
                class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors {{ $activeTab === 'attendance' ? 'border-primary text-primary' : 'border-transparent text-base-content/60 hover:text-base-content' }}"
            >
                <x-ts-icon name="clock" class="size-4" />
                {{ __('assessment.tab_attendance') }}
            </button>
        </div>

        {{-- Tab 1: Rubric Assessment Form --}}
        @if ($activeTab === 'assessment')
            <div class="space-y-6">
                {{-- Evaluator Identification --}}
                <x-ts-card shadowless class="border-base-content/10 bg-base-100 border">
                    <x-slot:header>
                        <h2 class="text-base font-semibold text-neutral-900 dark:text-white">
                            {{ __('assessment.evaluator_identity') }}
                        </h2>
                    </x-slot:header>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ts-input
                            wire:model="evaluatorName"
                            :label="__('assessment.evaluator_name')"
                            :disabled="$isSubmitted"
                            required
                        />
                        <x-ts-input
                            wire:model="evaluatorPhone"
                            :label="__('assessment.evaluator_phone')"
                            :disabled="$isSubmitted"
                        />
                    </div>
                </x-ts-card>

                {{-- Competencies & Indicators List --}}
                @forelse ($this->competencies as $competency)
                    <x-ts-card shadowless class="border-base-content/10 bg-base-100 border">
                        <x-slot:header>
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-semibold text-neutral-900 dark:text-white">
                                        {{ $competency->name }}
                                    </h3>
                                    @if (! empty($competency->description))
                                        <p class="text-base-content/60 mt-0.5 text-xs">
                                            {{ $competency->description }}
                                        </p>
                                    @endif
                                </div>
                                @if (isset($competency->weight))
                                    <span class="badge badge-neutral text-xs">
                                        {{ __('assessment.weight') }}: {{ $competency->weight }}%
                                    </span>
                                @endif
                            </div>
                        </x-slot:header>

                        <div class="divide-base-content/10 divide-y">
                            @foreach ($competency->indicators ?? [] as $indicator)
                                <div class="flex flex-col justify-between gap-3 py-3 sm:flex-row sm:items-center">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-neutral-800 dark:text-neutral-200">
                                            {{ $indicator['name'] ?? $indicator['description'] ?? __('assessment.indicators') }}
                                        </p>
                                        @if (! empty($indicator['description']) && isset($indicator['name']))
                                            <p class="text-base-content/60 text-xs">{{ $indicator['description'] }}</p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 sm:w-36">
                                        <x-ts-input
                                            type="number"
                                            wire:model="scores.{{ $competency->id }}.{{ $indicator['id'] }}"
                                            placeholder="0 - {{ $indicator['max_score'] ?? 100 }}"
                                            min="0"
                                            max="{{ $indicator['max_score'] ?? 100 }}"
                                            :disabled="$isSubmitted"
                                        />
                                        <span class="text-base-content/50 text-xs whitespace-nowrap">
                                            / {{ $indicator['max_score'] ?? 100 }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-ts-card>
                @empty
                    <x-ts-card shadowless class="border-base-content/10 bg-base-100 border py-6 text-center">
                        <p class="text-base-content/60 text-sm">{{ __('assessment.no_rubric_available') }}</p>
                    </x-ts-card>
                @endforelse

                {{-- Feedback / Notes --}}
                <x-ts-card shadowless class="border-base-content/10 bg-base-100 border">
                    <x-ts-textarea
                        wire:model="feedback"
                        :label="__('assessment.evaluator_feedback')"
                        rows="3"
                        :disabled="$isSubmitted"
                    />
                </x-ts-card>

                {{-- Action Submit --}}
                @if (! $isSubmitted)
                    <div class="flex justify-end pt-2">
                        <x-ts-button
                            wire:click="submit"
                            color="primary"
                            icon="paper-airplane"
                            :text="__('assessment.submit_assessment')"
                            loading="submit"
                        />
                    </div>
                @endif
            </div>
        @endif

        {{-- Tab 2: Student Journals List --}}
        @if ($activeTab === 'journals')
            <div class="space-y-4">
                @forelse ($this->journals as $journal)
                    <x-ts-card shadowless class="border-base-content/10 bg-base-100 border">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="text-primary text-xs font-semibold">
                                    {{ $journal->date?->translatedFormat('l, d F Y') ?? '—' }}
                                </span>
                                <p class="text-base-content mt-1 text-sm whitespace-pre-line">
                                    {{ $journal->content }}
                                </p>
                                @if (! empty($journal->learning_outcomes))
                                    <div class="bg-base-200/50 mt-2 rounded-lg p-2.5 text-xs">
                                        <span class="text-base-content/70 font-semibold">{{ __('journal.learning_outcomes') }}:</span>
                                        <span class="text-base-content/80">{{ $journal->learning_outcomes }}</span>
                                    </div>
                                @endif
                            </div>
                            <span class="badge {{ $journal->is_verified ? 'badge-success' : 'badge-ghost' }} text-xs shrink-0">
                                {{ $journal->is_verified ? __('journal.verified') : __('journal.pending') }}
                            </span>
                        </div>
                    </x-ts-card>
                @empty
                    <x-ts-card shadowless class="border-base-content/10 bg-base-100 border py-8 text-center">
                        <x-ts-icon name="book-open" class="text-base-content/30 mx-auto size-8" />
                        <p class="text-base-content/60 mt-2 text-sm">{{ __('assessment.journal_empty') }}</p>
                    </x-ts-card>
                @endforelse
            </div>
        @endif

        {{-- Tab 3: Student Attendance --}}
        @if ($activeTab === 'attendance')
            <div class="space-y-6">
                {{-- Quick Attendance Stats --}}
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                    <div class="border-base-content/10 bg-base-100 rounded-xl border p-3 text-center">
                        <span class="text-base-content/60 text-xs">{{ __('attendance.present') }}</span>
                        <p class="text-success text-xl font-bold">{{ $this->attendanceStats['present'] }}</p>
                    </div>
                    <div class="border-base-content/10 bg-base-100 rounded-xl border p-3 text-center">
                        <span class="text-base-content/60 text-xs">{{ __('attendance.late') }}</span>
                        <p class="text-warning text-xl font-bold">{{ $this->attendanceStats['late'] }}</p>
                    </div>
                    <div class="border-base-content/10 bg-base-100 rounded-xl border p-3 text-center">
                        <span class="text-base-content/60 text-xs">{{ __('attendance.sick') }}</span>
                        <p class="text-info text-xl font-bold">{{ $this->attendanceStats['sick'] }}</p>
                    </div>
                    <div class="border-base-content/10 bg-base-100 rounded-xl border p-3 text-center">
                        <span class="text-base-content/60 text-xs">{{ __('attendance.permit') }}</span>
                        <p class="text-secondary text-xl font-bold">{{ $this->attendanceStats['permit'] }}</p>
                    </div>
                    <div class="border-base-content/10 bg-base-100 rounded-xl border p-3 text-center">
                        <span class="text-base-content/60 text-xs">{{ __('attendance.absent') }}</span>
                        <p class="text-error text-xl font-bold">{{ $this->attendanceStats['absent'] }}</p>
                    </div>
                </div>

                {{-- Attendance Logs List --}}
                <div class="border-base-content/10 bg-base-100 overflow-hidden rounded-xl border">
                    <div class="divide-base-content/10 divide-y">
                        @forelse ($this->attendances as $att)
                            <div class="flex items-center justify-between px-4 py-3 text-xs sm:text-sm">
                                <div>
                                    <span class="font-medium text-neutral-800 dark:text-neutral-200">
                                        {{ $att->date?->translatedFormat('d M Y') ?? '—' }}
                                    </span>
                                    <span class="text-base-content/50 ml-2">
                                        {{ $att->clock_in ?? '—' }} - {{ $att->clock_out ?? '—' }}
                                    </span>
                                </div>
                                <span class="badge badge-sm"> {{ $att->status?->label() ?? $att->status }} </span>
                            </div>
                        @empty
                            <div class="text-base-content/60 py-8 text-center text-sm">
                                {{ __('assessment.attendance_empty') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
