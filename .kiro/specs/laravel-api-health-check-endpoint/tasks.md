# 実装計画

## 概要

本実装計画は、Laravel APIヘルスチェックエンドポイント（Liveness Probe）の実装タスクを定義します。既存のNext.jsアプリケーションヘルスチェック実装と整合性を保ちながら、Closure形式による軽量実装とDocker統合を実現します。

## 実装タスク

- [ ] 1. ヘルスチェックエンドポイントの実装
- [x] 1.1 APIルートにヘルスチェックエンドポイントを追加
  - `/api/health`エンドポイントをClosure形式で実装
  - HTTPステータス200 OKで`{"status": "ok"}`を返却
  - レート制限を除外（withoutMiddleware('throttle:api')）
  - ルート名を`health`として登録
  - 認証不要のパブリックアクセス可能エンドポイント
  - _Requirements: 1.1, 1.2, 2.1, 2.2, 2.4, 5.1, 5.2, 5.5, 6.1, 6.2, 6.3, 6.4_

- [x] 1.2 エンドポイントのレスポンス最適化
  - Cache-Control: no-storeヘッダーを含むJSON応答
  - 外部依存なしで10ミリ秒以内に応答
  - データベース接続やRedis接続を実行しない
  - ログ出力を最小限に抑制
  - _Requirements: 1.3, 1.4, 1.5, 2.3, 7.1, 7.2, 7.3_

- [ ] 2. Pest 4 Featureテストの実装
- [x] 2.1 ヘルスチェックエンドポイントの基本テスト作成
  - Pest 4 Function Syntaxでテストファイル作成
  - HTTPステータス200 OKの検証
  - JSONレスポンス構造`{"status": "ok"}`の検証
  - describe()によるテストグループ化
  - _Requirements: 4.1, 4.2, 4.3, 6.6, 6.7_

- [x] 2.2 セキュリティとパフォーマンステストの追加
  - Cache-Control: no-storeヘッダーの検証
  - 認証なしでアクセス可能であることの検証
  - レート制限が適用されないことの検証（150回連続アクセス）
  - _Requirements: 1.3, 2.1, 2.2, 2.5, 4.4, 4.5_

- [ ] 3. Dockerヘルスチェックの統合
- [x] 3.1 Dockerfileにヘルスチェック命令を追加
  - HEALTHCHECKディレクティブをEXPOSE 13000の後に配置
  - wgetコマンドで`http://127.0.0.1:13000/api/health`に10秒間隔アクセス
  - タイムアウト3秒、起動猶予期間30秒、リトライ3回を設定
  - IPv4明示対応（127.0.0.1使用）でlocalhost DNS解決問題回避
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 5.4, 5.6, 6.5_

- [x] 3.2 Docker Composeサービス依存関係の更新
  - admin-appのdepends_onにlaravel-apiのservice_healthy条件を追加
  - user-appのdepends_onにlaravel-apiのservice_healthy条件を追加
  - Laravel APIがhealthy状態になるまで依存サービスの起動を待機
  - _Requirements: 3.7, 3.8, 5.3_

- [ ] 4. コード品質とテスト実行の確認
- [x] 4.1 Laravel Pintによるコードフォーマット確認
  - routes/api.phpのヘルスチェック定義がLaravel Pint標準ルールに準拠
  - `./vendor/bin/pint`コマンドで自動フォーマット実行
  - フォーマットチェック通過確認
  - _Requirements: 4.6_

- [x] 4.2 Larastan静的解析の実行
  - routes/api.phpのヘルスチェック定義がLevel 8静的解析基準を満たす
  - `./vendor/bin/phpstan analyse`コマンドで静的解析実行
  - 型安全性と静的解析チェック通過確認
  - _Requirements: 4.7_

- [x] 4.3 Pestテストスイート実行確認
  - `./vendor/bin/pest tests/Feature/HealthCheckTest.php`でテスト実行
  - 全テストケース（5テスト）が成功することを確認
  - テストカバレッジが要件を満たすことを確認
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ] 5. Docker環境での動作確認
- [x] 5.1 ローカルAPIエンドポイント動作確認
  - `curl -f http://localhost:13000/api/health`でエンドポイントアクセス
  - HTTPステータス200 OKと`{"status": "ok"}`レスポンス確認
  - 応答時間が10ミリ秒以内であることを確認
  - _Requirements: 1.1, 1.2, 1.4_

