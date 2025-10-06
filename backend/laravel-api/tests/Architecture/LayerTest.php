<?php

declare(strict_types=1);

arch('controllers should not depend on models directly')
    ->expect('App\Http\Controllers')
    ->not->toUse('App\Models')
    ->toOnlyUse([
        'Illuminate',
        'App\Services',
        'App\Http\Requests',
        'App\Http\Resources',
        'App\Actions',
        'Ddd\Application',
        'Ddd\Domain',
    ]);

arch('models should not depend on controllers')
    ->expect('App\Models')
    ->not->toUse('App\Http\Controllers');

arch('services should be stateless')
    ->expect('App\Services')
    ->toBeClasses()
    ->ignoring('App\Services\Traits');
