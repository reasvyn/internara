<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Setup\SetupState;
use Database\Factories\SetupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class Setup extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'is_installed',
        'setup_token',
        'token_expires_at',
        'completed_steps',
        'school_id',
        'department_id',
        'recovery_key',
    ];

    protected function casts(): array
    {
        return [
            'is_installed' => 'boolean',
            'token_expires_at' => 'datetime',
            'completed_steps' => 'array',
        ];
    }

    protected static function newFactory(): SetupFactory
    {
        return SetupFactory::new();
    }

    public function asSetupState(): SetupState
    {
        return SetupState::fromModel($this);
    }

    public static function state(): SetupState
    {
        try {
            $model = self::latest('created_at')->first() ?? new self;
        } catch (QueryException $e) {
            Log::warning('Setups table does not exist yet, assuming not installed', [
                'error' => $e->getMessage(),
            ]);

            $model = new self;
        }

        return SetupState::fromModel($model);
    }
}
