<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadBrandAssetAction
{
    public function execute(UploadedFile $file, string $type = 'logo'): string
    {
        $path = $file->store('brand', 'public');

        return Storage::url($path);
    }
}
