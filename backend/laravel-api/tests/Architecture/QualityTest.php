<?php

declare(strict_types=1);

arch('no debugging functions in production code')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'ray'])
    ->not->toBeUsed();

arch('strict types should be declared')
    ->expect('App')
    ->toUseStrictTypes()
    ->ignoring(['App\Providers', 'App\Console']);

arch('final classes where appropriate')
    ->expect('App\ValueObjects')
    ->toBeFinal()
    ->ignoring('App\ValueObjects\NotFinal');