- [ ] 5.2 Dockerイメージビルドとコンテナ起動
  - `docker compose up --build -d`で全サービスビルドと起動
  - Laravel APIコンテナが正常に起動することを確認
  - 起動猶予期間（30秒）経過後のヘルスチェック実行確認
  - _Requirements: 3.5_

- [ ] 5.3 Docker Composeヘルスチェックステータス確認
  - `docker compose ps`でlaravel-apiのHEALTHカラムにhealthyステータス表示
  - admin-app/user-appがlaravel-api healthy後に起動することを確認
  - サービス間依存関係が正しく動作することを確認
  - _Requirements: 3.7, 3.8_

- [x] 5.4 コンテナ内からヘルスチェック実行
  - `docker exec laravel-api curl -f http://127.0.0.1:13000/api/health`実行
  - コンテナ内部からIPv4明示アドレスで正常アクセス確認
  - wgetコマンドの動作確認（ヘルスチェック実行ログ確認）
  - _Requirements: 3.2, 5.4_

- [ ] 6. CI/CD環境での検証
- [ ] 6.1 GitHub Actions php-qualityワークフロー確認
  - Laravel Pint + Larastan品質チェックが成功することを確認
  - routes/api.phpの変更がコード品質基準を満たすことを確認
  - CI/CD環境でのフォーマットと静的解析が通過することを確認
  - _Requirements: 4.6, 4.7, 4.8_

- [ ] 6.2 GitHub Actions testワークフロー確認
  - Pest 4テストスイートが成功することを確認
  - ヘルスチェックエンドポイントテストが全て合格することを確認
  - テストカバレッジレポートが生成されることを確認
  - _Requirements: 4.1, 4.8_

- [ ] 7. パフォーマンスと運用要件の検証
- [ ] 7.1 高頻度アクセステスト
  - 秒間100リクエストまで安定処理できることを確認
  - レート制限除外により制限なくアクセス可能であることを確認
  - CPU使用率増加が1%未満であることを確認
  - メモリ使用率増加が1%未満であることを確認
  - _Requirements: 2.5, 7.1, 7.5_

- [ ] 7.2 長時間安定動作確認
  - 10秒間隔のヘルスチェックが長時間（1時間以上）安定動作
  - ヘルスチェック失敗時の3回リトライとunhealthy判定動作確認
  - unhealthy状態からの回復動作確認
  - _Requirements: 3.3, 3.4, 7.4, 7.6_

- [ ] 8. 統合テストと最終確認
- [ ] 8.1 全サービス統合動作確認
  - docker compose upで全サービス（laravel-api, admin-app, user-app, pgsql, redis）が正常起動
  - サービス間依存関係が正しく動作（laravel-api healthy後にフロントエンドアプリ起動）
  - E2Eテストサービスが全依存サービスhealthy後に起動可能であることを確認
  - _Requirements: 3.8, 5.3_

- [ ] 8.2 エンドポイント整合性確認
  - Next.jsアプリ（admin-app, user-app）の`/api/health`と同じパスを使用
  - Next.jsアプリと同じレスポンス形式`{"status": "ok"}`を返却
  - Docker Composeヘルスチェック設計（`.kiro/specs/docker-compose-healthcheck/design.md`）との整合性確認
  - _Requirements: 5.1, 5.2, 5.3_

- [ ] 8.3 将来拡張への準備確認
  - Closure実装がController + Infrastructure層実装に移行可能な設計であることを確認
  - Readinessエンドポイント（`/api/ready`）追加を想定した拡張可能設計を確認
  - テスト構成が将来的にReadinessテスト追加に対応できることを確認
  - _Requirements: 8.1, 8.2, 8.3, 8.4_

## 要件カバレッジマトリクス

