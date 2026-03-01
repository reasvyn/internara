<?php

declare(strict_types=1);

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureDatabase();
    }

    /**
     * Boot the testing helper traits.
     */
    protected function setUpTraits(): array
    {
        $this->configureDatabase();
        $traits = parent::setUpTraits();

        return $traits;
    }

    /**
     * Configure the database connection for Dusk.
     */
    protected function configureDatabase(): void
    {
        if (
            config('database.default') === 'sqlite' &&
            config('database.connections.sqlite.database') === ':memory:'
        ) {
            config(['database.connections.sqlite.database' => database_path('dusk.sqlite')]);

            // Purge the connection to ensure the new configuration is used
            app('db')->purge();
        }
    }

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions())->addArguments(
            collect([
                $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
                '--disable-search-engine-choice-screen',
                '--disable-smooth-scrolling',
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--disable-setuid-sandbox',
                '--disable-gpu',
                '--disable-software-rasterizer',
            ])
                ->unless($this->hasHeadlessDisabled(), function (Collection $items) {
                    return $items->merge(['--headless=new']);
                })
                ->all(),
        );

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? (env('DUSK_DRIVER_URL') ?? 'http://localhost:9515'),
            DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options),
        );
    }
}
