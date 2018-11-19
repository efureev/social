<?php

return [
    'redirectOnAuth' => '/',
    'routes'         => [
        'callback'   => '/auth/callback',
        'redirect'   => '/auth/redirect',
        'middleware' => ['web']
    ],

    'drivers' => []
];
