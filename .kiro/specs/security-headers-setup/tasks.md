# 実装タスク: セキュリティヘッダー設定

## 1. Laravel API セキュリティヘッダー基盤実装

- [x] 1.1 SecurityHeaders ミドルウェア作成
  - SecurityHeaders ミドルウェアクラスを作成する
  - handle() メソッドで基本セキュリティヘッダー（X-Frame-Options, X-Content-Type-Options, Referrer-Policy）を付与する機能を実装する
  - レスポンスオブジェクトにヘッダーを追加し、次のミドルウェアに処理を委譲する
  - _Requirements: 1.1, 1.2_

- [x] 1.2 セキュリティ設定ファイル作成
  - config/security.php を作成する
  - 環境変数から基本セキュリティヘッダー設定を読み込む機能を実装する
  - デフォルト値を設定し、環境変数が未設定の場合に安全な値を使用する
  - _Requirements: 1.1, 1.2_

- [x] 1.3 ミドルウェアチェーンへの登録
  - bootstrap/app.php で SecurityHeaders ミドルウェアをミドルウェアスタックに登録する
  - CORS ミドルウェアの後に配置し、ヘッダー重複を回避する
  - _Requirements: 1.7_

## 2. Content Security Policy (CSP) 動的構築機能

- [x] 2.1 CSP ポリシー構築機能実装
  - SecurityHeaders ミドルウェアに buildCspPolicy() プライベートメソッドを追加する
  - config/security.php から CSP ディレクティブ設定を読み込む機能を実装する
  - 環境変数駆動で各ディレクティブ（script-src, style-src, img-src, connect-src, font-src）を動的に構築する
  - デフォルトディレクティブ（default-src, object-src, frame-ancestors, upgrade-insecure-requests）を含める
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

- [x] 2.2 CSP モード切り替え機能実装
  - 環境変数 SECURITY_CSP_MODE の値に基づいて、Report-Only または Enforce モードを選択する機能を実装する
  - Report-Only モードの場合は Content-Security-Policy-Report-Only ヘッダーを使用する
  - Enforce モードの場合は Content-Security-Policy ヘッダーを使用する
  - CSP が無効（SECURITY_ENABLE_CSP=false）の場合は CSP ヘッダーを付与しない
  - _Requirements: 1.3, 1.4, 1.6_

- [x] 2.3 CSP レポート URI 設定機能
  - 環境変数 SECURITY_CSP_REPORT_URI が設定されている場合、report-uri ディレクティブを CSP ポリシーに含める機能を実装する
  - デフォルト値として /api/csp/report を使用する
  - _Requirements: 2.7_

## 3. HTTPS セキュリティ強化機能

- [x] 3.1 HSTS ヘッダー付与機能実装
  - リクエストが HTTPS プロトコル経由であることを検証する機能を実装する
  - 環境変数 SECURITY_FORCE_HSTS が true の場合、Strict-Transport-Security ヘッダーを付与する
  - HSTS ヘッダーの値（max-age, includeSubDomains, preload）を環境変数から読み込む
  - _Requirements: 1.5_

## 4. CSP 違反レポート収集機能

- [x] 4.1 CspReportController 作成
  - CspReportController を作成する
  - store() メソッドでブラウザからの CSP 違反レポートを受信する機能を実装する
  - リクエストボディから CSP 違反情報（blocked-uri, violated-directive, document-uri, source-file, line-number）を抽出する
  - _Requirements: 3.2_

- [x] 4.2 CSP 違反ログ記録機能実装
  - security ログチャンネルに CSP 違反情報を記録する機能を実装する
  - ログに含める情報（blocked-uri, violated-directive, source-file, line-number, タイムスタンプ）を設定する
  - ログ記録後、HTTP ステータス 204 (No Content) を返却する
  - _Requirements: 3.3, 3.4_

- [x] 4.3 セキュリティログチャンネル設定
  - config/logging.php に security ログチャンネルを追加する
  - ログファイルのパスを storage/logs/security.log に設定する
  - ログレベルを warning に設定する
  - _Requirements: 3.3_

