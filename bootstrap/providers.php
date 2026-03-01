<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\BindServiceProvider::class,
    Barryvdh\DomPDF\ServiceProvider::class,
    SimpleSoftwareIO\QrCode\QrCodeServiceProvider::class,
];
