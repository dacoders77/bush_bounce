<?php

use Faker\Generator as Faker;

$factory->define(App\Order::class, function (Faker $faker) {
    return [
        'trade_direction' => $faker->name,
        'order_volume' => $faker->randomNumber(3)
    ];
});

//$factory->afterCreating(App\User::class, function ($user, $faker) {
//    $user->accounts()->save(factory(App\Account::class)->make());
//});
