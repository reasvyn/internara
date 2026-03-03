<?php

declare(strict_types=1);

namespace Modules\Permission\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Permission\Models\Role;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'name' => fake()->unique()->word,
            'guard_name' => 'web',
            'module' => fake()->word(),
        ];
    }
}
