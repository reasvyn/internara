<x-core::ui.record-manager
    :title="__('journals.visit_title')"
    :subtitle="__('journals.visit_subtitle')"
>
    <x-slot:headerActions>
        <x-mary-button :label="__('journals.record_visit')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <x-core::ui.confirm
        wire:model="showConfirm"
        confirmText="{{ __('common.actions.confirm') }}"
        cancelText="{{ __('common.actions.cancel') }}"
    />

    <div class="overflow-x-auto">
        <x-mary-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            class="table-sm"
        >
            @scope('cell_visit_date', $v)
                <span class="text-sm">{{ $v->visit_date?->format('d M Y') }}</span>
            @endscope

            @scope('cell_method', $v)
                <x-mary-badge :value="$v->method->label()" class="badge-ghost badge-sm" />
            @endscope

            @scope('cell_is_verified', $v)
                @if($v->is_verified)
                    <x-mary-badge :value="__('journals.verified')" class="badge-success badge-sm" />
                @else
                    <x-mary-badge :value="__('journals.pending')" class="badge-warning badge-sm" />
                @endif
            @endscope

            @scope('actions', $v)
                <div class="flex justify-end gap-1">
                    @can('verify', App\Journals\MonitoringVisit\Models\MonitoringVisit::class)
                        @if(!$v->is_verified)
                            <x-mary-button icon="o-check" class="btn-ghost btn-sm text-success" wire:click="askVerify('{{ $v->id }}')" :aria-label="__('journals.verify')" />
                        @endif
                    @endcan
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-slot:modal>
        <x-mary-modal wire:model="showModal" :title="__('journals.record_visit')" separator class="backdrop-blur-sm">
            <x-mary-form wire:submit="save" class="space-y-5">
                <x-mary-select :label="__('journals.student')" wire:model="registrationId" :options="$this->students->map(fn($r) => ['id' => $r->id, 'name' => $r->student->name])" :placeholder="__('journals.select_student')" />
                <x-mary-input :label="__('journals.visit_date')" wire:model="visitDate" type="date" icon="o-calendar" />
                <x-mary-select :label="__('journals.method')" wire:model="method" :options="$this->methodOptions" />
                <x-mary-input :label="__('journals.location')" wire:model="location" icon="o-map-pin" />
                <div class="grid grid-cols-2 gap-4">
                    <x-mary-input :label="__('journals.duration_minutes')" wire:model="durationMinutes" type="number" icon="o-clock" />
                </div>
                <x-mary-textarea :label="__('journals.notes')" wire:model="notes" rows="3" />
                <x-mary-textarea :label="__('journals.student_condition')" wire:model="studentCondition" rows="2" />
                <x-mary-textarea :label="__('journals.company_feedback')" wire:model="companyFeedback" rows="2" />
                <x-mary-textarea :label="__('journals.follow_up')" wire:model="followUpActions" rows="2" />

                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('common.actions.save')" class="btn-primary btn-sm" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-core::ui.record-manager>
