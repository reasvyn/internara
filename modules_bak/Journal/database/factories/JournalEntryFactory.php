<?php

declare(strict_types=1);

namespace Modules\Journal\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Journal\Models\JournalEntry;

class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        $topics = [
            'Instalasi Sistem Operasi',
            'Konfigurasi Routing Statis',
            'Troubleshooting Jaringan Lokal',
            'Pemeliharaan Server',
            'Coding Backend API',
            'Desain User Interface',
            'Perbaikan Hardware Client',
            'Manajemen Database',
        ];

        $competences = [
            'KD 3.1 Menerapkan instalasi sistem operasi',
            'KD 4.2 Melakukan konfigurasi jaringan',
            'KD 3.5 Menganalisis routing statis',
            'KD 4.8 Membuat desain prototype',
        ];

        $characters = [
            'Disiplin, Tanggung Jawab',
            'Ketelitian, Kejujuran',
            'Kerjasama, Proaktif',
            'Kreativitas, Mandiri',
            'Kerja Keras, Tekun',
        ];

        return [
            'id' => Str::uuid()->toString(),
            'registration_id' => (string) Str::uuid(),
            'student_id' => (string) Str::uuid(),
            'date' => now(),
            'work_topic' => $this->faker->randomElement($topics),
            'activity_description' => $this->faker->paragraph(),
            'basic_competence' => $this->faker->randomElement($competences),
            'character_values' => $this->faker->randomElement($characters),
            'reflection' => 'Hari ini saya belajar bahwa ' . $this->faker->sentence(),
        ];
    }
}
