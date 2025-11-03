# 実装計画

## Phase 1: 基盤整備（エラーコード体系・多言語リソース）

- [x] 1. エラーコード体系とリソース構造を設計する
- [x] 1.1 エラーコード定義ファイルを作成する
  - JSON形式でエラーコード体系を定義する（DOMAIN-SUBDOMAIN-CODE形式）
  - 必須フィールド（code, http_status, type, default_message, translation_key, category）を含める
  - 認証（AUTH）、バリデーション（VAL）、ビジネスロジック（BIZ）、インフラ（INFRA）のカテゴリーを定義する
  - JSON Schemaでバリデーションを実行する
  - _Requirements: 4.1, 4.2_

- [x] 1.2 多言語リソースファイルを準備する
  - 日本語エラーメッセージリソースを作成する
  - 英語エラーメッセージリソースを作成する
  - 翻訳キーをエラーコード定義と対応させる
  - フォールバックメッセージ機能を検証する
  - _Requirements: 5.1, 5.4_

- [x] 1.3 型定義自動生成スクリプトを作成する
  - TypeScript型定義ファイル生成スクリプトを実装する
  - PHP Enum生成スクリプトを実装する
  - ビルド時のバリデーション機能を追加する
  - CI/CDパイプラインに統合する
  - _Requirements: 4.3, 4.4_

## Phase 2: バックエンド実装（DDD Exception階層・Middleware・Handler）

- [ ] 2. DDD Exception階層を拡張する
- [x] 2.1 既存DomainExceptionを拡張する
  - toProblemDetails()メソッドを実装する
  - RFC 7807形式の配列を生成する機能を追加する
  - getErrorType()ヘルパーメソッドを実装する
  - エラーコードとHTTPステータスコードのマッピングを検証する
  - _Requirements: 1.1, 1.6, 2.1, 2.4_

- [x] 2.2 ApplicationException基底クラスを実装する
  - Application層のユースケースエラー用基底クラスを作成する
  - getStatusCode()、getErrorCode()、toProblemDetails()メソッドを実装する
  - 具体的なサブクラス例（ResourceNotFoundException、UnauthorizedAccessException）を作成する
  - HTTPステータスコード403/404のマッピングを検証する
  - _Requirements: 2.2, 2.4_

- [x] 2.3 InfrastructureException基底クラスを実装する
  - Infrastructure層の外部システムエラー用基底クラスを作成する
  - getStatusCode()、getErrorCode()、toProblemDetails()メソッドを実装する
  - 具体的なサブクラス例（DatabaseConnectionException、ExternalApiTimeoutException）を作成する
  - HTTPステータスコード502/503/504のマッピングを検証する
  - _Requirements: 2.3, 2.4_

- [ ] 3. ミドルウェアスタックを拡張する
- [x] 3.1 Accept-Languageヘッダー解析ミドルウェアを実装する
  - Accept-Languageヘッダーを解析する機能を実装する
  - Laravelアプリケーションロケールを設定する機能を追加する
  - サポート言語（ja/en）のリストを定義する
  - デフォルトロケール（ja）のフォールバック機能を検証する
  - 既存ミドルウェアスタックに統合する
  - _Requirements: 5.1, 5.3_

- [x] 4. Exception Handlerを拡張する
- [x] 4.1 RFC 7807形式レスポンス生成機能を実装する
  - render()メソッドでRFC 7807形式のJSONレスポンスを生成する
  - Content-Typeヘッダーにapplication/problem+jsonを設定する
  - toProblemDetails()メソッドを呼び出してRFC 7807配列を取得する
  - Laravel Translation（trans()）を使用して多言語メッセージを適用する
  - X-Request-IDヘッダーをレスポンスに追加する
  - _Requirements: 1.1, 1.2, 5.2_

- [x] 4.2 環境別エラーマスキング機能を実装する
  - 本番環境判定ロジックを実装する
  - 本番環境では内部エラー詳細をマスクする機能を追加する
  - 開発環境ではスタックトレースを含む詳細情報を返却する
  - Request IDを常に返却してログ追跡を可能にする
  - _Requirements: 1.4, 1.5, 非機能要件セキュリティ.1_

- [x] 4.3 バリデーションエラー特別処理を実装する
  - ValidationExceptionをキャッチしてフィールド別エラーメッセージを返却する
  - errorsフィールドをRFC 7807レスポンスに追加する
  - フィールド名とエラーメッセージの配列を生成する
  - _Requirements: 1.3_

