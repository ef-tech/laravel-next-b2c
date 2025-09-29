<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MigrationGuideTest extends TestCase
{
    /**
     * 移行ガイドとベストプラクティス文書の存在確認テスト
     */
    public function test_migration_guide_exists(): void
    {
        $migrationGuidePath = base_path('docs/migration-guide.md');
        $this->assertFileExists($migrationGuidePath, 'Migration guide should exist');

        $content = File::get($migrationGuidePath);
        $this->assertStringContainsString('# 移行ガイドとベストプラクティス', $content);
        $this->assertStringContainsString('## 既存プロジェクトの移行手順', $content);
        $this->assertStringContainsString('## パフォーマンステスト結果', $content);
        $this->assertStringContainsString('## 最適化効果の定量的分析', $content);
        $this->assertStringContainsString('## 他プロジェクトへの適用指針', $content);
    }

    /**
     * ベストプラクティス項目の存在確認テスト
     */
    public function test_best_practices_content_exists(): void
    {
        $migrationGuidePath = base_path('docs/migration-guide.md');
        $content = File::get($migrationGuidePath);

        $this->assertStringContainsString('ベストプラクティス', $content);
        $this->assertStringContainsString('段階的移行', $content);
        $this->assertStringContainsString('品質保証', $content);
        $this->assertStringContainsString('パフォーマンス測定', $content);
    }

    /**
     * 定量的分析データの存在確認テスト
     */
    public function test_quantitative_analysis_exists(): void
    {
        $migrationGuidePath = base_path('docs/migration-guide.md');
        $content = File::get($migrationGuidePath);

        // 定量的データの存在確認
        $this->assertStringContainsString('33.3%', $content, 'Should contain startup speed improvement data');
        $this->assertStringContainsString('96.5%', $content, 'Should contain dependency reduction data');
        $this->assertStringContainsString('0.33KB', $content, 'Should contain memory efficiency data');
    }
}