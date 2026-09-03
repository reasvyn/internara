<x-ui::components.record-manager :title="__('journals.visit_title')" :subtitle="__('journals.visit_subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('journals.record_visit')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <div class="overflow-x-auto">
        <x-ts-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            class="table-sm"
        >
            @interact('column_visit_date', $v)
                <span class="text-sm">{{ $v->visit_date?->format('d M Y') }}</span>
            @endinteract

            @interact('column_method', $v)
                <x-ts-badge :text="$v->method->label()" color="white" xs />
            @endinteract

            @interact('column_is_verified', $v)
                @if ($v->is_verified)
                    <x-ts-badge :text="__('journals.verified')" color="green" xs />
                @else
                    <x-ts-badge :text="__('journals.pending')" color="yellow" xs />
                @endif
            @endinteract

            @interact('column_action', $v)
                <div class="flex justify-end gap-1">
                    @can('verify', App\Modules\Journals\Domain\MonitoringVisit\Models\MonitoringVisit::class)
                        @if (! $v->is_verified)
                            <x-ts-button.circle
                                icon="check"
                                color="green"
                                sm
                                wire:click="verify('{{ $v->id }}')"
                                aria-label="{{ __('journals.verify') }}"
                            />
                        @endif
                    @endcan
                </div>
            @endinteract
        </x-ts-table>
    </div>

    <x-slot:modal>
        <x-ts-modal wire="showModal" :title="__('journals.record_visit')" separator blur>
            <form wire:submit="save" class="space-y-5">
                <x-ts-select.native
                    :label="__('journals.student')"
                    wire:model="registrationId"
                    :options="ts_options($this->students->map(fn ($r) => ['id' => $r->id, 'name' => $r->student->name]), __('journals.select_student'))"
                />
                <x-ts-input :label="__('journals.visit_date')" wire:model="visitDate" type="date" icon="calendar" />
                <x-ts-select.native
                    :label="__('journals.method')"
                    wire:model="method"
                    :options="ts_options($this->methodOptions)"
                />
                <x-ts-input :label="__('journals.location')" wire:model="location" icon="map-pin" />
                <div class="grid grid-cols-2 gap-4">
                    <x-ts-input
                        :label="__('journals.duration_minutes')"
                        wire:model="durationMinutes"
                        type="number"
                        icon="clock"
                    />
                </div>
                <x-ts-textarea :label="__('journals.notes')" wire:model="notes" rows="3" />
                <x-ts-textarea :label="__('journals.student_condition')" wire:model="studentCondition" rows="2" />
                <x-ts-textarea :label="__('journals.company_feedback')" wire:model="companyFeedback" rows="2" />
                <x-ts-textarea :label="__('journals.follow_up')" wire:model="followUpActions" rows="2" />

                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showModal', false)"
                        color="white"
                        sm
                    />
                    <x-ts-button :text="__('common.actions.save')" color="primary" sm type="submit" loading="save" />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>
</x-ui::components.record-manager>
