# CSP 段階的導入デプロイメントチェックリスト

このドキュメントは Content Security Policy (CSP) を段階的に導入するための運用チェックリストです。

## 📋 概要

CSP の段階的導入は以下の4つのフェーズで構成されます:

1. **Phase 1: Report-Only モード有効化** (1週間)
2. **Phase 2: CSP 違反レポート収集・分析** (継続)
3. **Phase 3: Enforce モード移行判断** (1日)
4. **Phase 4: Enforce モード切り替え** (段階的ロールアウト)
5. **Phase 5: Enforce モード展開後監視** (24時間 + 継続)

---

## Phase 1: Report-Only モード有効化

### 前提条件

- [ ] セキュリティヘッダー実装が完了している
- [ ] Laravel API, User App, Admin App すべてのアプリケーションにセキュリティヘッダーが実装されている
- [ ] E2E テストがすべて通過している（全17テスト）
- [ ] CI/CD パイプラインでセキュリティヘッダー検証が成功している

### ステージング環境設定

#### Laravel API (.env)

```bash
# CSP 有効化
SECURITY_ENABLE_CSP=true

# Report-Only モード設定
SECURITY_CSP_MODE=report-only

# CSP レポート収集エンドポイント
SECURITY_CSP_REPORT_URI=/api/csp/report

# CSP ディレクティブ設定（開発環境と同じ）
SECURITY_CSP_SCRIPT_SRC='self' 'unsafe-eval' 'unsafe-inline'
SECURITY_CSP_STYLE_SRC='self' 'unsafe-inline'
SECURITY_CSP_IMG_SRC='self' data: https:
SECURITY_CSP_CONNECT_SRC='self'
SECURITY_CSP_FONT_SRC='self' data:
```

#### Next.js User App (.env.local)

```bash
# 環境変数は不要（next.config.ts で環境判定）
# NODE_ENV=production で本番環境設定が適用される
```

#### Next.js Admin App (.env.local)

```bash
# 環境変数は不要（next.config.ts で環境判定）
```

### デプロイ手順

1. **環境変数設定**
   ```bash
   # ステージング環境の .env ファイルを更新
   ssh staging-server
   cd /var/www/laravel-api
   vim .env
   # SECURITY_CSP_MODE=report-only に設定
   ```

2. **Laravel API デプロイ**
   ```bash
   # デプロイスクリプト実行
   ./deploy-staging.sh

   # または手動デプロイ
   git pull origin main
   composer install --no-dev --optimize-autoloader
   php artisan config:cache
   php artisan route:cache
   php artisan migrate --force
   sudo systemctl restart php8.4-fpm
   ```

3. **Next.js User App デプロイ**
   ```bash
   cd /var/www/user-app
   git pull origin main
   npm ci --production
   npm run build
   pm2 restart user-app
   ```

4. **Next.js Admin App デプロイ**
   ```bash
   cd /var/www/admin-app
   git pull origin main
   npm ci --production
   npm run build
   pm2 restart admin-app
   ```

### デプロイ後確認

- [ ] Laravel API ヘルスチェック成功
  ```bash
  curl -I https://staging-api.example.com/api/health
  # Content-Security-Policy-Report-Only ヘッダーが存在することを確認
  ```

- [ ] User App ヘルスチェック成功
  ```bash
  curl -I https://staging.example.com
  # Content-Security-Policy-Report-Only ヘッダーが存在することを確認
  ```

- [ ] Admin App ヘルスチェック成功
  ```bash
  curl -I https://staging-admin.example.com
  # Content-Security-Policy-Report-Only ヘッダーが存在することを確認
  ```

- [ ] セキュリティログ記録確認
  ```bash
  ssh staging-server
  tail -f /var/www/laravel-api/storage/logs/security.log
  # CSP 違反レポートがログに記録されることを確認（ページアクセス時）
  ```

### タイムライン

- **開始日時**: _______________
- **終了予定日時**: _______________ (1週間後)
- **担当者**: _______________

