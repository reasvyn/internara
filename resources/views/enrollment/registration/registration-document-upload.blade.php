<div>
    @if($registration)
        <x-mary-card class="bg-base-100 border border-base-content/10">
            <x-slot:title>
                <span class="font-semibold">{{ __('registration.doc_upload_title') }}</span>
            </x-slot:title>

            <div class="space-y-4">
                @foreach($requirements as $req)
                    <div class="flex items-start gap-4 p-4 rounded-lg bg-base-200/30 border border-base-content/10">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium">
                                {{ $req->document?->name ?? 'Document' }}
                                @if($req->is_mandatory)
                                    <span class="text-error">*</span>
                                @endif
                            </p>
                            @php
                                $existing = $existingDocs->firstWhere('internship_document_requirement_id', $req->id);
                            @endphp
                            @if($existing)
                                <p class="text-xs text-success mt-1">{{ __('registration.doc_uploaded') }}</p>
                            @else
                                <input type="file" wire:model="uploads.{{ $req->id }}" class="file-input file-input-bordered file-input-sm w-full mt-2" accept=".pdf,.jpg,.jpeg,.png" />
                                @error("uploads.{$req->id}") <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                            @endif
                        </div>
                    </div>
                @endforeach

                @if($requirements->isNotEmpty())
                    <div class="flex justify-end">
                        <x-mary-button wire:click="upload" :label="__('common.submit')" class="btn-primary" />
                    </div>
                @else
                    <div class="text-center py-6 text-base-content/50 text-sm">
                        {{ __('registration.doc_no_requirements') }}
                    </div>
                @endif
            </div>
        </x-mary-card>
    @else
        <div class="text-center py-12 text-base-content/20">
            <x-mary-icon name="o-document" class="size-12 mb-3" />
            <p class="text-sm font-medium">{{ __('registration.doc_no_registration') }}</p>
        </div>
    @endif
</div>
