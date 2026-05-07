<div class="p-8">
    <x-mary-header title="Mentor Profiles" subtitle="Manage school teachers and industry supervisors" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Add Mentor" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    @if ($showForm)
        <x-mary-card shadow class="bg-base-100 border border-base-200 mb-6">
            <form wire:submit="{{ $editingMentor ? 'update' : 'store' }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if (!$editingMentor)
                        <x-mary-select
                            label="User"
                            wire:model="userId"
                            :options="$usersWithoutMentorProfile->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])"
                            option-value="id"
                            option-label="name"
                            placeholder="Select a user"
                            required
                        />
                    @endif

                    <x-mary-select
                        label="Mentor Type"
                        wire:model="type"
                        :options="[
                            ['id' => 'school_teacher', 'name' => 'School Teacher'],
                            ['id' => 'industry_supervisor', 'name' => 'Industry Supervisor'],
                        ]"
                        option-value="id"
                        option-label="name"
                        placeholder="Select type"
                        required
                    />

                    @if ($type === 'school_teacher')
                        <x-mary-input
                            label="Employee ID"
                            wire:model="employeeId"
                            placeholder="e.g., EMP-001"
                        />
                    @endif

                    @if ($type === 'industry_supervisor')
                        <x-mary-input
                            label="Company Name"
                            wire:model="companyName"
                            placeholder="Company where they supervise"
                        />
                    @endif

                    <x-mary-input label="Position" wire:model="position" placeholder="Job title" />

                    <x-mary-input label="Phone" wire:model="phone" type="tel" placeholder="Contact number" />
                </div>

                <x-mary-textarea label="Bio" wire:model="bio" placeholder="Brief background..." rows="3" class="mt-4" />

                <x-mary-input
                    label="Specialization"
                    wire:model="specialization"
                    placeholder="e.g., Web Development, Data Science (comma separated)"
                    class="mt-4"
                />

                <div class="flex justify-end gap-2 mt-4">
                    <x-mary-button label="Cancel" wire:click="cancel" />
                    <x-mary-button
                        label="{{ $editingMentor ? 'Update' : 'Create' }}"
                        type="submit"
                        class="btn-primary"
                        spinner
                    />
                </div>
            </form>
        </x-mary-card>
    @endif

    <x-mary-card shadow class="bg-base-100 border border-base-200 mb-6">
        <div class="flex gap-2 mb-4">
            <x-mary-button
                label="All"
                :class="!$filterType ? 'btn-primary' : 'btn-ghost'"
                wire:click="$set('filterType', '')"
            />
            <x-mary-button
                label="School Teachers"
                :class="$filterType === 'school_teacher' ? 'btn-primary' : 'btn-ghost'"
                wire:click="$set('filterType', 'school_teacher')"
            />
            <x-mary-button
                label="Industry Supervisors"
                :class="$filterType === 'industry_supervisor' ? 'btn-primary' : 'btn-ghost'"
                wire:click="$set('filterType', 'industry_supervisor')"
            />
        </div>

        @if ($mentors->isEmpty())
            <div class="text-center py-8 opacity-60">
                <x-mary-icon name="o-user-group" class="w-12 h-12 mx-auto mb-3" />
                <p class="text-lg">No mentor profiles found.</p>
                <p class="text-sm">Click "Add Mentor" to create a new mentor profile.</p>
            </div>
        @else
            @php
                $headers = [
                    ['key' => 'name', 'label' => 'Name'],
                    ['key' => 'type', 'label' => 'Type'],
                    ['key' => 'details', 'label' => 'Details'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'actions', 'label' => ''],
                ];
            @endphp

            <x-mary-table :headers="$headers" :rows="$mentors" with-pagination>
                @scope('cell_name', $mentor)
                    <div>
                        <div class="font-medium">{{ $mentor->user->name }}</div>
                        <div class="text-xs opacity-50">{{ $mentor->user->email }}</div>
                    </div>
                @endscope

                @scope('cell_type', $mentor)
                    <x-mary-badge
                        :value="$mentor->isSchoolTeacher() ? 'School Teacher' : 'Industry Supervisor'"
                        :class="$mentor->isSchoolTeacher() ? 'badge-primary' : 'badge-secondary'"
                    />
                @endscope

                @scope('cell_details', $mentor)
                    <div class="text-sm">
                        @if ($mentor->position)
                            <div>{{ $mentor->position }}</div>
                        @endif
                        @if ($mentor->isSchoolTeacher() && $mentor->employee_id)
                            <div class="text-xs opacity-50">ID: {{ $mentor->employee_id }}</div>
                        @endif
                        @if ($mentor->isIndustrySupervisor() && $mentor->company_name)
                            <div class="text-xs opacity-50">{{ $mentor->company_name }}</div>
                        @endif
                    </div>
                @endscope

                @scope('cell_status', $mentor)
                    <x-mary-badge
                        :value="$mentor->is_active ? 'Active' : 'Inactive'"
                        :class="$mentor->is_active ? 'badge-success' : 'badge-neutral'"
                    />
                @endscope

                @scope('cell_actions', $mentor)
                    <div class="flex gap-2">
                        <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit('{{ $mentor->id }}')" />
                        <x-mary-button
                            :icon="$mentor->is_active ? 'o-eye-slash' : 'o-eye'"
                            class="btn-ghost btn-sm"
                            wire:click="toggleStatus('{{ $mentor->id }}')"
                            wire:confirm="Toggle mentor status?"
                        />
                    </div>
                @endscope
            </x-mary-table>
        @endif
    </x-mary-card>
</div>
