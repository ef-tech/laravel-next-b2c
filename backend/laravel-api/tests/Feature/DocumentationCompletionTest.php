<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DocumentationCompletionTest extends TestCase
{
    /**
     * Laravel最小限パッケージ構成の文書化完了確認テスト
     */
    public function test_optimization_process_documentation_exists(): void
    {
        $docsPath = base_path('docs');
        $this->assertDirectoryExists($docsPath, 'Documentation directory should exist');

        // 最適化プロセス文書の存在確認
        $optimizationGuide = $docsPath.'/laravel-optimization-process.md';
        $this->assertFileExists($optimizationGuide, 'Optimization process documentation should exist');

        $content = File::get($optimizationGuide);
        $this->assertStringContainsString('# Laravel最小限パッケージ構成', $content);
        $this->assertStringContainsString('## 最適化プロセス', $content);
        $this->assertStringContainsString('## 変更箇所一覧', $content);
        $this->assertStringContainsString('## パフォーマンス効果', $content);
    }

    /**
     * トラブルシューティングガイドの存在確認テスト
     */
    public function test_troubleshooting_guide_exists(): void
    {
        $troubleshootingPath = base_path('docs/troubleshooting.md');
        $this->assertFileExists($troubleshootingPath, 'Troubleshooting guide should exist');

        $content = File::get($troubleshootingPath);
        $this->assertStringContainsString('# トラブルシューティングガイド', $content);
        $this->assertStringContainsString('## 認証エラー', $content);
        $this->assertStringContainsString('## テスト失敗', $content);
        $this->assertStringContainsString('## パフォーマンス問題', $content);
    }

    /**
     * 設定変更手順の文書化確認テスト
     */
    public function test_configuration_change_documentation_exists(): void
    {
        $configDocPath = base_path('docs/configuration-changes.md');
        $this->assertFileExists($configDocPath, 'Configuration changes documentation should exist');

        $content = File::get($configDocPath);
        $this->assertStringContainsString('# 設定変更詳細', $content);
        $this->assertStringContainsString('## composer.json', $content);
        $this->assertStringContainsString('## bootstrap/app.php', $content);
        $this->assertStringContainsString('## .env設定', $content);
    }

    /**
     * パフォーマンス改善レポートの存在確認テスト
     */
    public function test_performance_improvement_report_exists(): void
    {
        $performanceReportPath = base_path('docs/performance-report.md');
        $this->assertFileExists($performanceReportPath, 'Performance improvement report should exist');

        $content = File::get($performanceReportPath);
        $this->assertStringContainsString('# パフォーマンス改善レポート', $content);
        $this->assertStringContainsString('起動速度改善', $content);
        $this->assertStringContainsString('メモリ使用量削減', $content);
        $this->assertStringContainsString('依存関係最適化', $content);
    }
}