---

## Phase 2: CSP 違反レポート収集・分析

### 収集期間

- **最低期間**: 1週間
- **推奨期間**: 2週間（トラフィックが少ない場合）

### 日次モニタリング

毎日以下のコマンドを実行し、違反レポートを確認してください。

```bash
# 1. 総違反件数確認
ssh staging-server
cd /var/www/laravel-api
bash scripts/analyze-csp-violations.sh storage/logs/security.log
```

### 分析観点

#### 1. 違反ディレクティブTop 10の確認

```bash
# 違反ディレクティブTop 10
grep "violated_directive" storage/logs/security.log | \
  jq -r '.violated_directive' | \
  sort | uniq -c | sort -rn | head -10
```

**判断基準:**
- `script-src` 違反: サードパーティスクリプト読み込みの可能性
- `style-src` 違反: インラインスタイルまたは外部CSSの問題
- `img-src` 違反: 外部画像ドメインの許可が必要
- `connect-src` 違反: API呼び出し先の追加が必要

#### 2. ブロックされたURIの特定

```bash
# ブロックされたURITop 10
grep "blocked_uri" storage/logs/security.log | \
  jq -r '.blocked_uri' | \
  sort | uniq -c | sort -rn | head -10
```

**アクション:**
- 正当なリソース（CDN、アナリティクス、フォントなど）: CSPポリシーに追加
- 不正なリソース（XSS試行など）: ログ保存、セキュリティチームに報告

#### 3. 違反率計算

```bash
# 違反率計算スクリプト
bash scripts/analyze-csp-violations.sh
```

**目標基準:**
- 違反率: **< 0.1%**
- 正当な違反が100%（不正な試行がない）

### CSP ポリシー調整

違反レポートに基づいて、必要なドメインをCSPポリシーに追加します。

#### Laravel API (config/security.php)

```php
// 例: Google Analytics を許可する場合
'csp' => [
    'script_src' => env('SECURITY_CSP_SCRIPT_SRC', "'self' https://www.google-analytics.com https://www.googletagmanager.com"),
    'connect_src' => env('SECURITY_CSP_CONNECT_SRC', "'self' https://www.google-analytics.com"),
],
```

#### Next.js (frontend/security-config.ts)

```typescript
// 例: Google Fonts を許可する場合
scriptSrc: ["'self'", "https://fonts.googleapis.com"],
styleSrc: ["'self'", "https://fonts.googleapis.com"],
fontSrc: ["'self'", "https://fonts.gstatic.com", "data:"],
```

### 調整後の再デプロイ

- [ ] CSPポリシー更新をコミット
- [ ] ステージング環境に再デプロイ
- [ ] 違反件数が減少したことを確認
- [ ] 3日間追加モニタリング

### 週次レポート作成

毎週金曜日に以下の情報をまとめたレポートを作成してください。

```
# CSP Report-Only モード 週次レポート (Week X)

## 期間
- 開始: YYYY-MM-DD
- 終了: YYYY-MM-DD

## 統計
- 総違反件数: X,XXX
- 総リクエスト数: XX,XXX
- 違反率: X.XX%

## 違反ディレクティブTop 5
1. script-src: XXX件
2. style-src: XXX件
...

## ブロックされたURITop 5
1. https://cdn.example.com/...: XXX件
...

## アクション
- [ ] CSPポリシー調整（XX件）
- [ ] 正当な違反対応完了
- [ ] 不正な試行を検出（X件）

## 次週計画
- 違反率をX.XX%以下に削減
- Enforce モード移行判断
```

---

## Phase 3: Enforce モード移行判断

### 前提条件

- [ ] Report-Only モード運用期間: **最低1週間**
- [ ] CSP 違反率: **< 0.1%**
- [ ] 正当な違反: **すべて CSP ポリシーに反映済み**
- [ ] 不正な試行: **ログに記録済み、対策完了**

### 移行判断会議

