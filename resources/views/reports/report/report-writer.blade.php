<div>
    <x-core::ui.page-header :title="__('report.my_report')" :subtitle="__('report.my_report_subtitle')" />

    <div class="max-w-4xl mx-auto mt-6">
        <x-mary-form wire:submit="saveDraft">
            <x-mary-card>
                <div class="space-y-5">
                    <x-mary-select :label="__('report.registration')" wire:model="registrationId"
                        :placeholder="__('report.registration_placeholder')"
                        :options="$registrations"
                        option-label="internship.name"
                        option-value="id" />

                    <x-mary-input :label="__('report.title')" wire:model="title" :placeholder="__('report.title_placeholder')" />

                    <x-mary-textarea :label="__('report.content')" wire:model="chapterContent"
                        :placeholder="__('report.content_placeholder')" rows="15" />
                </div>

                <x-slot:actions>
                    <x-mary-button :label="__('report.save_draft')" class="btn-ghost" type="submit" spinner="saveDraft" />
                    <x-mary-button :label="__('report.submit')" class="btn-primary" wire:click="askSubmit" />
                </x-slot:actions>
            </x-mary-card>
        </x-mary-form>
    </div>

    <x-core::ui.confirm :message="__('report.submit_confirm')" />
</div>
