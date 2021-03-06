[![Latest Stable Version](https://poser.pugx.org/efureev/social/v/stable)](https://packagist.org/packages/efureev/social)
[![Total Downloads](https://poser.pugx.org/efureev/social/downloads)](https://packagist.org/packages/efureev/social)
[![Latest Unstable Version](https://poser.pugx.org/efureev/social/v/unstable)](https://packagist.org/packages/efureev/social)

## Information
Wrapper on Laravel Socialite

## Install
- `composer require efureev/social`
- Run migrations: `./artisan migrate`.
- If need - make published config: `./artisan vendor:publish --tag=social`.

## Basic usage
- Published resources: `php artisan vendor:publish --tag=social`
- Fill config file `social.php` into `config` dir with your social drivers:
```php
<?php
return [
    'drivers' => [
        'vk' => [
            'clientId' => env('VK_CLIENT_ID'),
            'clientSecret' => env('VK_CLIENT_SECRET'),
        ],
        'github' => [
            // ...
        ],
        // ...
    ],
];
```
- add into your app `.env` file variables: `VK_CLIENT_ID=...` and `VK_CLIENT_SECRET=...` with VK credentials. See in `https://vk.com/apps?act=manage`
- run migration: `php artisan migration`
- add into view (ex:`resources/views/auth/login.blade.php`):
  - for list: `@include('social::list', ['socials' => app('social')->getProviders()])`
  - for icons: `@include('social::icons', ['socials' => app('social')->getProviders()])`
- Done! 

For customizing perform - see config and docs.

## Config

### Config Props 
- `redirectOnAuth` [string] redirect on address after user auth.
- `onSuccess` [\Closure|array] action on auth success. Params: \Fureev\Socialite\Two\AbstractProvider
- `drivers` [array] Driver list (`driverName => driverConfig`)
- `userClass` [string] Auth User Class (`userClass => 'App/Models/User'`)

### Driver Config
- `clientId` [string] Require
- `clientSecret` [string] Require
- `enabled`  [bool] Default, true.
- `label` [string] Title for view. Default, `driverName`
- `provider` [string] Class of Provider (\Fureev\Socialite\Two\AbstractProvider)
- `url_token`  [string] Token URL for the provider
- `url_auth`   [string] Authentication URL for the provider
- `userInfoUrl`   [string] Url to get the raw user data
- `onSuccess` [\Closure|array] action on auth success. Overwrite common `onSuccess` 
- `scopeSeparator` [string]
- `scopes` [array]

### Example
File `config/social.php`
```php
<?php

return [
    'onSuccess' => function ($driver) {
        $user = \Fureev\Social\Services\SocialAccountService::setOrGetUser($driver);

        return \Fureev\Social\Services\SocialAccountService::auth($user);
    },
    //'onSuccess' => [\App\Http\Controllers\IndexController::class, 'index'],
    'drivers'   => [
        'gitlab' => [
            'enabled'  => false,
            'provider' => \Fureev\Socialite\Two\GitlabProvider::class,
            //            'enabled'  => false,
            'label'    => '<i class="fab fa-gitlab"></i>'
        ],
        'vk'     => [
            // 'enabled'  => false,
            'label'        => '<i class="fab fa-vk"></i>',
            'clientId'     => env('VK_CLIENT_ID'),
            'clientSecret' => env('VK_CLIENT_SECRET'),
        ],
        'github' => [
            'enabled' => false,
            'label'   => '<i class="fab fa-github-alt"></i>'
        ],
        'custom_auth' => [
            'clientId' => env('SOCIAL_AUTH_CLIENT_ID'),
            'clientSecret' => env('SOCIAL_AUTH_CLIENT_SECRET'),
            'url_auth' => 'http://api.auth.x/auth/authorize',
            'url_token' => 'http://api.auth.x/auth/token',
            'userInfoUrl' => 'http://api.auth.x/users/info',
            'scopeSeparator' => ',',
            'scopes' => ['name','email','photo'],
            'tokenFieldsExtra' => [
                'grant_type' => 'authorization_code',
            ],
            'mapFields' =>
                [
                    'id' => 'id',
                    'name' => ['profile.first_name.v', new \Fureev\Socialite\Separator, 'profile.last_name.v'],
                    'email' => 'profile.email.v',
                    'avatar' => 'photo',
                    'nickname' => 'id',
                    'profileId' => 'profileId',
                ],
            'guzzle' => [
                'query' => [
                    'prettyPrint' => 'false',
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer {{%TOKEN%}}',
                ],
            ],
        ],
        'google' => [
            // 'enabled'      => false,
            'clientId'         => env('G+_CLIENT_ID'),
            'clientSecret'     => env('G+_CLIENT_SECRET'),
            'url_token'        => 'https://accounts.google.com/o/oauth2/token',
            'url_auth'         => 'https://accounts.google.com/o/oauth2/auth',
            'userInfoUrl'      => 'https://www.googleapis.com/plus/v1/people/me?',
            'label'            => '<i class="fab fa-google"></i>',
            //            'onSuccess'        => [\App\Http\Controllers\HomeController::class, 'index'],
            'scopeSeparator'   => ' ',
            'scopes'           => ['openid', 'profile', 'email',],
            'tokenFieldsExtra' => [
                'grant_type' => 'authorization_code'
            ],
            'mapFields'        =>
                [
                    'id'     => 'id',
                    'name'   => 'displayName',
                    'email'  => 'emails.0.value',
                    'avatar' => 'image.url',
                ],
            'guzzle'           => [
                'query'   => [
                    'prettyPrint' => 'false',
                ],
                'headers' => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer {{%TOKEN%}}',
                ],
            ]
        ]
    ]
];

```

File `\App\Services\SocialAccountService.php`
```php
<?php 
use Fureev\Socialite\Contracts\Provider as ProviderContract;
class SocialAccountService
{
    public static function setOrGetUser(ProviderContract $provider)
    {
        $providerUser = $provider->user();

        $providerName = $provider->getName();

        //...

    }
}
```
Auto add social providers in your view: 
```blade
@extends('layouts.login')

@section('content')
  <form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="input-group">
      <input type="text" name="email" id="email"
             class="input-text" autocomplete="email"
             spellcheck="false" required autofocus value="{{ old('email') }}"><i class="ig-helpers"></i>
      <label for="email" class="input-label">E-mail</label>
    </div>
    <div class="input-group">
      <input type="password" name="password" id="password" autocomplete="password"
             class="input-text" required><i class="ig-helpers"></i>
      <label class="input-label" for="password">Пароль</label>
    </div>
    <div class="buttons">
      <button class="btn-rnd" type="submit"><i class="fas fa-sign-in-alt"></i></button>
      <a href="{{ route('password.request') }}" title="забыли пароль?"><i class="far fa-question-circle"></i></a>
    </div>
    @include('social::icons', ['socials' => app('social')->getProviders()])
    // or 
    @include('social::list', ['socials' => app('social')->getProviders()])
  </form>
@endsection
```
