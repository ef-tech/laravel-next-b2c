<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DevelopmentSetupTest extends TestCase
{
    /**
     * 開発環境セットアップ手順文書の存在確認テスト
     */
    public function test_development_setup_guide_exists(): void
    {
        $setupGuidePath = base_path('docs/development-setup.md');
        $this->assertFileExists($setupGuidePath, 'Development setup guide should exist');

        $content = File::get($setupGuidePath);
        $this->assertStringContainsString('# 開発環境セットアップ手順', $content);
        $this->assertStringContainsString('## API専用構成での環境構築', $content);
        $this->assertStringContainsString('## Docker/Laravel Sail', $content);
        $this->assertStringContainsString('## 開発者向けクイックスタート', $content);
    }

    /**
     * CI/CD パイプライン動作確認手順の存在確認テスト
     */
    public function test_cicd_verification_guide_exists(): void
    {
        $setupGuidePath = base_path('docs/development-setup.md');
        $content = File::get($setupGuidePath);

        $this->assertStringContainsString('## CI/CD パイプライン', $content);
        $this->assertStringContainsString('テスト実行', $content);
        $this->assertStringContainsString('品質チェック', $content);
    }

    /**
     * API専用構成の動作確認手順テスト
     */
    public function test_api_configuration_verification_exists(): void
    {
        $setupGuidePath = base_path('docs/development-setup.md');
        $content = File::get($setupGuidePath);

        $this->assertStringContainsString('API専用', $content);
        $this->assertStringContainsString('認証確認', $content);
        $this->assertStringContainsString('エンドポイント', $content);
    }
}
