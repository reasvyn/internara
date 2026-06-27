<div class="animate-in fade-in slide-in-from-bottom-8 duration-1000">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8 gap-4">
        <div>
            <h2 class="text-3xl font-black tracking-tightest text-base-content">Submission Grading</h2>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-base-content/40 mt-2">Evaluate and score student submissions</p>
        </div>
    </div>

    @if (! $selectedSubmission)
        {{-- Filters --}}
        <div class="mb-8 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
            <div class="w-full lg:max-w-md relative group">
                <x-mary-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search student name..."
                    icon="o-magnifying-glass"
                    clearable
                    class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 transition-all duration-300 bg-base-200/50 focus:bg-base-100 h-14 relative z-10"
                />
            </div>
            <div class="flex gap-4 w-full lg:w-auto">
                <x-mary-select
                    wire:model.live="statusFilter"
                    placeholder="Status"
                    :options="['submitted' => 'Submitted', 'revision_required' => 'Revision Required']"
                    class="rounded-[1.5rem] border-base-content/5 bg-base-200/50 h-14 min-w-[160px]"
                />
                <x-mary-select
                    wire:model.live="assignmentFilter"
                    placeholder="Assignment"
                    :options="$assignments->pluck('title', 'id')"
                    class="rounded-[1.5rem] border-base-content/5 bg-base-200/50 h-14 min-w-[200px]"
                />
            </div>
        </div>

        {{-- Submissions List --}}
        <x-mary-card shadow class="!bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 overflow-hidden">
            @if ($submissions->isEmpty())
                <div class="flex flex-col items-center justify-center py-20 gap-4">
                    <x-mary-icon name="o-check-circle" class="size-16 text-base-content/20" />
                    <h3 class="text-xl font-black tracking-tight text-base-content/40">All caught up!</h3>
                    <p class="text-sm text-base-content/30">No submissions pending grading.</p>
                </div>
            @else
                <div class="divide-y divide-base-content/5">
                    @foreach ($submissions as $submission)
                        <div class="flex items-center justify-between p-5 hover:bg-base-200/30 transition-colors cursor-pointer" wire:click="viewSubmission('{{ $submission->id }}')">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-1">
                                    <span class="font-black text-sm text-base-content">{{ $submission->student->name }}</span>
                                    <span class="text-[10px] opacity-50">{{ $submission->assignment->type?->name ?? '—' }}</span>
                                </div>
                                <p class="text-sm text-base-content/60 truncate">{{ $submission->assignment->title }}</p>
                                <p class="text-[10px] text-base-content/40 mt-1">Submitted {{ $submission->submitted_at?->diffForHumans() ?? '—' }}</p>
                            </div>
                            <div class="text-right shrink-0 ml-4">
                                @php
                                    $badgeClass = $submission->status->value === 'submitted' ? 'badge-warning' : 'badge-info';
                                @endphp
                                <span class="badge badge-sm {{ $badgeClass }} font-black uppercase tracking-wider text-[9px] px-3 py-2 rounded-xl">
                                    {{ $submission->status->label() }}
                                </span>
                                @if ($submission->score)
                                    <div class="text-sm font-black text-base-content/60 mt-2">{{ $submission->score }}/100</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="p-5 border-t border-base-content/5">
                    {{ $submissions->links() }}
                </div>
            @endif
        </x-mary-card>
    @else
        {{-- Submission Detail / Grading Form --}}
        <x-mary-card shadow class="!bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 overflow-hidden">
            <div class="mb-6">
                <x-mary-button icon="o-arrow-left" :label="__('common.actions.back')" wire:click="back" class="btn-ghost rounded-[1.5rem] font-black uppercase tracking-widest text-[10px]" />
            </div>

            {{-- Student & Assignment Info --}}
            <div class="mb-8 p-6 bg-base-200/30 rounded-[2rem]">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-2xl font-black tracking-tight text-base-content mb-1">{{ $selectedSubmission->student->name }}</h3>
                        <p class="text-sm text-base-content/60">{{ $selectedSubmission->assignment->title }}</p>
                        <div class="flex items-center gap-3 mt-3">
                            <span class="badge badge-sm badge-soft badge-primary font-black uppercase tracking-wider text-[9px] px-3 py-2 rounded-xl">
                                {{ $selectedSubmission->assignment->type?->name ?? '—' }}
                            </span>
                            @php
                                $badgeClass = $selectedSubmission->status->value === 'submitted' ? 'badge-warning' : 'badge-info';
                            @endphp
                            <span class="badge badge-sm {{ $badgeClass }} font-black uppercase tracking-wider text-[9px] px-3 py-2 rounded-xl">
                                {{ $selectedSubmission->status->label() }}
                            </span>
                        </div>
                        <p class="text-[10px] text-base-content/40 mt-3">
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
                    <h4 class="font-black text-sm uppercase tracking-tight text-base-content mb-4">Student Submission</h4>
                    <div class="p-6 bg-base-200/30 rounded-[2rem] text-sm text-base-content/70 leading-relaxed">
                        {{ $selectedSubmission->content }}
                    </div>
                </div>
            @endif

            {{-- Uploaded File --}}
            @php $media = $selectedSubmission->getFirstMedia('file'); @endphp
            @if ($media)
                <div class="mb-8 p-4 bg-primary/5 border border-primary/20 rounded-[2rem] flex items-center justify-between shadow-xl shadow-primary/5">
                    <div class="flex items-center gap-4">
                        <div class="size-12 rounded-[1.5rem] bg-primary text-primary-content flex items-center justify-center shadow-lg shadow-primary/30">
                            <x-mary-icon name="o-document" class="size-6" />
                        </div>
                        <div>
                            <h4 class="font-black text-sm text-primary">{{ $media->file_name }}</h4>
                            <p class="text-[9px] uppercase font-black tracking-[0.3em] text-primary/40 mt-1">Attached File</p>
                        </div>
                    </div>
                    <a href="{{ $media->getUrl() }}" target="_blank" class="btn btn-primary btn-sm rounded-[1.5rem] font-black uppercase tracking-wider text-[10px] px-6 shadow-lg shadow-primary/20">
                        <x-mary-icon name="o-arrow-down-tray" class="size-4" />
                        Download
                    </a>
                </div>
            @endif

            {{-- Grading Form --}}
            <div class="p-6 bg-base-200/30 border border-base-content/5 rounded-[2rem]">
                <h4 class="font-black text-sm uppercase tracking-tight text-base-content mb-6">Grade Submission</h4>

                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-mary-input
                                :label="__('submission.score')"
                                type="number"
                                wire:model="score"
                                min="0"
                                max="100"
                                class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 bg-base-200/50 py-3"
                            />
                        </div>
                        <div>
                            <x-mary-select
                                :label="__('submission.status')"
                                wire:model="gradeStatus"
                                :options="['graded' => 'Grade & Accept', 'revision_required' => 'Request Revision']"
                                class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 bg-base-200/50"
                            />
                        </div>
                    </div>

                    <div>
                        <x-mary-textarea
                            :label="__('submission.feedback')"
                            wire:model="feedback"
                            placeholder="Provide detailed feedback for the student..."
                            rows="4"
                            class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 bg-base-200/50"
                        />
                    </div>

                    <div class="flex justify-end gap-4 pt-6 border-t border-base-content/5">
                        <x-mary-button
                            :label="__('common.actions.cancel')"
                            wire:click="back"
                            class="btn-ghost rounded-[1.5rem] font-black uppercase tracking-widest text-[10px] px-8"
                        />
                        <x-mary-button
                            :label="__('submission.submit_grade')"
                            icon="o-check-circle"
                            class="btn-primary rounded-[2rem] font-black uppercase tracking-[0.2em] text-[10px] px-10 h-12 shadow-2xl shadow-primary/30 hover:scale-[1.02] transition-transform"
                            wire:click="grade"
                            spinner="grade"
                        />
                    </div>
                </div>
            </div>
        </x-mary-card>
    @endif
</div>