- [x] 4.4 構造化ログ機能を実装する
  - Log::withContext()を使用してtrace_id、error_code、user_id、request_pathをログに追加する
  - 個人情報ハッシュ化機能を実装する（LOG_HASH_SENSITIVE_DATA環境変数制御）
  - ログレベル別の分離機能を検証する
  - _Requirements: 2.5, 3.4, 非機能要件セキュリティ.2_

## Phase 3: フロントエンド実装（エラークラス・APIクライアント・Error Boundaries）

- [ ] 5. エラークラスを実装する
- [x] 5.1 RFC 7807 Problem Details型定義を作成する
  - RFC7807Problem型インターフェースを定義する
  - 必須フィールド（type, title, status, detail）を含める
  - 拡張フィールド（error_code, trace_id, instance, timestamp）を含める
  - バリデーションエラー用errorsフィールドを含める
  - _Requirements: 8.1_

- [x] 5.2 ApiErrorクラスを実装する
  - RFC 7807レスポンスからApiErrorインスタンスを生成する機能を実装する
  - 型安全なプロパティ（status, errorCode, title, detail, requestId等）を定義する
  - ヘルパーメソッド（isValidationError(), isAuthenticationError(), isNotFoundError()）を実装する
  - getDisplayMessage()でユーザー向けメッセージを生成する機能を追加する
  - AppError基底インターフェースを実装する
  - _Requirements: 6.2, 6.5, 6.6, 8.1, 8.2, 8.5_

- [x] 5.3 NetworkErrorクラスを実装する
  - Fetch APIエラーからNetworkErrorを生成するファクトリーメソッドを実装する
  - isRetryableプロパティを設定する機能を追加する
  - isTimeout()、isConnectionError()判定メソッドを実装する
  - getDisplayMessage()でユーザー向けメッセージを生成する機能を追加する
  - AppError基底インターフェースを実装する
  - _Requirements: 6.3, 8.3, 8.5_

- [x] 6. 統一APIクライアントを実装する
- [x] 6.1 fetch wrapper基本機能を実装する
  - request()メソッドで統一されたAPIリクエスト機能を実装する
  - X-Request-IDヘッダー自動生成機能を追加する（crypto.randomUUID()使用）
  - Accept-Languageヘッダー自動付与機能を追加する（ブラウザ言語設定取得）
  - Accept: application/problem+jsonヘッダーを設定する
  - AbortControllerによる30秒タイムアウト管理を実装する
  - _Requirements: 6.1, 6.4_

- [x] 6.2 RFC 7807レスポンス解析機能を実装する
  - 4xx/5xxレスポンスをRFC 7807形式として解析する機能を追加する
  - ApiErrorインスタンスを生成してthrowする
  - バリデーションエラー（errorsフィールド）を適切に解析する
  - _Requirements: 6.2_

- [x] 6.3 ネットワークエラーハンドリング機能を実装する
  - TypeError: Failed to fetchをキャッチしてNetworkErrorをthrowする
  - AbortError（タイムアウト）をキャッチしてNetworkErrorをthrowする
  - その他のFetch APIエラーを適切にハンドリングする
  - _Requirements: 6.3_

- [x] 6.4 RESTful APIメソッドを実装する
  - get()メソッドを実装する
  - post()メソッドを実装する
  - put()メソッドを実装する
  - delete()メソッドを実装する
  - _Requirements: 6.1_

- [x] 7. Error Boundariesを実装する
- [x] 7.1 セグメント用Error Boundaryを実装する（user-app）
  - error.tsxファイルを作成する
  - ApiErrorを検出してRFC 7807情報を画面表示する機能を実装する
  - NetworkErrorを検出してネットワークエラーメッセージと再試行ボタンを表示する機能を実装する
  - Request ID（trace_id）をユーザーに提示する
  - reset()による再試行機能を実装する
  - 本番環境では内部エラー詳細をマスクする
  - _Requirements: 7.1, 7.2, 7.3, 7.5, 7.6, 7.7_

- [x] 7.2 セグメント用Error Boundaryを実装する（admin-app）
  - error.tsxファイルを作成する
  - ApiErrorを検出してRFC 7807情報を画面表示する機能を実装する
  - NetworkErrorを検出してネットワークエラーメッセージと再試行ボタンを表示する機能を実装する
  - Request ID（trace_id）をユーザーに提示する
  - reset()による再試行機能を実装する
  - 本番環境では内部エラー詳細をマスクする
  - _Requirements: 7.1, 7.2, 7.3, 7.5, 7.6, 7.7_

