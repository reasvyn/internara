<div>
    <x-core::ui.page-header :title="__('evaluation.title')" :subtitle="__('evaluation.subtitle')" />

    <div class="mt-6 max-w-2xl mx-auto space-y-4">
        <x-mary-card>
            <form wire:submit="submit">
                <div class="space-y-4">
                    <x-mary-select
                        wire:model="mentorId"
                        :label="__('evaluation.mentor')"
                        :options="$mentors"
                        option-label="name"
                        option-value="id"
                        required
                    />

                    <x-mary-textarea
                        wire:model="feedback"
                        :label="__('evaluation.feedback')"
                        rows="4"
                        required
                    />

                    <div class="grid grid-cols-3 gap-4">
                        <x-mary-input
                            wire:model="scoreCommunication"
                            :label="__('evaluation.communication')"
                            type="number"
                            min="0"
                            max="100"
                        />
                        <x-mary-input
                            wire:model="scoreResponsiveness"
                            :label="__('evaluation.responsiveness')"
                            type="number"
                            min="0"
                            max="100"
                        />
                        <x-mary-input
                            wire:model="scoreGuidance"
                            :label="__('evaluation.guidance_quality')"
                            type="number"
                            min="0"
                            max="100"
                        />
                    </div>

                    <div class="flex justify-end gap-3">
                        <x-mary-button
                            type="submit"
                            :label="__('common.submit')"
                            class="btn-primary"
                        />
                    </div>
                </div>
            </form>
        </x-mary-card>
    </div>
</div>
