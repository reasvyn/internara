<?php

declare(strict_types=1);

use App\Domain\Core\Support\Locale;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;

test('Locale checks supported locales correctly', function () {
    expect(Locale::isSupported('id'))->toBeTrue();
    expect(Locale::isSupported('en'))->toBeTrue();
    expect(Locale::isSupported('fr'))->toBeFalse();
});

test('Locale sets locale and queues a cookie', function () {
    $cookie = new Symfony\Component\HttpFoundation\Cookie('locale', 'id');
    Cookie::shouldReceive('forever')->with('locale', 'id')->andReturn($cookie);
    Cookie::shouldReceive('queue')->once()->with($cookie);

    $result = Locale::set('id');
    expect($result)->toBeTrue();
    expect(App::getLocale())->toBe('id');
});

test('Locale metadata returns expected native names', function () {
    expect(Locale::metadata('id'))->toBe([
        'name' => 'Indonesian',
        'native' => 'Bahasa Indonesia',
    ]);
});