- [x] 7.3 ルートレイアウト用Global Error Boundaryを実装する（user-app）
  - global-error.tsxファイルを作成する
  - ルートセグメントエラー用のフォールバックUIを実装する
  - Request IDを表示してサポート問い合わせを促す
  - _Requirements: 7.4, 7.6_

- [x] 7.4 ルートレイアウト用Global Error Boundaryを実装する（admin-app）
  - global-error.tsxファイルを作成する
  - ルートセグメントエラー用のフォールバックUIを実装する
  - Request IDを表示してサポート問い合わせを促す
  - _Requirements: 7.4, 7.6_

## Phase 4: テスト実装（Unit・Feature・E2E）

- [ ] 8. バックエンドUnit Testsを実装する
- [ ] 8.1 DomainException Unit Testsを実装する
  - toProblemDetails()メソッドのRFC 7807形式変換テストを実装する
  - getErrorCode()メソッドのエラーコード形式テストを実装する
  - getStatusCode()メソッドのHTTPステータスコードテストを実装する
  - 90%以上のカバレッジを達成する
  - _Requirements: 9.1_

- [ ] 8.2 ApplicationException & InfrastructureException Unit Testsを実装する
  - toProblemDetails()メソッドのRFC 7807形式変換テストを実装する
  - getErrorCode()、getStatusCode()メソッドのテストを実装する
  - 各レイヤーの具象クラス例のテストを実装する
  - 90%以上のカバレッジを達成する
  - _Requirements: 9.1_

- [ ] 8.3 SetLocaleFromAcceptLanguage Middleware Unit Testsを実装する
  - Accept-Language: jaヘッダーのロケール設定テストを実装する
  - Accept-Language: enヘッダーのロケール設定テストを実装する
  - デフォルトロケール（ja）のフォールバックテストを実装する
  - サポート外言語のフォールバックテストを実装する
  - 90%以上のカバレッジを達成する
  - _Requirements: 9.1_

- [ ] 9. バックエンドFeature Testsを実装する
- [ ] 9.1 Exception Handler統合テストを実装する
  - DomainException発生時のRFC 7807レスポンス生成テストを実装する
  - ApplicationException発生時のRFC 7807レスポンス生成テストを実装する
  - InfrastructureException発生時のRFC 7807レスポンス生成テストを実装する
  - Content-Typeヘッダーのapplication/problem+json検証テストを実装する
  - 85%以上のカバレッジを達成する
  - _Requirements: 9.2, 9.7_

- [ ] 9.2 Request ID伝播フローテストを実装する
  - リクエストヘッダーX-Request-ID生成テストを実装する
  - レスポンスヘッダーX-Request-ID返却テストを実装する
  - エラーレスポンスtrace_idフィールド設定テストを実装する
  - ログコンテキストへのtrace_id追加テストを実装する
  - 85%以上のカバレッジを達成する
  - _Requirements: 9.2, 9.5_

- [ ] 9.3 多言語エラーメッセージテストを実装する
  - Accept-Language: jaで日本語エラーメッセージ返却テストを実装する
  - Accept-Language: enで英語エラーメッセージ返却テストを実装する
  - 翻訳キー存在確認テストを実装する
  - フォールバックメッセージテストを実装する
  - 85%以上のカバレッジを達成する
  - _Requirements: 9.2, 9.6_

- [ ] 9.4 バリデーションエラー特別処理テストを実装する
  - ValidationException発生時のerrorsフィールド生成テストを実装する
  - フィールド別エラーメッセージ配列テストを実装する
  - 422 Unprocessable Entityステータスコードテストを実装する
  - _Requirements: 9.2_

- [ ] 9.5 環境別エラーマスキングテストを実装する
  - 本番環境での内部エラー詳細マスキングテストを実装する
  - 開発環境でのスタックトレース表示テストを実装する
  - Request ID常時返却テストを実装する
  - _Requirements: 9.2_

- [ ] 10. フロントエンドUnit Testsを実装する
- [ ] 10.1 ApiClient Unit Testsを実装する
  - RFC 7807レスポンス解析テストを実装する
  - X-Request-ID自動生成テストを実装する
  - Accept-Languageヘッダー自動付与テストを実装する
  - 30秒タイムアウト管理テストを実装する
  - ApiError/NetworkError生成テストを実装する
  - 80%以上のカバレッジを達成する
  - _Requirements: 9.3_

- [ ] 10.2 ApiError Unit Testsを実装する
  - RFC 7807レスポンスからインスタンス生成テストを実装する
  - isValidationError()判定テストを実装する
  - isAuthenticationError()判定テストを実装する
  - isNotFoundError()判定テストを実装する
  - getDisplayMessage()メッセージ生成テストを実装する
  - 80%以上のカバレッジを達成する
  - _Requirements: 9.3_

