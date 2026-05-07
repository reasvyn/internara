<div class="p-8">
    <x-mary-header title="Competency Logs" subtitle="Track your competency assessments and progress" separator progress-indicator>
        @if ($registration)
            <x-slot:actions>
                <x-mary-button label="Add Log" icon="o-plus" class="btn-primary" wire:click="create" />
            </x-slot:actions>
        @endif
    </x-mary-header>

    @if (!$registration)
        <div class="alert alert-warning">
            <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5" />
            <span>No active internship registration found. Please register for an internship first.</span>
        </div>
    @else
        @if ($showCreateForm)
            <x-mary-card shadow class="bg-base-100 border border-base-200 mb-6">
                <form wire:submit="{{ $editingLog ? 'update' : 'store' }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-select
                            label="Competency"
                            wire:model="competencyId"
                            :options="$competencies->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])"
                            option-value="id"
                            option-label="name"
                            placeholder="Select a competency"
                            required
                        />

                        <x-mary-input
                            label="Score"
                            type="number"
                            step="0.1"
                            min="0"
                            max="100"
                            wire:model="score"
                            placeholder="0 - 100"
                            required
                        />
                    </div>

                    <x-mary-textarea
                        label="Notes"
                        wire:model="notes"
                        placeholder="Additional notes or observations..."
                        rows="3"
                    />

                    <div class="flex justify-end gap-2 mt-4">
                        <x-mary-button label="Cancel" wire:click="cancel" />
                        <x-mary-button
                            label="{{ $editingLog ? 'Update' : 'Create' }}"
                            type="submit"
                            class="btn-primary"
                            spinner
                        />
                    </div>
                </form>
            </x-mary-card>
        @endif

        <x-mary-card shadow class="bg-base-100 border border-base-200">
            @if ($logs->isEmpty())
                <div class="text-center py-8 opacity-60">
                    <x-mary-icon name="o-clipboard-document-list" class="w-12 h-12 mx-auto mb-3" />
                    <p class="text-lg">No competency logs yet.</p>
                    <p class="text-sm">Click "Add Log" to record your first competency assessment.</p>
                </div>
            @else
                @php
                    $headers = [
                        ['key' => 'competency', 'label' => 'Competency'],
                        ['key' => 'score', 'label' => 'Score'],
                        ['key' => 'evaluator', 'label' => 'Evaluator'],
                        ['key' => 'created_at', 'label' => 'Date'],
                        ['key' => 'actions', 'label' => ''],
                    ];
                @endphp

                <x-mary-table :headers="$headers" :rows="$logs" with-pagination>
                    @scope('cell_competency', $log)
                        <div>
                            <div class="font-medium">{{ $log->competency->name }}</div>
                            @if ($log->competency->code)
                                <div class="text-xs opacity-50">{{ $log->competency->code }}</div>
                            @endif
                        </div>
                    @endscope

                    @scope('cell_score', $log)
                        <x-mary-badge
                            :value="$log->score"
                            :class="$log->score >= 80 ? 'badge-success' : ($log->score >= 60 ? 'badge-warning' : 'badge-error')"
                        />
                    @endscope

                    @scope('cell_evaluator', $log)
                        {{ $log->evaluator->name }}
                    @endscope

                    @scope('cell_created_at', $log)
                        {{ $log->created_at->format('d M Y H:i') }}
                    @endscope

                    @scope('cell_actions', $log)
                        <div class="flex gap-2">
                            <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit('{{ $log->id }}')" />
                            <x-mary-button
                                icon="o-trash"
                                class="btn-ghost btn-sm text-error"
                                wire:click="delete('{{ $log->id }}')"
                                wire:confirm="Are you sure you want to delete this competency log?"
                            />
                        </div>
                    @endscope
                </x-mary-table>
            @endif
        </x-mary-card>
    @endif
</div>
