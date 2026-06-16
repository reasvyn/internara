<?php

declare(strict_types=1);

namespace App\Core\Actions\Concerns;

use App\Core\Exceptions\AppException;
use App\Core\Exceptions\ModuleException;
use App\Core\Support\SmartLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait HandlesActionErrors
{
    protected function withErrorHandling(callable $callback, string $context): mixed
    {
        try {
            return $callback();
        } catch (AppException|ModuleException|RuntimeException|ValidationException|AuthorizationException|ModelNotFoundException|NotFoundHttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            SmartLogger::error($context)
                ->withPayload([
                    'error' => $e->getMessage(),
                    'original_file' => $e->getFile(),
                    'original_line' => $e->getLine(),
                ])
                ->withPiiMasking()
                ->systemOnly()
                ->save();

            throw new RuntimeException(rtrim($context, '.').'.', 0, $e);
        }
    }
}
