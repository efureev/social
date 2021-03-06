<?php

declare(strict_types=1);

namespace Fureev\Social\Services;

use Fureev\Social\Models\SocialAccount;
use Fureev\Socialite\Contracts\Provider as ProviderContract;
use Fureev\Socialite\Contracts\User;
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
     * User function for mapping social User data to User model
     * @var array|callable
     */
    public static $socialToUserMapFn = [SocialAccountService::class, 'socialToUserMapDefault'];

    /**
     * @param ProviderContract $provider
     *
     * @return SocialAccount
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function setOrGetUser(ProviderContract $provider): SocialAccount
    {
        $providerUser = $provider->user();

        $providerName = $provider->getName();

        return static::syncUser($providerName, $providerUser);
    }

    /**
     * Synchronizing social-user with app`s user
     *
     * @param string $providerName
     * @param User $providerUser
     *
     * @return SocialAccount
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function syncUser(string $providerName, User $providerUser): SocialAccount
    {
        $account = SocialAccount::whereProvider($providerName)
            ->whereProviderUserId($providerUser->getId())
            ->first();

        if ($account) {
            $account->raw = Arr::merge($account->raw, $providerUser->getRaw());
            $account->save();

            return $account;
        }

        $account = new SocialAccount(
            [
                'provider_user_id' => $providerUser->getId(),
                'provider' => $providerName,
                'raw' => $providerUser->getRaw(),
            ]
        );

        /** @var \Illuminate\Database\Eloquent\Model $userModel */
        $userModelClass = config('social.userClass', 'App/User');
        $userModel = new $userModelClass;

        if (!$user = Auth::user()) {
            $user = $userModel->newQuery()->where('email', $providerUser->getEmail())->first();
        }

        if (!$user) {
            $map = call_user_func(static::$socialToUserMapFn, $providerUser);
            $user = $userModel->newQuery()->create($map);
        }

        $account->user()->associate($user);
        $account->save();

        return $account;
    }

    /**
     * @param User $user
     *
     * @return array
     */
    public static function socialToUserMapDefault(User $user): array
    {
        return [
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'login' => $user->getNickname(),
        ];
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
