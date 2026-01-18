<div>
    <x-ui::header 
        title="{{ $form->id ? __('Edit Jurnal') : __('Buat Jurnal Baru') }}" 
        subtitle="{{ __('Catat aktivitas harian Anda dengan lengkap dan jujur.') }}" 
    />

    <x-ui::main>
        <div class="max-w-3xl mx-auto">
            <x-ui::card>
                <x-ui::form wire:submit="save">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-ui::input 
                            label="{{ __('Tanggal') }}" 
                            type="date" 
                            wire:model="form.date" 
                            required 
                        />
                    </div>

                    <x-ui::input 
                        label="{{ __('Topik Pekerjaan') }}" 
                        placeholder="{{ __('Misal: Instalasi OS, Troubleshooting Jaringan, dll') }}"
                        wire:model="form.work_topic" 
                        required 
                    />

                    <x-ui::textarea 
                        label="{{ __('Deskripsi Aktivitas') }}" 
                        placeholder="{{ __('Jelaskan apa saja yang Anda kerjakan hari ini...') }}"
                        wire:model="form.activity_description" 
                        rows="5"
                        required 
                    />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-ui::input 
                            label="{{ __('Kompetensi Dasar (KD)') }}" 
                            placeholder="{{ __('Misal: KD 3.1') }}"
                            wire:model="form.basic_competence" 
                        />
                        <x-ui::input 
                            label="{{ __('Nilai-nilai Karakter') }}" 
                            placeholder="{{ __('Misal: Disiplin, Tanggung Jawab') }}"
                            wire:model="form.character_values" 
                        />
                    </div>

                    <x-ui::textarea 
                        label="{{ __('Refleksi & Pembelajaran') }}" 
                        placeholder="{{ __('Apa hal baru yang Anda pelajari hari ini?') }}"
                        wire:model="form.reflection" 
                        rows="3"
                    />

                                    <x-ui::file
                                        label="{{ __('journal::journal.field.attachments') }}"
                                        wire:model="attachments"
                                        multiple
                                        accept="image/*,application/pdf"
                                        :preview="$attachment_urls"
                                    />
                    <x-slot:actions>
                        <x-ui::button 
                            label="{{ __('Batal') }}" 
                            link="{{ route('journal.index') }}" 
                            class="btn-ghost" 
                        />
                        <x-ui::button 
                            label="{{ __('Simpan sebagai Draf') }}" 
                            wire:click="save(true)" 
                            class="btn-outline" 
                            spinner="save(true)" 
                        />
                        <x-ui::button 
                            label="{{ __('Kirim Jurnal') }}" 
                            wire:click="save(false)" 
                            class="btn-primary" 
                            spinner="save(false)" 
                        />
                    </x-slot:actions>
                </x-ui::form>
            </x-ui::card>
        </div>
    </x-ui::main>
</div>