- [x] 4.4 CSP レポートルート登録
  - routes/api.php に POST /api/csp/report ルートを追加する
  - CspReportController の store() メソッドにマッピングする
  - レート制限から除外する設定を追加する
  - _Requirements: 3.1, 3.5_

## 5. フロントエンド共通セキュリティ設定モジュール

- [x] 5.1 共通セキュリティ設定モジュール作成
  - frontend/security-config.ts を作成する
  - CSPConfig, PermissionsPolicyConfig, SecurityConfig インターフェースを定義する
  - getSecurityConfig() 関数を実装し、開発環境と本番環境で異なる設定を返却する
  - _Requirements: 6.1, 6.2_

- [x] 5.2 CSP ポリシー文字列構築機能実装
  - buildCSPString() 関数を実装する
  - CSPConfig オブジェクトから CSP ポリシー文字列を構築する
  - 各ディレクティブをセミコロン区切りで連結する
  - _Requirements: 6.3_

- [x] 5.3 Permissions-Policy 文字列構築機能実装
  - buildPermissionsPolicyString() 関数を実装する
  - PermissionsPolicyConfig オブジェクトから Permissions-Policy 文字列を構築する
  - 各ポリシーをカンマ区切りで連結する
  - _Requirements: 6.4_

- [x] 5.4 Nonce 生成機能実装
  - generateNonce() 関数を実装する
  - ランダムな nonce 値を生成し、Base64 エンコードして返却する
  - _Requirements: 6.5_

## 6. Next.js User App セキュリティヘッダー実装

- [x] 6.1 User App next.config.ts ヘッダー設定
  - frontend/user-app/next.config.ts を更新する
  - security-config.ts から getSecurityConfig() をインポートする
  - headers() 関数で基本セキュリティヘッダー（X-Frame-Options: SAMEORIGIN, X-Content-Type-Options: nosniff, Referrer-Policy: strict-origin-when-cross-origin）を設定する
  - _Requirements: 4.1, 4.2_

- [x] 6.2 User App CSP ヘッダー設定
  - buildCSPString() を使用して CSP ポリシー文字列を構築する
  - 開発環境では script-src に 'unsafe-eval' を含め、connect-src に ws: wss: http://localhost:13000 を含める
  - 本番環境では厳格なポリシー（script-src 'self', connect-src 'self' https://api.example.com）を使用する
  - _Requirements: 4.3_

- [x] 6.3 User App Permissions-Policy 設定
  - buildPermissionsPolicyString() を使用して Permissions-Policy 文字列を構築する
  - geolocation=(self), camera=(), microphone=(), payment=(self) を設定する
  - _Requirements: 4.4_

- [x] 6.4 User App HSTS 設定
  - 本番環境かつ HTTPS プロトコルの場合、Strict-Transport-Security ヘッダーを付与する
  - max-age=31536000, includeSubDomains を設定する
  - _Requirements: 4.5_

## 7. Next.js Admin App セキュリティヘッダー実装

- [x] 7.1 Admin App next.config.ts ヘッダー設定
  - frontend/admin-app/next.config.ts を更新する
  - User App よりも厳格な基本セキュリティヘッダー（X-Frame-Options: DENY, Referrer-Policy: no-referrer）を設定する
  - _Requirements: 5.1, 5.2_

- [x] 7.2 Admin App 厳格 CSP ヘッダー設定
  - script-src 'self' のみを許可し、'unsafe-inline' と 'unsafe-eval' を禁止する
  - その他のディレクティブも User App より厳格に設定する
  - _Requirements: 5.3_

- [x] 7.3 Admin App 厳格 Permissions-Policy 設定
  - すべてのブラウザ API を禁止する（geolocation=(), camera=(), microphone=(), payment=(), usb=(), bluetooth=()）
  - _Requirements: 5.4_

- [x] 7.4 Admin App 追加セキュリティヘッダー設定
  - X-Permitted-Cross-Domain-Policies: none を追加する
  - Cross-Origin-Embedder-Policy: require-corp を追加する
  - Cross-Origin-Opener-Policy: same-origin を追加する
  - _Requirements: 5.5_

## 8. Next.js CSP レポート収集 API 実装

