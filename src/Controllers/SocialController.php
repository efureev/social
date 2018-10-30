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
        /** @var \Fureev\Socialite\Two\CustomProvider $driver */
        $driver = Socialite::with($service);
        $user = $driver->user();

        $exec = $driver->getDriverConfig('onSuccess');

        if ($exec) {
            if (is_array($exec)) {
                return app()->make($exec[0])->{$exec[1]}($user, $driver);
            }
            if ($exec instanceof \Closure) {
                return app()->make($exec)($user, $driver);
            }
        }
    }
}
