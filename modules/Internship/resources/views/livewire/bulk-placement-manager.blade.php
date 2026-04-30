<div>
    <x-ui::header 
        wire:key="bulk-placement-manager-header"
        :title="__('internship::ui.bulk_placement_title')" 
        :subtitle="__('internship::ui.bulk_placement_subtitle')"
    >
        <x-slot:actions wire:key="bulk-placement-manager-actions">
            <x-ui::button :label="__('ui::common.reset')" icon="tabler.refresh" variant="secondary" wire:click="resetForm" />
        </x-slot:actions>
    </x-ui::header>

    <x-ui::card>
        <x-ui::form wire:submit="showConfirmation">
            {{-- Internship Selection --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui::select
                    label="{{ __('internship::ui.internship_program') }}"
                    wire:model.live="internshipId"
                    :options="$this->internships"
                    placeholder="{{ __('ui::common.select') }}"
                    required
                />

                <x-ui::select
                    label="{{ __('internship::ui.select_company') }}"
                    wire:model.live="companyId"
                    :options="$this->companies"
                    placeholder="{{ __('ui::common.select') }}"
                    required
                    :disabled="!$internshipId"
                />
            </div>

            {{-- Quota Information --}}
            @if($internshipId && $companyId)
                <div class="mt-4 p-4 bg-base-200 rounded-lg border border-base-300">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-base-content">{{ __('internship::ui.remaining_quota') }}</p>
                            <p class="text-2xl font-bold text-primary">{{ $this->remainingQuota }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-base-content">{{ __('internship::ui.selected_students') }}</p>
                            <p class="text-2xl font-bold text-info">{{ count($selectedStudents) }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Student Selection --}}
            @if($internshipId)
                <div class="mt-6">
                    <label class="label">
                        <span class="label-text font-semibold">{{ __('internship::ui.select_students') }}</span>
                    </label>
                    
                    @if(count($this->availableStudents) > 0)
                        <div class="w-full overflow-auto rounded-xl border border-base-200 bg-base-100 shadow-sm max-h-[400px]">
                            <div class="p-4 space-y-2">
                                @foreach($this->availableStudents as $student)
                                    <label class="flex items-center gap-3 p-2 hover:bg-base-200 rounded-lg cursor-pointer transition-colors">
                                        <input 
                                            type="checkbox" 
                                            value="{{ $student['value'] }}"
                                            wire:model="selectedStudents"
                                            class="checkbox checkbox-primary"
                                        />
                                        <div class="flex-1">
                                            <p class="font-medium text-sm">{{ $student['student_name'] }}</p>
                                            <p class="text-xs opacity-60">{{ __('ui::common.id') }}: {{ substr($student['student_id'], 0, 8) }}...</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <x-ui::icon name="tabler.info-circle" class="size-5" />
                            <span>{{ __('internship::ui.no_unplaced_students') }}</span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Submit Button --}}
            <div class="mt-6 flex gap-3">
                <x-ui::button 
                    label="{{ __('ui::common.cancel') }}" 
                    wire:click="resetForm"
                    variant="secondary"
                />
                <x-ui::button 
                    label="{{ __('internship::ui.preview_placement') }}" 
                    type="submit"
                    class="btn-primary"
                    :disabled="empty($selectedStudents) || !$internshipId || !$companyId"
                />
            </div>
        </x-ui::form>
    </x-ui::card>

    {{-- Confirmation Modal --}}
    <x-ui::modal id="placement-confirm-modal" wire:model="confirmModal" :title="__('ui::common.confirm')">
        <div class="space-y-4">
            <p class="text-base">{{ __('internship::ui.confirm_placement_message') }}</p>
            
            <div class="bg-base-200 p-4 rounded-lg space-y-2">
                <div class="flex justify-between">
                    <span class="font-medium">{{ __('internship::ui.students_to_place') }}:</span>
                    <span class="font-bold text-primary">{{ count($selectedStudents) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium">{{ __('internship::ui.available_quota') }}:</span>
                    <span class="font-bold text-success">{{ $remainingQuota }}</span>
                </div>
            </div>

            <div class="alert alert-warning">
                <x-ui::icon name="tabler.alert-circle" class="size-5" />
                <span>{{ __('internship::ui.placement_cannot_undo') }}</span>
            </div>
        </div>

        <x-slot:actions>
            <x-ui::button label="{{ __('ui::common.cancel') }}" wire:click="$set('confirmModal', false)" />
            <x-ui::button 
                label="{{ __('internship::ui.confirm_placement') }}" 
                class="btn-error"
                wire:click="executePlacement"
                spinner="executePlacement"
            />
        </x-slot:actions>
    </x-ui::modal>
</div>
