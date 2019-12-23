<?php
/**
 * @var array<string,\Fureev\Socialite\Two\CustomProvider> $socials
 */
?>

@include('social::symbols')
<link rel="stylesheet" href="/vendor/social/css/style.css">
<ul class="auth-social icons">
    @foreach( $socials as $driver)
        <li>
            <a href="{{ $driver->getRedirectUrl() }}">
                <svg class="social-icon">
                    <use xlink:href="#{{$driver->getName()}}"></use>
                </svg>
            </a>
        </li>
    @endforeach
</ul>
