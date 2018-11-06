<?php

declare(strict_types=1);

namespace Fureev\Social\Controllers;

use Fureev\Socialite\Facades\Socialite;
use Illuminate\Routing\Controller;

/**
 * Class SocialController
 *
 * @package Fureev\Social\Controllers
 */
class SocialController extends Controller
{
    /**
     * @param string $service
     *
     * @return mixed
     */
    public function redirectToProvider(string $service)
    {
        return Socialite::driver($service)->redirect();
    }

    /**
     * @param string $service
     *
     * @return mixed
     * @throws \Exception
     */
    public function handleProviderCallback(string $service)
    {
        /** @var $social \Fureev\Socialite\SocialiteManager */
        $social = app('social');

        /** @var \Fureev\Socialite\Two\CustomProvider $driver */
        $driver = $social->with($service);

        $user = $driver->user();

        $exec = $social->getConfig('onSuccess') ?? $driver->getDriverConfig('onSuccess');

        if ($exec) {
            if (is_array($exec)) {
                return app()->make($exec[0])->{$exec[1]}($user, $driver);
            }
            if ($exec instanceof \Closure) {
                return $exec($user, $driver);
            }
        }
    }
}