### Requirement 1: Livenessエンドポイント実装
- 1.1 HTTP 200 OK応答 → タスク 1.1, 5.1
- 1.2 JSON `{"status": "ok"}` 応答 → タスク 1.1, 5.1, 8.2
- 1.3 Cache-Control: no-store ヘッダー → タスク 1.2, 2.2
- 1.4 10ミリ秒以内応答 → タスク 1.2, 5.1
- 1.5 外部依存なし → タスク 1.2

### Requirement 2: セキュリティとアクセス制御
- 2.1 認証不要アクセス → タスク 1.1, 2.2
- 2.2 レート制限除外 → タスク 1.1, 2.2
- 2.3 機微情報を含めない → タスク 1.2
- 2.4 認証なしHTTP 200 OK → タスク 1.1, 2.2
- 2.5 秒間100リクエスト安定処理 → タスク 2.2, 7.1

### Requirement 3: Docker ヘルスチェック統合
- 3.1 10秒間隔実行 → タスク 3.1
- 3.2 wgetコマンド使用 → タスク 3.1, 5.4
- 3.3 3回リトライ → タスク 3.1, 7.2
- 3.4 3回連続失敗でunhealthy → タスク 3.1, 7.2
- 3.5 30秒起動猶予期間 → タスク 3.1, 5.2
- 3.6 3秒タイムアウト → タスク 3.1
- 3.7 docker compose psでステータス表示 → タスク 5.3
- 3.8 service_healthy条件待機 → タスク 3.2, 5.3, 8.1

### Requirement 4: テストカバレッジと品質保証
- 4.1 Pest 4テスト合格 → タスク 2.1, 4.3, 6.2
- 4.2 HTTP 200 OK検証 → タスク 2.1, 4.3
- 4.3 JSONレスポンス検証 → タスク 2.1, 4.3
- 4.4 Cache-Controlヘッダー検証 → タスク 2.2, 4.3
- 4.5 認証なしアクセス検証 → タスク 2.2, 4.3
- 4.6 Laravel Pint準拠 → タスク 4.1, 6.1
- 4.7 Larastan Level 8準拠 → タスク 4.2, 6.1
- 4.8 GitHub Actions品質チェック合格 → タスク 6.1, 6.2

### Requirement 5: 既存システムとの統合整合性
- 5.1 `/api/health`パス統一 → タスク 1.1, 8.2
- 5.2 最小JSON応答形式統一 → タスク 1.1, 8.2
- 5.3 Docker Composeヘルスチェック設計準拠 → タスク 3.2, 8.2
- 5.4 IPv4明示対応（127.0.0.1） → タスク 3.1, 5.4
- 5.5 Closure実装（DDD非適用） → タスク 1.1
- 5.6 ポート13000使用 → タスク 3.1

### Requirement 6: 実装とコード構成
- 6.1 routes/api.phpに追加 → タスク 1.1
- 6.2 Closure形式実装 → タスク 1.1
- 6.3 withoutMiddleware('throttle:api') → タスク 1.1
- 6.4 name('health') → タスク 1.1
- 6.5 Dockerfile EXPOSE後に配置 → タスク 3.1
- 6.6 tests/Feature/HealthCheckTest.php → タスク 2.1
- 6.7 Pest 4 Function Syntax → タスク 2.1

### Requirement 7: パフォーマンスと運用要件
- 7.1 CPU/メモリオーバーヘッド最小化 → タスク 1.2, 7.1
- 7.2 外部依存なし → タスク 1.2
- 7.3 ログ最小化 → タスク 1.2
- 7.4 リソース枯渇回避 → タスク 7.2
- 7.5 秒間100リクエスト処理 → タスク 7.1
- 7.6 3秒タイムアウト検出 → タスク 7.2

### Requirement 8: 将来拡張への配慮
- 8.1 Readinessエンドポイント拡張性 → タスク 8.3
- 8.2 Controller + Infrastructure移行可能 → タスク 8.3
- 8.3 Livenessのみ実装を明記 → タスク 8.3
- 8.4 Readinessテスト追加可能構造 → タスク 8.3

## 実装順序と依存関係

### フェーズ1: コア実装（タスク1-2）
1. タスク1.1: ヘルスチェックエンドポイント実装
2. タスク1.2: レスポンス最適化
3. タスク2.1: 基本テスト作成
4. タスク2.2: セキュリティ・パフォーマンステスト追加

