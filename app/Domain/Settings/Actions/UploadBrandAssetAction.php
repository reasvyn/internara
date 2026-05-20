<?php

declare(strict_types=1);

namespace App\Domain\Settings\Actions;

use App\Domain\Core\Actions\BaseAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadBrandAssetAction extends BaseAction
{
    public function execute(UploadedFile $file, string $type = 'logo'): string
    {
        $path = $file->store('brand', 'public');

        return Storage::url($path);
    }
}