**参加者:**
- セキュリティチーム
- 開発チーム
- インフラチーム
- プロダクトオーナー

**議題:**
1. CSP 違反レポート総括
2. 違反率確認（< 0.1%）
3. ビジネスインパクト評価
4. ロールバック計画確認
5. Enforce モード移行承認

### 移行判断チェックリスト

- [ ] 違反率 < 0.1%
- [ ] 正当な違反すべて対応済み
- [ ] ステージング環境で Enforce モード動作確認済み
- [ ] 本番環境デプロイ手順確認済み
- [ ] ロールバック手順確認済み
- [ ] 監視体制構築済み（24時間体制）
- [ ] セキュリティチーム承認取得
- [ ] プロダクトオーナー承認取得

### ステージング環境 Enforce モード検証

Enforce モードに切り替える前に、ステージング環境で検証します。

```bash
# 1. Enforce モード設定
ssh staging-server
cd /var/www/laravel-api
vim .env
# SECURITY_CSP_MODE=enforce に変更

# 2. デプロイ
php artisan config:cache
sudo systemctl restart php8.4-fpm

# 3. 動作確認
curl -I https://staging-api.example.com/api/health
# Content-Security-Policy ヘッダー（Report-Only なし）を確認

# 4. 手動テスト
# - 全主要機能が正常動作することを確認
# - ブラウザコンソールでCSPエラーが発生しないことを確認
# - 外部リソース（画像、スクリプト、スタイル）が正常読み込まれることを確認
```

**検証期間**: 最低4-6時間

---

## Phase 4: Enforce モード切り替え

### 段階的ロールアウト計画

| ステージ | トラフィック | 監視期間 | ロールバック閾値 |
|----------|-------------|----------|-----------------|
| **カナリア** | 10% | 4-6時間 | エラー率 > 0.5% |
| **Phase 1** | 25% | 6-12時間 | エラー率 > 0.3% |
| **Phase 2** | 50% | 12-24時間 | エラー率 > 0.2% |
| **Phase 3** | 100% | 継続監視 | エラー率 > 0.1% |

### カナリアデプロイ（10%）

#### 1. 環境変数設定

```bash
# 本番環境サーバー1台のみ Enforce モード設定
ssh prod-server-1
cd /var/www/laravel-api
vim .env
# SECURITY_CSP_MODE=enforce に変更
```

#### 2. デプロイ

```bash
php artisan config:cache
sudo systemctl restart php8.4-fpm

# Next.js アプリも同様
cd /var/www/user-app
pm2 restart user-app

cd /var/www/admin-app
pm2 restart admin-app
```

#### 3. トラフィック切り替え

```bash
# ロードバランサー設定（例: nginx upstream）
# prod-server-1 へのトラフィックを10%に制限

# または Application Load Balancer（AWS）でターゲットグループの重み付け調整
```

#### 4. 監視（4-6時間）

```bash
# エラーログ監視
ssh prod-server-1
tail -f /var/www/laravel-api/storage/logs/laravel.log | grep -i error

# CSP 違反監視
tail -f /var/www/laravel-api/storage/logs/security.log

# アプリケーションメトリクス監視
# - エラー率
# - レスポンスタイム
# - リクエスト成功率
```

**監視項目:**
- [ ] エラー率 < 0.5%
- [ ] レスポンスタイム増加 < 10ms
- [ ] ユーザーからの問い合わせなし
- [ ] CSP違反件数増加なし

### Phase 1デプロイ（25%）

カナリアデプロイが成功したら、25%のトラフィックに拡大します。

```bash
# 本番環境サーバー2-3台に Enforce モード展開
ssh prod-server-2
cd /var/www/laravel-api
vim .env
# SECURITY_CSP_MODE=enforce に変更
# デプロイ手順はカナリアと同じ
```

**監視期間**: 6-12時間

### Phase 2デプロイ（50%）

**監視期間**: 12-24時間

### Phase 3デプロイ（100%）

全サーバーに Enforce モードを展開します。

