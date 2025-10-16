# 環境変数バリデーション機能ロールアウト計画

## 📋 概要

本ドキュメントでは、環境変数バリデーション機能の既存環境への段階的ロールアウト計画を定義します。

## 🎯 目的

- 既存環境への影響を最小化しながら、環境変数バリデーション機能を段階的に導入する
- 警告モードからエラーモードへの安全な移行を実現する
- チーム全体で統一されたロールアウトプロセスを確立する
- トラブル発生時の迅速な対応体制を整備する

---

## 1. ロールアウト戦略

### 1.1 3フェーズアプローチ

```
Phase 1: 警告モード導入（2週間）
  ↓
Phase 2: エラーモード段階的移行（1週間）
  ↓
Phase 3: 完全エラーモード運用
```

### 1.2 フェーズ詳細

| フェーズ | 期間 | モード | 説明 | ロールバック |
|---------|------|------|------|------------|
| **Phase 1** | Week 1-2 | Warning | バリデーションエラーを警告として表示、アプリは継続起動 | 容易 |
| **Phase 2** | Week 3 | Error (Staged) | 開発環境→ステージング環境の順にエラーモード適用 | 可能 |
| **Phase 3** | Week 4+ | Error (全環境) | 本番環境含む全環境でエラーモード適用 | 緊急時のみ |

---

## 2. Phase 1: 警告モード導入（Week 1-2）

### 2.1 実施内容

**Laravel設定**:
```bash
# backend/laravel-api/.env
ENV_VALIDATION_MODE=warning
```

**Next.js設定**:
```bash
# frontend/admin-app/.env.local, frontend/user-app/.env.local
# 警告モードはデフォルト動作（バリデーション失敗時に警告表示）
```

**CI/CD設定**:
```yaml
# .github/workflows/env-validation.yml
# 警告モードで実行（失敗してもワークフロー継続）
```

### 2.2 監視項目

- [ ] バリデーション警告ログの記録
- [ ] エラー発生頻度の測定
- [ ] エラー種別の分類（必須変数不足、型エラー、未知キー等）
- [ ] チームメンバーからのフィードバック収集

### 2.3 成功基準

- ✅ 全環境でアプリケーションが正常起動する
- ✅ バリデーション警告ログが収集されている
- ✅ チームメンバーが警告メッセージを理解できる
- ✅ 重大なエラー（アプリ停止）が発生していない

### 2.4 対応アクション

```bash
# 警告ログの確認（Laravel）
cd backend/laravel-api
php artisan log:show | grep "Environment validation warning"

# 警告ログの確認（Docker環境）
docker compose logs laravel.test | grep "validation warning"

# チーム全体への周知
- Slackで警告モード導入を通知
- ドキュメントへのリンクを共有
- 質問窓口の案内
```

---

## 3. Phase 2: エラーモード段階的移行（Week 3）

### 3.1 実施スケジュール

| Day | 環境 | 実施内容 | 責任者 | 確認事項 |
|-----|------|---------|-------|---------|
| Day 1-2 | 開発環境 | エラーモード適用 | DevOps | 起動確認、エラーログ確認 |
| Day 3-4 | ステージング環境 | エラーモード適用 | DevOps + QA | E2Eテスト実行、パフォーマンス測定 |
| Day 5 | 振り返り | エラー分析、改善実施 | 全チーム | エラー傾向分析、ドキュメント改善 |

### 3.2 開発環境（Day 1-2）

**Laravel設定**:
```bash
# backend/laravel-api/.env
ENV_VALIDATION_MODE=error
```

**確認手順**:
```bash
# 1. 環境変数バリデーション実行
php artisan env:validate

# 2. アプリケーション起動確認
php artisan serve

# 3. エラーログ確認
tail -f storage/logs/laravel.log
```

**想定されるエラーと対応**:

| エラー | 原因 | 対応方法 |
|--------|------|---------|
| APP_KEY不足 | 設定忘れ | `php artisan key:generate` |
| DB_PASSWORD不足 | `.env`未設定 | `.env.example`からコピーして設定 |
| CORS_ALLOWED_ORIGINS形式エラー | 不正なURL | 正しいURL形式に修正 |