- [x] 8.1 User App CSP レポート API 作成
  - frontend/user-app/src/app/api/csp-report/route.ts を作成する
  - POST メソッドで CSP 違反レポートを受信する
  - 開発環境では console.warn() でブラウザコンソールに出力する
  - HTTP 204 (No Content) を返却する
  - _Requirements: 7.1, 7.2, 7.5_

- [x] 8.2 Admin App CSP レポート API 作成
  - frontend/admin-app/src/app/api/csp-report/route.ts を作成する
  - User App と同様の処理を実装する
  - _Requirements: 7.4, 7.5_

- [ ] 8.3 本番環境外部監視サービス統合準備
  - 本番環境では CSP 違反レポートを外部監視サービス（Sentry/LogRocket 等）に転送する処理の準備をする
  - 環境変数による外部サービス URL 設定を追加する
  - _Requirements: 7.3_

## 9. 環境変数設定と検証

- [x] 9.1 .env.example 更新
  - .env.example にセキュリティヘッダー関連の環境変数を追加する
  - SECURITY_X_FRAME_OPTIONS, SECURITY_REFERRER_POLICY, SECURITY_ENABLE_CSP, SECURITY_CSP_MODE, SECURITY_CSP_SCRIPT_SRC, SECURITY_CSP_REPORT_URI, SECURITY_FORCE_HSTS, SECURITY_CORS_ALLOWED_ORIGINS の設定例を追加する
  - 各環境変数にコメントでデフォルト値と説明を追記する
  - _Requirements: 8.1_

- [x] 9.2 .env.production.example 作成
  - .env.production.example を作成する
  - 本番環境向けの厳格な設定例を含める（SECURITY_X_FRAME_OPTIONS=DENY, SECURITY_CSP_MODE=enforce, SECURITY_FORCE_HSTS=true）
  - _Requirements: 8.2_

- [ ] 9.3 環境変数バリデーション実装
  - 環境変数が不正な値の場合、起動時にバリデーションエラーをスローする機能を実装する
  - SECURITY_CSP_MODE が report-only または enforce 以外の値の場合、警告ログを記録しデフォルト値を使用する
  - _Requirements: 8.3, 8.4_

## 10. テスト実装（Laravel Pest）

- [x] 10.1 SecurityHeaders ミドルウェアテスト作成
  - tests/Feature/SecurityHeadersTest.php を作成する
  - 基本セキュリティヘッダー（X-Frame-Options, X-Content-Type-Options, Referrer-Policy）が設定されることを検証する
  - _Requirements: 9.1_

- [x] 10.2 CSP ヘッダーテスト実装
  - CSP が有効な場合、Content-Security-Policy または Content-Security-Policy-Report-Only ヘッダーが存在することを検証する
  - Report-Only モードと Enforce モードでヘッダー名が切り替わることを検証する
  - _Requirements: 9.2_

- [x] 10.3 HSTS ヘッダーテスト実装
  - HTTPS 環境かつ SECURITY_FORCE_HSTS=true の場合、Strict-Transport-Security ヘッダーが設定されることを検証する
  - HTTP 環境では HSTS ヘッダーが設定されないことを検証する
  - _Requirements: 9.3_

- [x] 10.4 CORS 統合テスト実装
  - CORS 許可オリジンからのリクエストで API が正常にレスポンスを返却することを検証する
  - CORS 拒否オリジンからのリクエストで API が適切にブロックすることを検証する
  - OPTIONS プリフライトリクエストで CORS ヘッダーが正しく付与されることを検証する
  - _Requirements: 9.4, 9.5, 9.6_

- [x] 10.5 CspReportController テスト実装
  - 有効な CSP レポートが送信された場合、HTTP 204 を返却することを検証する
  - CSP 違反情報が security ログチャンネルに記録されることを検証する
  - _Requirements: 9.1_

- [x] 10.6 テストカバレッジ確認
  - SecurityHeaders ミドルウェアのカバレッジが 90% 以上であることを確認する
  - _Requirements: 9.7_

## 11. テスト実装（Next.js Jest）

