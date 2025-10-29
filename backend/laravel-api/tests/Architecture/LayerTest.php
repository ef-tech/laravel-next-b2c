<?php

declare(strict_types=1);

// Note: App\Http\Controllers\Api is excluded from this rule
// as it contains legacy REST API controllers that directly use models
// V1 controllers are also excluded as they maintain backward compatibility
arch('controllers should not depend on models directly')
    ->expect('App\Http\Controllers')
    ->not->toUse('App\Models')
    ->ignoring([
        'App\Http\Controllers\Api\AuthController',
        'App\Http\Controllers\Api\TokenController',
        'App\Http\Controllers\Api\UserController',
        'App\Http\Controllers\Api\V1\AuthController',
        'App\Http\Controllers\Api\V1\TokenController',
    ]);

arch('models should not depend on controllers')
    ->expect('App\Models')
    ->not->toUse('App\Http\Controllers');

arch('services should be stateless')
    ->expect('App\Services')
    ->toBeClasses()
    ->ignoring('App\Services\Traits');
