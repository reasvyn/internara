<?php

declare(strict_types=1);

namespace Modules\Teacher\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Profile\Models\Concerns\HasProfileMorphRelation;
use Modules\Shared\Models\Concerns\HasUuid;

/**
 * Class Teacher
 *
 * Represents specific data for a Teacher.
 */
class Teacher extends Model
{
    use HasFactory;
    use HasProfileMorphRelation;
    use HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['nip'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nip' => 'encrypted',
        ];
    }
}
