<?php

use Illuminate\Support\Facades\Route;

Route::namespace('Fureev\Social\Controllers')
    ->middleware(config('social.routes.middleware', ['web']))
    ->group(function (): void {
        Route::get(config('social.routes.redirect') . '/{service}', 'SocialController@redirectToProvider')->name('social.redirect');
        Route::get(config('social.routes.callback') . '/{service}', 'SocialController@handleProviderCallback')->name('social.callback');
    });
