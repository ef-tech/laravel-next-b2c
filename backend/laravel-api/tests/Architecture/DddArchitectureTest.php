<?php

declare(strict_types=1);

arch('Domain layer must not depend on Laravel')
    ->expect('Ddd\Domain')
    ->not->toUse([
        'Illuminate',
        'Laravel',
    ])
    ->ignoring('Carbon'); // Carbon is allowed per Steering規約

arch('Domain layer must not depend on Eloquent')
    ->expect('Ddd\Domain')
    ->not->toUse('Eloquent');

arch('Domain layer must not depend on Infrastructure')
    ->expect('Ddd\Domain')
    ->not->toUse('Ddd\Infrastructure');

arch('Application layer must not depend on Infrastructure')
    ->expect('Ddd\Application')
    ->not->toUse('Ddd\Infrastructure');

// Note: App\Http\Controllers\Api is excluded from this rule
// as it contains legacy REST API controllers that directly use models
// Note: V1 User Controllers are temporarily excluded pending UseCase migration
arch('Controllers should use UseCases instead of Models')
    ->expect('App\Http\Controllers')
    ->not->toUse('App\Models')
    ->ignoring([
        'App\Http\Controllers\Api\AuthController',
        'App\Http\Controllers\Api\TokenController',
        'App\Http\Controllers\Api\UserController',
        'App\Http\Controllers\Api\V1\User\LoginController',
        'App\Http\Controllers\Api\V1\User\LogoutController',
        'App\Http\Controllers\Api\V1\User\ProfileController',
    ]);

arch('Repository implementations should be in Infrastructure layer')
    ->expect('Ddd\Infrastructure\Persistence')
    ->toOnlyBeUsedIn([
        'Ddd\Infrastructure',
        'App\Providers\DddServiceProvider',
    ]);

arch('Domain exceptions should extend DomainException')
    ->expect('Ddd\Shared\Exceptions')
    ->classes()
    ->toExtend('Ddd\Shared\Exceptions\DomainException')
    ->ignoring('Ddd\Shared\Exceptions\DomainException');
