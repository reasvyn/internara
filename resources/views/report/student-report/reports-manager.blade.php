{{-- resources/views/report/student-report/reports-manager.blade.php --}}
<div class="w-full">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base-content text-2xl font-semibold">{{ __('report.management') }}</h1>
            <p class="text-base-content/70 mt-1">{{ __('report.management_subtitle') }}</p>
        </div>
        <x-ts-button wire:click="openCreateModal" icon="plus" :text="__('report.create_new')" color="primary" />
    </div>

    {{-- Search and Filter --}}
    <div class="card bg-base-100 mb-6 shadow-sm">
        <div class="card-body p-4">
            <div class="flex flex-col gap-4 sm:flex-row">
                <div class="flex-1">
                    <label class="input input-bordered w-full max-w-xs">
                        <svg class="text-base-content/50 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input
                            type="text"
                            wire:model.debounce.300ms="search"
                            placeholder="{{ __('report.search_placeholder') }}"
                            aria-label="{{ __('report.search_placeholder') }}"
                            class="w-full border-0 bg-transparent focus:border-0 focus:ring-0"
                        />
                    </label>
                </div>
                <div class="w-full sm:w-48">
                    <x-ts-select.native wire:model="statusFilter" class="w-full">
                        <option value="">{{ __('report.all_statuses') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">
                                {{ __('report.status_' . $status->value . '_label') }}
                            </option>
                        @endforeach
                    </x-ts-select.native>
                </div>
            </div>
        </div>
    </div>

    {{-- Reports Table --}}
    <div class="card bg-base-100 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr class="text-base-content/70 text-sm tracking-wider uppercase">
                        <th class="px-4 py-3">{{ __('report.student') }}</th>
                        <th class="px-4 py-3">{{ __('report.registration') }}</th>
                        <th class="px-4 py-3">{{ __('report.status') }}</th>
                        <th class="px-4 py-3">{{ __('report.final_score') }}</th>
                        <th class="px-4 py-3">{{ __('report.grade_letter') }}</th>
                        <th class="px-4 py-3">{{ __('report.finalized_at') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('common.actions.label') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-base-200 divide-y">
                    @forelse ($this->reports as $report)
                        <tr class="hover:bg-base-200/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $report->registration->student->name }}</div>
                                <div class="text-base-content/60 text-sm">
                                    {{ $report->registration->student->email }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div>{{ $report->registration->internship->name ?? '—' }}</div>
                                <div class="text-base-content/60 text-sm">
                                    {{ $report->registration->placement->company->name ?? '—' }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $isFinalized = $report->status->value === 'finalized';
                                @endphp
                                <x-ts-badge
                                    :text="__('report.status_'.$report->status->value.'_label')"
                                    :color="$isFinalized ? 'green' : 'yellow'"
                                    xs
                                />
                            </td>
                            <td class="px-4 py-3 font-mono">
                                {{ $report->final_score ? number_format($report->final_score, 1) : '—' }}
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $report->grade_letter ?? '—' }}</td>
                            <td class="text-base-content/70 px-4 py-3">
                                {{ $report->finalized_at ? $report->finalized_at->format('d M Y H:i') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($report->status->value === 'draft')
                                        <x-ts-button.circle
                                            wire:click="openCalculateModal('{{ $report->id }}')"
                                            icon="calculator"
                                            color="primary"
                                            outline
                                            xs
                                            aria-label="{{ __('report.calculate_grades') }}"
                                            title="{{ __('report.calculate_grades') }}"
                                        />
                                        <x-ts-button.circle
                                            wire:click="openFinalizeModal('{{ $report->id }}')"
                                            icon="check-circle"
                                            color="green"
                                            outline
                                            xs
                                            aria-label="{{ __('report.finalize_grade_card') }}"
                                            title="{{ __('report.finalize_grade_card') }}"
                                        />
                                    @endif
                                    <x-ts-button.circle
                                        href="{{ route('sysadmin.student-reports.download', $report) }}"
                                        icon="arrow-down-tray"
                                        color="secondary"
                                        outline
                                        xs
                                        aria-label="{{ __('report.download') }}"
                                        title="{{ __('report.download') }}"
                                    />
                                    @if ($report->status->value === 'draft')
                                        <x-ts-button.circle
                                            wire:click="askDelete('{{ $report->id }}')"
                                            icon="trash"
                                            color="red"
                                            outline
                                            xs
                                            aria-label="{{ __('common.actions.delete') }}"
                                            title="{{ __('common.actions.delete') }}"
                                        />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-base-content/50 px-4 py-12 text-center">
                                <svg class="text-base-content/30 mx-auto mb-4 h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p class="text-lg">{{ __('report.no_available_students') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($this->reports->hasPages())
            <div class="card-body border-base-200 border-t px-4 py-3">{{ $this->reports->links() }}</div>
        @endif
    </div>

    {{-- Create Modal --}}
    <x-ts-modal wire="createModal" :title="__('report.create_grade_card')" blur>
        <p class="text-base-content/70 mb-6 text-sm">{{ __('report.select_student') }}</p>

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
                    <x-ts-button :text="__('report.create_grade_card')" wire:click="createReport" color="primary" sm />
                @endif
            </div>
        </x-slot:footer>
    </x-ts-modal>

    {{-- Calculate Modal --}}
    <x-ts-modal wire="calculateModal" :title="__('report.calculate_grades')" blur>
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
                <x-ts-button :text="__('report.calculate_grades')" wire:click="calculateGrades" color="primary" sm />
            </div>
        </x-slot:footer>
    </x-ts-modal>

    {{-- Finalize Modal --}}
    <x-ts-modal wire="finalizeModal" :title="__('report.finalize_grade_card')" blur>
        <x-ts-alert color="warning" :text="__('report.finalize_confirm')" icon="exclamation-triangle" class="mb-4" />

        <x-slot:footer>
            <div class="flex justify-end gap-2">
                <x-ts-button
                    :text="__('common.actions.cancel')"
                    wire:click="$set('finalizeModal', false)"
                    color="slate"
                    outline
                    sm
                />
                <x-ts-button :text="__('report.finalize_grade_card')" wire:click="finalizeReport" color="green" sm />
            </div>
        </x-slot:footer>
    </x-ts-modal>

    {{-- Delete Confirm Modal --}}
    <x-ts-modal wire="showConfirm" :title="__('common.actions.delete')" blur>
        <p class="text-base-content/70 mb-4 text-sm">{{ __('common.confirm_delete') }}</p>

        <x-slot:footer>
            <div class="flex justify-end gap-2">
                <x-ts-button
                    :text="__('common.actions.cancel')"
                    wire:click="$set('showConfirm', false)"
                    color="slate"
                    outline
                    sm
                />
                <x-ts-button :text="__('common.actions.delete')" wire:click="confirmDelete" color="red" sm />
            </div>
        </x-slot:footer>
    </x-ts-modal>
</div>
