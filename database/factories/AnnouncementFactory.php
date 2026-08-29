<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\SysAdmin\Domain\Announcement\Enums\AnnouncementStatus;
use App\Modules\SysAdmin\Domain\Announcement\Models\Announcement;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'message' => fake()->paragraph(),
            'type' => 'info',
            'status' => AnnouncementStatus::DRAFT->value,
            'link' => null,
            'target_roles' => null,
            'created_by' => User::factory(),
        ];
    }
}
