<div class="animate-in fade-in slide-in-from-bottom-8 duration-1000">
    {{-- Header Section --}}
    <div class="mb-8 flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h2 class="tracking-tightest text-base-content text-3xl font-black">My Assignments</h2>
            <p class="text-base-content/40 mt-2 text-[10px] font-black tracking-[0.3em] uppercase">
                Submit your internship tasks
            </p>
        </div>
    </div>

    @if ($assignments->isEmpty())
        <x-ts-card class="!bg-base-100 shadow-base-content/5 border-base-content/5 overflow-hidden border shadow-2xl">
            <div class="flex flex-col items-center justify-center gap-4 py-20">
                <x-ts-icon name="document-text" class="text-base-content/20 size-16" />
                <h3 class="text-base-content/40 text-xl font-black tracking-tight">No assignments yet</h3>
                <p class="text-base-content/60 text-sm">Assignments will appear here once published by your school.</p>
            </div>
        </x-ts-card>
    @elseif (! $showDetail)
        {{-- Assignment List --}}
        <div class="grid grid-cols-1 gap-6">
            @foreach ($assignments as $assignment)
                <x-ts-card
                    class="!bg-base-100 shadow-base-content/5 border-base-content/5 hover:border-primary/20 cursor-pointer overflow-hidden border shadow-2xl transition-all duration-300"
                    wire:click="viewDetail('{{ $assignment->id }}')"
                >
                    <div class="flex items-start justify-between gap-6">
                        <div class="min-w-0 flex-1">
                            <div class="mb-3 flex items-center gap-3">
                                <x-ts-badge :text="$assignment->assignment_type" color="primary" xs />
                                @if ($assignment->is_mandatory)
                                    <x-ts-badge :text="__('assignment.required')" color="red" xs />
                                @else
                                    <x-ts-badge :text="__('assignment.optional')" color="white" xs />
                                @endif
                            </div>
                            <h3 class="text-base-content mb-2 text-xl font-black tracking-tight">
                                {{ $assignment->title }}
                            </h3>
                            @if ($assignment->description)
                                <p class="text-base-content/60 line-clamp-2 text-sm">{{ $assignment->description }}</p>
                            @endif
                        </div>
                        <div class="shrink-0 text-right">
                            <div class="text-base-content/40 text-sm font-black">
                                {{ $assignment->due_date?->format('d M Y') ?? __('submission.no_due_date') }}
                            </div>
                            @php
                                $submission = $assignment->submissions->first();
                            @endphp
                            @if ($submission)
                                <x-ts-badge :text="__('submission.submitted')" color="green" xs class="mt-2" />
                            @elseif ($assignment->asAssignmentRules()->isOverdue(now()))
                                <x-ts-badge :text="__('submission.overdue')" color="red" xs class="mt-2" />
                            @else
                                <x-ts-badge :text="__('submission.pending')" color="yellow" xs class="mt-2" />
                            @endif
                        </div>
                    </div>
                </x-ts-card>
            @endforeach
        </div>
    @else
        {{-- Assignment Detail --}}
        <x-ts-card class="!bg-base-100 shadow-base-content/5 border-base-content/5 overflow-hidden border shadow-2xl">
            <div class="mb-6 flex items-center justify-between">
                <x-ts-button icon="arrow-left" :text="__('common.actions.back')" wire:click="back" color="white" sm />
            </div>

            <div class="mb-4 flex items-center gap-3">
                <x-ts-badge :text="$selectedAssignment->assignment_type" color="primary" xs />
                @if ($selectedAssignment->is_mandatory)
                    <x-ts-badge :text="__('assignment.required')" color="red" xs />
                @else
                    <x-ts-badge :text="__('assignment.optional')" color="white" xs />
                @endif
                @if ($selectedAssignment->asAssignmentRules()->isOverdue(now()))
                    <x-ts-badge :text="__('submission.overdue')" color="red" xs />
                @endif
            </div>

            <h2 class="tracking-tightest text-base-content mb-2 text-3xl font-black">
                {{ $selectedAssignment->title }}
            </h2>

            <div class="text-base-content/40 mb-6 text-sm">
                Due: {{ $selectedAssignment->due_date?->format('l, d F Y') ?? __('submission.no_due_date') }}
            </div>

            @if ($selectedAssignment->description)
                <div class="prose prose-sm text-base-content/80 mb-8 max-w-none">
                    {{ $selectedAssignment->description }}
                </div>
            @endif

            {{-- Template Document --}}
            @if ($selectedAssignment->document)
                <div class="bg-primary/5 border-primary/20 shadow-primary/5 mb-8 flex items-center justify-between rounded-[2rem] border p-4 shadow-xl">
                    <div class="flex items-center gap-4">
                        <div class="bg-primary text-primary-content shadow-primary/30 flex size-12 items-center justify-center rounded-[1.5rem] shadow-lg">
                            <x-ts-icon name="document" class="size-6" />
                        </div>
                        <div>
                            <h4 class="text-primary text-sm font-black">{{ $selectedAssignment->document->name }}</h4>
                            <p class="text-primary/40 mt-1 text-[9px] font-black tracking-[0.3em] uppercase">
                                Template / Guide
                            </p>
                        </div>
                    </div>
                    <a
                        href="{{ $selectedAssignment->document->getFirstMediaUrl('file') }}"
                        target="_blank"
                        class="btn btn-primary btn-sm shadow-primary/20 rounded-[1.5rem] px-6 text-[10px] font-black tracking-wider uppercase shadow-lg"
                    >
                        <x-ts-icon name="arrow-down-tray" class="size-4" />
                        Download
                    </a>
                </div>
            @endif

            @php
                $existingSubmission = $selectedAssignment->submissions->first();
            @endphp

            @if ($existingSubmission && $existingSubmission->status->value === 'revision_required')
                {{-- Revision Required --}}
                <div class="bg-warning/5 border-warning/20 shadow-warning/5 mb-6 rounded-[2rem] border p-6 shadow-xl">
                    <div class="mb-4 flex items-center gap-4">
                        <div class="bg-warning text-warning-content shadow-warning/30 flex size-12 items-center justify-center rounded-[1.5rem] shadow-lg">
                            <x-ts-icon name="exclamation-triangle" class="size-6" />
                        </div>
                        <div>
                            <h4 class="text-warning text-sm font-black tracking-tight uppercase">Revision requested</h4>
                            <p class="text-warning/40 mt-1 text-[9px] font-black tracking-[0.3em] uppercase">
                                Please revise and resubmit
                            </p>
                        </div>
                    </div>
                    @if ($existingSubmission->feedback)
                        <div class="bg-base-200/50 rounded-[1.5rem] p-4">
                            <span class="text-base-content/40 mb-2 block text-[9px] font-black tracking-[0.2em] uppercase">Feedback</span>
                            <p class="text-base-content/70 text-sm">{{ $existingSubmission->feedback }}</p>
                        </div>
                    @endif
                </div>

                {{-- Resubmission Form --}}
                <div class="bg-base-200/30 border-base-content/5 rounded-[2rem] border p-6">
                    <h4 class="text-base-content mb-6 text-sm font-black tracking-tight uppercase">
                        Revise & Resubmit
                    </h4>
                    <div class="space-y-6">
                        <div>
                            <x-ts-textarea
                                :label="__('submission.content')"
                                wire:model="content"
                                placeholder="Update your work based on the feedback..."
                                rows="5"
                                class="border-base-content/5 focus:border-primary/30 bg-base-200/50 rounded-[1.5rem]"
                            />
                        </div>
                        <div class="border-base-content/5 flex justify-end border-t pt-4">
                            <x-ts-button
                                text="{{ __('submission.resubmit') }}"
                                icon-right="paper-airplane"
                                class="shadow-warning/30 h-12 rounded-[2rem] px-10 text-[10px] font-black tracking-[0.2em] uppercase shadow-2xl transition-transform hover:scale-[1.02]"
                                color="yellow"
                                wire:click="submit"
                                loading="submit"
                            />
                        </div>
                    </div>
                </div>
            @elseif ($existingSubmission)
                {{-- Already Submitted --}}
                <div class="bg-success/5 border-success/20 shadow-success/5 rounded-[2rem] border p-6 shadow-xl">
                    <div class="mb-4 flex items-center gap-4">
                        <div class="bg-success text-success-content shadow-success/30 flex size-12 items-center justify-center rounded-[1.5rem] shadow-lg">
                            <x-ts-icon name="check-circle" class="size-6" />
                        </div>
                        <div>
                            <h4 class="text-success text-sm font-black tracking-tight uppercase">Submitted</h4>
                            <p class="text-success/40 mt-1 text-[9px] font-black tracking-[0.3em] uppercase">
                                {{ $existingSubmission->submitted_at?->format('d M Y H:i') ?? __('submission.just_now') }}
                            </p>
                        </div>
                    </div>
                    @if ($existingSubmission->content)
                        <div class="text-base-content/70 bg-base-200/50 mb-4 rounded-[1.5rem] p-4 text-sm">
                            {{ $existingSubmission->content }}
                        </div>
                    @endif
                    @if ($existingSubmission->status->value === 'verified')
                        <div class="bg-success/10 flex items-center gap-4 rounded-[1.5rem] p-4">
                            <x-ts-icon name="shield-check" class="text-success size-5" />
                            <span class="text-success text-sm font-black tracking-tight uppercase">Verified by mentor</span>
                        </div>
                        @if ($existingSubmission->feedback)
                            <div class="bg-base-200/50 mt-3 rounded-[1.5rem] p-4">
                                <span class="text-base-content/40 mb-2 block text-[9px] font-black tracking-[0.2em] uppercase">Feedback</span>
                                <p class="text-base-content/70 text-sm">{{ $existingSubmission->feedback }}</p>
                            </div>
                        @endif
                    @elseif ($existingSubmission->status->value === 'graded')
                        <div class="bg-base-200/50 mt-3 rounded-[1.5rem] p-4">
                            @if ($existingSubmission->score)
                                <span class="text-base-content/40 mb-2 block text-[9px] font-black tracking-[0.2em] uppercase">Score</span>
                                <p class="text-base-content text-sm font-black">{{ $existingSubmission->score }}/100</p>
                            @endif
                            @if ($existingSubmission->feedback)
                                <span class="text-base-content/40 mt-3 mb-2 block text-[9px] font-black tracking-[0.2em] uppercase">Feedback</span>
                                <p class="text-base-content/70 text-sm">{{ $existingSubmission->feedback }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            @elseif (! $selectedAssignment->asAssignmentRules()->isOverdue(now()))
                {{-- Submission Form --}}
                <div class="bg-base-200/30 border-base-content/5 rounded-[2rem] border p-6">
                    <h4 class="text-base-content mb-6 text-sm font-black tracking-tight uppercase">Submit Your Work</h4>

                    <div class="space-y-6">
                        <div>
                            <x-ts-textarea
                                :label="__('submission.content')"
                                wire:model="content"
                                placeholder="Describe your work or paste your report content..."
                                rows="5"
                                class="border-base-content/5 focus:border-primary/30 bg-base-200/50 rounded-[1.5rem]"
                            />
                        </div>

                        <div>
                            <x-ts-upload
                                :label="__('submission.upload_file')"
                                wire:model="file"
                                class="border-base-content/5 focus:border-primary/30 bg-base-200/50 rounded-[1.5rem]"
                            />
                        </div>

                        <div class="border-base-content/5 flex justify-end border-t pt-4">
                            <x-ts-button
                                :text="__('submission.submit')"
                                icon-right="paper-airplane"
                                class="shadow-primary/30 h-12 rounded-[2rem] px-10 text-[10px] font-black tracking-[0.2em] uppercase shadow-2xl transition-transform hover:scale-[1.02]"
                                color="primary"
                                wire:click="submit('{{ $selectedAssignment->id }}')"
                                spinner="submit"
                            />
                        </div>
                    </div>
                </div>
            @endif
        </x-ts-card>
    @endif
</div>
