<div>
    <x-ui::header
        wire:key="batch-onboarding-header"
        :title="__('admin::ui.batch_onboarding.title')"
        :subtitle="__('admin::ui.batch_onboarding.subtitle')"
        context="admin::ui.menu.batch_onboarding"
    >
        <x-slot:actions wire:key="batch-onboarding-actions">
            <x-ui::button
                :label="__('admin::ui.batch_onboarding.download_template')"
                icon="tabler.download"
                variant="secondary"
                wire:click="downloadTemplate"
                spinner="downloadTemplate"
            />
        </x-slot:actions>
    </x-ui::header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Import Form --}}
        <div class="lg:col-span-1">
            <x-ui::card :title="__('admin::ui.batch_onboarding.import_settings')">
                <x-ui::form wire:submit="import">
                    <x-ui::select
                        :label="__('admin::ui.batch_onboarding.stakeholder_type')"
                        icon="tabler.users"
                        wire:model="type"
                        :options="[
                            ['id' => 'student', 'name' => __('admin::ui.menu.students')],
                            ['id' => 'teacher', 'name' => __('admin::ui.menu.teachers')],
                            ['id' => 'mentor',  'name' => __('admin::ui.menu.mentors')],
                        ]"
                        required
                    />

                    <x-ui::input
                        :label="__('admin::ui.batch_onboarding.batch_name')"
                        :placeholder="__('admin::ui.batch_onboarding.batch_name_placeholder')"
                        icon="tabler.tag"
                        wire:model="batchName"
                    />

                    <x-ui::input
                        :label="__('admin::ui.batch_onboarding.csv_file')"
                        type="file"
                        wire:model="file"
                        accept=".csv"
                        required
                    />

                    <div wire:loading wire:target="file" class="text-sm text-info">
                        {{ __('admin::ui.batch_onboarding.uploading') }}
                    </div>

                    <x-slot:actions>
                        <x-ui::button
                            :label="__('admin::ui.batch_onboarding.start_import')"
                            type="submit"
                            variant="primary"
                            class="w-full"
                            spinner="import"
                        />
                    </x-slot:actions>
                </x-ui::form>
            </x-ui::card>
        </div>

        {{-- Results / Instructions --}}
        <div class="lg:col-span-2">
            @if($results)
                <x-ui::card :title="__('admin::ui.batch_onboarding.import_results')">
                    <div class="mb-6 grid grid-cols-2 gap-4">
                        <div class="stat rounded-lg bg-success/10 p-4">
                            <div class="stat-title text-success">{{ __('admin::ui.batch_onboarding.success') }}</div>
                            <div class="stat-value text-success">{{ $results['success'] }}</div>
                        </div>
                        <div class="stat rounded-lg bg-error/10 p-4">
                            <div class="stat-title text-error">{{ __('admin::ui.batch_onboarding.failure') }}</div>
                            <div class="stat-value text-error">{{ $results['failure'] }}</div>
                        </div>
                    </div>

                    @if($results['success'] > 0)
                        <x-ui::alert type="warning" icon="tabler.key" class="mb-4">
                            {{ __('admin::ui.batch_onboarding.credentials_notice') }}
                            <x-ui::button
                                :label="__('admin::ui.batch_onboarding.show_codes')"
                                icon="tabler.eye"
                                variant="warning"
                                class="btn-sm mt-2"
                                wire:click="$set('credentialSlipsModal', true)"
                            />
                        </x-ui::alert>
                    @endif

                    @if(!empty($results['errors']))
                        <div class="mt-4">
                            <h4 class="mb-2 font-bold">{{ __('admin::ui.batch_onboarding.error_logs') }}</h4>
                            <div class="max-h-60 overflow-y-auto rounded bg-base-200 p-2 font-mono text-xs">
                                @foreach($results['errors'] as $error)
                                    <div class="border-b border-base-300 py-1 text-error">{{ $error }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </x-ui::card>
            @else
                <x-ui::card :title="__('admin::ui.batch_onboarding.instructions_title')">
                    <p class="mb-4">{{ __('admin::ui.batch_onboarding.instructions_intro') }}</p>
                    <ul class="list-inside list-disc space-y-2 text-sm">
                        <li>
                            <strong>{{ __('admin::ui.batch_onboarding.required_columns') }}</strong>
                            <code>name</code>
                        </li>
                        <li>
                            <strong>{{ __('admin::ui.batch_onboarding.optional_columns') }}</strong>
                            <code>email</code>, <code>username</code>, <code>phone</code>, <code>address</code>, <code>department_id</code>
                        </li>
                        <li>
                            <strong>{{ __('admin::ui.batch_onboarding.role_specific') }}</strong>
                            <ul class="ml-4 mt-1 list-inside list-disc">
                                <li>{{ __('admin::ui.menu.students') }}: <code>national_identifier</code>, <code>registration_number</code></li>
                                <li>{{ __('admin::ui.menu.teachers') }}: <code>nip</code></li>
                            </ul>
                        </li>
                    </ul>

                    <x-ui::alert type="info" icon="tabler.key" class="mt-4">
                        {{ __('admin::ui.batch_onboarding.activation_code_info') }}
                    </x-ui::alert>
                </x-ui::card>
            @endif
        </div>
    </div>

    {{-- Credential Slips Modal --}}
    <x-ui::modal wire:model="credentialSlipsModal" :title="__('user::ui.manager.credential_slips.title')" class="max-w-3xl">
        <div class="space-y-3">
            <x-ui::alert type="warning" icon="tabler.alert-triangle">
                {{ __('user::ui.manager.credential_slips.warning') }}
            </x-ui::alert>

            <div class="max-h-80 overflow-auto rounded-lg border border-base-300">
                <table class="table table-sm w-full">
                    <thead>
                        <tr>
                            <th>{{ __('user::ui.manager.table.name') }}</th>
                            <th>{{ __('user::ui.manager.table.username') }}</th>
                            <th>{{ __('user::ui.manager.credential_slips.code') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($credentialSlips as $slip)
                            <tr>
                                <td>{{ $slip['name'] }}</td>
                                <td class="font-mono">{{ $slip['username'] }}</td>
                                <td class="font-mono font-bold tracking-widest">{{ $slip['code'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.close')" wire:click="closeCredentialSlips" />
        </x-slot:actions>
    </x-ui::modal>
</div>

