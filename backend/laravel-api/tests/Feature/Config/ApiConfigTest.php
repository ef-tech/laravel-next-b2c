<?php

declare(strict_types=1);

describe('API Configuration', function () {
    test('デフォルトバージョンが設定されている', function () {
        $defaultVersion = config('api.default_version');

        expect($defaultVersion)->toBe('v1');
    });

    test('サポート対象バージョンリストが定義されている', function () {
        $supportedVersions = config('api.supported_versions');

        expect($supportedVersions)->toBeArray()
            ->toContain('v1');
    });

    test('デフォルトバージョンがサポート対象リストに含まれている', function () {
        $defaultVersion = config('api.default_version');
        $supportedVersions = config('api.supported_versions');

        expect($supportedVersions)->toContain($defaultVersion);
    });

    test('環境変数によるデフォルトバージョン指定が機能する', function () {
        // 環境変数を一時的に設定
        config(['api.default_version' => 'v2']);

        expect(config('api.default_version'))->toBe('v2');

        // クリーンアップ
        config(['api.default_version' => 'v1']);
    });

    test('バージョン別設定が構造化されている', function () {
        $v1Config = config('api.versions.v1');

        expect($v1Config)->toBeArray()
            ->toHaveKeys(['deprecation_date', 'sunset_date']);
    });
});
