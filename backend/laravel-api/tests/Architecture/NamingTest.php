<?php

declare(strict_types=1);

arch('controllers should be suffixed with Controller')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('models should not be suffixed with Model')
    ->expect('App\Models')
    ->not->toHaveSuffix('Model');

arch('requests should be suffixed with Request')
    ->expect('App\Http\Requests')
    ->toHaveSuffix('Request');

arch('resources should be suffixed with Resource')
    ->expect('App\Http\Resources')
    ->toHaveSuffix('Resource');
