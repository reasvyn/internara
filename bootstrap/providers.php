<?php

declare(strict_types=1);
use App\Providers\AppServiceProvider;
use App\Providers\BindServiceProvider;
use Barryvdh\DomPDF\ServiceProvider;
use SimpleSoftwareIO\QrCode\QrCodeServiceProvider;

return [
    AppServiceProvider::class,
    App\Providers\BindServiceProvider::class,
    ServiceProvider::class,
    QrCodeServiceProvider::class,
];
