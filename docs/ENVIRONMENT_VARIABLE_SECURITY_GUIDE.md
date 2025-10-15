# 環境変数セキュリティガイド

## 目次

1. [セキュリティ原則](#セキュリティ原則)
2. [機密情報の分類](#機密情報の分類)
3. [環境変数管理のベストプラクティス](#環境変数管理のベストプラクティス)
4. [Laravel セキュリティ設定](#laravel-セキュリティ設定)
5. [Next.js セキュリティ設定](#nextjs-セキュリティ設定)
6. [CI/CD セキュリティ](#cicd-セキュリティ)
7. [セキュリティチェックリスト](#セキュリティチェックリスト)
8. [インシデント対応手順](#インシデント対応手順)

---

## セキュリティ原則

### 基本原則

このプロジェクトでは、環境変数のセキュリティに関して以下の原則を遵守します。

#### 1. 機密情報の明確な定義

機密情報とは、漏洩した場合にシステムのセキュリティやプライバシーを侵害する可能性のある情報です。

**機密情報の例**:
- データベース認証情報（ユーザー名、パスワード）
- APIキー、シークレットキー
- 暗号化キー、アプリケーションキー
- OAuth トークン、個人アクセストークン
- サードパーティサービスの認証情報

#### 2. .envファイルの厳格な管理

- `.env` ファイルは**絶対にバージョン管理に含めない**
- `.gitignore` に `.env` が含まれていることを確認
- `.env.example` はバージョン管理に含め、値は空またはダミー値を使用

#### 3. 最小権限の原則

- 必要最小限の環境変数のみ設定
- アクセス権限は必要最小限に制限
- 環境ごとに異なる認証情報を使用

#### 4. 多層防御

- アプリケーションレベル: バリデーション、暗号化
- インフラレベル: ファイアウォール、VPC分離
- CI/CDレベル: GitHub Secrets、アクセス制御

---

## 機密情報の分類

環境変数を以下の3つのセキュリティレベルに分類します。

### レベル1: 公開可能（Public）

漏洩してもシステムに影響がない情報。

**例**:
- `APP_NAME` - アプリケーション名
- `APP_TIMEZONE` - タイムゾーン
- `LOG_LEVEL` - ログレベル

**管理方法**:
- `.env.example` に実際の値を記載可能
- バージョン管理に含めても問題なし

### レベル2: 機密（Confidential）

漏洩すると一部の機能やデータが侵害される可能性がある情報。

**例**:
- `NEXT_PUBLIC_API_URL` - APIエンドポイント
- `DB_HOST` - データベースホスト
- `REDIS_HOST` - Redisホスト

**管理方法**:
- `.env.example` には開発環境の値またはダミー値を記載
- 本番環境の値はGitHub Secretsで管理
- アクセス制御を設定

### レベル3: 極秘（Top Secret）

漏洩すると重大なセキュリティインシデントにつながる情報。

**例**:
- `APP_KEY` - Laravel アプリケーションキー
- `DB_PASSWORD` - データベースパスワード
- `JWT_SECRET` - JWT署名キー
- `AWS_SECRET_ACCESS_KEY` - AWSシークレットアクセスキー

**管理方法**:
- `.env.example` には**絶対に実際の値を記載しない**
- 本番環境の値はGitHub Environment Secretsで管理
- 定期的なローテーション（3ヶ月ごと）
- デプロイ承認フロー必須

---

## 環境変数管理のベストプラクティス

### 1. .envファイルの管理

#### ✅ Do（推奨）

```bash
# .gitignore に .env を含める
.env
.env.local
.env.*.local

# .env.example は管理する
!.env.example
```

#### ❌ Don't（非推奨）

```bash
# .env をバージョン管理に含めない
git add .env  # NG
```

### 2. .env.example のメンテナンス

#### ✅ 適切な .env.example

```bash
# アプリケーション設定
# 必須: アプリケーション名（公開可能）
APP_NAME="Laravel Next B2C"

# 必須: アプリケーションキー（極秘）
# 生成コマンド: php artisan key:generate
# セキュリティ: 極秘 - 絶対に公開しない
APP_KEY=

# 必須: データベースパスワード（極秘）
# セキュリティ: 極秘 - 強力なパスワードを設定
# 開発環境: password
# 本番環境: GitHub Secrets で管理
DB_PASSWORD=
```

#### ❌ 不適切な .env.example

```bash
# 本番環境の実際の値を記載（NG）
DB_PASSWORD=SuperSecretProductionPassword123!

# コメントなし（NG）
APP_KEY=
```

### 3. 環境変数のバリデーション

このプロジェクトでは、以下のバリデーション機能を実装しています。

#### Laravel（起動時バリデーション）

```bash
# 手動バリデーション
php artisan env:validate

# 起動時自動バリデーション
# bootstrap/app.php で自動実行
```

#### Next.js（ビルド時バリデーション）

```bash
# ビルド前バリデーション
npm run build  # 自動的に check-env.ts が実行される
```

#### 環境変数同期チェック

```bash
# .env.example と .env の差分チェック
npm run env:check

# 差分を自動同期
npm run env:sync
```

### 4. 環境変数の暗号化

#### Laravel - 機密データの暗号化

```php
use Illuminate\Support\Facades\Crypt;

// 暗号化
$encrypted = Crypt::encryptString('機密データ');

// 復号化
$decrypted = Crypt::decryptString($encrypted);
```

#### Next.js - Server-side Only環境変数

```typescript
// ❌ Client-sideで使用可能（ブラウザに露出）
const apiKey = process.env.NEXT_PUBLIC_API_KEY;

// ✅ Server-sideでのみ使用可能（安全）
const secretKey = process.env.SECRET_API_KEY;
```

**重要**: `NEXT_PUBLIC_` プレフィックスは、クライアント側で使用可能な環境変数です。機密情報には使用しないでください。

---

## Laravel セキュリティ設定

### 1. アプリケーションキー（APP_KEY）

**重要性**: 極秘 - セッション、Cookie、暗号化データの署名に使用

**設定方法**:
```bash
# 新規生成
php artisan key:generate

# .env に自動的に設定される
APP_KEY=base64:ランダムな文字列
```

**セキュリティ対策**:
- アプリケーションキーは**絶対に共有しない**
- 環境ごとに異なるキーを使用
- 定期的にローテーション（本番環境: 6ヶ月ごと）
- キー変更時は既存セッションが無効化されることに注意

### 2. データベース接続設定

**機密度**: 極秘 - データベースへの完全なアクセス権

**推奨設定**:
```bash
# .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1  # 機密
DB_PORT=5432
DB_DATABASE=laravel  # 機密
DB_USERNAME=laravel_user  # 機密
DB_PASSWORD=強力なパスワード  # 極秘
```

**セキュリティ対策**:
- データベースパスワードは**20文字以上**を推奨
- 英数字 + 記号を混在
- 開発・本番環境で異なるパスワードを使用
- 本番環境のパスワードはGitHub Secretsで管理

### 3. Laravel Sanctum 設定

**機密度**: 極秘 - API認証トークンの発行

**推奨設定**:
```bash
# .env
SANCTUM_STATEFUL_DOMAINS=localhost:13001,localhost:13002
SESSION_DOMAIN=localhost
SESSION_LIFETIME=120  # セッション有効期限（分）
```

**セキュリティ対策**:
- `SANCTUM_STATEFUL_DOMAINS` は信頼できるドメインのみ設定
- 本番環境では実際のドメインを設定
- セッション有効期限を適切に設定（推奨: 2時間）

### 4. CORS 設定

**機密度**: 機密 - クロスオリジンリクエストの制御

**推奨設定**:
```bash
# .env
FRONTEND_ADMIN_URL=http://localhost:13001
FRONTEND_USER_URL=http://localhost:13002
```

```php
// config/cors.php
'allowed_origins' => array_filter([
    env('FRONTEND_ADMIN_URL'),
    env('FRONTEND_USER_URL'),
]),
```

**セキュリティ対策**:
- ワイルドカード（`*`）は**絶対に使用しない**
- 信頼できるオリジンのみ許可
- 本番環境では実際のドメインを設定

### 5. CSRF プロテクション

LaravelのCSRFプロテクションはデフォルトで有効です。

**セキュリティ対策**:
- CSRFトークンを**無効化しない**
- API エンドポイントには Sanctum トークン認証を使用
- ステートフルドメイン以外からのリクエストは拒否

---

## Next.js セキュリティ設定

### 1. 環境変数の分類

#### Server-side Only（サーバー側のみ）

```bash
# .env.local
SECRET_API_KEY=極秘キー
DATABASE_URL=postgresql://...
JWT_SECRET=JWT署名キー
```

これらの環境変数は**クライアント側では使用できません**。

#### Client-side（クライアント側で使用可能）

```bash
# .env.local
NEXT_PUBLIC_API_URL=http://localhost:13000/api
NEXT_PUBLIC_APP_NAME=Admin App
```

`NEXT_PUBLIC_` プレフィックスの環境変数は、ブラウザに露出されます。

**重要**: 機密情報には `NEXT_PUBLIC_` を**絶対に使用しない**でください。

### 2. 環境変数バリデーション（Zod）

```typescript
// lib/validation/env-validator.ts
import { z } from 'zod';

const envSchema = z.object({
  NEXT_PUBLIC_API_URL: z.string().url(),
  NODE_ENV: z.enum(['development', 'test', 'production']),
});

export const env = envSchema.parse(process.env);
```

**セキュリティ対策**:
- ビルド時に自動バリデーション
- 不正な値の場合はビルド失敗
- 型安全な環境変数アクセス

### 3. APIエンドポイント設定

**機密度**: 機密 - API通信の基盤

**推奨設定**:
```bash
# 開発環境
NEXT_PUBLIC_API_URL=http://localhost:13000/api

# 本番環境（GitHub Secrets）
NEXT_PUBLIC_API_URL=https://api.example.com
```

**セキュリティ対策**:
- HTTPS必須（本番環境）
- 環境ごとに異なるエンドポイントを使用
- 本番環境のURLはGitHub Secretsで管理

### 4. Content Security Policy (CSP)

Next.jsでCSPを設定することを推奨します。

```typescript
// next.config.js
const cspHeader = `
  default-src 'self';
  script-src 'self' 'unsafe-eval' 'unsafe-inline';
  style-src 'self' 'unsafe-inline';
  img-src 'self' blob: data:;
  font-src 'self';
  connect-src 'self' ${process.env.NEXT_PUBLIC_API_URL};
  frame-ancestors 'none';
`;
```

---

## CI/CD セキュリティ

### 1. GitHub Secrets の管理

#### Repository Secrets vs Environment Secrets

| 環境 | 使用するSecrets | 理由 |
|------|----------------|------|
| 本番環境 | Environment Secrets | デプロイ承認が必要 |
| ステージング | Environment Secrets | 環境分離が必要 |
| テスト環境 | Repository Secrets | CI/CD専用 |

#### 推奨設定

```yaml
# .github/workflows/deploy.yml
jobs:
  deploy:
    runs-on: ubuntu-latest
    environment: production  # Environment Secrets 使用
    steps:
      - name: Deploy
        env:
          DB_PASSWORD: ${{ secrets.LARAVEL_PROD_DB_PASSWORD }}
```

### 2. Secrets のローテーション

| Secret種類 | ローテーション頻度 | 理由 |
|-----------|------------------|------|
| 本番環境DB | 3ヶ月 | 高リスク |
| API キー | 3ヶ月 | 高リスク |
| テスト用 | 6ヶ月 | 低リスク |

### 3. アクセス制御

#### デプロイ承認フロー

```yaml
# Environment 設定
# Settings > Environments > production
- Required reviewers: 最低2名
- Deployment branches: main のみ
```

### 4. 監査ログの確認

**確認頻度**: 月1回

**確認項目**:
- Secretsの作成・更新・削除履歴
- ワークフロー実行履歴
- デプロイ承認履歴

**確認方法**:
1. GitHub Settings > Audit log
2. フィルター: `action:repo.update_actions_secret`

---

## セキュリティチェックリスト

### セットアップ時

- [ ] `.gitignore` に `.env` が含まれている
- [ ] `.env.example` に機密情報の実際の値が含まれていない
- [ ] Laravel `APP_KEY` を生成済み
- [ ] データベースパスワードが強力（20文字以上）
- [ ] CORS 設定が適切（ワイルドカード未使用）
- [ ] Next.js 環境変数バリデーションが動作
- [ ] GitHub Secrets が設定済み
- [ ] 本番環境にEnvironment Secretsを使用

### 運用時

- [ ] 環境変数バリデーションがCI/CDで動作
- [ ] 本番環境のSecretsを3ヶ月ごとにローテーション
- [ ] 監査ログを月1回確認
- [ ] デプロイ承認フローが機能
- [ ] 不要なSecretsを削除
- [ ] `.env.example` が最新状態

### インシデント発生時

- [ ] 該当Secretを即座に無効化
- [ ] 影響範囲を調査
- [ ] 関連サービスの認証情報を変更
- [ ] 監査ログを確認
- [ ] インシデント報告書を作成
- [ ] 再発防止策を実施

---

## インシデント対応手順

### フェーズ1: 検知と初動対応（0-15分）

#### 1. インシデントの検知

**検知方法**:
- 異常なアクセスログ
- GitHub Secretsの不正な変更通知
- 第三者からの指摘
- セキュリティスキャンツールのアラート

#### 2. 即座に実施すべきこと

1. **該当Secretを無効化**
   ```bash
   # GitHub Settings > Secrets and variables > Actions
   # 該当Secretを削除または値を変更
   ```

2. **関連サービスの認証情報を変更**
   - データベースパスワード変更
   - APIキー再発行
   - アクセストークン無効化

3. **アクセスログの確保**
   - GitHubアクセスログ
   - アプリケーションログ
   - データベースアクセスログ

### フェーズ2: 影響範囲調査（15分-1時間）

#### 1. 漏洩範囲の特定

- [ ] どのSecretが漏洩したか
- [ ] いつ漏洩したか
- [ ] どこに漏洩したか（GitHub、ログ、外部サービス）
- [ ] 誰がアクセスしたか

#### 2. データアクセス調査

```bash
# データベースアクセスログ確認
# PostgreSQL例
SELECT * FROM pg_stat_activity WHERE usename = 'laravel_user';

# Laravelログ確認
tail -f storage/logs/laravel.log
```

#### 3. 不正アクセスの有無確認

- [ ] 不正なログイン試行
- [ ] データの不正な読み取り・変更・削除
- [ ] 不審なAPIリクエスト

### フェーズ3: 復旧対応（1-3時間）

#### 1. 新しい認証情報の設定

```bash
# Laravel APP_KEY再生成
php artisan key:generate --force

# データベースパスワード変更
ALTER USER laravel_user WITH PASSWORD '新しい強力なパスワード';

# GitHub Secretsに新しい値を設定
# Settings > Secrets and variables > Actions
```

#### 2. 影響を受けたユーザーへの対応

- [ ] ユーザーセッションの無効化
- [ ] パスワードリセットの実施（必要に応じて）
- [ ] ユーザーへの通知（重大インシデントの場合）

#### 3. システムの動作確認

- [ ] アプリケーションが正常に起動
- [ ] データベース接続が正常
- [ ] API認証が正常
- [ ] ログイン・ログアウトが正常

### フェーズ4: 報告と再発防止（3時間-1週間）

#### 1. インシデント報告書の作成

**報告書に含める内容**:
- インシデントの概要
- 検知日時と検知方法
- 漏洩した情報の種類と範囲
- 影響範囲（ユーザー数、データ量等）
- 対応内容と対応日時
- 原因分析
- 再発防止策

#### 2. 再発防止策の実施

**技術的対策**:
- [ ] 環境変数バリデーションの強化
- [ ] CI/CDセキュリティチェックの追加
- [ ] アクセス制御の見直し
- [ ] 監査ログの自動アラート設定

**プロセス改善**:
- [ ] セキュリティレビューの徹底
- [ ] Secretsローテーションポリシーの見直し
- [ ] チーム教育・トレーニングの実施

#### 3. フォローアップ

- [ ] 1週間後の状況確認
- [ ] 1ヶ月後の監査ログ確認
- [ ] 再発防止策の効果測定

### エスカレーションフロー

| 重要度 | 対応者 | エスカレーション先 |
|-------|-------|------------------|
| 低 | 開発チーム | チームリーダー |
| 中 | チームリーダー | プロジェクトマネージャー |
| 高 | プロジェクトマネージャー | セキュリティチーム + 経営層 |

**重要度の判断基準**:
- **低**: テスト環境のSecret漏洩、影響範囲が限定的
- **中**: ステージング環境のSecret漏洩、一部ユーザーに影響
- **高**: 本番環境のSecret漏洩、全ユーザーに影響、個人情報漏洩の可能性

---

## 関連ドキュメント

- [GitHub Actions Secrets 設定ガイド](./GITHUB_ACTIONS_SECRETS_GUIDE.md)
- [README - 環境変数管理セクション](../README.md#環境変数管理)
- [CORS設定ガイド](./CORS_CONFIGURATION_GUIDE.md)

---

## 更新履歴

- 2025-01-15: 初版作成
