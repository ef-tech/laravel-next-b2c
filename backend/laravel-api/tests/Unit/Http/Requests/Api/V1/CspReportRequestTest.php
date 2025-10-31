<?php

declare(strict_types=1);

use App\Http\Requests\Api\V1\CspReportRequest;
use Illuminate\Support\Facades\Validator;

describe('V1 CspReportRequest', function () {
    test('有効なCSPレポートデータがバリデーションを通過する', function (): void {
        $request = new CspReportRequest;
        $data = [
            'csp-report' => [
                'blocked-uri' => 'https://evil.com',
                'violated-directive' => 'script-src',
            ],
        ];

        $validator = Validator::make($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('csp-reportが必須である', function (): void {
        $request = new CspReportRequest;
        $data = [];

        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('csp-report'))->toBeTrue();
    });

    test('csp-reportは配列でなければならない', function (): void {
        $request = new CspReportRequest;
        $data = [
            'csp-report' => 'invalid-string',
        ];

        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('csp-report'))->toBeTrue();
    });

    test('空のcsp-reportは許可されない', function (): void {
        $request = new CspReportRequest;
        $data = [
            'csp-report' => [],
        ];

        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('csp-report'))->toBeTrue();
    });

    test('オプションフィールドを含むCSPレポートがバリデーションを通過する', function (): void {
        $request = new CspReportRequest;
        $data = [
            'csp-report' => [
                'blocked-uri' => 'https://evil.com',
                'violated-directive' => 'script-src',
                'original-policy' => 'default-src self',
                'document-uri' => 'https://example.com',
                'referrer' => '',
                'source-file' => 'https://example.com/app.js',
                'line-number' => 42,
                'column-number' => 10,
                'status-code' => 200,
            ],
        ];

        $validator = Validator::make($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('authorizeメソッドがtrueを返す', function (): void {
        $request = new CspReportRequest;

        expect($request->authorize())->toBeTrue();
    });
});
