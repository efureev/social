<?php
/**
 * @var array<string,\Fureev\Socialite\Two\CustomProvider> $socials
 */
?>
<ul id="auth-social">
    @foreach( $socials as $driver)
        <li>
            <a href="{{ $driver->getRedirectUrl() }}">{{ $driver->getDriverConfig('icon') }}</a>
        </li>
    @endforeach
</ul>
