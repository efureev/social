<?php

declare(strict_types=1);

namespace Fureev\Social;

use Fureev\Social\Services\SocialAccountService;
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
    public function boot(): void
    {
        $this->loadViewsFrom(static::getPath('resources/views'), 'social');

        if ($this->app->runningInConsole()) {
            $this->registerMigrations();
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        if (!$this->app->configurationIsCached()) {
            $this->mergeConfigFrom(static::getPath('config/social.php'), 'social');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                static::getPath('config/social.php') => $this->app->configPath('social.php'),
            ]);
        }

        $this->loadRoutesFrom(static::getPath('routes/routes.php'));

        $this->app->singleton(SocialiteManager::class, function ($app) {
            return (new SocialiteManager($app))->buildCustomProviders(array_keys($this->app['config']['social.drivers']));
        });

        $this->app->alias(SocialiteManager::class, 'social');
    }

    private static $rootDirPath;

    /**
     * @param string $path
     *
     * @return string
     */
    private static function getPath(string $path): string
    {
        return (static::$rootDirPath ?? static::$rootDirPath = dirname(__DIR__)) . "/$path";
    }

    protected function registerMigrations(): void
    {
        if (SocialAccountService::$runsMigrations) {
            $this->loadMigrationsFrom(static::getPath('database/migrations'));
        }
    }
}
