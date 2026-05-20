<div>
    <x-mary-header title="Document Requirements" subtitle="Configure required documents for this internship program" separator>
        <x-slot:actions>
            <x-mary-button label="Add Requirement" icon="o-plus" wire:click="add" class="btn-primary" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card>
        @if($this->internship->documentRequirements->isEmpty())
            <div class="text-center py-12 text-base-content/40">
                <x-mary-icon name="o-document" class="size-16 mx-auto mb-4 opacity-30" />
                <p class="text-lg font-medium">No requirements configured yet.</p>
                <p class="text-sm">Add document requirements that students must submit during registration.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($this->internship->documentRequirements as $requirement)
                    <div class="flex items-center justify-between p-4 bg-base-200/50 rounded-xl border border-base-200">
                        <div class="flex items-center gap-4">
                            <x-mary-icon name="o-document-text" class="size-8 text-primary/60" />
                            <div>
                                <p class="font-medium">{{ $requirement->document->name }}</p>
                                <p class="text-xs text-base-content/40">
                                    {{ $requirement->document->category?->label() ?? $requirement->document->category }}
                                    @if($requirement->is_mandatory)
                                        <span class="text-error font-medium"> • Mandatory</span>
                                    @else
                                        <span class="text-base-content/30"> • Optional</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-mary-button icon="o-pencil" wire:click="edit('{{ $requirement->id }}')" class="btn-sm btn-ghost" />
                            <x-mary-button icon="o-trash" wire:click="remove('{{ $requirement->id }}')" wire:confirm="Remove this requirement?" class="btn-sm btn-ghost text-error" />
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-mary-card>

    <x-mary-modal wire:model="requirementModal" title="{{ $formData['id'] ? 'Edit Requirement' : 'Add Requirement' }}" separator class="backdrop-blur-sm">
        <x-mary-form wire:submit="save">
            <x-mary-select
                label="Document Template"
                wire:model="formData.document_id"
                :options="$this->availableDocuments"
                placeholder="Select a document template"
                icon="o-document-text" />

            <x-mary-checkbox label="Mandatory (student must upload this document)" wire:model="formData.is_mandatory" />

            <x-slot:actions>
                <x-mary-button label="Cancel" wire:click="$set('requirementModal', false)" />
                <x-mary-button label="Save" type="submit" icon="o-check" class="btn-primary" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