### 3.3 ステージング環境（Day 3-4）

**デプロイ前チェック**:
```bash
# GitHub Secrets 確認
gh secret list

# 必須Secrets存在確認
# - LARAVEL_DB_PASSWORD
# - LARAVEL_APP_KEY
# - NEXT_PUBLIC_API_URL_STAGING
```

**デプロイ手順**:
```bash
# 1. ステージングブランチにマージ
git checkout staging
git merge main

# 2. 環境変数設定確認
# GitHub Settings → Environments → staging → Variables

# 3. デプロイ実行
git push origin staging

# 4. デプロイ成功確認
gh run list --branch staging --limit 1
gh run view <run-id> --log
```

**動作確認**:
```bash
# 1. アプリケーションアクセス確認
curl https://staging.example.com/api/health

# 2. ログ確認
gh run view <run-id> --log | grep "Environment validation"

# 3. E2Eテスト実行
npm run test:e2e:staging
```

### 3.4 振り返り（Day 5）

**エラー分析**:
```bash
# エラーログ集計
cat storage/logs/laravel.log | grep "validation failed" | wc -l

# エラー種別集計
cat storage/logs/laravel.log | grep "Missing required variable" | cut -d: -f2 | sort | uniq -c
```

**改善アクション**:
- [ ] エラーメッセージの改善
- [ ] ドキュメントの追加・修正
- [ ] よくあるエラーのFAQ追加
- [ ] チーム向け勉強会の実施

---

## 4. Phase 3: 本番環境エラーモード適用（Week 4+）

### 4.1 実施前提条件

Phase 3に進むための必須条件:

- ✅ Phase 1で2週間の警告モード運用が完了
- ✅ Phase 2でステージング環境でのエラーモード運用が成功
- ✅ チーム全体の承認取得（最低2名のレビュー承認）
- ✅ ロールバック手順の確認完了
- ✅ 緊急連絡体制の整備完了

### 4.2 本番環境デプロイ

**デプロイ推奨時間帯**:
- 平日 10:00-16:00（業務時間内、サポート体制が整っている時間）
- 避けるべき時間: 金曜日、祝日前日、月末月初

**デプロイ手順**:
```bash
# 1. 本番環境Secrets確認
gh secret list --env production

# 2. 環境変数設定確認
# GitHub Settings → Environments → production → Variables
# - ENV_VALIDATION_MODE=error を設定

# 3. デプロイ実行
git checkout main
git pull origin main
git push origin main

# 4. デプロイ監視
gh run watch <run-id>
```

**監視項目**:
```bash
# リアルタイムログ監視
gh run view <run-id> --log --exit-status

# アプリケーションヘルスチェック
watch -n 5 'curl -s https://api.example.com/health | jq'

# エラーログ監視
gh run view <run-id> --log | grep -i "error\|failed\|exception"
```

### 4.3 デプロイ後確認

**即時確認（5分以内）**:
- [ ] アプリケーションが起動している
- [ ] ヘルスチェックエンドポイントが正常応答
- [ ] エラーログにCriticalエラーがない
- [ ] 主要機能が正常動作している

**短期確認（1時間以内）**:
- [ ] 全APIエンドポイントが正常応答
- [ ] フロントエンドアプリが正常表示
- [ ] ユーザー認証フローが正常動作
- [ ] データベース接続が正常

**中期確認（24時間以内）**:
- [ ] パフォーマンス指標が正常範囲内
- [ ] エラー率が通常範囲内
- [ ] ユーザーからの問い合わせがない
- [ ] ログに異常なパターンがない

---

## 5. ロールバック手順

### 5.1 緊急ロールバックトリガー

以下の状況でロールバックを検討:

🚨 **即時ロールバック**:
- アプリケーションが起動しない
- Critical エラーが発生している
- 主要機能が動作しない
- ユーザーへの影響が甚大

⚠️ **計画的ロールバック**:
- エラーログが大量発生
- パフォーマンス劣化が確認される
- チームメンバーが対応困難と判断

### 5.2 ロールバック手順（Laravel）