- [ ] 10.3 NetworkError Unit Testsを実装する
  - fromFetchError()ファクトリーメソッドテストを実装する
  - isTimeout()判定テストを実装する
  - isConnectionError()判定テストを実装する
  - getDisplayMessage()メッセージ生成テストを実装する
  - isRetryableプロパティテストを実装する
  - 80%以上のカバレッジを達成する
  - _Requirements: 9.3_

- [ ] 11. E2E Testsを実装する
- [ ] 11.1 APIエラー表示E2Eテストを実装する
  - RFC 7807情報（title, detail, errorCode, requestId）の画面表示テストを実装する
  - Error Boundary UIの表示検証テストを実装する
  - Request IDのサポート用参照ID表示テストを実装する
  - _Requirements: 9.4_

- [ ] 11.2 バリデーションエラー表示E2Eテストを実装する
  - フィールド別エラーメッセージ画面表示テストを実装する
  - errorsフィールド解析・表示テストを実装する
  - _Requirements: 9.4_

- [ ] 11.3 認証エラー（401）リダイレクトE2Eテストを実装する
  - 401エラー発生時のログインページリダイレクトテストを実装する
  - 認証エラーメッセージ表示テストを実装する
  - _Requirements: 9.4_

- [ ] 11.4 ネットワークエラー表示E2Eテストを実装する
  - ネットワークエラーメッセージ表示テストを実装する
  - 再試行ボタン表示・動作テストを実装する
  - NetworkError検出・UI表示テストを実装する
  - _Requirements: 9.4_

- [ ] 11.5 500エラーマスキングE2Eテストを実装する
  - 本番環境設定時の内部エラー詳細マスキングテストを実装する
  - 汎用エラーメッセージ表示テストを実装する
  - Request ID表示維持テストを実装する
  - _Requirements: 9.4_

- [ ] 11.6 再試行ボタン動作E2Eテストを実装する
  - Error Boundaryの再試行ボタンクリックテストを実装する
  - reset()関数呼び出しテストを実装する
  - router.refresh()またはreset()によるリカバリーテストを実装する
  - _Requirements: 9.4_

- [ ] 11.7 Request ID表示E2Eテストを実装する
  - Error Boundary UIのRequest ID（trace_id）表示テストを実装する
  - サポート問い合わせ用参照ID提示テストを実装する
  - _Requirements: 9.4_

- [ ] 12. パフォーマンステストを実装する
- [ ] 12.1 Exception生成コストテストを実装する
  - DomainException生成時のオーバーヘッド計測テストを実装する
  - 5ms以下の目標達成を検証する
  - _Requirements: 非機能要件パフォーマンス.1_

- [ ] 12.2 翻訳処理オーバーヘッドテストを実装する
  - Laravel Translation Cache効果計測テストを実装する
  - 2回目以降のアクセスが1ms以下であることを検証する
  - _Requirements: 非機能要件パフォーマンス.2_

- [ ] 12.3 RFC 7807変換コストテストを実装する
  - toProblemDetails()メソッド実行時のオーバーヘッド計測テストを実装する
  - 3ms以下の目標達成を検証する
  - _Requirements: 非機能要件パフォーマンス_

## Phase 5: ドキュメント・CI/CD統合

- [ ] 13. ドキュメントを作成する
- [ ] 13.1 エラーコード一覧ドキュメントを作成する
  - カテゴリー別エラーコード一覧表を作成する
  - 認証（AUTH-*）、バリデーション（VAL-*）、ビジネスロジック（BIZ-*）、インフラ（INFRA-*）を分類する
  - 各エラーコードのレスポンス例を記載する
  - HTTPステータスコード・メッセージ・対処方法を記載する
  - _Requirements: 10.1_

- [ ] 13.2 トラブルシューティングガイドを作成する
  - よくある問題と解決策を記載する
  - Request ID追跡方法を説明する
  - 多言語メッセージ設定ミスのトラブルシューティングを記載する
  - Error Boundary動作不良のトラブルシューティングを記載する
  - _Requirements: 10.2_

- [ ] 14. CI/CDパイプラインを統合する
- [ ] 14.1 GitHub Actionsワークフローを更新する
  - エラーハンドリングテストスイートをワークフローに追加する
  - Pull Request作成時の自動テスト実行を設定する
  - mainブランチpush時の自動テスト実行を設定する
  - テスト完了時間10分以内の目標を検証する
  - _Requirements: 10.3, 10.5_
