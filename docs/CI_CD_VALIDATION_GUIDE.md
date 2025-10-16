# CI/CD環境での環境変数バリデーション動作確認ガイド

## 📋 概要

このガイドでは、GitHub Actions CI/CD環境における環境変数バリデーション機能の動作確認手順を説明します。

## 🎯 目的

- 環境変数バリデーションがCI/CD環境で正しく動作することを確認する
- エラーメッセージの明瞭性を検証する
- ビルド失敗時の適切なエラー表示を確認する
- チーム全体で統一された検証プロセスを確立する

---

## 1. GitHub Actions ワークフロー構成

### 1.1 環境変数バリデーション専用ワークフロー

**ファイル**: `.github/workflows/env-validation.yml`

**トリガー条件**:
- Pull Request: 環境変数関連ファイル変更時
- Push to main/develop: 環境変数関連ファイル変更時

**検証ジョブ**:
1. **validate-laravel**: Laravel環境変数バリデーション
2. **validate-frontend**: Next.js環境変数バリデーション（Admin App / User App）
3. **validate-sync**: 環境変数同期チェック

### 1.2 既存ワークフローへの統合

**Laravel テストワークフロー** (`.github/workflows/test.yml`):
- `php artisan env:validate` ステップを追加
- データベースマイグレーション前に実行

**フロントエンドテストワークフロー** (`.github/workflows/frontend-test.yml`):
- `npm run env:check` ステップを追加
- Lint/Test実行前に実行

---

## 2. 動作確認手順

### 2.1 正常系の動作確認

#### ✅ ステップ1: Pull Request作成
```bash
# 環境変数関連ファイルを変更してPR作成
git checkout -b test/env-validation
# 例: .env.example にコメント追加
git add .env.example
git commit -m "Test: 環境変数バリデーション動作確認"
git push origin test/env-validation
gh pr create --title "Test: 環境変数バリデーション" --body "動作確認用PR"
```

#### ✅ ステップ2: ワークフロー実行確認
```bash
# ワークフロー実行状態を確認
gh pr checks

# 期待される結果:
# ✓ Environment Variables Validation / validate-laravel
# ✓ Environment Variables Validation / validate-frontend (admin-app)
# ✓ Environment Variables Validation / validate-frontend (user-app)
# ✓ Environment Variables Validation / validate-sync
```

#### ✅ ステップ3: ログ確認
```bash
# 最新のワークフロー実行ログを確認
gh run list --workflow=env-validation.yml
gh run view <run-id> --log

# 期待されるログ出力例:
# Laravel: "✔ Environment validation passed"
# Next.js: "✔ Environment variables validated successfully"
# Sync: "✔ All environment files are in sync"
```

---

### 2.2 異常系の動作確認

#### ❌ ケース1: Laravel必須環境変数不足

**シナリオ**: Laravel `.env` から `APP_KEY` を削除

```bash
# backend/laravel-api/.env.example から APP_KEY を一時削除
git add backend/laravel-api/.env.example
git commit -m "Test: APP_KEY不足エラー確認"
git push
```

**期待される動作**:
- ✗ GitHub Actions ワークフローが失敗
- エラーメッセージ表示:
  ```
  RuntimeException: Environment validation failed:
  - Missing required variable: APP_KEY
    Description: Laravel application encryption key
    Generate: php artisan key:generate
    Example: base64:ランダムな32文字の文字列
  ```

**エラーメッセージ評価基準**:
- ✅ 不足している変数名が明示されている
- ✅ 変数の用途が説明されている
- ✅ 修正方法（生成コマンド）が示されている
- ✅ サンプル値が提供されている

#### ❌ ケース2: Next.js環境変数の型エラー

**シナリオ**: `NEXT_PUBLIC_API_URL` に不正なURL

```bash
# frontend/admin-app/.env.example の NEXT_PUBLIC_API_URL を無効な値に変更
NEXT_PUBLIC_API_URL=invalid-url
```

**期待される動作**:
- ✗ GitHub Actions ワークフローが失敗
- エラーメッセージ表示:
  ```
  ZodError: Environment validation failed:
  - NEXT_PUBLIC_API_URL: Invalid url format
    Expected: Valid HTTP/HTTPS URL
    Example: http://localhost:13000
  ```

**エラーメッセージ評価基準**:
- ✅ エラーが発生した変数名が明示されている
- ✅ 期待される形式が説明されている
- ✅ 正しい設定例が提供されている

#### ❌ ケース3: 環境変数同期エラー

**シナリオ**: `.env.example` に新規変数追加、`.env` に反映されていない

```bash
# .env.example に新しい変数を追加
echo "NEW_VARIABLE=default" >> .env.example
# .env には反映しない
```

**期待される動作**:
- ✗ `npm run env:check` が差分を検出
- 警告メッセージ表示:
  ```
  Warning: Environment files are out of sync
  Missing keys in .env:
  - NEW_VARIABLE

  Run 'npm run env:sync' to synchronize
  ```

