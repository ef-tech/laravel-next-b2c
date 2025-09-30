<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * 移行ガイドとベストプラクティス文書の存在確認テスト
 */
it('migration_guide_exists', function () {
    $migrationGuidePath = base_path('docs/migration-guide.md');
    expect($migrationGuidePath)->toBeFile('Migration guide should exist');

    $content = File::get($migrationGuidePath);
    expect($content)->toContain('# 移行ガイドとベストプラクティス');
    expect($content)->toContain('## 既存プロジェクトの移行手順');
    expect($content)->toContain('## パフォーマンステスト結果');
    expect($content)->toContain('## 最適化効果の定量的分析');
    expect($content)->toContain('## 他プロジェクトへの適用指針');
});

/**
 * ベストプラクティス項目の存在確認テスト
 */
it('best_practices_content_exists', function () {
    $migrationGuidePath = base_path('docs/migration-guide.md');
    $content = File::get($migrationGuidePath);

    expect($content)->toContain('ベストプラクティス');
    expect($content)->toContain('段階的移行');
    expect($content)->toContain('品質保証');
    expect($content)->toContain('パフォーマンス測定');
});

/**
 * 定量的分析データの存在確認テスト
 */
it('quantitative_analysis_exists', function () {
    $migrationGuidePath = base_path('docs/migration-guide.md');
    $content = File::get($migrationGuidePath);

    // 定量的データの存在確認
    expect($content)->toContain('33.3%', 'Should contain startup speed improvement data');
    expect($content)->toContain('96.5%', 'Should contain dependency reduction data');
    expect($content)->toContain('0.33KB', 'Should contain memory efficiency data');
});
