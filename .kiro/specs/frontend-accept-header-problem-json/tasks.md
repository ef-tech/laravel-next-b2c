# Implementation Plan

## 概要

フロントエンドAPIクライアントのAcceptヘッダーにRFC 7807準拠の`application/problem+json` MIMEタイプを追加する実装タスク。

---

- [ ] 1. 共通APIクライアントのAcceptヘッダー更新
- [ ] 1.1 共通モジュールのAcceptヘッダー設定変更
  - `frontend/lib/api-client.ts`のAcceptヘッダー設定を`application/problem+json, application/json`に更新
  - RFC 7807準拠とContent Negotiationの目的を説明するコメントを追加
  - カスタムAcceptヘッダーが指定された場合の動作が維持されることを確認
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 3.1, 5.1, 5.2_

- [ ] 1.2 V1 APIクライアントのAcceptヘッダー設定変更
  - `frontend/lib/api-client-v1.ts`のheaders定義内のAcceptを`application/problem+json, application/json`に更新
  - RFC 7807準拠と後方互換性についてのコメントを追加
  - _Requirements: 1.1, 1.2, 1.3, 3.4, 5.1, 5.2_

- [ ] 2. テストアサーションの更新
- [ ] 2.1 共通APIクライアントのテスト期待値更新
  - `frontend/admin-app/src/__tests__/lib/api-client.test.ts`のAcceptヘッダー検証テストの期待値を更新
  - `application/problem+json, application/json`が設定されることを検証
  - カスタムAcceptヘッダーが上書きされないことを検証するテストケースを確認
  - _Requirements: 4.1, 4.2, 4.3_

- [ ] 2.2 V1 APIクライアントのAcceptヘッダー検証テスト追加（オプション）
  - fetchV1関数がRFC 7807準拠のAcceptヘッダーを送信することを検証するテストケースを追加
  - 既存のテストファイルにAcceptヘッダー検証ロジックを追加
  - _Requirements: 4.1, 4.2_

- [ ] 3. 品質検証と統合確認
- [ ] 3.1 フロントエンドユニットテストの実行と検証
  - `npm test`でフロントエンド全テストを実行
  - 全テストがパスすることを確認
  - テストカバレッジが96.1%以上を維持していることを確認
  - _Requirements: 4.2, 4.3_

- [ ] 3.2 ESLintチェックの実行
  - `npm run lint`でESLintチェックを実行
  - 追加したコメントやコード変更がスタイルルールに準拠していることを確認
  - _Requirements: NFR-3_

- [ ] 3.3 Laravel APIとの統合確認
  - 開発環境を起動し、各APIエンドポイントへのリクエストを確認
  - 正常レスポンス（`application/json`）が正しく処理されることを確認
  - エラーレスポンス（`application/problem+json`）が正しく処理されることを確認
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 4.4_