**エラーメッセージ評価基準**:
- ✅ 不足しているキーが明示されている
- ✅ 同期コマンドが案内されている

---

## 3. CI/CD環境での確認項目チェックリスト

### 3.1 Laravel環境変数バリデーション

- [ ] **正常系**: 全ての必須環境変数が存在する場合、バリデーションが成功する
- [ ] **異常系**: `APP_KEY` 不足時、明確なエラーメッセージが表示される
- [ ] **異常系**: `DB_CONNECTION=pgsql` で `DB_HOST` 不足時、条件付き必須エラーが表示される
- [ ] **異常系**: `APP_DEBUG` に `true/false` 以外の値を設定した場合、型エラーが表示される
- [ ] **警告モード**: `ENV_VALIDATION_MODE=warning` 時、エラーがあっても起動継続する
- [ ] **スキップ**: `ENV_VALIDATION_SKIP=true` 時、バリデーションがスキップされる

### 3.2 Next.js環境変数バリデーション

- [ ] **正常系**: 全ての必須環境変数が存在する場合、ビルドが成功する
- [ ] **異常系**: `NEXT_PUBLIC_API_URL` 不足時、明確なエラーメッセージが表示される
- [ ] **異常系**: `NEXT_PUBLIC_API_URL` が不正なURL形式の場合、型エラーが表示される
- [ ] **異常系**: `NODE_ENV` に許可されていない値を設定した場合、エラーが表示される
- [ ] **型安全性**: TypeScriptで `env.NEXT_PUBLIC_API_URL` にアクセス時、型推論が機能する

### 3.3 環境変数同期スクリプト

- [ ] **正常系**: `.env` と `.env.example` が同期している場合、チェックが成功する
- [ ] **異常系**: `.env` に `.env.example` のキーが不足している場合、差分が表示される
- [ ] **異常系**: `.env` に `.env.example` にないキーが存在する場合、未知キーとして警告される
- [ ] **同期機能**: `npm run env:sync` で不足キーが自動追加される
- [ ] **既存値保持**: 同期実行時、既存の `.env` の値が保持される

### 3.4 GitHub Actions ワークフロー

- [ ] **トリガー**: 環境変数関連ファイル変更時にワークフローが自動実行される
- [ ] **トリガー**: 環境変数関連ファイル以外の変更時はワークフローがスキップされる
- [ ] **並列実行**: Admin App と User App のバリデーションが並列実行される
- [ ] **失敗時**: バリデーション失敗時、PRのチェックステータスが失敗になる
- [ ] **成功時**: バリデーション成功時、PRのチェックステータスが成功になる
- [ ] **パフォーマンス**: ワークフロー全体の実行時間が許容範囲内（約3-5分）である

---

## 4. エラーメッセージ品質基準

### 4.1 評価基準

エラーメッセージは以下の基準を満たす必要があります:

✅ **明瞭性**: 技術用語を最小限にし、新規メンバーでも理解できる表現
✅ **具体性**: エラーの原因と影響範囲が明確に示されている
✅ **解決策提示**: 修正方法が具体的に案内されている
✅ **サンプル値**: 正しい設定例が提供されている
✅ **関連リンク**: 詳細ドキュメントへのリンクが提供されている（該当する場合）

### 4.2 良いエラーメッセージ例

```
❌ Environment validation failed

Missing required variable: DB_PASSWORD
└─ Description: Database connection password
└─ Required: Yes (when DB_CONNECTION=pgsql or mysql)
└─ Security Level: Top Secret
└─ Action: Set a strong password in .env file
└─ Example: DB_PASSWORD=your-secure-password-here

Tip: Never commit your .env file to Git!
For CI/CD, use GitHub Secrets: docs/GITHUB_ACTIONS_SECRETS_GUIDE.md
```

### 4.3 悪いエラーメッセージ例

```
❌ Error: Validation failed
Details: DB_PASSWORD is undefined
```

**問題点**:
- ✗ 変数の用途が不明
- ✗ 修正方法が示されていない
- ✗ サンプル値がない
- ✗ セキュリティ上の注意がない

---

## 5. トラブルシューティング

### 5.1 ワークフローがトリガーされない

**原因**: 環境変数関連ファイル以外を変更している

**解決方法**:
```bash
# paths設定を確認
cat .github/workflows/env-validation.yml | grep -A 10 "paths:"

# 環境変数関連ファイルを変更
git add .env.example
git commit -m "Trigger workflow"
git push
```

### 5.2 バリデーション成功しているのにエラー表示される

**原因**: `.env` ファイルがコミットされている可能性

**解決方法**:
```bash
# .gitignore に .env が登録されているか確認
grep "\.env$" .gitignore

# .env がGit管理されている場合は削除
git rm --cached .env
git commit -m "Remove .env from Git"
```

### 5.3 CI/CD環境でのみバリデーションエラー

**原因**: GitHub Secrets が設定されていない

