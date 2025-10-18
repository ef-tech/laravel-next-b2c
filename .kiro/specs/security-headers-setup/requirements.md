# Requirements Document

## GitHub Issue Information

**Issue**: [#32](https://github.com/ef-tech/laravel-next-b2c/issues/32) - セキュリティヘッダー設定
**Labels**: なし
**Milestone**: なし
**Assignees**: なし

## Introduction

本要件は、Laravel 12 + Next.js 15 モノレポアプリケーションにおいて、OWASP セキュリティベストプラクティスに準拠した包括的なセキュリティヘッダーを実装することを目的としています。XSS、CSRF、クリックジャッキング、MIME タイプスニッフィングなどの攻撃からアプリケーションを保護し、段階的導入（Report-Only → Enforce）により既存機能への影響を最小化しながら、セキュアな Web アプリケーション環境を構築します。

**ビジネス価値**:
- アプリケーションの信頼性とセキュリティの向上
- OWASP 推奨のセキュリティ基準への準拠
- セキュリティインシデントリスクの大幅削減
- ユーザーデータ保護の強化

## Requirements

### Requirement 1: Laravel API セキュリティヘッダー実装

**Objective**: セキュリティエンジニアとして、Laravel API から返却されるすべてのレスポンスに OWASP 推奨のセキュリティヘッダーを自動付与できるようにしたい。これにより、API レベルでの攻撃ベクトルを遮断し、フロントエンドアプリケーションとの通信を保護できる。

#### Acceptance Criteria

1. WHEN Laravel API がレスポンスを返却する THEN SecurityHeaders ミドルウェア SHALL 以下の基本セキュリティヘッダーを付与する
   - `X-Frame-Options: DENY`（環境変数で SAMEORIGIN に変更可能）
   - `X-Content-Type-Options: nosniff`
   - `Referrer-Policy: strict-origin-when-cross-origin`（環境変数で変更可能）

2. WHEN 環境変数 `SECURITY_ENABLE_CSP` が `true` の場合 THEN SecurityHeaders ミドルウェア SHALL Content-Security-Policy ヘッダーを動的に構築して付与する

3. IF 環境変数 `SECURITY_CSP_MODE` が `report-only` の場合 THEN Laravel API SHALL `Content-Security-Policy-Report-Only` ヘッダーを使用する（監視モード）

4. IF 環境変数 `SECURITY_CSP_MODE` が `enforce` の場合 THEN Laravel API SHALL `Content-Security-Policy` ヘッダーを使用する（強制モード）

5. WHEN リクエストが HTTPS プロトコル経由 AND 環境変数 `SECURITY_FORCE_HSTS` が `true` の場合 THEN Laravel API SHALL `Strict-Transport-Security` ヘッダーを付与する
   - 値: `max-age=31536000; includeSubDomains; preload`

6. WHEN 環境変数 `SECURITY_ENABLE_CSP` が `false` の場合 THEN Laravel API SHALL CSP ヘッダーを付与しない

7. WHEN SecurityHeaders ミドルウェアが登録される THEN Laravel API SHALL CORS ミドルウェアの後、かつ他のミドルウェアの前に登録する（ヘッダー重複回避）

### Requirement 2: Content Security Policy (CSP) 動的構築

**Objective**: セキュリティエンジニアとして、環境変数駆動で CSP ポリシーを柔軟に設定できるようにしたい。これにより、開発環境と本番環境で異なるセキュリティレベルを適用し、XSS 攻撃を効果的に防止できる。

#### Acceptance Criteria

1. WHEN SecurityHeaders ミドルウェアが CSP ポリシーを構築する THEN Laravel API SHALL 以下のデフォルトディレクティブを含める
   - `default-src 'self'`
   - `object-src 'none'`
   - `frame-ancestors 'none'`
   - `upgrade-insecure-requests`（HTTPS 環境のみ）

2. WHEN 環境変数 `SECURITY_CSP_SCRIPT_SRC` が設定されている THEN Laravel API SHALL その値を `script-src` ディレクティブに使用する
   - デフォルト: `'self'`
   - 開発環境推奨: `'self' 'unsafe-eval'`（Next.js HMR 対応）

3. WHEN 環境変数 `SECURITY_CSP_STYLE_SRC` が設定されている THEN Laravel API SHALL その値を `style-src` ディレクティブに使用する
   - デフォルト: `'self' 'unsafe-inline'`

4. WHEN 環境変数 `SECURITY_CSP_IMG_SRC` が設定されている THEN Laravel API SHALL その値を `img-src` ディレクティブに使用する
   - デフォルト: `'self' data: https:`

5. WHEN 環境変数 `SECURITY_CSP_CONNECT_SRC` が設定されている THEN Laravel API SHALL その値を `connect-src` ディレクティブに使用する
   - デフォルト: `'self'`
   - 開発環境: CORS 許可オリジンと `ws:` `wss:` を含める

6. WHEN 環境変数 `SECURITY_CSP_FONT_SRC` が設定されている THEN Laravel API SHALL その値を `font-src` ディレクティブに使用する
   - デフォルト: `'self' data:`

7. WHEN 環境変数 `SECURITY_CSP_REPORT_URI` が設定されている AND CSP が有効 THEN Laravel API SHALL `report-uri` ディレクティブをポリシーに含める

### Requirement 3: CSP 違反レポート収集

**Objective**: セキュリティエンジニアとして、CSP 違反レポートを収集・記録できるようにしたい。これにより、Report-Only モード中に CSP ポリシーを調整し、Enforce モードへの安全な移行を実現できる。

#### Acceptance Criteria

1. WHEN ブラウザが CSP 違反を検出する THEN ブラウザ SHALL 設定された `report-uri` エンドポイントに違反レポートを POST する

2. WHEN Laravel API が `/api/csp/report` に POST リクエストを受信する THEN CspReportController SHALL リクエストボディから CSP 違反情報を抽出する

3. WHEN CspReportController が CSP 違反レポートを受信する THEN Laravel API SHALL `security` ログチャンネルに以下の情報を記録する
   - `blocked-uri`: ブロックされたリソース URI
   - `violated-directive`: 違反したディレクティブ
   - `source-file`: 違反が発生したソースファイル
   - `line-number`: 行番号
   - タイムスタンプ

4. WHEN CspReportController がレポート処理を完了する THEN Laravel API SHALL HTTP ステータス 204 (No Content) を返却する

5. WHEN `/api/csp/report` エンドポイントが登録される THEN Laravel API SHALL レート制限を除外する（大量レポート送信に対応）

### Requirement 4: Next.js セキュリティヘッダー実装（User App）

**Objective**: フロントエンド開発者として、User App から配信されるすべてのページに適切なセキュリティヘッダーを設定できるようにしたい。これにより、ユーザー向けアプリケーションのセキュリティを強化できる。

#### Acceptance Criteria

1. WHEN User App が任意のページをレンダリングする THEN Next.js SHALL `next.config.ts` の `headers()` メソッドで定義されたセキュリティヘッダーを付与する

2. WHEN User App がセキュリティヘッダーを付与する THEN Next.js SHALL 以下の基本ヘッダーを含める
   - `X-Frame-Options: SAMEORIGIN`
   - `X-Content-Type-Options: nosniff`
   - `Referrer-Policy: strict-origin-when-cross-origin`

3. WHEN User App が CSP ヘッダーを構築する THEN Next.js SHALL 開発環境と本番環境で異なるポリシーを適用する
   - 開発環境: `script-src 'self' 'unsafe-eval'`, `connect-src 'self' ws: wss: http://localhost:13000`
   - 本番環境: `script-src 'self'`, `connect-src 'self' https://api.example.com`

4. WHEN User App が Permissions-Policy ヘッダーを設定する THEN Next.js SHALL 以下の制限を含める
   - `geolocation=(self)`: 位置情報は自サイトのみ許可
   - `camera=()`: カメラアクセス禁止
   - `microphone=()`: マイクアクセス禁止
   - `payment=(self)`: 決済 API は自サイトのみ許可

5. IF 本番環境 AND HTTPS プロトコル THEN User App SHALL `Strict-Transport-Security` ヘッダーを付与する
   - 値: `max-age=31536000; includeSubDomains`

### Requirement 5: Next.js セキュリティヘッダー実装（Admin App）

**Objective**: フロントエンド開発者として、Admin App に User App よりも厳格なセキュリティヘッダーを設定できるようにしたい。これにより、管理画面の高度なセキュリティ保護を実現できる。

#### Acceptance Criteria

1. WHEN Admin App が任意のページをレンダリングする THEN Next.js SHALL User App よりも厳格なセキュリティヘッダーを付与する

2. WHEN Admin App がセキュリティヘッダーを付与する THEN Next.js SHALL 以下の基本ヘッダーを含める
   - `X-Frame-Options: DENY`（User App: SAMEORIGIN）
   - `X-Content-Type-Options: nosniff`
   - `Referrer-Policy: no-referrer`（User App: strict-origin-when-cross-origin）

3. WHEN Admin App が CSP ヘッダーを構築する THEN Next.js SHALL `script-src 'self'` のみを許可する（`unsafe-inline` と `unsafe-eval` を禁止）

4. WHEN Admin App が Permissions-Policy ヘッダーを設定する THEN Next.js SHALL すべてのブラウザ API を禁止する
   - 値: `geolocation=(), camera=(), microphone=(), payment=(), usb=(), bluetooth=()`

5. WHEN Admin App が追加のセキュリティヘッダーを付与する THEN Next.js SHALL 以下を含める
   - `X-Permitted-Cross-Domain-Policies: none`
   - `Cross-Origin-Embedder-Policy: require-corp`
   - `Cross-Origin-Opener-Policy: same-origin`

### Requirement 6: 共通セキュリティ設定モジュール

**Objective**: フロントエンド開発者として、User App と Admin App で共通のセキュリティ設定ロジックを再利用できるようにしたい。これにより、設定の一貫性を保ち、メンテナンス性を向上できる。

#### Acceptance Criteria

1. WHEN `frontend/security-config.ts` モジュールがインポートされる THEN モジュール SHALL `getSecurityConfig(isDev: boolean)` 関数をエクスポートする

2. WHEN `getSecurityConfig()` 関数が呼び出される THEN モジュール SHALL 開発環境と本番環境の判定に基づいてセキュリティ設定オブジェクトを返却する

3. WHEN `security-config.ts` モジュールが CSP ポリシーを生成する THEN モジュール SHALL `buildCSPString(config: CSPConfig)` 関数を提供する

4. WHEN `security-config.ts` モジュールが Permissions-Policy を生成する THEN モジュール SHALL `buildPermissionsPolicyString(config: PermissionsConfig)` 関数を提供する

5. WHEN `security-config.ts` モジュールが nonce を生成する THEN モジュール SHALL `generateNonce()` 関数を提供する（CSP nonce ベース認証用）

### Requirement 7: CSP レポート収集 API（Next.js 側）

**Objective**: フロントエンド開発者として、Next.js アプリケーション側でも CSP 違反レポートを収集できるようにしたい。これにより、フロントエンド固有の CSP 問題を早期に検出できる。

#### Acceptance Criteria

1. WHEN User App が CSP 違反レポートを受信する THEN `/api/csp-report` Route Handler SHALL リクエストボディから違反情報を抽出する

2. IF 開発環境 THEN User App SHALL CSP 違反レポートを `console.warn()` でブラウザコンソールに出力する

3. IF 本番環境 THEN User App SHALL CSP 違反レポートを外部監視サービス（Sentry/LogRocket 等）に転送する

4. WHEN Admin App が CSP 違反レポートを受信する THEN Admin App SHALL User App と同様の処理を実行する

5. WHEN CSP レポート API が処理を完了する THEN Next.js SHALL HTTP ステータス 204 (No Content) を返却する

### Requirement 8: 環境変数管理

**Objective**: DevOps エンジニアとして、環境変数を使用してセキュリティヘッダーを柔軟に設定できるようにしたい。これにより、開発・ステージング・本番環境で異なるセキュリティレベルを適用できる。

#### Acceptance Criteria

1. WHEN `.env.example` ファイルが更新される THEN ファイル SHALL 以下のセキュリティヘッダー関連の環境変数を含める
   - `SECURITY_X_FRAME_OPTIONS`: X-Frame-Options の値（デフォルト: DENY）
   - `SECURITY_REFERRER_POLICY`: Referrer-Policy の値（デフォルト: strict-origin-when-cross-origin）
   - `SECURITY_ENABLE_CSP`: CSP 有効化フラグ（デフォルト: true）
   - `SECURITY_CSP_MODE`: CSP モード（report-only または enforce）
   - `SECURITY_CSP_SCRIPT_SRC`: script-src ディレクティブ
   - `SECURITY_CSP_REPORT_URI`: CSP レポート送信先 URI
   - `SECURITY_FORCE_HSTS`: HSTS 強制フラグ（デフォルト: false、本番のみ true）
   - `SECURITY_CORS_ALLOWED_ORIGINS`: CORS 許可オリジン（カンマ区切り）

2. WHEN `.env.production.example` ファイルが作成される THEN ファイル SHALL 本番環境向けの厳格な設定例を含める
   - `SECURITY_X_FRAME_OPTIONS=DENY`
   - `SECURITY_REFERRER_POLICY=strict-origin`
   - `SECURITY_FORCE_HSTS=true`
   - `SECURITY_CSP_MODE=enforce`
   - `SECURITY_CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com`

3. WHEN 環境変数が設定されていない THEN Laravel API SHALL セキュアなデフォルト値を使用する

4. WHEN 環境変数が不正な値の場合 THEN Laravel API SHALL 起動時にバリデーションエラーをスローする

### Requirement 9: テスト実装（Laravel Pest）

**Objective**: QA エンジニアとして、Laravel API のセキュリティヘッダー実装を包括的にテストできるようにしたい。これにより、セキュリティヘッダーの正確性を保証できる。

#### Acceptance Criteria

1. WHEN Pest テストが実行される THEN `tests/Feature/SecurityHeadersTest.php` SHALL 基本セキュリティヘッダーの設定を検証する

2. WHEN CSP が有効な場合のテスト THEN Pest SHALL `Content-Security-Policy` または `Content-Security-Policy-Report-Only` ヘッダーの存在を検証する

3. WHEN HTTPS 環境のテスト THEN Pest SHALL `Strict-Transport-Security` ヘッダーの値を検証する

4. WHEN CORS 許可オリジンからのリクエストテスト THEN Pest SHALL API が正常にレスポンスを返却することを検証する

5. WHEN CORS 拒否オリジンからのリクエストテスト THEN Pest SHALL API が適切にリクエストをブロックすることを検証する

6. WHEN OPTIONS プリフライトリクエストテスト THEN Pest SHALL CORS ヘッダーが正しく付与されることを検証する

7. WHEN テストカバレッジを測定する THEN Pest SHALL SecurityHeaders ミドルウェアのカバレッジが 90% 以上であることを保証する

### Requirement 10: テスト実装（Next.js Jest）

**Objective**: QA エンジニアとして、Next.js フロントエンドのセキュリティ設定モジュールをテストできるようにしたい。これにより、フロントエンド側のセキュリティヘッダー実装の正確性を保証できる。

#### Acceptance Criteria

1. WHEN Jest テストが実行される THEN `frontend/*/src/__tests__/security.test.ts` SHALL `security-config.ts` モジュールの各関数をテストする

2. WHEN `getSecurityConfig()` 関数のテスト THEN Jest SHALL 開発環境と本番環境で異なる設定が返却されることを検証する

3. WHEN `buildCSPString()` 関数のテスト THEN Jest SHALL 正しい CSP ポリシー文字列が生成されることを検証する

4. WHEN `buildPermissionsPolicyString()` 関数のテスト THEN Jest SHALL 正しい Permissions-Policy 文字列が生成されることを検証する

5. WHEN `generateNonce()` 関数のテスト THEN Jest SHALL ランダムな nonce 値が生成されることを検証する

### Requirement 11: E2E テスト実装（Playwright）

**Objective**: QA エンジニアとして、ブラウザレベルでセキュリティヘッダーの動作を検証できるようにしたい。これにより、実際のユーザー環境でのセキュリティヘッダーの有効性を保証できる。

#### Acceptance Criteria

1. WHEN Playwright テストが実行される THEN `e2e/tests/security-headers.spec.ts` SHALL Laravel API のセキュリティヘッダーをブラウザ経由で検証する

2. WHEN User App のセキュリティヘッダーテスト THEN Playwright SHALL レスポンスヘッダーに必要なセキュリティヘッダーが含まれることを検証する

3. WHEN Admin App のセキュリティヘッダーテスト THEN Playwright SHALL User App よりも厳格なヘッダーが設定されていることを検証する

4. WHEN CSP 違反検出テスト THEN Playwright SHALL ブラウザコンソールで CSP 違反イベントを監視し、違反が発生しないことを検証する

5. WHEN CORS 許可オリジンテスト THEN Playwright SHALL User App から Laravel API への API 呼び出しが成功することを検証する

6. WHEN CORS 拒否オリジンテスト THEN Playwright SHALL 未許可オリジンからの API 呼び出しがブロックされることを検証する

### Requirement 12: CI/CD 統合

**Objective**: DevOps エンジニアとして、セキュリティヘッダーの自動検証を CI/CD パイプラインに統合できるようにしたい。これにより、セキュリティヘッダーの継続的な品質保証を実現できる。

#### Acceptance Criteria

1. WHEN `.github/workflows/security-headers.yml` ワークフローが追加される THEN GitHub Actions SHALL Pull Request 作成/更新時に自動実行する

2. WHEN セキュリティ関連ファイルが変更される THEN GitHub Actions SHALL 以下のファイルパスで自動トリガーする
   - `backend/laravel-api/app/Http/Middleware/SecurityHeaders.php`
   - `backend/laravel-api/config/security.php`
   - `frontend/*/next.config.ts`
   - `frontend/security-config.ts`

3. WHEN CI/CD ワークフローが実行される THEN GitHub Actions SHALL 以下のジョブを並列実行する
   - セキュリティヘッダー検証（Laravel API）
   - セキュリティヘッダー検証（Next.js User App）
   - セキュリティヘッダー検証（Next.js Admin App）
   - CSP ポリシー構文検証
   - CORS 設定整合性確認

4. WHEN `scripts/validate-security-headers.sh` スクリプトが実行される THEN スクリプト SHALL `curl -I` コマンドで各サービスのヘッダーを取得し、必須ヘッダーの存在を検証する

5. WHEN `scripts/validate-cors-config.sh` スクリプトが実行される THEN スクリプト SHALL CORS 許可オリジンリストの整合性を確認する

6. IF セキュリティヘッダー検証が失敗した場合 THEN GitHub Actions SHALL ワークフローを失敗させ、Pull Request をブロックする

### Requirement 13: パフォーマンス影響測定

**Objective**: DevOps エンジニアとして、セキュリティヘッダー追加によるパフォーマンス影響を測定できるようにしたい。これにより、セキュリティとパフォーマンスのバランスを最適化できる。

#### Acceptance Criteria

1. WHEN CI/CD パフォーマンステストが実行される THEN GitHub Actions SHALL セキュリティヘッダー追加前後のレスポンスタイムを比較する

2. WHEN レスポンスタイムを測定する THEN CI/CD SHALL Apache Bench (`ab`) または類似ツールを使用する
   - コマンド例: `ab -n 1000 -c 10 http://localhost:13000/api/health`

3. WHEN パフォーマンス影響を評価する THEN CI/CD SHALL レスポンスタイム増加が 5ms 以下であることを検証する

4. WHEN ヘッダーサイズを測定する THEN CI/CD SHALL レスポンスヘッダーサイズ増加が 1KB 以下であることを検証する

5. IF パフォーマンス基準を満たさない場合 THEN CI/CD SHALL 警告を表示するが、ワークフローは失敗させない（セキュリティ優先）

### Requirement 14: ドキュメント整備

**Objective**: テクニカルライターとして、セキュリティヘッダー実装の包括的なドキュメントを整備できるようにしたい。これにより、チーム全体がセキュリティヘッダーの設定・運用・トラブルシューティングを理解できる。

#### Acceptance Criteria

1. WHEN `SECURITY_HEADERS_IMPLEMENTATION_GUIDE.md` が更新される THEN ドキュメント SHALL 以下のセクションを含める
   - 実装手順詳細（Laravel/Next.js）
   - 環境変数設定ガイド
   - CSP ポリシーカスタマイズ方法
   - CORS 設定との統合

2. WHEN `docs/SECURITY_HEADERS_OPERATION.md` が作成される THEN ドキュメント SHALL 以下のセクションを含める
   - 運用マニュアル
   - Report-Only モード運用手順
   - Enforce モード切り替え手順
   - CSP 違反レポート分析方法

3. WHEN `docs/SECURITY_HEADERS_TROUBLESHOOTING.md` が作成される THEN ドキュメント SHALL 以下のセクションを含める
   - よくある問題と解決策
   - CSP 違反のデバッグ方法
   - CORS エラーのトラブルシューティング
   - パフォーマンス問題の診断

4. WHEN `README.md` が更新される THEN ドキュメント SHALL セキュリティヘッダーセクションを追加し、以下を含める
   - セキュリティヘッダー機能概要
   - クイックスタートガイド
   - 関連ドキュメントへのリンク

### Requirement 15: 段階的 CSP 導入

**Objective**: セキュリティエンジニアとして、CSP を段階的に導入できるようにしたい。これにより、既存機能への影響を最小化しながら、安全に CSP Enforce モードに移行できる。

#### Acceptance Criteria

1. WHEN Report-Only モードが有効化される THEN Laravel API SHALL 環境変数 `SECURITY_CSP_MODE=report-only` を設定する

2. WHEN Report-Only モード運用期間中 THEN セキュリティチーム SHALL 最低 1 週間 CSP 違反レポートを収集・分析する

3. WHEN CSP 違反レポートを分析する THEN セキュリティチーム SHALL 以下の対応を実施する
   - 正当な違反（外部リソース追加必要）の特定
   - 不正な違反（XSS 試行）の特定
   - CSP ポリシーの調整（必要なドメイン/ディレクティブ追加）

4. IF Report-Only モード期間中の違反率が 0.1% 以下 THEN セキュリティチーム SHALL Enforce モードへの切り替えを承認する

5. WHEN Enforce モードに切り替える THEN DevOps エンジニア SHALL 環境変数 `SECURITY_CSP_MODE=enforce` を設定し、本番環境にデプロイする

6. WHEN Enforce モード展開後 THEN 監視チーム SHALL 24 時間体制で CSP 違反とアプリケーション動作を監視する

7. IF Enforce モード展開後に重大な問題が発生した場合 THEN DevOps エンジニア SHALL 環境変数 `SECURITY_ENABLE_CSP=false` で緊急無効化する

## Extracted Information

### Technology Stack
**Backend**: Laravel 12, Pest, PHPUnit, PHPStan Level 8
**Frontend**: Next.js 15, React 19, TypeScript, Jest, Testing Library
**Infrastructure**: Docker, Docker Compose, GitHub Actions, PostgreSQL, Redis
**Tools**: Playwright, OWASP ZAP, Pint, Larastan, ESLint, Prettier

### Development Services Configuration
- **Laravel API**: ポート 13000, エンドポイント `/api/health`
- **User App**: ポート 13001
- **Admin App**: ポート 13002

### Project Structure
主要な実装ファイル:
```
backend/laravel-api/
├── app/Http/Middleware/SecurityHeaders.php        # Laravel セキュリティヘッダーミドルウェア
├── app/Http/Controllers/CspReportController.php   # CSP レポート収集コントローラー
├── config/security.php                            # セキュリティ設定ファイル
├── config/logging.php                             # セキュリティログチャンネル追加
├── routes/api.php                                 # CSP レポートルート追加
└── tests/Feature/SecurityHeadersTest.php          # Pest テスト

frontend/
├── security-config.ts                             # 共通セキュリティ設定モジュール
├── user-app/
│   ├── next.config.ts                            # User App ヘッダー設定
│   └── src/app/api/csp-report/route.ts          # CSP レポート API
├── admin-app/
│   ├── next.config.ts                            # Admin App 厳格ヘッダー設定
│   └── src/app/api/csp-report/route.ts          # CSP レポート API
└── */src/__tests__/security.test.ts              # Jest テスト

e2e/tests/security-headers.spec.ts                 # Playwright E2E テスト

.github/workflows/security-headers.yml             # GitHub Actions ワークフロー

scripts/
├── validate-security-headers.sh                   # セキュリティヘッダー検証スクリプト
└── validate-cors-config.sh                        # CORS 設定検証スクリプト

docs/
├── SECURITY_HEADERS_OPERATION.md                  # 運用マニュアル
└── SECURITY_HEADERS_TROUBLESHOOTING.md            # トラブルシューティング
```
