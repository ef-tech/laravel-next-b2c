<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * Laravel最小限パッケージ構成の文書化完了確認テスト
 */
it('optimization_process_documentation_exists', function () {
    $docsPath = base_path('docs');
    expect($docsPath)->toBeDirectory('Documentation directory should exist');

    // 最適化プロセス文書の存在確認
    $optimizationGuide = $docsPath.'/laravel-optimization-process.md';
    expect($optimizationGuide)->toBeFile('Optimization process documentation should exist');

    $content = File::get($optimizationGuide);
    expect($content)->toContain('# Laravel最小限パッケージ構成');
    expect($content)->toContain('## 最適化プロセス');
    expect($content)->toContain('## 変更箇所一覧');
    expect($content)->toContain('## パフォーマンス効果');
});

/**
 * トラブルシューティングガイドの存在確認テスト
 */
it('troubleshooting_guide_exists', function () {
    $troubleshootingPath = base_path('docs/troubleshooting.md');
    expect($troubleshootingPath)->toBeFile('Troubleshooting guide should exist');

    $content = File::get($troubleshootingPath);
    expect($content)->toContain('# トラブルシューティングガイド');
    expect($content)->toContain('## 認証エラー');
    expect($content)->toContain('## テスト失敗');
    expect($content)->toContain('## パフォーマンス問題');
});

/**
 * 設定変更手順の文書化確認テスト
 */
it('configuration_change_documentation_exists', function () {
    $configDocPath = base_path('docs/configuration-changes.md');
    expect($configDocPath)->toBeFile('Configuration changes documentation should exist');

    $content = File::get($configDocPath);
    expect($content)->toContain('# 設定変更詳細');
    expect($content)->toContain('## composer.json');
    expect($content)->toContain('## bootstrap/app.php');
    expect($content)->toContain('## .env設定');
});

/**
 * パフォーマンス改善レポートの存在確認テスト
 */
it('performance_improvement_report_exists', function () {
    $performanceReportPath = base_path('docs/performance-report.md');
    expect($performanceReportPath)->toBeFile('Performance improvement report should exist');

    $content = File::get($performanceReportPath);
    expect($content)->toContain('# パフォーマンス改善レポート');
    expect($content)->toContain('起動速度改善');
    expect($content)->toContain('メモリ使用量削減');
    expect($content)->toContain('依存関係最適化');
});
