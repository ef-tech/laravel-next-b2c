# Requirements Document

## GitHub Issue Information

**Issue**: [#33](https://github.com/ef-tech/laravel-next-b2c/issues/33) - 環境変数適切管理方法整備
**Labels**: なし
**Milestone**: なし
**Assignees**: なし

### Original Issue Description

# 環境変数適切管理方法整備

## 📋 Background & Objectives

### 背景
Laravel 12 + Next.js 15.5 モノレポ構成において、環境変数の管理が標準化されていない状況です。現在、以下の課題が存在しています:

- `.env.example` ファイルのコメントが不十分で、各変数の意味・必須性・セキュリティレベルが不明確
- 環境変数のバリデーション機能が未実装で、設定ミスが実行時エラーにつながる
- 開発環境と本番環境での設定差分の管理が属人化
- CI/CD環境での環境変数注入方法が統一されていない
- チーム内での機密情報共有方法が未確立
- 型安全な環境変数アクセスが保証されていない（Next.js）

### 目的
機密情報の安全な管理方法を標準化し、以下を実現します:

1. **起動前バリデーション**: 環境変数の不足・誤設定を起動時に検知してフェイルファスト
2. **型安全性**: TypeScript型定義による環境変数の型安全なアクセス（Next.js）
3. **仕様化された .env.example**: 生きた仕様書としての詳細コメント整備
4. **自動同期**: `.env.example` と実際の `.env` の差分を自動検出・同期
5. **セキュアなCI/CD**: GitHub Secrets を活用した安全な環境変数注入
6. **チーム標準化**: 環境変数管理のベストプラクティスをドキュメント化

## 🏷️ Category

**主カテゴリ**: Chore（開発環境整備）

**詳細分類**:
- **Code**: Laravel/Next.js バリデーション実装（30%）
- **Docs**: セキュリティガイド・運用ドキュメント作成（30%）
- **CI-CD**: GitHub Actions ワークフロー統合（20%）
- **Ops**: 環境変数管理運用標準化（20%）

## 🎯 Scope

### 含まれるもの ✅
- ルート、`backend/laravel-api`、`e2e` の3つの `.env.example` 詳細コメント整備
- Laravel 環境変数バリデーション実装（Bootstrapper/Artisanコマンド）
- Next.js 環境変数バリデーション実装（Zod スキーマ）
- 環境変数同期スクリプト作成（TypeScript）
- GitHub Actions Secrets 設定ガイド作成
- 環境変数セキュリティドキュメント作成
- CI/CD ワークフローへのバリデーション統合
- チーム展開用ロールアウト計画

### 含まれないもの ❌
- 既存の環境変数値の変更（移行ガイドのみ提供）
- Kubernetes Secrets や AWS Secrets Manager などの外部シークレット管理ツール統合
- .env ファイルの暗号化機能（Laravel 11の `env:encrypt` は別タスク）
- 環境変数の自動ローテーション機能

## 📝 Specifications & Procedures

### 1. .env.example 詳細コメント整備

**対象ファイル**:
- `.env.example` (ルート)
- `backend/laravel-api/.env.example`
- `e2e/.env.example`

**コメント追加フォーマット**:
```bash
# 変数名
# - 説明: 変数の用途と影響範囲
# - 必須: はい/いいえ
# - 環境: 開発環境=値例, 本番環境=値例
# - セキュリティ: 公開可/機密/極秘
# - デフォルト: デフォルト値（存在する場合）
# - 注意事項: 変更時の影響や制約
変数名=デフォルト値
```

### 2. Laravel 環境変数バリデーション実装

#### 2.1 環境変数スキーマ定義
**ファイル**: `backend/laravel-api/config/env_schema.php`

#### 2.2 バリデータ本体
**ファイル**: `backend/laravel-api/app/Support/EnvValidator.php`

#### 2.3 Bootstrapper実装
**ファイル**: `backend/laravel-api/app/Bootstrap/ValidateEnvironment.php`

#### 2.4 Artisanコマンド実装
**ファイル**: `backend/laravel-api/app/Console/Commands/EnvValidate.php`

**使用方法**:
```bash
php artisan env:validate
```

### 3. Next.js 環境変数バリデーション実装

**依存関係追加**:
```bash
npm install --save-dev zod @next/env tsx
```

#### 3.1 環境変数スキーマ定義
**ファイル**: `frontend/admin-app/src/lib/env.ts`, `frontend/user-app/src/lib/env.ts`

#### 3.2 ビルド前検証スクリプト
**ファイル**: `frontend/admin-app/scripts/check-env.ts`

#### 3.3 package.json 統合
```json
{
  "scripts": {
    "predev": "tsx scripts/check-env.ts",
    "prebuild": "tsx scripts/check-env.ts"
  }
}
```

### 4. 環境変数同期スクリプト作成

**ファイル**: `scripts/env-sync.ts`

**package.json スクリプト追加**:
```json
{
  "scripts": {
    "env:check": "tsx scripts/env-sync.ts --check",
    "env:sync": "tsx scripts/env-sync.ts --write"
  }
}
```

### 5. ドキュメント作成

#### 5.1 GitHub Actions Secrets 設定ガイド
**ファイル**: `docs/GITHUB_ACTIONS_SECRETS_GUIDE.md`

**内容**:
- Secrets命名規約
- 設定手順（Repository Secrets / Environment Secrets）
- 必須Secrets一覧（Backend/Frontend）
- CI/CDワークフローでの使用例
- セキュリティベストプラクティス

#### 5.2 環境変数セキュリティガイド
**ファイル**: `docs/ENVIRONMENT_SECURITY_GUIDE.md`

**内容**:
- セキュリティ原則（機密情報の定義、.env管理）
- Laravel/Next.jsセキュリティ設定
- CI/CDセキュリティ
- セキュリティチェックリスト
- インシデント対応手順

### 6. CI/CD ワークフロー統合

#### 6.1 既存ワークフロー修正
- `.github/workflows/test.yml`: Laravel環境変数バリデーション追加
- `.github/workflows/frontend-test.yml`: Next.js環境変数バリデーション追加

#### 6.2 新規ワークフロー作成
**ファイル**: `.github/workflows/environment-validation.yml`

**トリガー**:
- Pull Request (環境変数関連ファイル変更時)
- Push to main branch

## ⚠️ Impact & Risk

### 影響範囲
- **開発環境**: 既存の `.env` ファイルは変更不要（バリデーションが追加されるのみ）
- **CI/CD**: 新規ステップ追加により、ビルド時間が約10-20秒増加
- **チーム**: 新規メンバーの環境構築時に `.env` 設定ミスが減少
- **本番環境**: 起動時バリデーションにより、環境変数不足での障害を防止

### リスクと軽減策

| リスク | 発生確率 | 影響度 | 軽減策 |
|--------|---------|-------|-------|
| 既存環境でバリデーションエラー | 中 | 中 | マイグレーション期間を設け、警告のみ表示するモード追加 |
| CI/CD ビルド失敗 | 低 | 高 | 段階的ロールアウト、バリデーションスキップフラグ提供 |
| 環境変数スキーマの保守負荷 | 低 | 低 | .env.example 変更時の自動チェック |

## ✅ Checklist

### フェーズ1: 基盤整備
- [ ] ルート `.env.example` 詳細コメント追加
- [ ] Laravel `.env.example` 詳細コメント追加
- [ ] E2E `.env.example` 詳細コメント追加

### フェーズ2: バリデーション実装（Laravel）
- [ ] 環境変数スキーマ定義（`config/env_schema.php`）
- [ ] バリデータ実装（`app/Support/EnvValidator.php`）
- [ ] Bootstrapper 実装（`app/Bootstrap/ValidateEnvironment.php`）
- [ ] Artisan コマンド実装（`app/Console/Commands/EnvValidate.php`）
- [ ] Bootstrapper 登録（`bootstrap/app.php`）

### フェーズ3: バリデーション実装（Next.js）
- [ ] Zod スキーマ実装（Admin App: `src/lib/env.ts`）
- [ ] Zod スキーマ実装（User App: `src/lib/env.ts`）
- [ ] ビルド前検証スクリプト統合（両アプリ）

### フェーズ4: ツール実装
- [ ] 環境変数同期スクリプト実装（`scripts/env-sync.ts`）
- [ ] package.json スクリプト追加
- [ ] 同期スクリプト動作確認

### フェーズ5: ドキュメント作成
- [ ] GitHub Actions Secrets 設定ガイド作成
- [ ] 環境変数セキュリティガイド作成
- [ ] README.md 更新（環境変数管理セクション追加）

### フェーズ6: CI/CD統合
- [ ] Laravel テストワークフロー修正
- [ ] フロントエンドテストワークフロー修正
- [ ] 環境変数バリデーション専用ワークフロー作成
- [ ] CI/CD 動作確認

### フェーズ7: テスト・検証
- [ ] ユニットテスト実装（Laravel EnvValidator）
- [ ] ユニットテスト実装（Next.js env.ts）
- [ ] 統合テスト実装（env-sync.ts）
- [ ] CI/CD環境でのE2Eテスト
- [ ] エラーメッセージの分かりやすさ確認

### フェーズ8: チーム展開
- [ ] チームレビュー実施
- [ ] フィードバック反映
- [ ] ロールアウト計画確定
- [ ] 運用開始

## 🧪 Testing Strategy

### 1. ユニットテスト
**Laravel バリデータテスト**:
- 必須変数不足時のRuntimeException検証
- 正常な環境変数でのバリデーション成功確認
- 型違い・値範囲外のバリデーションエラー確認

**Next.js Zodスキーマテスト**:
- 正常な環境変数でのバリデーション成功確認
- 不正なURL形式でのバリデーションエラー確認
- NEXT_PUBLIC_プレフィックス有無の検証

### 2. 統合テスト
**環境変数同期スクリプトテスト**:
- `.env.example` のみ存在する状態で同期実行
- `.env` に既存値がある状態で同期実行
- 不足キーがある状態でチェック実行
- 未知キーがある状態で同期実行

### 3. E2Eテスト（CI/CD）
- GitHub Actions ワークフローの全ステップ実行確認
- 環境変数不足時のビルド失敗確認
- 環境変数バリデーションエラーメッセージ確認

### 4. 手動検証
- [ ] ローカル開発環境での起動確認
- [ ] Docker環境での起動確認
- [ ] CI/CD環境でのビルド成功確認
- [ ] ドキュメントの読みやすさ確認

## 🎯 Definition of Done (DoD)

### 完了条件
1. **実装完了**:
   - [ ] 全8フェーズのチェックリスト項目が完了
   - [ ] Laravel バリデーション実装が正常動作（`php artisan env:validate` 成功）
   - [ ] Next.js バリデーション実装が正常動作（ビルド前検証成功）
   - [ ] 環境変数同期スクリプトが正常動作（`npm run env:check` 成功）

2. **テスト完了**:
   - [ ] ユニットテストが全てパス
   - [ ] 統合テストが全てパス
   - [ ] CI/CDワークフローが全てパス
   - [ ] 手動検証項目が全て完了

3. **ドキュメント完了**:
   - [ ] GitHub Actions Secrets 設定ガイド作成・レビュー完了
   - [ ] 環境変数セキュリティガイド作成・レビュー完了
   - [ ] README.md 更新完了

4. **品質基準**:
   - [ ] Laravel Pint によるコードフォーマット検証済み
   - [ ] Larastan (PHPStan Level 8) による静的解析合格
   - [ ] ESLint によるTypeScriptコード検証済み
   - [ ] Prettier によるコードフォーマット統一済み

5. **チーム承認**:
   - [ ] コードレビュー完了（最低2名の承認）
   - [ ] セキュリティレビュー完了
   - [ ] ドキュメントレビュー完了

6. **運用準備**:
   - [ ] ロールアウト計画が承認されている
   - [ ] トラブルシューティングガイドが整備されている
   - [ ] 問い合わせ窓口が明確になっている

### 受け入れ基準
- **機能性**: 環境変数の不足・誤設定が起動時に検知され、明確なエラーメッセージが表示される
- **セキュリティ**: 機密情報が .gitignore により保護され、GitHub Secrets で安全に管理される
- **保守性**: .env.example が仕様書として機能し、新規メンバーが迷わず設定できる
- **自動化**: CI/CD で環境変数バリデーションが自動実行され、ビルドエラーが早期検出される

## 📚 References

### Primary Sources
- [Laravel 12 環境設定](https://laravel.com/docs/12.x/configuration)
- [Next.js 15 環境変数](https://nextjs.org/docs/app/building-your-application/configuring/environment-variables)
- [Zod スキーマバリデーション](https://zod.dev/)
- [GitHub Actions Secrets](https://docs.github.com/en/actions/security-guides/encrypted-secrets)

### Project Documents
- `backend/laravel-api/docs/configuration-changes.md` - Laravel設定変更履歴
- `docs/CORS_CONFIGURATION_GUIDE.md` - CORS環境変数設定ガイド
- `.kiro/steering/tech.md` - 技術スタック詳細

### Security Best Practices
- [OWASP Secrets Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html)
- [The Twelve-Factor App - Config](https://12factor.net/config)

---

**実装期間**: 5-6週間
**優先度**: High（セキュリティ改善・開発効率向上）
**担当**: DevOps Team + Security Team

## Extracted Information

### Technology Stack
**Backend**: Laravel 12, PHP 8.4, Composer, Artisan
**Frontend**: Next.js 15.5, React 19, TypeScript, Zod, @next/env, tsx
**Infrastructure**: Docker, Docker Compose, GitHub Actions
**Tools**: Laravel Pint, Larastan (PHPStan Level 8), ESLint, Prettier

### Project Structure
主要な実装対象ファイル:
```
.env.example
backend/laravel-api/.env.example
e2e/.env.example
backend/laravel-api/config/env_schema.php
backend/laravel-api/app/Support/EnvValidator.php
backend/laravel-api/app/Bootstrap/ValidateEnvironment.php
backend/laravel-api/app/Console/Commands/EnvValidate.php
frontend/admin-app/src/lib/env.ts
frontend/user-app/src/lib/env.ts
frontend/admin-app/scripts/check-env.ts
frontend/user-app/scripts/check-env.ts
scripts/env-sync.ts
docs/GITHUB_ACTIONS_SECRETS_GUIDE.md
docs/ENVIRONMENT_SECURITY_GUIDE.md
.github/workflows/environment-validation.yml
```

### Requirements Hints
基本要件から抽出されたヒント:
- 起動前バリデーション: 環境変数の不足・誤設定を起動時に検知してフェイルファスト
- 型安全性: TypeScript型定義による環境変数の型安全なアクセス（Next.js）
- 仕様化された .env.example: 生きた仕様書としての詳細コメント整備
- 自動同期: .env.example と実際の .env の差分を自動検出・同期
- セキュアなCI/CD: GitHub Secrets を活用した安全な環境変数注入
- チーム標準化: 環境変数管理のベストプラクティスをドキュメント化

### TODO Items from Issue
Issue #33から自動インポートされたTODOアイテム:
- [ ] ルート .env.example 詳細コメント追加
- [ ] Laravel .env.example 詳細コメント追加
- [ ] E2E .env.example 詳細コメント追加
- [ ] 環境変数スキーマ定義（config/env_schema.php）
- [ ] バリデータ実装（app/Support/EnvValidator.php）
- [ ] Bootstrapper 実装（app/Bootstrap/ValidateEnvironment.php）
- [ ] Artisan コマンド実装（app/Console/Commands/EnvValidate.php）
- [ ] Bootstrapper 登録（bootstrap/app.php）
- [ ] Zod スキーマ実装（Admin App: src/lib/env.ts）
- [ ] Zod スキーマ実装（User App: src/lib/env.ts）
- [ ] ビルド前検証スクリプト統合（両アプリ）
- [ ] 環境変数同期スクリプト実装（scripts/env-sync.ts）
- [ ] package.json スクリプト追加
- [ ] 同期スクリプト動作確認
- [ ] GitHub Actions Secrets 設定ガイド作成
- [ ] 環境変数セキュリティガイド作成
- [ ] README.md 更新（環境変数管理セクション追加）
- [ ] Laravel テストワークフロー修正
- [ ] フロントエンドテストワークフロー修正
- [ ] 環境変数バリデーション専用ワークフロー作成
- [ ] CI/CD 動作確認
- [ ] ユニットテスト実装（Laravel EnvValidator）
- [ ] ユニットテスト実装（Next.js env.ts）
- [ ] 統合テスト実装（env-sync.ts）
- [ ] CI/CD環境でのE2Eテスト
- [ ] エラーメッセージの分かりやすさ確認
- [ ] チームレビュー実施
- [ ] フィードバック反映
- [ ] ロールアウト計画確定
- [ ] 運用開始

## Requirements

### Introduction
本プロジェクトでは、Laravel 12 + Next.js 15.5 モノレポ構成における環境変数管理の標準化を実現します。現状の課題（`.env.example`の不十分なコメント、バリデーション機能の欠如、CI/CD環境での設定不統一、型安全性の欠如）を解決し、機密情報の安全な管理とチーム開発の効率化を目指します。

本要件定義では、起動前バリデーション、型安全な環境変数アクセス、詳細なドキュメント整備、CI/CD統合による6つの主要機能を定義します。これにより、環境変数設定ミスによる実行時エラーを防止し、新規メンバーのオンボーディングを迅速化し、セキュリティベストプラクティスを標準化します。

---

### Requirement 1: 環境変数テンプレートファイル詳細化
**Objective:** 開発者として、`.env.example`ファイルに詳細なコメントと仕様情報を記載することで、環境変数の意味・必須性・セキュリティレベルを明確に理解し、設定ミスを防止したい

#### Acceptance Criteria

1. WHEN 開発者が `.env.example` ファイルを開いたとき THEN 環境変数管理システムは 各変数に対して以下の情報を含むコメントを提供すること
   - 変数の説明: 用途と影響範囲
   - 必須性: はい/いいえ/条件付き
   - 環境別デフォルト値: 開発環境・本番環境の推奨値例
   - セキュリティレベル: 公開可/機密/極秘
   - 注意事項: 変更時の影響や制約

2. WHERE ルートディレクトリの `.env.example` THE 環境変数管理システムは モノレポ全体で共通の環境変数のみを記載すること

3. WHERE `backend/laravel-api/.env.example` THE 環境変数管理システムは Laravel API固有の環境変数（DB接続、Sanctum設定、CORS設定等）を記載すること

4. WHERE `e2e/.env.example` THE 環境変数管理システムは E2Eテスト実行に必要な環境変数（テストURL、認証情報等）を記載すること

5. WHEN 新規メンバーが環境構築を行うとき THEN 環境変数管理システムは `.env.example`の詳細コメントにより、5分以内に環境変数の意味を理解できるようにすること

---

### Requirement 2: Laravel環境変数バリデーション機能
**Objective:** システム管理者として、Laravel APIの起動時に環境変数の不足・誤設定を自動検知することで、実行時エラーを防止し、フェイルファスト設計を実現したい

#### Acceptance Criteria

1. WHEN Laravel APIが起動するとき THEN 環境変数バリデータは アプリケーションブートストラップフェーズで環境変数のバリデーションを実行すること

2. IF 必須環境変数が不足しているとき THEN 環境変数バリデータは 明確なエラーメッセージ（不足変数名、必須理由、設定例）とともに`RuntimeException`をスローし、アプリケーション起動を停止すること

3. IF 環境変数の値が型制約に違反しているとき（例: ポート番号が数値でない） THEN 環境変数バリデータは 型エラーメッセージとともに`RuntimeException`をスローし、アプリケーション起動を停止すること

4. WHEN 開発者が手動で環境変数を検証したいとき THEN 環境変数バリデータは `php artisan env:validate`コマンドを提供し、現在の`.env`設定の妥当性を検証すること

5. WHERE `backend/laravel-api/config/env_schema.php` THE 環境変数バリデータは 環境変数のスキーマ定義（必須性、型、デフォルト値、バリデーションルール）を宣言的に記述できること

6. WHEN 環境変数バリデーションが成功したとき THEN 環境変数バリデータは ログに検証成功メッセージを記録し、アプリケーション起動を継続すること

7. IF CI/CD環境でバリデーションエラーが発生したとき THEN 環境変数バリデータは ビルドを失敗させ、エラー詳細をログに出力すること

---

### Requirement 3: Next.js環境変数型安全アクセス機能
**Objective:** フロントエンド開発者として、TypeScriptの型システムを活用して環境変数に安全にアクセスすることで、ランタイムエラーを防止し、開発効率を向上させたい

#### Acceptance Criteria

1. WHEN Next.jsアプリケーション（Admin App / User App）が起動またはビルドされるとき THEN 環境変数バリデータは Zodスキーマによる環境変数バリデーションを実行すること

2. IF 必須環境変数（例: `NEXT_PUBLIC_API_URL`）が不足しているとき THEN 環境変数バリデータは ビルドまたは開発サーバー起動を停止し、不足変数名とサンプル値を含むエラーメッセージを表示すること

3. IF 環境変数の値が型制約に違反しているとき（例: URLが不正な形式） THEN 環境変数バリデータは 型エラーメッセージを表示し、ビルドまたは起動を停止すること

4. WHEN フロントエンド開発者が環境変数にアクセスするとき THEN 環境変数管理システムは 型安全な環境変数オブジェクト（例: `env.NEXT_PUBLIC_API_URL`）を提供し、TypeScriptの型チェックを有効化すること

5. WHERE `frontend/admin-app/src/lib/env.ts` および `frontend/user-app/src/lib/env.ts` THE 環境変数管理システムは Zodスキーマによる環境変数定義と型エクスポートを提供すること

6. WHEN `package.json`の`predev`または`prebuild`スクリプトが実行されるとき THEN 環境変数バリデータは ビルド前検証スクリプト（`tsx scripts/check-env.ts`）を自動実行すること

7. IF `NEXT_PUBLIC_`プレフィックスのない環境変数がクライアント側コードで参照されたとき THEN 環境変数バリデータは ビルド時にエラーを表示し、セキュリティリスクを警告すること

---

### Requirement 4: 環境変数同期スクリプト機能
**Objective:** 開発チームとして、`.env.example`と`.env`ファイルの差分を自動検出し同期することで、環境変数の追加・削除時の設定漏れを防止したい

#### Acceptance Criteria

1. WHEN 開発者が`npm run env:check`を実行したとき THEN 環境変数同期スクリプトは `.env.example`と`.env`の差分を検出し、不足キーまたは未知キーのリストを表示すること

2. IF `.env`に`.env.example`にないキーが存在するとき THEN 環境変数同期スクリプトは 未知キーとして警告を表示し、`.env.example`への追加を推奨すること

3. IF `.env`に`.env.example`にあるキーが不足しているとき THEN 環境変数同期スクリプトは 不足キーとして警告を表示し、`.env`への追加を推奨すること

4. WHEN 開発者が`npm run env:sync`を実行したとき THEN 環境変数同期スクリプトは `.env.example`の新規キーを`.env`に追加し、デフォルト値（または空文字列）を設定すること

5. IF `.env`ファイルが存在しないとき THEN 環境変数同期スクリプトは `.env.example`を`.env`にコピーし、初期設定を生成すること

6. WHEN 同期スクリプトが実行されるとき THEN 環境変数同期スクリプトは 既存の`.env`の値を保持し、新規キーのみを追加すること

7. WHERE ルート`scripts/env-sync.ts` THE 環境変数同期スクリプトは TypeScriptで実装され、`tsx`コマンドで実行可能であること

---

### Requirement 5: GitHub Actions Secrets統合ガイド機能
**Objective:** DevOpsエンジニアとして、GitHub ActionsでのSecrets設定方法と命名規約を標準化することで、CI/CD環境での環境変数注入を安全かつ統一的に実施したい

#### Acceptance Criteria

1. WHERE `docs/GITHUB_ACTIONS_SECRETS_GUIDE.md` THE 環境変数管理システムは GitHub Actions Secrets設定の包括的ガイドを提供すること

2. WHEN DevOpsエンジニアがSecrets設定を行うとき THEN 環境変数管理システムは 以下の情報を含むガイドを提供すること:
   - Secrets命名規約（例: `LARAVEL_DB_PASSWORD`, `NEXT_PUBLIC_API_URL_PROD`）
   - Repository Secrets vs Environment Secretsの使い分け基準
   - 必須Secrets一覧（Backend: DB接続、APIキー等 / Frontend: API URL、外部サービスキー等）
   - CI/CDワークフローでのSecrets参照方法（`${{ secrets.SECRET_NAME }}`）
   - セキュリティベストプラクティス（ローテーション、アクセス制御、監査ログ）

3. IF CI/CD環境で必須Secretsが不足しているとき THEN 環境変数バリデータは ワークフロー実行を失敗させ、不足Secrets名を明示すること

4. WHEN チームメンバーがSecretsを追加するとき THEN 環境変数管理システムは ガイドの命名規約に従い、一貫性のあるSecrets名を使用することを推奨すること

---

### Requirement 6: 環境変数セキュリティガイド機能
**Objective:** セキュリティチームとして、環境変数管理のセキュリティベストプラクティスを標準化し、機密情報の漏洩リスクを最小化したい

#### Acceptance Criteria

1. WHERE `docs/ENVIRONMENT_SECURITY_GUIDE.md` THE 環境変数管理システムは 環境変数セキュリティの包括的ガイドを提供すること

2. WHEN セキュリティチームがガイドを参照するとき THEN 環境変数管理システムは 以下の情報を含むガイドを提供すること:
   - セキュリティ原則（機密情報の定義、.env管理、バージョン管理からの除外）
   - Laravel/Next.jsセキュリティ設定（CORS、CSRFプロテクション、Sanctum認証設定）
   - CI/CDセキュリティ（Secrets暗号化、アクセス制御、監査ログ）
   - セキュリティチェックリスト（`.env`の`.gitignore`登録確認、機密情報の定期ローテーション）
   - インシデント対応手順（機密情報漏洩時の緊急対応、影響範囲調査、再発防止策）

3. IF `.env`ファイルがGit管理されているとき THEN 環境変数管理システムは pre-commitフックまたはCI/CDチェックで警告を表示し、コミットを防止すること

4. WHEN 機密情報（例: `DB_PASSWORD`, `API_SECRET_KEY`）が`.env.example`に平文で記載されているとき THEN 環境変数管理システムは 警告を表示し、サンプル値（例: `your-db-password-here`）への置き換えを推奨すること

5. IF 環境変数に極秘情報（例: プライベートキー、OAuth Secret）が含まれるとき THEN 環境変数管理システムは Secrets管理ツール（GitHub Secrets、AWS Secrets Manager等）の使用を推奨すること

---

### Requirement 7: CI/CDワークフロー環境変数バリデーション統合機能
**Objective:** CI/CDエンジニアとして、GitHub ActionsワークフローにおいてPull Request時に環境変数バリデーションを自動実行することで、環境変数設定ミスを早期検出したい

#### Acceptance Criteria

1. WHERE `.github/workflows/test.yml` THE 環境変数管理システムは Laravel環境変数バリデーションステップを追加すること

2. WHERE `.github/workflows/frontend-test.yml` THE 環境変数管理システムは Next.js環境変数バリデーションステップを追加すること

3. WHERE `.github/workflows/environment-validation.yml` THE 環境変数管理システムは 環境変数専用バリデーションワークフローを新規作成すること

4. WHEN Pull Requestが作成またはコミットが追加されたとき AND 環境変数関連ファイル（`.env.example`, `env_schema.php`, `env.ts`等）が変更されているとき THEN CI/CDシステムは 環境変数バリデーションワークフローを自動実行すること

5. WHEN mainブランチへプッシュされたとき THEN CI/CDシステムは 環境変数バリデーションワークフローを自動実行すること

6. IF 環境変数バリデーションが失敗したとき THEN CI/CDシステムは Pull Requestのチェックステータスを失敗にし、エラー詳細をログに出力すること

7. WHEN 環境変数バリデーションが成功したとき THEN CI/CDシステムは Pull Requestのチェックステータスを成功にし、次のワークフローステップを継続すること

8. IF CI/CD環境でビルド時間が約10-20秒増加したとき THEN CI/CDシステムは パフォーマンス影響を許容範囲内とし、環境変数バリデーションの価値（エラー早期検出）を優先すること

---

### Requirement 8: テスト戦略と品質保証機能
**Objective:** QAエンジニアとして、環境変数バリデーション機能のユニットテスト・統合テスト・E2Eテストを実装することで、機能の信頼性を保証したい

#### Acceptance Criteria

1. WHERE `backend/laravel-api/tests/Unit/Support/EnvValidatorTest.php` THE 環境変数管理システムは Laravel環境変数バリデータのユニットテストを提供すること

2. WHEN Laravel環境変数バリデータのユニットテストが実行されるとき THEN テストスイートは 以下のテストケースをカバーすること:
   - 必須変数不足時の`RuntimeException`検証
   - 正常な環境変数でのバリデーション成功確認
   - 型違い・値範囲外のバリデーションエラー確認

3. WHERE `frontend/admin-app/src/lib/__tests__/env.test.ts` および `frontend/user-app/src/lib/__tests__/env.test.ts` THE 環境変数管理システムは Next.js Zodスキーマのユニットテストを提供すること

4. WHEN Next.js環境変数バリデータのユニットテストが実行されるとき THEN テストスイートは 以下のテストケースをカバーすること:
   - 正常な環境変数でのバリデーション成功確認
   - 不正なURL形式でのバリデーションエラー確認
   - `NEXT_PUBLIC_`プレフィックス有無の検証

5. WHERE `scripts/__tests__/env-sync.test.ts` THE 環境変数管理システムは 環境変数同期スクリプトの統合テストを提供すること

6. WHEN 環境変数同期スクリプトの統合テストが実行されるとき THEN テストスイートは 以下のテストケースをカバーすること:
   - `.env.example`のみ存在する状態で同期実行
   - `.env`に既存値がある状態で同期実行
   - 不足キーがある状態でチェック実行
   - 未知キーがある状態で同期実行

7. WHEN GitHub ActionsワークフローのE2Eテストが実行されるとき THEN テストスイートは 以下のシナリオをカバーすること:
   - 全ステップの正常実行確認
   - 環境変数不足時のビルド失敗確認
   - 環境変数バリデーションエラーメッセージの明瞭性確認

8. IF エラーメッセージが不明瞭または技術的すぎるとき THEN 環境変数管理システムは エラーメッセージを改善し、新規メンバーでも理解できる内容にすること

---

### Requirement 9: 段階的ロールアウトと移行戦略機能
**Objective:** プロジェクトマネージャーとして、既存環境へのバリデーション導入によるリスクを最小化するため、段階的ロールアウト戦略を実施したい

#### Acceptance Criteria

1. WHEN 環境変数バリデーション機能が初めて導入されるとき THEN 環境変数管理システムは 警告モード（Warning Mode）を提供し、バリデーションエラーを警告として表示しつつ、アプリケーション起動は継続すること

2. IF 警告モードが有効なとき AND バリデーションエラーが検出されたとき THEN 環境変数管理システムは ログに警告メッセージを記録し、エラー詳細と修正方法を表示すること

3. WHEN マイグレーション期間（例: 2週間）が経過したとき THEN 環境変数管理システムは エラーモード（Error Mode）に切り替え、バリデーションエラー時にアプリケーション起動を停止すること

4. WHERE 環境変数スキーマ定義 THE 環境変数管理システムは バリデーションスキップフラグ（例: `ENV_VALIDATION_SKIP=true`）を提供し、緊急時にバリデーションを無効化できること

5. WHEN 既存環境でバリデーションエラーが発生したとき THEN 環境変数管理システムは 移行ガイド（エラー原因、修正手順、サンプル設定）を提供すること

6. IF CI/CD環境でビルドが失敗したとき THEN 環境変数管理システムは ロールバック手順（バリデーション無効化、緊急デプロイ）をドキュメント化すること

---

### Requirement 10: チーム標準化とドキュメント整備機能
**Objective:** チームリーダーとして、環境変数管理のベストプラクティスをドキュメント化し、新規メンバーのオンボーディングを迅速化したい

#### Acceptance Criteria

1. WHERE `README.md` THE 環境変数管理システムは 環境変数管理セクションを追加し、以下の情報を提供すること:
   - セットアップ手順（`.env.example`コピー、環境変数設定、バリデーション実行）
   - 環境変数テンプレート構成（ルート、Laravel、E2E）
   - バリデーションコマンド（`php artisan env:validate`, `npm run env:check`）
   - トラブルシューティング（よくあるエラーと解決方法）

2. WHEN 新規メンバーがプロジェクトに参加したとき THEN 環境変数管理システムは ドキュメントにより、15分以内に環境変数のセットアップを完了できるようにすること

3. WHERE `docs/GITHUB_ACTIONS_SECRETS_GUIDE.md` THE 環境変数管理システムは CI/CD Secrets設定の手順書を提供すること

4. WHERE `docs/ENVIRONMENT_SECURITY_GUIDE.md` THE 環境変数管理システムは セキュリティベストプラクティスを提供すること

5. WHEN チームレビューが実施されるとき THEN 環境変数管理システムは 最低2名のレビュー承認を必要とし、コードレビュー・セキュリティレビュー・ドキュメントレビューを完了すること

6. IF ドキュメントの内容が不明瞭または情報不足のとき THEN 環境変数管理システムは フィードバックを反映し、ドキュメント品質を改善すること

7. WHEN ロールアウト計画が承認されたとき THEN 環境変数管理システムは トラブルシューティングガイドと問い合わせ窓口を整備すること
