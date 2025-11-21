# Requirements Document

## Introduction

本機能は、フロントエンドAPIクライアントのAcceptヘッダーにRFC 7807準拠の`application/problem+json` MIMEタイプを追加する技術的改善である。これにより、クライアントがRFC 7807形式のエラーレスポンスを明示的にサポートすることを宣言し、HTTP Content Negotiationのベストプラクティスに準拠する。

**背景:**
- 現在のフロントエンドAPIクライアントは`Accept: application/json`のみを送信
- LaravelバックエンドはRFC 7807準拠の`application/problem+json`レスポンスを返却可能
- Codexレビューで「Acceptヘッダーに`application/problem+json`を明示すべき」という指摘を受けた

**ビジネス価値:**
- RFC 7807標準への完全準拠
- 将来的なContent Negotiation拡張への備え
- APIクライアントとサーバー間の契約明確化

## Requirements

### Requirement 1: Acceptヘッダーの更新

**Objective:** 開発者として、APIクライアントがRFC 7807準拠のエラーレスポンスを明示的にサポートすることを宣言したい。これにより、HTTP Content Negotiationのベストプラクティスに準拠できる。

#### Acceptance Criteria

1. WHEN APIクライアントがHTTPリクエストを送信する際にAcceptヘッダーが明示的に設定されていない THEN APIクライアントは `Accept: application/problem+json, application/json` ヘッダーを設定するものとする（SHALL）

2. WHEN APIクライアントがAcceptヘッダーを設定する THEN `application/problem+json` を `application/json` より前に配置するものとする（SHALL）

3. WHEN APIクライアントがAcceptヘッダーを設定する THEN q-factor（品質係数）は省略し、両MIMEタイプにデフォルト値 `q=1.0` を適用するものとする（SHALL）

4. IF 呼び出し元がカスタムAcceptヘッダーを指定している THEN APIクライアントはその値を上書きせずそのまま使用するものとする（SHALL）

### Requirement 2: 後方互換性の維持

**Objective:** 運用チームとして、既存のAPIエンドポイントとの互換性が100%維持されることを確認したい。これにより、本番環境への安全なデプロイが可能になる。

#### Acceptance Criteria

1. WHILE Acceptヘッダーに `application/json` が含まれている THEN APIクライアントは既存のLaravel APIエンドポイントと正常に通信できるものとする（SHALL）

2. WHEN サーバーが正常レスポンスを返却する THEN APIクライアントは `application/json` 形式のレスポンスを正しく解析できるものとする（SHALL）

3. WHEN サーバーがエラーレスポンスを返却する THEN APIクライアントは `application/problem+json` 形式（RFC 7807）のレスポンスを正しく解析できるものとする（SHALL）

4. WHERE 既存のApiErrorクラスがRFC 7807形式を解析している THEN エラーハンドリングロジックは変更不要であるものとする（SHALL）

### Requirement 3: 対象ファイルの網羅的更新

**Objective:** 開発者として、すべてのAPIクライアント実装ファイルが一貫した方法でAcceptヘッダーを設定することを確認したい。これにより、コードベース全体での統一性が保たれる。

#### Acceptance Criteria

1. WHEN 共通モジュール `frontend/lib/api-client.ts` を更新する THEN Acceptヘッダー設定を `application/problem+json, application/json` に変更するものとする（SHALL）

2. WHEN Admin App固有モジュール `frontend/admin-app/src/lib/api-client.ts` を更新する THEN Acceptヘッダー設定を `application/problem+json, application/json` に変更するものとする（SHALL）

3. WHEN User App固有モジュール `frontend/user-app/src/lib/api-client.ts` を更新する THEN Acceptヘッダー設定を `application/problem+json, application/json` に変更するものとする（SHALL）

4. WHEN API V1クライアント `frontend/lib/api-client-v1.ts` を更新する THEN Acceptヘッダー設定を `application/problem+json, application/json` に変更するものとする（SHALL）

### Requirement 4: テストの更新

**Objective:** QAエンジニアとして、Acceptヘッダーの変更がテストで検証されることを確認したい。これにより、変更の正確性が保証される。

#### Acceptance Criteria

1. WHEN Acceptヘッダー検証テストを実行する THEN 期待値が `application/problem+json, application/json` であることを検証するものとする（SHALL）

2. WHEN フロントエンドユニットテスト（`npm test`）を実行する THEN すべてのテストがパスするものとする（SHALL）

3. WHILE テストカバレッジを維持する THEN 既存のカバレッジ率（96.1%以上）を下回らないものとする（SHALL）

4. WHEN E2Eテストを実行する THEN Laravel API ↔ Frontend間の統合が正常に動作するものとする（SHALL）

### Requirement 5: コードドキュメントの更新

**Objective:** 将来の開発者として、Acceptヘッダー設定の意図と根拠を理解したい。これにより、コードの保守性が向上する。

#### Acceptance Criteria

1. WHEN Acceptヘッダー設定のコードを変更する THEN RFC 7807準拠を明示するコメントを追加するものとする（SHALL）

2. WHERE コメントを追加する THEN Content Negotiationの目的と後方互換性の保証について説明するものとする（SHALL）

## Non-Functional Requirements

### NFR-1: パフォーマンス
- Acceptヘッダーの変更によるHTTPリクエストのオーバーヘッドは無視できるレベル（数バイトの増加）であること

### NFR-2: セキュリティ
- Acceptヘッダーの変更はセキュリティに影響しないこと（情報漏洩リスクなし）

### NFR-3: 保守性
- すべてのAPIクライアントファイルで一貫したAcceptヘッダー設定パターンを使用すること

## Out of Scope

以下は本要件の対象外である：

1. **バックエンドLaravel APIの変更** - 既に`application/problem+json`対応済み
2. **Next.js App Router API routes** - バックエンド呼び出しをラップするのみ
3. **E2Eテストの変更** - APIクライアント経由で正常動作する
4. **q-factor（品質係数）の明示的設定** - デフォルト値（q=1.0）を使用

## References

### RFC 7807関連
- [RFC 7807: Problem Details for HTTP APIs](https://www.rfc-editor.org/rfc/rfc7807)
- [RFC 9457: Problem Details for HTTP APIs (RFC 7807 bis)](https://www.rfc-editor.org/rfc/rfc9457.html)

### HTTP Content Negotiation
- [MDN: Content negotiation](https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/Content_negotiation)
- [MDN: Accept header](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Accept)

### 関連Issue
- [#111](https://github.com/ef-tech/laravel-next-b2c/issues/111) - エラーハンドリングパターン作成（RFC 7807準拠）
- [#116](https://github.com/ef-tech/laravel-next-b2c/issues/116) - Frontend Accept headerの明示化（application/problem+json追加）
