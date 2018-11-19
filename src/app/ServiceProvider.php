<?php

declare(strict_types=1);

namespace Fureev\Social;

use Fureev\Socialite\SocialiteManager;
use Illuminate\Support\ServiceProvider as SP;

/**
 * Class ServiceProvider
 *
 * @package Fureev\Social
 */
class ServiceProvider extends SP
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/social.php' => $this->app->make('path.config') . DIRECTORY_SEPARATOR . 'social.php',
        ]);

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'social');

    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/social.php', 'social');

        $this->loadRoutesFrom(__DIR__ . '/../routes.php');

        $this->app->singleton('social', function ($app) {
            return (new SocialiteManager($app))->buildCustomProviders(array_keys($this->app['config']['social.drivers']));

        });
    }
}
