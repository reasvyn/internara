<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\InfrastructureException;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Core\Exceptions\UnauthorizedException;
use Illuminate\Support\Facades\Route;

function renderRoute(string $path, Throwable $exception): void
{
    Route::get($path, fn () => throw $exception);
}

test('89SRA-FR-ER1/FR-ER3: UnauthorizedException renders as 403 with a JSON message', function () {
    renderRoute('/test-render/unauthorized', new UnauthorizedException);

    $this->getJson('/test-render/unauthorized')
        ->assertStatus(403)
        ->assertJson(['message' => 'Unauthorized']);
});

test('89SRA-FR-ER1/FR-ER3: InfrastructureException renders as 500 with generic message', function () {
    renderRoute('/test-render/infrastructure', new class extends InfrastructureException {});

    $this->getJson('/test-render/infrastructure')
        ->assertStatus(500)
        ->assertJson(['message' => __('exceptions.unexpected')]);
});

test('89SRA-FR-ER2/FR-ER3: ModuleException renders as 400 with the exception message', function () {
    renderRoute('/test-render/rejected', new RejectedException('Business rule violated'));

    $this->getJson('/test-render/rejected')
        ->assertStatus(400)
        ->assertJson(['message' => 'Business rule violated']);
});

test('89SRA-FR-ER4: non-JSON requests receive an error page, not a JSON response', function () {
    renderRoute('/test-render/rejected', new RejectedException('Business rule violated'));

    $this->get('/test-render/rejected')->assertStatus(400);
});