```bash
# 全本番環境サーバーに Enforce モード展開
ansible-playbook -i production deploy-csp-enforce.yml
```

**監視期間**: 継続監視

---

## Phase 5: Enforce モード展開後監視

### 24時間集中監視

展開後24時間は、以下の項目を1時間ごとに監視してください。

#### 監視項目

1. **エラー率**
   ```bash
   # Laravel ログからエラー率を計算
   grep -c "ERROR" /var/www/laravel-api/storage/logs/laravel.log
   ```

2. **CSP 違反件数**
   ```bash
   bash scripts/analyze-csp-violations.sh
   ```

3. **アプリケーションメトリクス**
   - エラー率: < 0.1%
   - レスポンスタイム: < 10ms増加
   - リクエスト成功率: > 99.9%

4. **ユーザーフィードバック**
   - サポート問い合わせ件数
   - ソーシャルメディア監視

#### ダッシュボード

以下の情報をリアルタイムで表示するダッシュボードを準備してください。

- CSP 違反件数（時系列）
- エラー率（時系列）
- レスポンスタイム（時系列）
- 違反ディレクティブTop 10
- ブロックされたURITop 10

### 緊急ロールバック手順

重大な問題が発生した場合、以下の手順で即座にロールバックしてください。

#### ロールバック判断基準

- エラー率 > 1%
- ユーザーからの苦情 > 10件/時間
- 主要機能が動作しない
- セキュリティインシデント発生

#### ロールバック手順

1. **Report-Only モードに戻す（緊急）**
   ```bash
   ssh prod-server
   cd /var/www/laravel-api
   vim .env
   # SECURITY_CSP_MODE=report-only に変更
   php artisan config:cache
   sudo systemctl restart php8.4-fpm
   ```

2. **CSP を完全無効化（最終手段）**
   ```bash
   vim .env
   # SECURITY_ENABLE_CSP=false に変更
   php artisan config:cache
   sudo systemctl restart php8.4-fpm
   ```

3. **インシデントレポート作成**
   - 発生時刻
   - 影響範囲
   - 原因調査
   - 再発防止策

### 継続監視（1週間）

Enforce モード展開後1週間は、以下の項目を毎日監視してください。

- [ ] CSP 違反件数（日次レポート）
- [ ] エラー率（日次レポート）
- [ ] ユーザーフィードバック
- [ ] セキュリティインシデント有無

---

## 📊 成功基準

### Phase 1-2 (Report-Only)

- [ ] CSP 違反レポート収集期間: 最低1週間
- [ ] 違反率: < 0.1%
- [ ] 正当な違反すべて対応済み

### Phase 3-4 (Enforce)

- [ ] ステージング環境で Enforce モード検証完了
- [ ] カナリアデプロイ成功（エラー率 < 0.5%）
- [ ] 段階的ロールアウト完了（100%展開）

### Phase 5 (監視)

- [ ] 24時間集中監視完了
- [ ] エラー率 < 0.1%
- [ ] ユーザーからの問い合わせなし
- [ ] セキュリティインシデントなし

---

## 📝 関連ドキュメント

- [セキュリティヘッダー実装ガイド](../SECURITY_HEADERS_IMPLEMENTATION_GUIDE.md)
- [セキュリティヘッダー運用マニュアル](SECURITY_HEADERS_OPERATION.md)
- [トラブルシューティングガイド](SECURITY_HEADERS_TROUBLESHOOTING.md)
- [CSP 違反分析スクリプト](../scripts/analyze-csp-violations.sh)

---

## ✅ 完了サイン

| Phase | 完了日時 | 担当者 | 承認者 | 備考 |
|-------|---------|--------|--------|------|
| Phase 1: Report-Only 有効化 | | | | |
| Phase 2: 違反レポート収集 | | | | |
| Phase 3: Enforce 移行判断 | | | | |
| Phase 4: Enforce 切り替え | | | | |
| Phase 5: 展開後監視 | | | | |
