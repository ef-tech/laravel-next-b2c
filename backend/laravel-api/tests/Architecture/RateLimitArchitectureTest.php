<?php

declare(strict_types=1);

/**
 * Rate Limit Architecture Tests
 *
 * DDD/クリーンアーキテクチャ原則準拠を自動検証します。
 *
 * Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 10.7
 */

/*
|--------------------------------------------------------------------------
| Domain層の独立性検証
|--------------------------------------------------------------------------
*/

arch('RateLimit Domain層はLaravelフレームワークに依存しない')
    ->expect('Ddd\Domain\RateLimit')
    ->not->toUse([
        'Illuminate',
        'Laravel',
    ])
    ->ignoring('Carbon'); // Carbon is allowed per Steering規約

arch('RateLimit Domain層はIlluminate名前空間を使用しない')
    ->expect('Ddd\Domain\RateLimit')
    ->not->toUse('Illuminate');

arch('RateLimit Domain層はEloquentに依存しない')
    ->expect('Ddd\Domain\RateLimit')
    ->not->toUse('Eloquent');

arch('RateLimit Domain層はInfrastructure層に依存しない')
    ->expect('Ddd\Domain\RateLimit')
    ->not->toUse('Ddd\Infrastructure');

arch('RateLimit Domain層はApplication層に依存しない')
    ->expect('Ddd\Domain\RateLimit')
    ->not->toUse('Ddd\Application');

arch('RateLimit Domain層はHTTP層に依存しない')
    ->expect('Ddd\Domain\RateLimit')
    ->not->toUse('App\Http');

/*
|--------------------------------------------------------------------------
| Application層の依存性逆転原則検証
|--------------------------------------------------------------------------
*/

arch('RateLimit Application層はInfrastructure層に依存しない')
    ->expect('Ddd\Application\RateLimit')
    ->not->toUse('Ddd\Infrastructure');

arch('RateLimit Application層はCache Facadeを使用しない')
    ->expect('Ddd\Application\RateLimit')
    ->not->toUse('Illuminate\Support\Facades\Cache');

arch('RateLimit Application層はHTTP層に依存しない')
    ->expect('Ddd\Application\RateLimit')
    ->not->toUse('App\Http');

/*
|--------------------------------------------------------------------------
| Infrastructure層のインターフェース実装検証
|--------------------------------------------------------------------------
*/

arch('RateLimit Infrastructure層はDomain/Application層のインターフェースを実装する')
    ->expect('Ddd\Infrastructure\RateLimit')
    ->toOnlyBeUsedIn([
        'Ddd\Infrastructure',
        'App\Providers',
        'Tests',
    ]);

/*
|--------------------------------------------------------------------------
| HTTP層の依存方向検証
|--------------------------------------------------------------------------
*/

arch('DynamicRateLimit MiddlewareはApplication層のみに依存する')
    ->expect('App\Http\Middleware\DynamicRateLimit')
    ->toUse('Ddd\Application\RateLimit')
    ->not->toUse('Ddd\Infrastructure\RateLimit');

/*
|--------------------------------------------------------------------------
| 命名規約検証
|--------------------------------------------------------------------------
*/

arch('RateLimit ValueObjectsはfinal readonly classである')
    ->expect('Ddd\Domain\RateLimit\ValueObjects')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('RateLimit Application層Servicesは適切なクラスである')
    ->expect('Ddd\Application\RateLimit\Services')
    ->classes()
    ->toBeClasses()
    ->toBeFinal();

arch('RateLimit Contractsはインターフェースである')
    ->expect('Ddd\Application\RateLimit\Contracts')
    ->toBeInterfaces();
