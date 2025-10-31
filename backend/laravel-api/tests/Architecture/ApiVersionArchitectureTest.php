<?php

declare(strict_types=1);

/**
 * APIバージョニング Architecture Tests
 *
 * DDD Clean Architectureの原則に基づき、
 * バージョン固有のクラス（V1コントローラー、Presenter、Request）が
 * 正しい層に配置され、依存方向が守られていることを検証します。
 */

// ========================================
// 1. Domain層のバージョン非依存を検証
// ========================================
arch('Domain layer must not depend on version-specific controllers')
    ->expect('Ddd\Domain')
    ->not->toUse('App\Http\Controllers\Api\V1');

arch('Domain layer must not depend on version-specific presenters')
    ->expect('Ddd\Domain')
    ->not->toUse('Ddd\Infrastructure\Http\Presenters\V1');

arch('Domain layer must not depend on version-specific requests')
    ->expect('Ddd\Domain')
    ->not->toUse('App\Http\Requests\Api\V1');

// ========================================
// 2. Application層のバージョン非依存を検証
// ========================================
arch('Application layer must not depend on version-specific controllers')
    ->expect('Ddd\Application')
    ->not->toUse('App\Http\Controllers\Api\V1');

arch('Application layer must not depend on version-specific presenters')
    ->expect('Ddd\Application')
    ->not->toUse('Ddd\Infrastructure\Http\Presenters\V1');

arch('Application layer must not depend on version-specific requests')
    ->expect('Ddd\Application')
    ->not->toUse('App\Http\Requests\Api\V1');

// ========================================
// 3. V1コントローラーの名前空間配置を検証
// ========================================
arch('V1 controllers must be in correct namespace')
    ->expect('App\Http\Controllers\Api\V1')
    ->toBeClasses()
    ->toExtend('App\Http\Controllers\Controller');

arch('V1 controllers must have correct naming convention')
    ->expect('App\Http\Controllers\Api\V1')
    ->toHaveSuffix('Controller');

// ========================================
// 4. V1 Presenter/Requestの名前空間配置を検証
// ========================================
arch('V1 presenters must be in Infrastructure layer')
    ->expect('Ddd\Infrastructure\Http\Presenters\V1')
    ->toBeClasses()
    ->toHaveSuffix('Presenter')
    ->toOnlyBeUsedIn([
        'App\Http\Controllers\Api\V1',
        'Ddd\Infrastructure\Http\Presenters\V1',
    ]);

arch('V1 requests must be in correct namespace')
    ->expect('App\Http\Requests\Api\V1')
    ->toBeClasses()
    ->toExtend('Illuminate\Foundation\Http\FormRequest')
    ->toHaveSuffix('Request');

// ========================================
// 5. 依存方向違反ゼロを確認
// ========================================
arch('V1 controllers can depend on Application layer use cases')
    ->expect('App\Http\Controllers\Api\V1')
    ->toUse('Ddd\Application');

arch('V1 controllers can depend on Domain layer value objects')
    ->expect('App\Http\Controllers\Api\V1')
    ->toUse('Ddd\Domain');

arch('V1 presenters must not depend on Domain layer')
    ->expect('Ddd\Infrastructure\Http\Presenters\V1')
    ->not->toUse('Ddd\Domain')
    ->ignoring('Carbon'); // Carbon is allowed

arch('V1 presenters must not depend on Application layer')
    ->expect('Ddd\Infrastructure\Http\Presenters\V1')
    ->not->toUse('Ddd\Application');

arch('V1 requests must not depend on Domain layer')
    ->expect('App\Http\Requests\Api\V1')
    ->not->toUse('Ddd\Domain');

arch('V1 requests must not depend on Application layer')
    ->expect('App\Http\Requests\Api\V1')
    ->not->toUse('Ddd\Application');