**依存関係**: タスク1.2はタスク1.1に依存、タスク2.1-2.2はタスク1.1に依存

### フェーズ2: Docker統合（タスク3-4）
5. タスク3.1: Dockerfileヘルスチェック追加
6. タスク3.2: Docker Composeサービス依存関係更新
7. タスク4.1: Laravel Pint確認
8. タスク4.2: Larastan確認
9. タスク4.3: Pestテスト実行確認

**依存関係**: タスク3.2はタスク3.1に依存、タスク4.1-4.3はタスク1-2完了に依存

### フェーズ3: 動作確認（タスク5-6）
10. タスク5.1: ローカルAPI動作確認
11. タスク5.2: Dockerイメージビルドとコンテナ起動
12. タスク5.3: ヘルスチェックステータス確認
13. タスク5.4: コンテナ内ヘルスチェック実行
14. タスク6.1: GitHub Actions php-quality確認
15. タスク6.2: GitHub Actions test確認

**依存関係**: タスク5.2-5.4はタスク3.1完了に依存、タスク6.1-6.2はタスク1-4完了に依存

### フェーズ4: 統合検証（タスク7-8）
16. タスク7.1: 高頻度アクセステスト
17. タスク7.2: 長時間安定動作確認
18. タスク8.1: 全サービス統合動作確認
19. タスク8.2: エンドポイント整合性確認
20. タスク8.3: 将来拡張への準備確認

**依存関係**: タスク7.1-7.2はタスク5完了に依存、タスク8.1-8.3は全タスク完了に依存

## 完了基準

### 最小完了基準
- [ ] ヘルスチェックエンドポイント`/api/health`が実装され、HTTPステータス200 OKで`{"status": "ok"}`を返却
- [ ] Pest 4テストスイート（5テスト）が全て合格
- [ ] Laravel Pint + Larastan品質チェックが合格
- [ ] Dockerヘルスチェックが統合され、`docker compose ps`でhealthyステータス表示

### 完全完了基準
- [ ] 全41の要件（8要件グループ）がカバーされている
- [ ] GitHub Actions CI/CDワークフロー（php-quality, test）が成功
- [ ] Docker環境で全サービスが正常起動し、サービス間依存関係が動作
- [ ] パフォーマンステスト（秒間100リクエスト、1時間安定動作）が成功
- [ ] Next.jsアプリヘルスチェックとの整合性が確認済み

## 注意事項

### DDD非適用の理由
本エンドポイントはClosure実装を採用し、DDD/クリーンアーキテクチャの4層構造（Domain/Application/Infrastructure/HTTP）を経由しません。理由は以下の通りです:
- 単純なLiveness確認のみで、ビジネスロジックなし
- 外部依存なし（データベース接続、Redis接続不要）
- パフォーマンス最適（レイヤー経由のオーバーヘッドなし）
- 保守容易性優先（HTTP層で完結）

将来的にReadinessエンドポイント（データベース接続確認等を含む）を追加する場合は、Controller + Infrastructure層実装に移行を検討します。

### IPv4明示対応
プロジェクト標準としてIPv4明示対応（localhost→127.0.0.1）を採用しています。理由は以下の通りです:
- localhost DNS解決問題回避（一部環境でIPv6優先によるタイムアウト発生）
- Next.jsアプリヘルスチェックと整合性保持
- Docker内部ループバックアクセスで最速応答

### レート制限除外
`withoutMiddleware('throttle:api')`によりレート制限を完全除外しています。理由は以下の通りです:
- Docker Engine HEALTHCHECKは10秒間隔（6回/分）で定期実行
- オーケストレータ/ロードバランサーからの高頻度アクセス対応
- 認証不要の軽量エンドポイントのため、レート制限不要
- 本番環境ではインフラ層（Nginx、ALB等）でIP制限実装を推奨

### テストDB環境
本機能のテストはRefreshDatabase traitを使用しません。理由は以下の通りです:
- ステートレスエンドポイントでデータベースアクセスなし
- テストDB環境（SQLite/PostgreSQL）の切り替え不要
- テスト実行速度の最適化（データベースセットアップ不要）
