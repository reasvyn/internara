<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\BindServiceProvider::class,
    App\Providers\VoltServiceProvider::class,
    Barryvdh\DomPDF\ServiceProvider::class,
    SimpleSoftwareIO\QrCode\QrCodeServiceProvider::class,
];
