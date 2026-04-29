<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasUuid;

    protected $fillable = [
        'institutional_code',
        'name',
        'address',
        'email',
        'phone',
        'fax',
        'principal_name',
    ];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }
}
