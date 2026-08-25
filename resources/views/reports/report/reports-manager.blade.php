{{-- resources/views/reports/report/reports-manager.blade.php --}}
<div class="w-full">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base-content text-2xl font-semibold">{{ __('report.management') }}</h1>
            <p class="text-base-content/70 mt-1">{{ __('report.management_subtitle') }}</p>
        </div>
        <button wire:click="openCreateModal" class="btn btn-primary">
            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            {{ __('report.create_new') }}
        </button>
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
                            class="w-full border-0 bg-transparent focus:border-0 focus:ring-0"
                        />
                    </label>
                </div>
                <div class="w-full sm:w-48">
                    <x-ts-select.native wire:model="statusFilter" class="w-full">
                        <option value="">{{ __('report.all_statuses') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">
                                {{ __('report.status_' . $status->name . '_label') }}
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
                        <th class="px-4 py-3 text-right">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-base-200 divide-y">
                    @forelse ($reports as $report)
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
                                    :text="__('report.status_'.$report->status->name.'_label')"
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
                                        <button
                                            wire:click="openCalculateModal('{{ $report->id }}')"
                                            class="btn btn-sm btn-outline btn-primary"
                                            title="{{ __('report.calculate_grades') }}"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                        </button>
                                        <button
                                            wire:click="openFinalizeModal('{{ $report->id }}')"
                                            class="btn btn-sm btn-outline btn-success"
                                            title="{{ __('report.finalize_grade_card') }}"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </button>
                                    @endif
                                    <a
                                        href="{{ route('sysadmin.reports.download', $report) }}"
                                        class="btn btn-sm btn-outline"
                                        title="{{ __('report.download') }}"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    </a>
                                    @if ($report->status->value === 'draft')
                                        <button
                                            wire:click="askDelete('{{ $report->id }}')"
                                            class="btn btn-sm btn-outline btn-error"
                                            title="{{ __('common.delete') }}"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v10M7 7h10"></path></svg>
                                        </button>
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
        @if ($reports->hasPages())
            <div class="card-body border-base-200 border-t px-4 py-3">{{ $reports->links() }}</div>
        @endif
    </div>

    {{-- Create Modal --}}
    @if ($createModal)
        <div wire:ignore.self class="modal modal-open" role="dialog">
            <div class="modal-box">
                <h3 class="mb-4 text-lg font-bold">{{ __('report.create_grade_card') }}</h3>
                <p class="text-base-content/70 mb-6">{{ __('report.select_student') }}</p>

                @if ($registrations->isEmpty())
                    <x-ts-alert color="info" :text="__('report.no_available_students')" icon="information-circle" />
                @else
                    <div class="form-control w-full max-w-md">
                        <label class="label">
                            <span class="label-text">{{ __('report.select_student_placeholder') }}</span>
                        </label>
                        <x-ts-select.native wire:model="selectedRegistrationId" class="w-full">
                            <option value="">{{ __('report.select_student_placeholder') }}</option>
                            @foreach ($registrations as $registration)
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

                <div class="modal-action mt-6">
                    <button wire:click="$set('createModal', false)" class="btn btn-ghost">
                        {{ __('common.cancel') }}
                    </button>
                    @if (! $registrations->isEmpty())
                        <button wire:click="createReport" class="btn btn-primary">
                            {{ __('report.create_grade_card') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Calculate Modal --}}
    @if ($calculateModal)
        <div wire:ignore.self class="modal modal-open" role="dialog">
            <div class="modal-box">
                <h3 class="mb-4 text-lg font-bold">{{ __('report.calculate_grades') }}</h3>
                <p class="text-base-content/70 mb-6">{{ __('report.calculate_grades_confirm') }}</p>

                <div class="modal-action">
                    <button wire:click="$set('calculateModal', false)" class="btn btn-ghost">
                        {{ __('common.cancel') }}
                    </button>
                    <button wire:click="calculateGrades" class="btn btn-primary">
                        {{ __('report.calculate_grades') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Finalize Modal --}}
    @if ($finalizeModal)
        <div wire:ignore.self class="modal modal-open" role="dialog">
            <div class="modal-box">
                <h3 class="mb-4 text-lg font-bold">{{ __('report.finalize_grade_card') }}</h3>
                <p class="text-base-content/70 mb-6">{{ __('report.finalize_confirm') }}</p>

                <x-ts-alert color="warning" :text="__('report.finalize_confirm')" icon="exclamation-triangle" class="mb-4" />

                <div class="modal-action">
                    <button wire:click="$set('finalizeModal', false)" class="btn btn-ghost">
                        {{ __('common.cancel') }}
                    </button>
                    <button wire:click="finalizeReport" class="btn btn-success">
                        {{ __('report.finalize_grade_card') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirm Modal --}}
    @if ($showConfirm && $confirmAction === 'delete')
        <div wire:ignore.self class="modal modal-open" role="dialog">
            <div class="modal-box">
                <h3 class="mb-4 text-lg font-bold">{{ __('common.delete') }}</h3>
                <p class="text-base-content/70 mb-6">{{ __('common.delete_confirm') }}</p>

                <div class="modal-action">
                    <button wire:click="$set('showConfirm', false)" class="btn btn-ghost">
                        {{ __('common.cancel') }}
                    </button>
                    <button wire:click="confirmDelete" class="btn btn-error">{{ __('common.delete') }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
