<?php

declare(strict_types=1);

use App\Http\Requests\Api\V1\CreateTokenRequest;

describe('CreateTokenRequest', function () {
    it('authorizes all authenticated users', function () {
        $request = new CreateTokenRequest;

        expect($request->authorize())->toBeTrue();
    });

    it('validates name is optional', function () {
        $request = new CreateTokenRequest;
        $rules = $request->rules();

        expect($rules)->toHaveKey('name');
        expect($rules['name'])->toContain('sometimes');
    });

    it('validates name is string', function () {
        $request = new CreateTokenRequest;
        $rules = $request->rules();

        expect($rules['name'])->toContain('string');
    });

    it('validates name max length is 255', function () {
        $request = new CreateTokenRequest;
        $rules = $request->rules();

        expect($rules['name'])->toContain('max:255');
    });
});
