<?php

declare(strict_types=1);

use App\Support\EnvValidator;

describe('EnvValidator', function () {
    beforeEach(function () {
        // テスト用のシンプルなスキーマ
        $this->testSchema = [
            'REQUIRED_STRING' => [
                'required' => true,
                'type' => 'string',
            ],
            'REQUIRED_INTEGER' => [
                'required' => true,
                'type' => 'integer',
            ],
            'REQUIRED_BOOLEAN' => [
                'required' => true,
                'type' => 'boolean',
            ],
            'REQUIRED_URL' => [
                'required' => true,
                'type' => 'url',
            ],
            'OPTIONAL_STRING' => [
                'required' => false,
                'type' => 'string',
                'default' => 'default_value',
            ],
            'ALLOWED_VALUES_STRING' => [
                'required' => true,
                'type' => 'string',
                'allowed_values' => ['value1', 'value2', 'value3'],
            ],
            'CONDITIONAL_REQUIRED' => [
                'required' => false,
                'type' => 'string',
                'required_if' => [
                    'REQUIRED_STRING' => ['trigger_value'],
                ],
            ],
        ];
    });

    describe('正常系', function () {
        test('全ての必須環境変数が正しく設定されている場合、バリデーションが成功すること', function () {
            $env = [
                'REQUIRED_STRING' => 'test',
                'REQUIRED_INTEGER' => '123',
                'REQUIRED_BOOLEAN' => 'true',
                'REQUIRED_URL' => 'http://example.com',
                'ALLOWED_VALUES_STRING' => 'value1',
            ];

            $validator = new EnvValidator($this->testSchema, $env);
            $result = $validator->validate();

            expect($result)->toBeArray();
            expect($result)->toHaveKey('valid', true);
            expect($result)->toHaveKey('errors', []);
        });

        test('オプション変数が設定されていなくてもバリデーションが成功すること', function () {
            $env = [
                'REQUIRED_STRING' => 'test',
                'REQUIRED_INTEGER' => '123',
                'REQUIRED_BOOLEAN' => 'true',
                'REQUIRED_URL' => 'http://example.com',
                'ALLOWED_VALUES_STRING' => 'value1',
                // OPTIONAL_STRING は設定しない
            ];

            $validator = new EnvValidator($this->testSchema, $env);
            $result = $validator->validate();

            expect($result)->toHaveKey('valid', true);
        });

        test('条件付き必須変数の条件が満たされていない場合、設定がなくてもバリデーションが成功すること', function () {
            $env = [
                'REQUIRED_STRING' => 'other_value', // trigger_value ではない
                'REQUIRED_INTEGER' => '123',
                'REQUIRED_BOOLEAN' => 'true',
                'REQUIRED_URL' => 'http://example.com',
                'ALLOWED_VALUES_STRING' => 'value1',
                // CONDITIONAL_REQUIRED は設定しない
            ];

            $validator = new EnvValidator($this->testSchema, $env);
            $result = $validator->validate();

            expect($result)->toHaveKey('valid', true);
        });
    });

    describe('異常系 - 必須チェック', function () {
        test('必須の文字列型変数が設定されていない場合、エラーが返されること', function () {
            $env = [
                // REQUIRED_STRING を設定しない
                'REQUIRED_INTEGER' => '123',
                'REQUIRED_BOOLEAN' => 'true',
                'REQUIRED_URL' => 'http://example.com',
                'ALLOWED_VALUES_STRING' => 'value1',
            ];

            $validator = new EnvValidator($this->testSchema, $env);
            $result = $validator->validate();

            expect($result)->toHaveKey('valid', false);
            expect($result)->toHaveKey('errors');
            expect($result['errors'])->toHaveKey('REQUIRED_STRING');
        });

        test('条件付き必須変数の条件が満たされている場合、設定がないとエラーが返されること', function () {
            $env = [
                'REQUIRED_STRING' => 'trigger_value', // 条件トリガー
                'REQUIRED_INTEGER' => '123',
                'REQUIRED_BOOLEAN' => 'true',
                'REQUIRED_URL' => 'http://example.com',
                'ALLOWED_VALUES_STRING' => 'value1',
                // CONDITIONAL_REQUIRED を設定しない
            ];

            $validator = new EnvValidator($this->testSchema, $env);
            $result = $validator->validate();

            expect($result)->toHaveKey('valid', false);
            expect($result['errors'])->toHaveKey('CONDITIONAL_REQUIRED');
        });
    });

    describe('異常系 - 型チェック', function () {
        test('integer型の変数に数値以外の値が設定されている場合、エラーが返されること', function () {
            $env = [
                'REQUIRED_STRING' => 'test',
                'REQUIRED_INTEGER' => 'not_a_number', // 型エラー
                'REQUIRED_BOOLEAN' => 'true',
                'REQUIRED_URL' => 'http://example.com',
                'ALLOWED_VALUES_STRING' => 'value1',
            ];

            $validator = new EnvValidator($this->testSchema, $env);
            $result = $validator->validate();

            expect($result)->toHaveKey('valid', false);
            expect($result['errors'])->toHaveKey('REQUIRED_INTEGER');
        });

        test('boolean型の変数に真偽値以外の値が設定されている場合、エラーが返されること', function () {
            $env = [
                'REQUIRED_STRING' => 'test',
                'REQUIRED_INTEGER' => '123',
                'REQUIRED_BOOLEAN' => 'invalid_boolean', // 型エラー
                'REQUIRED_URL' => 'http://example.com',
                'ALLOWED_VALUES_STRING' => 'value1',
            ];

            $validator = new EnvValidator($this->testSchema, $env);
            $result = $validator->validate();

            expect($result)->toHaveKey('valid', false);
            expect($result['errors'])->toHaveKey('REQUIRED_BOOLEAN');
        });

        test('url型の変数に不正なURL形式の値が設定されている場合、エラーが返されること', function () {
            $env = [
                'REQUIRED_STRING' => 'test',
                'REQUIRED_INTEGER' => '123',
                'REQUIRED_BOOLEAN' => 'true',
                'REQUIRED_URL' => 'not_a_valid_url', // 型エラー
                'ALLOWED_VALUES_STRING' => 'value1',
            ];

            $validator = new EnvValidator($this->testSchema, $env);
            $result = $validator->validate();

            expect($result)->toHaveKey('valid', false);
            expect($result['errors'])->toHaveKey('REQUIRED_URL');
        });
    });

    describe('異常系 - 許可値チェック', function () {
        test('許可値リストにない値が設定されている場合、エラーが返されること', function () {
            $env = [
                'REQUIRED_STRING' => 'test',
                'REQUIRED_INTEGER' => '123',
                'REQUIRED_BOOLEAN' => 'true',
                'REQUIRED_URL' => 'http://example.com',
                'ALLOWED_VALUES_STRING' => 'invalid_value', // 許可されていない値
            ];

            $validator = new EnvValidator($this->testSchema, $env);
            $result = $validator->validate();

            expect($result)->toHaveKey('valid', false);
            expect($result['errors'])->toHaveKey('ALLOWED_VALUES_STRING');
        });
    });

    describe('警告モード', function () {
        test('警告モードでエラーがあってもvalid=trueが返されること', function () {
            $env = [
                // REQUIRED_STRING を設定しない（エラー）
                'REQUIRED_INTEGER' => '123',
                'REQUIRED_BOOLEAN' => 'true',
                'REQUIRED_URL' => 'http://example.com',
                'ALLOWED_VALUES_STRING' => 'value1',
            ];

            $validator = new EnvValidator($this->testSchema, $env, 'warning');
            $result = $validator->validate();

            expect($result)->toHaveKey('valid', true);
            expect($result)->toHaveKey('errors');
            expect($result['errors'])->not->toBeEmpty(); // エラーは記録される
        });
    });
});
