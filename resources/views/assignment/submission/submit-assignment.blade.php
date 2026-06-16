<div class="animate-in fade-in slide-in-from-bottom-8 duration-1000">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8 gap-4">
        <div>
            <h2 class="text-3xl font-black tracking-tightest text-base-content">My Assignments</h2>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-base-content/40 mt-2">Submit your internship tasks</p>
        </div>
    </div>

    @if($assignments->isEmpty())
        <x-mary-card shadow class="!bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 overflow-hidden">
            <div class="flex flex-col items-center justify-center py-20 gap-4">
                <x-mary-icon name="o-document-text" class="size-16 text-base-content/20" />
                <h3 class="text-xl font-black tracking-tight text-base-content/40">No assignments yet</h3>
                <p class="text-sm text-base-content/30">Assignments will appear here once published by your school.</p>
            </div>
        </x-mary-card>
    @elseif(!$showDetail)
        {{-- Assignment List --}}
        <div class="grid grid-cols-1 gap-6">
            @foreach($assignments as $assignment)
                <x-mary-card shadow class="!bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 overflow-hidden hover:border-primary/20 transition-all duration-300 cursor-pointer" wire:click="viewDetail('{{ $assignment->id }}')">
                    <div class="flex items-start justify-between gap-6">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="badge badge-sm badge-soft badge-primary font-black uppercase tracking-wider text-[9px] px-3 py-2 rounded-xl">
                                    {{ $assignment->type->name }}
                                </span>
                                @if($assignment->is_mandatory)
                                    <span class="badge badge-sm badge-soft badge-error font-black uppercase tracking-wider text-[9px] px-3 py-2 rounded-xl">Required</span>
                                @else
                                    <span class="badge badge-sm badge-soft badge-ghost font-black uppercase tracking-wider text-[9px] px-3 py-2 rounded-xl">Optional</span>
                                @endif
                            </div>
                            <h3 class="text-xl font-black tracking-tight text-base-content mb-2">{{ $assignment->title }}</h3>
                            @if($assignment->description)
                                <p class="text-sm text-base-content/60 line-clamp-2">{{ $assignment->description }}</p>
                            @endif
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-sm font-black text-base-content/40">{{ $assignment->due_date?->format('d M Y') ?? 'No due date' }}</div>
                            @php
                                $submission = $assignment->submissions->first();
                            @endphp
                            @if($submission)
                                <span class="badge badge-sm badge-soft badge-success font-black uppercase tracking-wider text-[9px] px-3 py-2 rounded-xl mt-2">Submitted</span>
                            @elseif($assignment->asAssignmentRules()->isOverdue())
                                <span class="badge badge-sm badge-soft badge-error font-black uppercase tracking-wider text-[9px] px-3 py-2 rounded-xl mt-2">Overdue</span>
                            @else
                                <span class="badge badge-sm badge-soft badge-warning font-black uppercase tracking-wider text-[9px] px-3 py-2 rounded-xl mt-2">Pending</span>
                            @endif
                        </div>
                    </div>
                </x-mary-card>
            @endforeach
        </div>
    @else
        {{-- Assignment Detail --}}
        <x-mary-card shadow class="!bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 overflow-hidden">
            <div class="mb-6">
                <x-mary-button icon="o-arrow-left" :label="__('common.actions.back')" wire:click="back" class="btn-ghost rounded-[1.5rem] font-black uppercase tracking-widest text-[10px]" />
            </div>

            <div class="flex items-center gap-3 mb-4">
                <span class="badge badge-sm badge-soft badge-primary font-black uppercase tracking-wider text-[9px] px-3 py-2 rounded-xl">
                    {{ $selectedAssignment->type->name }}
                </span>
                @if($selectedAssignment->is_mandatory)
                    <span class="badge badge-sm badge-soft badge-error font-black uppercase tracking-wider text-[9px] px-3 py-2 rounded-xl">Required</span>
                @else
                    <span class="badge badge-sm badge-soft badge-ghost font-black uppercase tracking-wider text-[9px] px-3 py-2 rounded-xl">Optional</span>
                @endif
                @if($selectedAssignment->asAssignmentRules()->isOverdue())
                    <span class="badge badge-sm badge-soft badge-error font-black uppercase tracking-wider text-[9px] px-3 py-2 rounded-xl">Overdue</span>
                @endif
            </div>

            <h2 class="text-3xl font-black tracking-tightest text-base-content mb-2">{{ $selectedAssignment->title }}</h2>

            <div class="text-sm text-base-content/40 mb-6">
                Due: {{ $selectedAssignment->due_date?->format('l, d F Y') ?? 'No due date' }}
            </div>

            @if($selectedAssignment->description)
                <div class="prose prose-sm max-w-none mb-8 text-base-content/80">
                    {{ $selectedAssignment->description }}
                </div>
            @endif

            {{-- Template Document --}}
            @if($selectedAssignment->document)
                <div class="mb-8 p-4 bg-primary/5 border border-primary/20 rounded-[2rem] flex items-center justify-between shadow-xl shadow-primary/5">
                    <div class="flex items-center gap-4">
                        <div class="size-12 rounded-[1.5rem] bg-primary text-primary-content flex items-center justify-center shadow-lg shadow-primary/30">
                            <x-mary-icon name="o-document" class="size-6" />
                        </div>
                        <div>
                            <h4 class="font-black text-sm text-primary">{{ $selectedAssignment->document->name }}</h4>
                            <p class="text-[9px] uppercase font-black tracking-[0.3em] text-primary/40 mt-1">Template / Guide</p>
                        </div>
                    </div>
                    <a href="{{ $selectedAssignment->document->getFirstMediaUrl('file') }}" target="_blank" class="btn btn-primary btn-sm rounded-[1.5rem] font-black uppercase tracking-wider text-[10px] px-6 shadow-lg shadow-primary/20">
                        <x-mary-icon name="o-arrow-down-tray" class="size-4" />
                        Download
                    </a>
                </div>
            @endif

            @php
                $existingSubmission = $selectedAssignment->submissions->first();
            @endphp

            @if($existingSubmission)
                {{-- Already Submitted --}}
                <div class="p-6 bg-success/5 border border-success/20 rounded-[2rem] shadow-xl shadow-success/5">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="size-12 rounded-[1.5rem] bg-success text-success-content flex items-center justify-center shadow-lg shadow-success/30">
                            <x-mary-icon name="o-check-circle" class="size-6" />
                        </div>
                        <div>
                            <h4 class="font-black text-sm text-success uppercase tracking-tight">Submitted</h4>
                            <p class="text-[9px] uppercase font-black tracking-[0.3em] text-success/40 mt-1">{{ $existingSubmission->submitted_at?->format('d M Y H:i') ?? 'Just now' }}</p>
                        </div>
                    </div>
                    @if($existingSubmission->content)
                        <div class="text-sm text-base-content/70 mb-4 p-4 bg-base-200/50 rounded-[1.5rem]">
                            {{ $existingSubmission->content }}
                        </div>
                    @endif
                    @if($existingSubmission->status->value === 'verified')
                        <div class="flex items-center gap-4 p-4 bg-success/10 rounded-[1.5rem]">
                            <x-mary-icon name="o-shield-check" class="size-5 text-success" />
                            <span class="font-black text-sm text-success uppercase tracking-tight">Verified by mentor</span>
                        </div>
                        @if($existingSubmission->feedback)
                            <div class="mt-3 p-4 bg-base-200/50 rounded-[1.5rem]">
                                <span class="text-[9px] font-black uppercase tracking-[0.2em] text-base-content/40 block mb-2">Feedback</span>
                                <p class="text-sm text-base-content/70">{{ $existingSubmission->feedback }}</p>
                            </div>
                        @endif
                    @elseif($existingSubmission->status->value === 'revision_required')
                        <div class="flex items-center gap-4 p-4 bg-warning/10 rounded-[1.5rem]">
                            <x-mary-icon name="o-exclamation-triangle" class="size-5 text-warning" />
                            <span class="font-black text-sm text-warning uppercase tracking-tight">Revision requested</span>
                        </div>
                        @if($existingSubmission->feedback)
                            <div class="mt-3 p-4 bg-base-200/50 rounded-[1.5rem]">
                                <span class="text-[9px] font-black uppercase tracking-[0.2em] text-base-content/40 block mb-2">Feedback</span>
                                <p class="text-sm text-base-content/70">{{ $existingSubmission->feedback }}</p>
                            </div>
                        @endif
                    @endif
                </div>
            @elseif(!$selectedAssignment->asAssignmentRules()->isOverdue())
                {{-- Submission Form --}}
                <div class="p-6 bg-base-200/30 border border-base-content/5 rounded-[2rem]">
                    <h4 class="font-black text-sm uppercase tracking-tight text-base-content mb-6">Submit Your Work</h4>

                    <div class="space-y-6">
                        <div>
                            <x-mary-textarea
                                :label="__('submission.content')"
                                wire:model="content"
                                placeholder="Describe your work or paste your report content..."
                                rows="5"
                                class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 bg-base-200/50"
                            />
                        </div>

                        <div>
                            <x-mary-file
                                :label="__('submission.upload_file')"
                                wire:model="file"
                                class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 bg-base-200/50"
                            />
                        </div>

                        <div class="flex justify-end pt-4 border-t border-base-content/5">
                            <x-mary-button
                                :label="__('submission.submit')"
                                icon-right="o-paper-airplane"
                                class="btn-primary rounded-[2rem] font-black uppercase tracking-[0.2em] text-[10px] px-10 h-12 shadow-2xl shadow-primary/30 hover:scale-[1.02] transition-transform"
                                wire:click="submit('{{ $selectedAssignment->id }}')"
                                spinner="submit"
                            />
                        </div>
                    </div>
                </div>
            @endif
        </x-mary-card>
    @endif
</div>
