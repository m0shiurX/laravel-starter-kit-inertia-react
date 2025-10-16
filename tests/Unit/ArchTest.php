<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->strict()->ignoring(App\Http\Controllers\Controller::class);
arch()->preset()->security()->ignoring([
    'assert',
]);

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed()
    ->ignoring(App\Http\Controllers\Controller::class);

//
