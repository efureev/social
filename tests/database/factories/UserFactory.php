<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use Illuminate\Support\Facades\Hash;
use Sitesoft\Alice\Modules\Pages\Tests\Models\User;

$factory->define(User::class, static function (Faker $faker) {
    static $password;

    return [
        'name' => $fullName = $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => Hash::make($password ?: $password = '12345'),
    ];
});