```bash
# 方法1: 警告モードに戻す（推奨）
# backend/laravel-api/.env
ENV_VALIDATION_MODE=warning

# 方法2: バリデーション完全スキップ（緊急時のみ）
# backend/laravel-api/.env
ENV_VALIDATION_SKIP=true

# 方法3: 以前のコミットに戻す
git revert <commit-id>
git push origin main
```

### 5.3 ロールバック手順（Next.js）

```bash
# Next.jsはビルド時バリデーションのため、.envファイル修正後に再ビルド必要

# GitHub Actions で再デプロイ
gh workflow run deploy-production.yml

# または以前のコミットに戻す
git revert <commit-id>
git push origin main
```

### 5.4 ロールバック後の対応

```bash
# 1. インシデント記録
# - 発生日時
# - 影響範囲
# - 根本原因
# - 対応内容

# 2. ポストモーテム実施
# - 原因分析
# - 再発防止策
# - ドキュメント改善

# 3. 再ロールアウト計画
# - 修正内容の確認
# - テスト計画の見直し
# - 段階的ロールアウトの再実施
```

---

## 6. リスク管理

### 6.1 リスク評価マトリクス

| リスク | 発生確率 | 影響度 | 軽減策 | 責任者 |
|--------|---------|-------|-------|-------|
| 既存環境でバリデーションエラー | 中 | 中 | 警告モード2週間運用 | DevOps |
| CI/CDビルド失敗 | 低 | 高 | 段階的ロールアウト、スキップフラグ | DevOps |
| パフォーマンス劣化 | 低 | 中 | ベンチマーク測定、最適化 | DevOps |
| チーム混乱 | 中 | 低 | ドキュメント整備、勉強会実施 | Tech Lead |

### 6.2 軽減策詳細

**警告モード2週間運用**:
- 目的: 既存環境のバリデーションエラーを事前検出
- 方法: ログ収集、エラー分析、修正実施
- 成功基準: エラー発生率が閾値以下（例: 5%未満）

**段階的ロールアウト**:
- 目的: 本番環境への影響を最小化
- 方法: 開発環境→ステージング環境→本番環境の順に適用
- 各フェーズで24-48時間の監視期間を設ける

**スキップフラグ提供**:
- 目的: 緊急時にバリデーションを無効化
- 方法: `ENV_VALIDATION_SKIP=true` 設定
- 使用条件: Critical障害発生時のみ、承認者の許可必要

---

## 7. チームコミュニケーション

### 7.1 コミュニケーションプラン

| タイミング | 対象 | チャネル | 内容 |
|----------|------|---------|------|
| Phase 1開始前 | 全チーム | Slack, Email | 警告モード導入の通知、ドキュメントリンク |
| Phase 1終了後 | 全チーム | Slack, MTG | エラー分析結果共有、Phase 2予定通知 |
| Phase 2開始前 | DevOps, QA | Slack, MTG | エラーモード移行手順確認 |
| Phase 2終了後 | 全チーム | Slack, Email | ステージング環境結果共有 |
| Phase 3開始前 | 経営層, 全チーム | Email, MTG | 本番デプロイ承認、リスク説明 |
| Phase 3開始後 | 全チーム | Slack, Email | 本番デプロイ成功通知 |

### 7.2 問い合わせ窓口

**技術的な質問**:
- Slack: #env-validation-support
- Email: devops@example.com
- 対応時間: 平日 9:00-18:00

**緊急対応**:
- Slack: #incidents
- 電話: DevOpsオンコール担当
- 対応時間: 24/7

### 7.3 ドキュメントリンク

