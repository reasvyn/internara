<x-ui::components.record-manager :title="__('report.management')" :subtitle="__('report.management_subtitle')">
    <x-slot:headerActions>
        <x-ts-button wire:click="openCreateModal" icon="plus" :text="__('report.create_new')" color="primary" sm />
    </x-slot:headerActions>

    <x-slot:filters>
        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('report.status') }}</label>
        <x-ts-select.native
            wire:model.live="filters.status"
            :options="ts_options(collect($statuses)->mapWithKeys(fn ($s) => [$s->value => __('report.status_'.$s->value.'_label')]), __('report.all_statuses'))"
        />
    </x-slot:filters>

    <x-ts-table :headers="$this->headers()" :rows="$this->rows()" :sort-by="$sortBy" with-pagination class="table-sm">
        @interact('column_student', $report)
            <div class="flex flex-col py-1">
                <span class="text-sm font-semibold">{{ $report->registration->student->name }}</span>
                <span class="font-mono text-xs opacity-50">{{ $report->registration->student->email }}</span>
            </div>
        @endinteract

        @interact('column_registration', $report)
            <div class="flex flex-col py-1">
                <span class="text-sm">{{ $report->registration->internship->name ?? '—' }}</span>
                <span class="text-base-content/50 text-xs">{{ $report->registration->placement->company->name ?? '—' }}</span>
            </div>
        @endinteract

        @interact('column_status', $report)
            <x-ts-badge
                :text="__('report.status_'.$report->status->value.'_label')"
                :color="$report->status->value === 'finalized' ? 'green' : 'yellow'"
                xs
            />
        @endinteract

        @interact('column_final_score', $report)
            <span class="font-mono text-sm">{{ $report->final_score ? number_format($report->final_score, 1) : '—' }}</span>
        @endinteract

        @interact('column_grade_letter', $report)
            <span class="text-sm font-semibold">{{ $report->grade_letter ?? '—' }}</span>
        @endinteract

        @interact('column_finalized_at', $report)
            <span class="text-sm">{{ $report->finalized_at?->format('d M Y H:i') ?? '—' }}</span>
        @endinteract

        @interact('column_actions', $report)
            <x-ui::components.table-actions>
                @if ($report->status->value === 'draft')
                    <x-ui::components.table-action
                        action="custom"
                        icon="calculator"
                        color="primary"
                        wire:click="openCalculateModal('{{ $report->id }}')"
                        :label="__('report.calculate_grades')"
                    />
                    <x-ui::components.table-action
                        action="custom"
                        icon="check-circle"
                        color="green"
                        wire:click="openFinalizeModal('{{ $report->id }}')"
                        :label="__('report.finalize_grade_card')"
                    />
                @endif
                <x-ui::components.table-action
                    action="download"
                    href="{{ route('sysadmin.student-reports.download', $report) }}"
                    :label="__('report.download')"
                />
                @if ($report->status->value === 'draft')
                    <x-ui::components.table-action action="delete" wire:click="askDelete('{{ $report->id }}')" />
                @endif
            </x-ui::components.table-actions>
        @endinteract
    </x-ts-table>

    <x-slot:modal>
        {{-- Create Modal --}}
        <x-ts-modal wire="createModal" :title="__('report.create_grade_card')" separator blur>
            <p class="text-base-content/70 mb-4 text-sm">{{ __('report.select_student') }}</p>

            @if ($this->registrations->isEmpty())
                <x-ts-alert color="info" :text="__('report.no_available_students')" icon="information-circle" />
            @else
                <div class="space-y-4">
                    <x-ts-select.native
                        :label="__('report.select_student_placeholder')"
                        wire:model="selectedRegistrationId"
                        class="w-full"
                    >
                        <option value="">{{ __('report.select_student_placeholder') }}</option>
                        @foreach ($this->registrations as $registration)
                            <option value="{{ $registration->id }}">
                                {{ $registration->student->name }} ({{ $registration->student->email }}) - {{ $registration->internship->name ?? '—' }}
                            </option>
                        @endforeach
                    </x-ts-select.native>
                    @error('selectedRegistrationId')
                        <p class="text-error mt-1 text-sm">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('createModal', false)"
                        color="slate"
                        outline
                        sm
                    />
                    @if (! $this->registrations->isEmpty())
                        <x-ts-button
                            :text="__('report.create_grade_card')"
                            wire:click="createReport"
                            color="primary"
                            sm
                        />
                    @endif
                </div>
            </x-slot:footer>
        </x-ts-modal>

        {{-- Calculate Modal --}}
        <x-ts-modal wire="calculateModal" :title="__('report.calculate_grades')" separator blur>
            <p class="text-base-content/70 mb-4 text-sm">{{ __('report.calculate_grades_confirm') }}</p>

            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('calculateModal', false)"
                        color="slate"
                        outline
                        sm
                    />
                    <x-ts-button
                        :text="__('report.calculate_grades')"
                        wire:click="calculateGrades"
                        color="primary"
                        sm
                    />
                </div>
            </x-slot:footer>
        </x-ts-modal>

        {{-- Finalize Modal --}}
        <x-ts-modal wire="finalizeModal" :title="__('report.finalize_grade_card')" separator blur>
            <p class="text-base-content/70 mb-4 text-sm">{{ __('report.finalize_grade_card_confirm') }}</p>

            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('finalizeModal', false)"
                        color="slate"
                        outline
                        sm
                    />
                    <x-ts-button
                        :text="__('report.finalize_grade_card')"
                        wire:click="finalizeReport"
                        color="green"
                        sm
                    />
                </div>
            </x-slot:footer>
        </x-ts-modal>
    </x-slot:modal>
</x-ui::components.record-manager>
