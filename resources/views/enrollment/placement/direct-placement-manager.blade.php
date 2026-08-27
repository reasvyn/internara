<div>
    <x-ui::components.page-header
        :title="__('placement.direct_placement.title')"
        :description="__('placement.direct_placement.subtitle')"
    />

    <x-ts-card shadowless>
        <form wire:submit="submit">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ts-select.native
                    :label="__('placement.direct_placement.student')"
                    wire:model="form.student_id"
                    :options="[null => __('placement.direct_placement.select_student')] + ($this->students)"
                    icon="user"
                />

                <x-ts-input
                    :label="__('placement.direct_placement.academic_year')"
                    wire:model="form.academic_year"
                    placeholder="e.g. 2025/2026"
                />

                <x-ts-select.native
                    :label="__('placement.direct_placement.placement')"
                    wire:model="form.placement_id"
                    :options="[null => __('placement.direct_placement.select_placement')] + ($this->placements)"
                    class="md:col-span-2"
                    icon="briefcase"
                />

                <x-ts-select.native
                    :label="__('placement.direct_placement.mentors')"
                    wire:model="form.mentor_ids"
                    :options="[null => __('placement.direct_placement.select_mentors')] + ($this->mentors)"
                    multiple
                    class="md:col-span-2"
                    icon="user-group"
                />
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-ts-button
                    :text="__('placement.direct_placement.assign')"
                    type="submit"
                    icon="check"
                    color="primary"
                />
            </div>
        </form>
</div>
