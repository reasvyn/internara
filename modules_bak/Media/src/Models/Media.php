<?php

declare(strict_types=1);

namespace Modules\Media\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

/**
 * @property string $module
 * @property string $model_id
 * @property array $manipulations
 * @property array $custom_properties
 * @property array $generated_conversions
 * @property array $responsive_images
 */
class Media extends SpatieMedia
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'model_type',
        'model_id',
        'uuid',
        'collection_name',
        'name',
        'module',
        'file_name',
        'mime_type',
        'disk',
        'conversions_disk',
        'size',
        'manipulations',
        'custom_properties',
        'generated_conversions',
        'responsive_images',
        'order_column',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'model_id' => 'string',
        'manipulations' => 'array',
        'custom_properties' => 'array',
        'generated_conversions' => 'array',
        'responsive_images' => 'array',
    ];

    public function withModule(?string $name = null): self
    {
        $this->module = $name;

        return $this;
    }
}
