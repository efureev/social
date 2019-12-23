<?php

use Fureev\Social\Services\SocialAccountService;

return [
    'userClass' => 'App\Models\User',
    'redirectOnAuth' => '/',
    'routes' => [
        'callback' => '/auth/callback',
        'redirect' => '/auth/redirect',
        'middleware' => ['web'],
    ],
    'onSuccess' => static function ($driver) {
        $user = SocialAccountService::setOrGetUser($driver);

        return SocialAccountService::auth($user);
    },

    'drivers' => [],
];
