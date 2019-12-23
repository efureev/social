<?php

declare(strict_types=1);

namespace Fureev\Social\Services;

use Fureev\Social\Models\SocialAccount;
use Fureev\Socialite\Contracts\Provider;
use Fureev\Socialite\Contracts\Provider as ProviderContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Php\Support\Helpers\Arr;

/**
 * Class SocialAccountService
 *
 * @package App\Services
 */
class SocialAccountService
{

    /**
     * Indicates if SocialAccountService migrations will be run.
     * @var bool
     */
    public static $runsMigrations = true;

    /**
     * User key type in migration
     * @var string
     */
    public static $userKeyType = 'uuid';

    /**
     * @param ProviderContract $provider
     *
     * @return Authenticatable|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|mixed|object|null
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function setOrGetUser(ProviderContract $provider)
    {
        $providerUser = $provider->user();

        $providerName = $provider->getName();

        $account = SocialAccount::whereProvider($providerName)
            ->whereProviderUserId($providerUser->getId())
            ->first();

        if ($account) {
            $account->raw = Arr::merge($account->raw, $providerUser->getRaw());
            $account->save();

            return $account->user;
        }

        $account = new SocialAccount([
            'provider_user_id' => $providerUser->getId(),
            'provider' => $providerName,
            'raw' => $providerUser->getRaw(),
        ]);

        /** @var \Illuminate\Database\Eloquent\Model $userModel */
        $userModel = app()->get('AuthenticatableModel');

        if (!$user = Auth::user()) {
            $user = $userModel->newQuery()->where('email', $providerUser->getEmail())->first();
        }

        if (!$user) {
            $user = $userModel->newQuery()->create([
                'email' => $providerUser->getEmail(),
                'display_name' => $providerUser->getName(),
                'login' => $providerUser->getNickname(),
            ]);
        }

        $account->user()->associate($user);
        $account->save();

        return $user;


    }

    /**
     * @param Authenticatable $user
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function auth(Authenticatable $user)
    {
        app('session')->regenerate();
        Auth::guard()->login($user, true);

        return redirect(config('social.redirectOnAuth', '/'));
    }
}
