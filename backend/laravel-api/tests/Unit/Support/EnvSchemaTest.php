<?php

declare(strict_types=1);

use App\Support\EnvSchema;

describe('EnvSchema', function () {
    test('スキーマ定義が存在すること', function () {
        $schema = EnvSchema::getSchema();

        expect($schema)->toBeArray();
        expect($schema)->not->toBeEmpty();
    });

    test('APP_NAME のスキーマ定義が正しいこと', function () {
        $schema = EnvSchema::getSchema();

        expect($schema)->toHaveKey('APP_NAME');
        expect($schema['APP_NAME'])->toHaveKey('required', true);
        expect($schema['APP_NAME'])->toHaveKey('type', 'string');
        expect($schema['APP_NAME'])->toHaveKey('default', 'Laravel');
    });

    test('APP_ENV のスキーマ定義が正しいこと', function () {
        $schema = EnvSchema::getSchema();

        expect($schema)->toHaveKey('APP_ENV');
        expect($schema['APP_ENV'])->toHaveKey('required', true);
        expect($schema['APP_ENV'])->toHaveKey('type', 'string');
        expect($schema['APP_ENV'])->toHaveKey('allowed_values', ['local', 'staging', 'production']);
    });

    test('APP_DEBUG のスキーマ定義が正しいこと', function () {
        $schema = EnvSchema::getSchema();

        expect($schema)->toHaveKey('APP_DEBUG');
        expect($schema['APP_DEBUG'])->toHaveKey('required', true);
        expect($schema['APP_DEBUG'])->toHaveKey('type', 'boolean');
    });

    test('APP_KEY のスキーマ定義が正しいこと', function () {
        $schema = EnvSchema::getSchema();

        expect($schema)->toHaveKey('APP_KEY');
        expect($schema['APP_KEY'])->toHaveKey('required', true);
        expect($schema['APP_KEY'])->toHaveKey('type', 'string');
        expect($schema['APP_KEY'])->toHaveKey('security_level', 'high');
    });

    test('APP_URL のスキーマ定義が正しいこと', function () {
        $schema = EnvSchema::getSchema();

        expect($schema)->toHaveKey('APP_URL');
        expect($schema['APP_URL'])->toHaveKey('required', true);
        expect($schema['APP_URL'])->toHaveKey('type', 'url');
    });

    test('DB_CONNECTION のスキーマ定義が正しいこと', function () {
        $schema = EnvSchema::getSchema();

        expect($schema)->toHaveKey('DB_CONNECTION');
        expect($schema['DB_CONNECTION'])->toHaveKey('required', true);
        expect($schema['DB_CONNECTION'])->toHaveKey('type', 'string');
        expect($schema['DB_CONNECTION'])->toHaveKey('allowed_values', ['sqlite', 'pgsql', 'mysql']);
        expect($schema['DB_CONNECTION'])->toHaveKey('default', 'sqlite');
    });

    test('DB_HOST の条件付き必須スキーマ定義が正しいこと', function () {
        $schema = EnvSchema::getSchema();

        expect($schema)->toHaveKey('DB_HOST');
        expect($schema['DB_HOST'])->toHaveKey('required', false);
        expect($schema['DB_HOST'])->toHaveKey('required_if');
        expect($schema['DB_HOST']['required_if'])->toHaveKey('DB_CONNECTION', ['pgsql', 'mysql']);
    });

    test('SANCTUM_EXPIRATION のスキーマ定義が正しいこと', function () {
        $schema = EnvSchema::getSchema();

        expect($schema)->toHaveKey('SANCTUM_EXPIRATION');
        expect($schema['SANCTUM_EXPIRATION'])->toHaveKey('required', false);
        expect($schema['SANCTUM_EXPIRATION'])->toHaveKey('type', 'integer');
        expect($schema['SANCTUM_EXPIRATION'])->toHaveKey('default', 60);
    });

    test('CORS_ALLOWED_ORIGINS のスキーマ定義が正しいこと', function () {
        $schema = EnvSchema::getSchema();

        expect($schema)->toHaveKey('CORS_ALLOWED_ORIGINS');
        expect($schema['CORS_ALLOWED_ORIGINS'])->toHaveKey('required', true);
        expect($schema['CORS_ALLOWED_ORIGINS'])->toHaveKey('type', 'string');
    });
});
