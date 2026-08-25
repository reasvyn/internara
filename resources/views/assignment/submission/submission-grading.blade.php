<div class="animate-in fade-in slide-in-from-bottom-8 duration-1000">
    {{-- Header --}}
    <div class="mb-8 flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h2 class="tracking-tightest text-base-content text-3xl font-black">Submission Grading</h2>
            <p class="text-base-content/40 mt-2 text-[10px] font-black tracking-[0.3em] uppercase">
                Evaluate and score student submissions
            </p>
        </div>
    </div>

    @if (! $selectedSubmission)
        {{-- Filters --}}
        <div class="mb-8 flex flex-col items-start justify-between gap-6 lg:flex-row lg:items-center">
            <div class="group relative w-full lg:max-w-md">
                <x-ts-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('submission.search_placeholder') }}"
                    icon="magnifying-glass"
                    clearable
                    class="border-base-content/5 focus:border-primary/30 bg-base-200/50 focus:bg-base-100 relative z-10 h-14 rounded-[1.5rem] transition-all duration-300"
                />
            </div>
            <div class="flex w-full gap-4 lg:w-auto">
                <x-ts-select.native
                    wire:model.live="statusFilter"
                    :options="[null => __('submission.status')] + (['submitted' => __('submission.statuses.submitted'), 'revision_required' => __('submission.statuses.revision_required')])"
                    class="border-base-content/5 bg-base-200/50 h-14 min-w-[160px] rounded-[1.5rem]"
                />
                <x-ts-select.native
                    wire:model.live="assignmentFilter"
                    :options="[null => __('submission.assignment')] + ($assignments->pluck('title', 'id'))"
                    class="border-base-content/5 bg-base-200/50 h-14 min-w-[200px] rounded-[1.5rem]"
                />
            </div>
        </div>

        {{-- Submissions List --}}
        <x-ts-card class="!bg-base-100 shadow-base-content/5 border-base-content/5 overflow-hidden border shadow-2xl">
            @if ($submissions->isEmpty())
                <div class="flex flex-col items-center justify-center gap-4 py-20">
                    <x-ts-icon name="check-circle" class="text-base-content/20 size-16" />
                    <h3 class="text-base-content/40 text-xl font-black tracking-tight">All caught up!</h3>
                    <p class="text-base-content/60 text-sm">No submissions pending grading.</p>
                </div>
            @else
                <div class="divide-base-content/5 divide-y">
                    @foreach ($submissions as $submission)
                        <div
                            class="hover:bg-base-200/30 flex cursor-pointer items-center justify-between p-5 transition-colors"
                            wire:click="viewSubmission('{{ $submission->id }}')"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="mb-1 flex items-center gap-3">
                                    <span class="text-base-content text-sm font-black">{{ $submission->student->name }}</span>
                                    <span class="text-[10px] opacity-50">{{ $submission->assignment->assignment_type ?? '—' }}</span>
                                </div>
                                <p class="text-base-content/60 truncate text-sm">
                                    {{ $submission->assignment->title }}
                                </p>
                                <p class="text-base-content/40 mt-1 text-[10px]">
                                    Submitted {{ $submission->submitted_at?->diffForHumans() ?? '—' }}
                                </p>
                            </div>
                            <div class="ml-4 shrink-0 text-right">
                                @php
                                    $badgeColor = $submission->status->value === 'submitted' ? 'yellow' : 'blue';
                                @endphp
                                <x-ts-badge :text="$submission->status->label()" :color="$badgeColor" xs />
                                @if ($submission->score)
                                    <div class="text-base-content/60 mt-2 text-sm font-black">
                                        {{ $submission->score }}/100
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="border-base-content/5 border-t p-5">{{ $submissions->links() }}</div>
            @endif

    @else
        {{-- Submission Detail / Grading Form --}}
        <x-ts-card class="!bg-base-100 shadow-base-content/5 border-base-content/5 overflow-hidden border shadow-2xl">
            <div class="mb-6">
                <x-ts-button
                    icon="arrow-left"
                    :text="__('common.actions.back')"
                    wire:click="back"
                    color="white"
                    sm
                />
            </div>

            {{-- Student & Assignment Info --}}
            <div class="bg-base-200/30 mb-8 rounded-[2rem] p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-base-content mb-1 text-2xl font-black tracking-tight">
                            {{ $selectedSubmission->student->name }}
                        </h3>
                        <p class="text-base-content/60 text-sm">{{ $selectedSubmission->assignment->title }}</p>
                        <div class="mt-3 flex items-center gap-3">
                            <x-ts-badge :text="$selectedSubmission->assignment->assignment_type ?? '—'" color="primary" xs />
                            @php
                                $badgeColor = $selectedSubmission->status->value === 'submitted' ? 'yellow' : 'blue';
                            @endphp
                            <x-ts-badge :text="$selectedSubmission->status->label()" :color="$badgeColor" xs />
                        </div>
                        <p class="text-base-content/40 mt-3 text-[10px]">
                            Submitted {{ $selectedSubmission->submitted_at?->format('d M Y H:i') ?? '—' }}
                            @if ($selectedSubmission->due_date)
                                &middot; Due {{ $selectedSubmission->assignment->due_date?->format('d M Y') }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            {{-- Submission Content --}}
            @if ($selectedSubmission->content)
                <div class="mb-8">
                    <h4 class="text-base-content mb-4 text-sm font-black tracking-tight uppercase">
                        Student Submission
                    </h4>
                    <div class="bg-base-200/30 text-base-content/70 rounded-[2rem] p-6 text-sm leading-relaxed">
                        {{ $selectedSubmission->content }}
                    </div>
                </div>
            @endif

            {{-- Uploaded File --}}
            @php $media = $selectedSubmission->getFirstMedia('file'); @endphp
            @if ($media)
                <div class="bg-primary/5 border-primary/20 shadow-primary/5 mb-8 flex items-center justify-between rounded-[2rem] border p-4 shadow-xl">
                    <div class="flex items-center gap-4">
                        <div class="bg-primary text-primary-content shadow-primary/30 flex size-12 items-center justify-center rounded-[1.5rem] shadow-lg">
                            <x-ts-icon name="document" class="size-6" />
                        </div>
                        <div>
                            <h4 class="text-primary text-sm font-black">{{ $media->file_name }}</h4>
                            <p class="text-primary/40 mt-1 text-[9px] font-black tracking-[0.3em] uppercase">
                                Attached File
                            </p>
                        </div>
                    </div>
                    <a
                        href="{{ $media->getUrl() }}"
                        target="_blank"
                        class="btn btn-primary btn-sm shadow-primary/20 rounded-[1.5rem] px-6 text-[10px] font-black tracking-wider uppercase shadow-lg"
                    >
                        <x-ts-icon name="arrow-down-tray" class="size-4" />
                        Download
                    </a>
                </div>
            @endif

            {{-- Grading Form --}}
            <div class="bg-base-200/30 border-base-content/5 rounded-[2rem] border p-6">
                <h4 class="text-base-content mb-6 text-sm font-black tracking-tight uppercase">Grade Submission</h4>

                <div class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <x-ts-input
                                :label="__('submission.score')"
                                type="number"
                                wire:model="score"
                                min="0"
                                max="100"
                                class="border-base-content/5 focus:border-primary/30 bg-base-200/50 rounded-[1.5rem] py-3"
                            />
                        </div>
                        <div>
                            <x-ts-select.native
                                :label="__('submission.status')"
                                wire:model="gradeStatus"
                                :options="['graded' => __('submission.grade_accept'), 'revision_required' => __('submission.request_revision')]"
                                class="border-base-content/5 focus:border-primary/30 bg-base-200/50 rounded-[1.5rem]"
                            />
                        </div>
                    </div>

                    <div>
                        <x-ts-textarea
                            :label="__('submission.feedback')"
                            wire:model="feedback"
                            placeholder="Provide detailed feedback for the student..."
                            rows="4"
                            class="border-base-content/5 focus:border-primary/30 bg-base-200/50 rounded-[1.5rem]"
                        />
                    </div>

                    <div class="border-base-content/5 flex justify-end gap-4 border-t pt-6">
                        @if ($selectedSubmission->status->value === 'submitted')
                            <x-ts-button
                                :text="__('submission.verify')"
                                icon="shield-check"
                                class="h-12 rounded-[2rem] px-10 text-[10px] font-black tracking-[0.2em] uppercase"
                                color="green"
                                wire:click="verify('{{ $selectedSubmission->id }}')"
                                wire:confirm="{{ __('submission.confirm_verify') }}"
                                spinner="verify"
                            />
                        @endif
                        <x-ts-button
                            :text="__('common.actions.cancel')"
                            wire:click="back"
                            class="rounded-[1.5rem] px-8 text-[10px] font-black tracking-widest uppercase"
                            color="white"
                        />
                        <x-ts-button
                            :text="__('submission.submit_grade')"
                            icon="check-circle"
                            class="shadow-primary/30 h-12 rounded-[2rem] px-10 text-[10px] font-black tracking-[0.2em] uppercase shadow-2xl transition-transform hover:scale-[1.02]"
                            color="primary"
                            wire:click="grade"
                            loading="grade"
                        />
                    </div>
                </div>
            </div>

    @endif
</div>