必須ドキュメント:
- [環境変数管理 - README.md](../README.md#環境変数管理)
- [GitHub Actions Secrets設定ガイド](./GITHUB_ACTIONS_SECRETS_GUIDE.md)
- [環境変数セキュリティガイド](./ENVIRONMENT_VARIABLE_SECURITY_GUIDE.md)
- [CI/CD動作確認ガイド](./CI_CD_VALIDATION_GUIDE.md)
- [マイグレーションガイド](#8-マイグレーションガイド)

---

## 8. マイグレーションガイド

### 8.1 既存環境での移行手順

#### ステップ1: 環境変数テンプレート確認

```bash
# .env.example の最新版を確認
git pull origin main

# 差分確認
npm run env:check

# 差分があれば同期
npm run env:sync
```

#### ステップ2: バリデーション実行

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
```

#### ステップ3: エラー修正

エラーが発生した場合:

```bash
# エラー例1: APP_KEY不足
RuntimeException: Missing required variable: APP_KEY

# 対応方法:
php artisan key:generate

# エラー例2: DB_PASSWORD不足
RuntimeException: Missing required variable: DB_PASSWORD

# 対応方法:
# backend/laravel-api/.env に追加
DB_PASSWORD=your-database-password

# エラー例3: NEXT_PUBLIC_API_URL形式エラー
ZodError: NEXT_PUBLIC_API_URL: Invalid url

# 対応方法:
# frontend/admin-app/.env.local に正しいURL設定
NEXT_PUBLIC_API_URL=http://localhost:13000
```

#### ステップ4: 動作確認

```bash
# Laravel起動確認
php artisan serve

# Next.js起動確認（Admin App）
cd frontend/admin-app
npm run dev

# Next.js起動確認（User App）
cd frontend/user-app
npm run dev
```

### 8.2 警告モードでの運用手順

**設定方法**:
```bash
# backend/laravel-api/.env
ENV_VALIDATION_MODE=warning
```

**ログ確認**:
```bash
# 警告ログ確認
tail -f storage/logs/laravel.log | grep "Environment validation warning"

# 警告ログ集計
grep "validation warning" storage/logs/laravel.log | wc -l
```

**警告への対応**:
```bash
# 1. 警告内容を確認
grep "validation warning" storage/logs/laravel.log | tail -10

# 2. エラー種別を特定
# - 必須変数不足
# - 型エラー
# - 未知キー

# 3. 修正実施
# .env ファイルを編集

# 4. 再バリデーション
php artisan env:validate
```

### 8.3 エラーモードへの切り替え手順

**前提条件**:
- 警告モードで2週間以上運用済み
- 警告ログが0件または許容範囲内
- チームメンバー全員が対応方法を理解している

**切り替え手順**:
```bash
# 1. 最終バリデーション確認
php artisan env:validate
npm run env:check

# 2. エラーモード設定
# backend/laravel-api/.env
ENV_VALIDATION_MODE=error

# 3. アプリケーション再起動
php artisan serve

# 4. 動作確認
curl http://localhost:8000/api/health

# 5. ログ確認
tail -f storage/logs/laravel.log
```

**切り替え後の監視**:
```bash
# 1時間ごとにエラーログ確認
watch -n 3600 'tail -n 50 storage/logs/laravel.log | grep -i "error\|exception"'

# パフォーマンス監視
# アプリケーション起動時間の測定
time php artisan serve
```

### 8.4 よくある移行エラーと解決方法

#### エラー1: 条件付き必須変数の不足

```bash
エラーメッセージ:
RuntimeException: Missing required variable: DB_HOST (required when DB_CONNECTION=pgsql)

原因:
DB_CONNECTION=pgsql が設定されているが、DB_HOST が未設定

解決方法:
# backend/laravel-api/.env
DB_HOST=localhost
DB_PORT=5432
```

#### エラー2: 型エラー

```bash
エラーメッセージ:
RuntimeException: Invalid value for APP_DEBUG: must be true or false

原因:
APP_DEBUG に true/false 以外の値が設定されている

解決方法:
# backend/laravel-api/.env
APP_DEBUG=true  # または false
```

#### エラー3: Next.js環境変数プレフィックスエラー

```bash
エラーメッセージ:
Error: Environment variable API_URL is not defined

原因:
クライアント側で参照する環境変数に NEXT_PUBLIC_ プレフィックスがない

解決方法:
# frontend/admin-app/.env.local
NEXT_PUBLIC_API_URL=http://localhost:13000
```

#### エラー4: 環境変数同期エラー

```bash
エラーメッセージ:
Warning: Environment files are out of sync
Missing keys in .env: NEW_VARIABLE

原因:
.env.example に追加された新規変数が .env に存在しない

解決方法:
# 自動同期
npm run env:sync

# または手動で .env に追加
echo "NEW_VARIABLE=default-value" >> .env
```

### 8.5 ロールバック手順（詳細）

#### 緊急時のロールバック

```bash
# 方法1: 警告モードに戻す（推奨）
# backend/laravel-api/.env
ENV_VALIDATION_MODE=warning

# アプリケーション再起動
php artisan serve

# 確認
curl http://localhost:8000/api/health
```

```bash
# 方法2: バリデーション完全スキップ（最終手段）
# backend/laravel-api/.env
ENV_VALIDATION_SKIP=true

# アプリケーション再起動
php artisan serve

# 警告: この設定は一時的な措置として使用し、根本原因を修正後に ENV_VALIDATION_SKIP=false に戻すこと
```

```bash
# 方法3: 以前のバージョンに戻す
git log --oneline | head -10
git revert <commit-id>
git push origin main

# CI/CDで自動デプロイ
gh run watch <run-id>
```

#### ロールバック後の確認

```bash
# 1. アプリケーション起動確認
curl http://localhost:8000/api/health

# 2. ログ確認
tail -f storage/logs/laravel.log

# 3. 主要機能確認
# - ユーザー認証
# - API呼び出し
# - データベース接続

# 4. チームへの通知
# Slack: #incidents チャネルで状況共有
```

---

## 9. 成功基準

### 9.1 Phase 1成功基準

- ✅ 警告モードで2週間運用完了
- ✅ バリデーション警告ログが収集されている
- ✅ チームメンバーがエラー内容を理解している
- ✅ 重大な障害が発生していない

### 9.2 Phase 2成功基準

- ✅ 開発環境・ステージング環境でエラーモード運用成功
- ✅ エラーログが閾値以下（例: 1日あたり5件未満）
- ✅ E2Eテストが全てパス
- ✅ パフォーマンス劣化が許容範囲内（起動時間+15秒以内）

### 9.3 Phase 3成功基準

- ✅ 本番環境でエラーモード運用成功
- ✅ ユーザーへの影響なし
- ✅ エラーログが閾値以下
- ✅ チームメンバーが自立して対応可能

---

## 10. ポストロールアウト

### 10.1 振り返りMTG

**実施タイミング**: Phase 3完了後1週間以内

**参加者**:
- DevOps Team
- Development Team
- QA Team
- Product Manager

**アジェンダ**:
1. ロールアウトプロセスの振り返り
2. 発生したエラーの分析
3. チームフィードバックの共有
4. ドキュメント改善点の洗い出し
5. 今後の運用方針の確認

### 10.2 ドキュメント更新

- [ ] README.md の更新
- [ ] トラブルシューティングガイドの充実化
- [ ] FAQセクションの追加
- [ ] ベストプラクティスの文書化

### 10.3 継続的改善

- [ ] エラーメッセージの改善
- [ ] バリデーションルールの見直し
- [ ] パフォーマンス最適化
- [ ] 新規環境変数の追加フロー確立

---

## 11. 関連ドキュメント

- [環境変数管理 - README.md](../README.md#環境変数管理)
- [GitHub Actions Secrets設定ガイド](./GITHUB_ACTIONS_SECRETS_GUIDE.md)
- [環境変数セキュリティガイド](./ENVIRONMENT_VARIABLE_SECURITY_GUIDE.md)
- [CI/CD動作確認ガイド](./CI_CD_VALIDATION_GUIDE.md)
- [Laravel環境変数バリデーション実装](../backend/laravel-api/app/Support/Validation/EnvValidator.php)
- [Next.js環境変数バリデーション実装](../frontend/admin-app/lib/validation/env-validator.ts)

---

## 12. 承認

本ロールアウト計画は以下の承認を必要とします:

| 役割 | 承認者 | 承認日 | 署名 |
|------|-------|--------|------|
| Tech Lead | - | - | - |
| DevOps Lead | - | - | - |
| Product Manager | - | - | - |

---

**最終更新**: 2025-10-15
**バージョン**: 1.0.0
**次回レビュー**: Phase 1完了後
**メンテナー**: DevOps Team
