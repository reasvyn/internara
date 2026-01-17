<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class AliasServiceProvider extends ServiceProvider
{
    /**
     * The model aliases to register.
     *
     * @var array<string, string>
     */
    protected array $aliases = [
        'Department' => \Modules\Department\Models\Department::class,
        'Internship' => \Modules\Internship\Models\Internship::class,
        'InternshipPlacement' => \Modules\Internship\Models\InternshipPlacement::class,
        'InternshipRegistration' => \Modules\Internship\Models\InternshipRegistration::class,
        'Media' => \Modules\Media\Models\Media::class,
        'Role' => \Modules\Permission\Models\Role::class,
        'Permission' => \Modules\Permission\Models\Permission::class,
        'Profile' => \Modules\Profile\Models\Profile::class,
        'School' => \Modules\School\Models\School::class,
        'Setting' => \Modules\Setting\Models\Setting::class,
        'User' => \Modules\User\Models\User::class,
    ];

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $loader = AliasLoader::getInstance();

        foreach ($this->aliases as $alias => $class) {
            $loader->alias($alias, $class);
        }
    }
}
