# Pest 4 New Features Guide

Pest 4で導入された新機能と、このプロジェクトでの将来的な活用方法を説明します。

---

## 1. Browser Testing（Playwright統合）

### 概要
Pest 4は[Playwright](https://playwright.dev/)を統合し、E2Eブラウザテストをネイティブサポートします。Next.jsフロントエンドとのエンドツーエンドテストが可能になります。

### 使用例
```php
it('can login and access dashboard', function () {
    $this->browse(function ($browser) {
        $browser->visit('http://localhost:3000/login')
            ->type('email', 'test@example.com')
            ->type('password', 'password')
            ->press('Login')
            ->assertPathIs('/dashboard');
    });
});
```

### 将来的な活用方法
- **フロントエンド統合テスト**: Next.js管理者アプリ・ユーザーアプリのE2Eテスト
- **認証フロー検証**: Sanctum認証とフロントエンドログインの統合テスト
- **CORS検証**: 実際のブラウザでCORSヘッダーの動作確認

### 参考リンク
- [Pest Browser Testing Documentation](https://pestphp.com/docs/browser-testing)
- [Playwright Documentation](https://playwright.dev/)

---

## 2. Test Sharding（並列テスト実行）

### 概要
テストスイートを複数のシャードに分割し、並列実行することで、CI/CD実行時間を短縮します。

### 使用例
```bash
# ローカル並列実行
./vendor/bin/pest --parallel

# CI/CDでのSharding（4並列）
./vendor/bin/pest --shard=1/4
./vendor/bin/pest --shard=2/4
./vendor/bin/pest --shard=3/4
./vendor/bin/pest --shard=4/4
```

### 現在の実装状況
✅ **実装済み**: `composer test-shard` と `.github/workflows/test.yml` で4並列Sharding設定済み

### 活用方法
- **CI/CD高速化**: GitHub Actionsで4並列実行、Pull Request時のフィードバック時間を1/4に短縮
- **ローカル開発**: `composer test-parallel` で並列実行

---

## 3. Visual Testing（視覚的回帰テスト）

### 概要
スクリーンショットベースの視覚的回帰テストをサポートします。UIコンポーネントの視覚的な変更を自動検出できます。

### 使用例
```php
it('matches dashboard screenshot', function () {
    $this->browse(function ($browser) {
        $browser->visit('/dashboard')
            ->assertMatchesSnapshot('dashboard');
    });
});
```

### 将来的な活用方法
- **UI回帰テスト**: Next.jsコンポーネントの視覚的な変更を自動検出
- **レスポンシブデザイン**: 複数デバイスサイズでのレイアウト検証
- **デザインシステム**: 一貫性のあるUI/UXの維持

### 参考リンク
- [Pest Visual Testing Documentation](https://pestphp.com/docs/visual-testing)

---

## 4. Dataset（パラメータ化テスト）

### 概要
同じテストロジックを異なるデータセットで実行できます。

### 使用例
```php
it('validates email format', function (string $email, bool $expected) {
    $validator = Validator::make(['email' => $email], ['email' => 'email']);
    expect($validator->passes())->toBe($expected);
})->with([
    ['test@example.com', true],
    ['invalid-email', false],
    ['test@localhost', true],
    ['', false],
]);
```

### 現在の実装状況
✅ **すぐに使用可能**: Pest 4標準機能として利用可能

### 活用方法
- **API入力検証**: 複数の入力パターンでバリデーションテスト
- **境界値テスト**: エッジケースを効率的にテスト

---

## 5. Mutation Testing（ミューテーションテスト）

### 概要
コードに意図的に変更を加え、テストが失敗するかを検証することで、テスト品質を評価します。

### 使用例
```bash
# Mutation Testing実行
./vendor/bin/pest --mutate
```

### 将来的な活用方法
- **テスト品質評価**: テストスイートがコードの変更を検出できるかを確認
- **カバレッジ補完**: 単純なカバレッジだけでなく、テストの実効性を評価

### 参考リンク
- [Pest Mutation Testing Documentation](https://pestphp.com/docs/mutation-testing)

---

## 6. Architecture Testing（アーキテクチャテスト）

### 概要
コードベースの設計原則を自動検証します（レイヤー分離、命名規則、コード品質）。

### 使用例
```php
arch('controllers should not depend on models directly')
    ->expect('App\Http\Controllers')
    ->not->toUse('App\Models');
```

### 現在の実装状況
✅ **実装済み**: `tests/Architecture/` に LayerTest、NamingTest、QualityTest実装済み

### 活用方法
- **設計原則の強制**: レイヤー分離、命名規則の自動検証
- **コード品質維持**: デバッグ関数、strictモード宣言の検証

---

## 導入ロードマップ

### Phase 1（現在）: 基本機能
- ✅ Test Sharding（CI/CD並列実行）
- ✅ Architecture Testing（設計原則自動検証）
- ✅ Dataset（パラメータ化テスト）

### Phase 2（3-6ヶ月後）: E2Eテスト
- 🔜 Browser Testing（Next.js統合E2Eテスト）
- 🔜 Visual Testing（UI回帰テスト）

### Phase 3（6-12ヶ月後）: 高度な品質管理
- 🔜 Mutation Testing（テスト品質評価）

---

## Tips

- **段階的導入**: 全機能を一度に導入せず、必要な機能から段階的に導入
- **既存テストとの互換性**: PHPUnitテストと並行して新機能を試すことが可能
- **ドキュメント**: 各機能の詳細は[Pest公式ドキュメント](https://pestphp.com/docs)を参照