<div class="space-y-4 py-3 sm:space-y-5 sm:py-4">
    <x-ui::components.page-header
        :title="__('placement.direct_placement.title')"
        :description="__('placement.direct_placement.subtitle')"
    />

    <div class="border-base-content/10 bg-base-100 rounded-2xl border p-6 shadow-2xs">
        <form wire:submit="submit">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ts-select.native
                    :label="__('placement.direct_placement.student')"
                    wire:model="form.student_id"
                    :options="ts_options($this->students, __('placement.direct_placement.select_student'))"
                    icon="user"
                />

                <x-ts-input
                    :label="__('placement.direct_placement.academic_year')"
                    wire:model="form.academic_year"
                    placeholder="e.g. 2025/2026"
                />

                <div class="md:col-span-2">
                    <x-ts-select.native
                        :label="__('placement.direct_placement.placement')"
                        wire:model="form.placement_id"
                        :options="ts_options($this->placements, __('placement.direct_placement.select_placement'))"
                        icon="briefcase"
                    />
                </div>

                <div class="md:col-span-2">
                    <x-ts-select.native
                        :label="__('placement.direct_placement.mentors')"
                        wire:model="form.mentor_ids"
                        :options="ts_options($this->mentors, __('placement.direct_placement.select_mentors'))"
                        multiple
                        icon="user-group"
                    />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-ts-button
                    :text="__('placement.direct_placement.assign')"
                    type="submit"
                    icon="check"
                    color="primary"
                    sm
                />
            </div>
        </form>
    </div>
</div>