- [x] 11.1 security-config.ts テスト作成
  - frontend/*/src/__tests__/security.test.ts を作成する
  - getSecurityConfig() 関数が開発環境と本番環境で異なる設定を返却することを検証する
  - _Requirements: 10.1, 10.2_

- [x] 11.2 CSP 文字列構築テスト実装
  - buildCSPString() 関数が正しい CSP ポリシー文字列を生成することを検証する
  - 各ディレクティブがセミコロン区切りで連結されることを確認する
  - _Requirements: 10.3_

- [x] 11.3 Permissions-Policy 文字列構築テスト実装
  - buildPermissionsPolicyString() 関数が正しい文字列を生成することを検証する
  - 各ポリシーがカンマ区切りで連結されることを確認する
  - _Requirements: 10.4_

- [x] 11.4 Nonce 生成テスト実装
  - generateNonce() 関数がランダムな nonce 値を生成することを検証する
  - 異なる呼び出しで異なる値が生成されることを確認する
  - Base64 形式であることを検証する
  - _Requirements: 10.5_

## 12. E2E テスト実装（Playwright）

- [x] 12.1 E2E セキュリティヘッダーテストファイル作成
  - e2e/tests/security-headers.spec.ts を作成する
  - Laravel API のセキュリティヘッダーをブラウザ経由で検証するテストを実装する
  - playwright.config.ts に api-chromium プロジェクトを追加する
  - 15個のE2Eテストを実装（Laravel API 6テスト、User App 3テスト、Admin App 4テスト、CSP違反検出 2テスト）
  - TDD REDフェーズ完了: 9テスト失敗、6テスト成功
  - _Requirements: 11.1_

- [x] 12.2 User App セキュリティヘッダー検証テスト実装
  - User App のトップページにアクセスし、レスポンスヘッダーに必要なセキュリティヘッダーが含まれることを検証する
  - X-Frame-Options が SAMEORIGIN であることを確認する
  - TDD GREENフェーズ完了: User App 3テスト全通過
  - _Requirements: 11.2_

- [x] 12.3 Admin App セキュリティヘッダー検証テスト実装
  - Admin App のトップページにアクセスし、User App よりも厳格なヘッダーが設定されていることを検証する
  - X-Frame-Options が DENY であることを確認する
  - TDD GREENフェーズ完了: Admin App 4テスト全通過
  - _Requirements: 11.3_

- [x] 12.4 CSP 違反検出テスト実装
  - ブラウザコンソールで CSP 違反イベントを監視する
  - 正常なページアクセスで CSP 違反が発生しないことを検証する
  - TDD GREENフェーズ完了: CSP違反検出 2テスト全通過
  - _Requirements: 11.4_

- [x] 12.5 CORS 統合テスト実装
  - User App から Laravel API への API 呼び出しが成功することを検証する
  - 未許可オリジンからの API 呼び出しがブロックされることを検証する
  - TDD RED-GREEN-REFACTORサイクル完了: 全17テスト通過
  - CORS_SUPPORTS_CREDENTIALS=true 設定完了（.env, .env.example更新）
  - _Requirements: 11.5, 11.6_

## 13. CI/CD 統合

- [ ] 13.1 セキュリティヘッダー検証ワークフロー作成
  - .github/workflows/security-headers.yml を作成する
  - Pull Request 作成/更新時に自動実行するトリガーを設定する
  - セキュリティ関連ファイル（SecurityHeaders.php, config/security.php, next.config.ts, security-config.ts）変更時に自動トリガーするパス設定を追加する
  - _Requirements: 12.1, 12.2_

- [ ] 13.2 並列ジョブ実装
  - Laravel API セキュリティヘッダー検証ジョブを実装する
  - Next.js User App セキュリティヘッダー検証ジョブを実装する
  - Next.js Admin App セキュリティヘッダー検証ジョブを実装する
  - CSP ポリシー構文検証ジョブを実装する
  - CORS 設定整合性確認ジョブを実装する
  - _Requirements: 12.3_

- [ ] 13.3 セキュリティヘッダー検証スクリプト作成
  - scripts/validate-security-headers.sh を作成する
  - curl -I コマンドで各サービスのヘッダーを取得し、必須ヘッダーの存在を検証する
  - _Requirements: 12.4_

- [ ] 13.4 CORS 設定検証スクリプト作成
  - scripts/validate-cors-config.sh を作成する
  - CORS 許可オリジンリストの整合性を確認する
  - _Requirements: 12.5_

- [ ] 13.5 検証失敗時のワークフロー制御
  - セキュリティヘッダー検証が失敗した場合、ワークフローを失敗させる設定を追加する
  - Pull Request をブロックする
  - _Requirements: 12.6_

## 14. パフォーマンス影響測定

- [ ] 14.1 パフォーマンステストジョブ実装
  - CI/CD にパフォーマンステストジョブを追加する
  - セキュリティヘッダー追加前後のレスポンスタイムを比較する
  - _Requirements: 13.1_

- [ ] 14.2 Apache Bench レスポンスタイム測定
  - Apache Bench を使用してレスポンスタイムを測定する（ab -n 1000 -c 10 コマンド）
  - レスポンスタイム増加が 5ms 以下であることを検証する
  - _Requirements: 13.2, 13.3_

- [ ] 14.3 ヘッダーサイズ測定
  - レスポンスヘッダーサイズ増加が 1KB 以下であることを検証する
  - _Requirements: 13.4_

- [ ] 14.4 パフォーマンス基準判定
  - パフォーマンス基準を満たさない場合、警告を表示するがワークフローは失敗させない設定を追加する
  - _Requirements: 13.5_

## 15. ドキュメント整備

- [ ] 15.1 実装ガイドドキュメント更新
  - SECURITY_HEADERS_IMPLEMENTATION_GUIDE.md を更新する
  - 実装手順詳細（Laravel/Next.js）、環境変数設定ガイド、CSP ポリシーカスタマイズ方法、CORS 設定との統合セクションを追加する
  - _Requirements: 14.1_

- [ ] 15.2 運用マニュアル作成
  - docs/SECURITY_HEADERS_OPERATION.md を作成する
  - 運用マニュアル、Report-Only モード運用手順、Enforce モード切り替え手順、CSP 違反レポート分析方法セクションを追加する
  - _Requirements: 14.2_

- [ ] 15.3 トラブルシューティングガイド作成
  - docs/SECURITY_HEADERS_TROUBLESHOOTING.md を作成する
  - よくある問題と解決策、CSP 違反のデバッグ方法、CORS エラーのトラブルシューティング、パフォーマンス問題の診断セクションを追加する
  - _Requirements: 14.3_

- [ ] 15.4 README.md 更新
  - README.md にセキュリティヘッダーセクションを追加する
  - セキュリティヘッダー機能概要、クイックスタートガイド、関連ドキュメントへのリンクを追加する
  - _Requirements: 14.4_

## 16. 段階的 CSP 導入運用

- [ ] 16.1 Report-Only モード有効化
  - 環境変数 SECURITY_CSP_MODE=report-only を設定する
  - ステージング環境にデプロイする
  - _Requirements: 15.1_

- [ ] 16.2 CSP 違反レポート収集・分析
  - 最低 1 週間 CSP 違反レポートを収集する
  - 正当な違反（外部リソース追加必要）と不正な違反（XSS 試行）を特定する
  - CSP ポリシーを調整し、必要なドメイン/ディレクティブを追加する
  - _Requirements: 15.2, 15.3_

- [ ] 16.3 Enforce モード移行判断
  - Report-Only モード期間中の違反率が 0.1% 以下であることを確認する
  - セキュリティチームによる承認を取得する
  - _Requirements: 15.4_

- [ ] 16.4 Enforce モード切り替え
  - 環境変数 SECURITY_CSP_MODE=enforce を設定する
  - 本番環境にデプロイする
  - _Requirements: 15.5_

- [ ] 16.5 Enforce モード展開後監視
  - 24 時間体制で CSP 違反とアプリケーション動作を監視する
  - 重大な問題が発生した場合、環境変数 SECURITY_ENABLE_CSP=false で緊急無効化する
  - _Requirements: 15.6, 15.7_
