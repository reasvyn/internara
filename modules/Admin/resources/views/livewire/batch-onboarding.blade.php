<div>
    <x-ui::main title="{{ __('Batch Onboarding') }}" subtitle="{{ __('Mass import students, teachers, or mentors via CSV.') }}">
        <x-slot:actions>
            <x-ui::button label="{{ __('Download Template') }}" icon="tabler.download" class="btn-outline" wire:click="downloadTemplate" />
        </x-slot:actions>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-1">
                <x-ui::card title="{{ __('Import Settings') }}">
                    <x-ui::form wire:submit="import">
                        <x-ui::select 
                            label="{{ __('Stakeholder Type') }}" 
                            wire:model="type" 
                            :options="[
                                ['id' => 'student', 'name' => 'Student'],
                                ['id' => 'teacher', 'name' => 'Teacher'],
                                ['id' => 'mentor', 'name' => 'Industry Mentor'],
                            ]"
                            required 
                        />

                        <x-ui::input 
                            label="{{ __('CSV File') }}" 
                            type="file" 
                            wire:model="file" 
                            accept=".csv"
                            required 
                        />

                        <div wire:loading wire:target="file" class="text-sm text-info">
                            {{ __('Uploading...') }}
                        </div>

                        <x-slot:actions>
                            <x-ui::button label="{{ __('Start Import') }}" type="submit" class="btn-primary w-full" spinner="import" />
                        </x-slot:actions>
                    </x-ui::form>
                </x-ui::card>
            </div>

            <div class="lg:col-span-2">
                @if($results)
                    <x-ui::card title="{{ __('Import Results') }}">
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="stat bg-success/10 rounded-lg p-4">
                                <div class="stat-title text-success">{{ __('Success') }}</div>
                                <div class="stat-value text-success">{{ $results['success'] }}</div>
                            </div>
                            <div class="stat bg-error/10 rounded-lg p-4">
                                <div class="stat-title text-error">{{ __('Failure') }}</div>
                                <div class="stat-value text-error">{{ $results['failure'] }}</div>
                            </div>
                        </div>

                        @if(!empty($results['errors']))
                            <div class="mt-4">
                                <h4 class="font-bold mb-2">{{ __('Error Logs') }}</h4>
                                <div class="max-h-60 overflow-y-auto bg-base-200 rounded p-2 text-xs font-mono">
                                    @foreach($results['errors'] as $error)
                                        <div class="text-error border-b border-base-300 py-1">{{ $error }}</div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </x-ui::card>
                @else
                    <x-ui::card title="{{ __('Instructions') }}">
                        <p class="mb-4">{{ __('Please ensure your CSV follows the required format:') }}</p>
                        <ul class="list-disc list-inside space-y-2 text-sm">
                            <li><strong>{{ __('Required Columns:') }}</strong> <code>name</code>, <code>email</code></li>
                            <li><strong>{{ __('Optional Columns:') }}</strong> <code>username</code>, <code>password</code>, <code>phone</code>, <code>address</code></li>
                            <li><strong>{{ __('Role Specific:') }}</strong>
                                <ul class="list-circle list-inside ml-4 mt-1">
                                    <li>{{ __('Students:') }} <code>nisn</code></li>
                                    <li>{{ __('Teachers:') }} <code>nip</code></li>
                                </ul>
                            </li>
                        </ul>
                    </x-ui::card>
                @endif
            </div>
        </div>
    </x-ui::main>
</div>
