<div class="p-8">
    <x-mary-header title="Mentor Evaluations" subtitle="Evaluate and track mentor performance" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="New Evaluation" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    @if ($showForm)
        <x-mary-card shadow class="bg-base-100 border border-base-200 mb-6">
            <form wire:submit="{{ $editingEvaluation ? 'update' : 'store' }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-mary-select
                        label="Mentor"
                        wire:model="mentorId"
                        :options="$mentors->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])"
                        option-value="id"
                        option-label="name"
                        placeholder="Select a mentor"
                        required
                    />

                    <x-mary-input
                        label="Overall Score"
                        type="number"
                        step="0.1"
                        min="0"
                        max="100"
                        wire:model="overallScore"
                        placeholder="0 - 100"
                        required
                    />
                </div>

                <div class="mt-4">
                    <label class="label">
                        <span class="label-text font-medium">Criteria Scores</span>
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-mary-input
                            label="Communication"
                            type="number"
                            step="0.1"
                            min="0"
                            max="100"
                            wire:model="criteriaScores.communication"
                        />

                        <x-mary-input
                            label="Responsiveness"
                            type="number"
                            step="0.1"
                            min="0"
                            max="100"
                            wire:model="criteriaScores.responsiveness"
                        />

                        <x-mary-input
                            label="Guidance Quality"
                            type="number"
                            step="0.1"
                            min="0"
                            max="100"
                            wire:model="criteriaScores.guidance_quality"
                        />
                    </div>
                </div>

                <x-mary-textarea
                    label="Feedback"
                    wire:model="feedback"
                    placeholder="Detailed feedback about the mentor's performance..."
                    rows="4"
                    class="mt-4"
                />

                <div class="flex justify-end gap-2 mt-4">
                    <x-mary-button label="Cancel" wire:click="cancel" />
                    <x-mary-button
                        label="{{ $editingEvaluation ? 'Update' : 'Submit' }}"
                        type="submit"
                        class="btn-primary"
                        spinner
                    />
                </div>
            </form>
        </x-mary-card>
    @endif

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        @if ($evaluations->isEmpty())
            <div class="text-center py-8 opacity-60">
                <x-mary-icon name="o-clipboard-document-check" class="w-12 h-12 mx-auto mb-3" />
                <p class="text-lg">No mentor evaluations yet.</p>
                <p class="text-sm">Click "New Evaluation" to assess a mentor's performance.</p>
            </div>
        @else
            @php
                $headers = [
                    ['key' => 'mentor', 'label' => 'Mentor'],
                    ['key' => 'overall_score', 'label' => 'Score'],
                    ['key' => 'evaluator', 'label' => 'Evaluated By'],
                    ['key' => 'created_at', 'label' => 'Date'],
                    ['key' => 'actions', 'label' => ''],
                ];
            @endphp

            <x-mary-table :headers="$headers" :rows="$evaluations" with-pagination>
                @scope('cell_mentor', $evaluation)
                    <div>
                        <div class="font-medium">{{ $evaluation->mentor->name }}</div>
                        <div class="text-xs opacity-50">{{ $evaluation->mentor->email }}</div>
                    </div>
                @endscope

                @scope('cell_overall_score', $evaluation)
                    <x-mary-badge
                        :value="$evaluation->overall_score"
                        :class="$evaluation->overall_score >= 80 ? 'badge-success' : ($evaluation->overall_score >= 60 ? 'badge-warning' : 'badge-error')"
                    />
                @endscope

                @scope('cell_evaluator', $evaluation)
                    {{ $evaluation->evaluator->name }}
                @endscope

                @scope('cell_created_at', $evaluation)
                    {{ $evaluation->created_at->format('d M Y') }}
                @endscope

                @scope('cell_actions', $evaluation)
                    <div class="flex gap-2">
                        <x-mary-button icon="o-eye" class="btn-ghost btn-sm" wire:click="edit('{{ $evaluation->id }}')" />
                        <x-mary-button
                            icon="o-trash"
                            class="btn-ghost btn-sm text-error"
                            wire:click="delete('{{ $evaluation->id }}')"
                            wire:confirm="Are you sure you want to delete this evaluation?"
                        />
                    </div>
                @endscope
            </x-mary-table>
        @endif
    </x-mary-card>
</div>
