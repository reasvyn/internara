<?php

declare(strict_types=1);

namespace Modules\Guidance\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Guidance\Models\Handbook;

/**
 * Class HandbookDownloadController
 *
 * Handles secure file streaming for instructional handbooks.
 */
class HandbookDownloadController extends Controller
{
    /**
     * Download the handbook file.
     */
    public function __invoke(Handbook $handbook): mixed
    {
        // Authorization check
        $this->authorize('view', $handbook);

        $media = $handbook->getFirstMedia('document');

        if (!$media) {
            abort(404, __('Berkas tidak ditemukan.'));
        }

        return $media;
    }
}
