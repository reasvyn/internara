<?php

declare(strict_types=1);

namespace Modules\Assignment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Models\Concerns\HasUuid;

class AssignmentType extends Model
{
    use HasFactory, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'slug', 'handler_class', 'description'];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Modules\Assignment\Database\Factories\AssignmentTypeFactory
    {
        return \Modules\Assignment\Database\Factories\AssignmentTypeFactory::new();
    }
}
