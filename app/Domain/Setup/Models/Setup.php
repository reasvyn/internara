<?php

declare(strict_types=1);

namespace App\Domain\Setup\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\Core\Support\SmartLogger;
use App\Domain\Setup\Entities\SetupState;
use Database\Factories\SetupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\QueryException;

#[Fillable(['is_installed', 'setup_token', 'token_expires_at', 'completed_steps', 'school_id', 'department_id', 'recovery_key'])]
class Setup extends BaseModel
{
    use HasFactory;

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
            SmartLogger::warning('Setups table does not exist yet, assuming not installed')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();

            $model = new self;
        }

        return SetupState::fromModel($model);
    }
}
