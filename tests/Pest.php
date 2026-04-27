<?php

declare(strict_types=1);
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\DuskTestCase;
use Tests\TestCase;

pest()
    ->extend(DuskTestCase::class)
    ->use(DatabaseMigrations::class)
    ->in(__DIR__ . '/Browser', __DIR__ . '/../modules/*/tests/Browser');

pest()
    ->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in(
        __DIR__ . '/Feature',
        __DIR__ . '/../modules/*/tests/Feature',
        __DIR__ . '/../modules/*/Tests/Feature',
    );

pest()
    ->extend(TestCase::class)
    ->in(
        __DIR__ . '/Unit',
        __DIR__ . '/../modules/*/tests/Unit',
        __DIR__ . '/../modules/*/Tests/Unit',
    );

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

// function something()
// {
//     // ..
// }