**解決方法**:
```bash
# 必須Secretsを確認
# docs/GITHUB_ACTIONS_SECRETS_GUIDE.md を参照

# GitHub Settings → Secrets and variables → Actions で設定
```

### 5.4 ビルド時間が大幅に増加

**原因**: バリデーション処理の追加による影響

**許容範囲**:
- Laravel: 約10-15秒増加（許容）
- Next.js: 約5-10秒増加（許容）

**最適化方法**:
- concurrency設定により、同一PR内の古い実行を自動キャンセル
- paths設定により、関連ファイル変更時のみ実行

---

## 6. 手動検証シナリオ

### 6.1 ローカル環境での検証

```bash
# Laravel環境変数バリデーション
cd backend/laravel-api
php artisan env:validate

# Next.js環境変数バリデーション（Admin App）
cd frontend/admin-app
npm run build

# Next.js環境変数バリデーション（User App）
cd frontend/user-app
npm run build

# 環境変数同期チェック
cd ../../
npm run env:check
```

### 6.2 Docker環境での検証

```bash
# Docker Compose で起動
docker compose up -d

# Laravel コンテナでバリデーション実行
docker compose exec laravel.test php artisan env:validate

# Next.js コンテナで動作確認
docker compose logs admin-app | grep "Environment"
docker compose logs user-app | grep "Environment"
```

### 6.3 CI/CD環境での検証

```bash
# Pull Request作成
gh pr create --title "Test: 環境変数バリデーション" --body "CI/CD動作確認"

# ワークフロー実行確認
gh pr checks

# 詳細ログ確認
gh run list --workflow=env-validation.yml --limit 1
gh run view <run-id> --log
```

---

## 7. パフォーマンス測定

### 7.1 ベンチマーク

| ワークフロー | 実行前 | 実行後 | 増加時間 | 評価 |
|------------|-------|-------|---------|-----|
| Laravel Test | 2分30秒 | 2分45秒 | +15秒 | ✅ 許容 |
| Frontend Test | 3分00秒 | 3分10秒 | +10秒 | ✅ 許容 |
| Env Validation (専用) | - | 2分00秒 | - | ✅ 新規 |

### 7.2 最適化施策

- **Concurrency設定**: 同一PR内の古い実行を自動キャンセル
- **Paths設定**: 環境変数関連ファイル変更時のみ実行
- **並列実行**: Admin App と User App を並列バリデーション
- **キャッシュ活用**: Composer/npm依存関係のキャッシュ

---

## 8. レビューチェックリスト

PR作成時に以下の項目を確認してください:

### 8.1 機能確認
- [ ] Laravel環境変数バリデーションが正常動作する
- [ ] Next.js環境変数バリデーションが正常動作する
- [ ] 環境変数同期チェックが正常動作する
- [ ] エラーメッセージが明瞭で理解しやすい
- [ ] CI/CDワークフローが自動実行される

### 8.2 ドキュメント確認
- [ ] README.md に環境変数管理セクションが追加されている
- [ ] GitHub Actions Secrets設定ガイドが作成されている
- [ ] 環境変数セキュリティガイドが作成されている
- [ ] 本CI/CD動作確認ガイドが作成されている

### 8.3 テスト確認
- [ ] ユニットテストが全てパスする
- [ ] 統合テストが全てパスする
- [ ] CI/CDワークフローが全てパスする
- [ ] 手動検証項目が全て完了している

### 8.4 セキュリティ確認
- [ ] `.env` ファイルが `.gitignore` に登録されている
- [ ] 機密情報が `.env.example` に平文で記載されていない
- [ ] GitHub Secrets が適切に設定されている
- [ ] エラーメッセージに機密情報が含まれていない

---

## 9. 関連ドキュメント

- [環境変数管理セクション - README.md](../README.md#環境変数管理)
- [GitHub Actions Secrets設定ガイド](./GITHUB_ACTIONS_SECRETS_GUIDE.md)
- [環境変数セキュリティガイド](./ENVIRONMENT_VARIABLE_SECURITY_GUIDE.md)
- [Laravel環境変数バリデーション実装](../backend/laravel-api/app/Support/Validation/EnvValidator.php)
- [Next.js環境変数バリデーション実装](../frontend/admin-app/lib/validation/env-validator.ts)

---

## 10. まとめ

このガイドに従い、CI/CD環境での環境変数バリデーション機能の動作を確認してください。

**期待される効果**:
- ✅ 環境変数設定ミスの早期検出
- ✅ 明瞭なエラーメッセージによる問題解決の迅速化
- ✅ CI/CD環境での自動バリデーションによる品質向上
- ✅ チーム全体での統一された環境変数管理

**問題が発生した場合**:
1. 本ガイドのトラブルシューティングセクションを参照
2. 関連ドキュメントを確認
3. GitHubプロジェクトのIssueで報告

---

**最終更新**: 2025-10-15
**バージョン**: 1.0.0
**メンテナー**: DevOps Team
