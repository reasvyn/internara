<?php

declare(strict_types=1);

namespace Modules\Auth\Concerns;

trait RedirectsUsers
{
    /**
     * Get the post register / login redirect path.
     */
    public function redirectPath(): string
    {
        /** @var \Modules\Auth\Services\Contracts\RedirectService $redirectService */
        $redirectService = app(\Modules\Auth\Services\Contracts\RedirectService::class);

        return $redirectService->getTargetUrl(auth()->user());
    }
}
