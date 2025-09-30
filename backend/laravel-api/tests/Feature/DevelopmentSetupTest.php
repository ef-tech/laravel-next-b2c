<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * 開発環境セットアップ手順文書の存在確認テスト
 */
it('development_setup_guide_exists', function () {
    $setupGuidePath = base_path('docs/development-setup.md');
    expect($setupGuidePath)->toBeFile('Development setup guide should exist');

    $content = File::get($setupGuidePath);
    expect($content)->toContain('# 開発環境セットアップ手順');
    expect($content)->toContain('## API専用構成での環境構築');
    expect($content)->toContain('## Docker/Laravel Sail');
    expect($content)->toContain('## 開発者向けクイックスタート');
});

/**
 * CI/CD パイプライン動作確認手順の存在確認テスト
 */
it('cicd_verification_guide_exists', function () {
    $setupGuidePath = base_path('docs/development-setup.md');
    $content = File::get($setupGuidePath);

    expect($content)->toContain('## CI/CD パイプライン');
    expect($content)->toContain('テスト実行');
    expect($content)->toContain('品質チェック');
});

/**
 * API専用構成の動作確認手順テスト
 */
it('api_configuration_verification_exists', function () {
    $setupGuidePath = base_path('docs/development-setup.md');
    $content = File::get($setupGuidePath);

    expect($content)->toContain('API専用');
    expect($content)->toContain('認証確認');
    expect($content)->toContain('エンドポイント');
});
