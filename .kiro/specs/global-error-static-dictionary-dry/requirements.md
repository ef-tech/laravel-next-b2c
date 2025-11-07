# Requirements Document

## GitHub Issue Information

**Issue**: [#119](https://github.com/ef-tech/laravel-next-b2c/issues/119) - Refactor: Global Error静的辞書の共通化（DRY原則改善）
**Labels**: enhancement, refactoring, frontend
**Milestone**: なし
**Assignees**: なし

### Original Issue Description

## 📝 概要

PR #118 のCodexレビューで指摘された改善提案です。

現在、`global-error.tsx`の静的辞書（messages）がUser AppとAdmin Appの両方に重複定義されています。将来的な表現調整時のドリフト防止のため、共有モジュール（`frontend/lib`）への切り出しを検討します。

## 🎯 目的

- **DRY原則の適用**: 重複コード削減
- **保守性向上**: 単一箇所での管理
- **ドリフト防止**: 表現の一貫性保証

## 📍 現状

### User App: `frontend/user-app/src/app/global-error.tsx`
```typescript
const messages = {
  ja: {
    network: {
      timeout: "リクエストがタイムアウトしました。しばらくしてから再度お試しください。",
      connection: "ネットワーク接続に問題が発生しました。インターネット接続を確認して再度お試しください。",
      unknown: "予期しないエラーが発生しました。しばらくしてから再度お試しください。",
    },
    boundary: {
      title: "エラーが発生しました",
      retry: "再試行",
      home: "ホームに戻る",
      status: "ステータスコード",
      requestId: "Request ID",
      networkError: "ネットワークエラー",
      timeout: "タイムアウト",
      connectionError: "接続エラー",
      retryableMessage: "このエラーは再試行可能です。しばらくしてから再度お試しください。",
    },
    validation: {
      title: "入力エラー",
    },
    global: {
      title: "予期しないエラーが発生しました",
      retry: "再試行",
      errorId: "Error ID",
      contactMessage: "お問い合わせの際は、このIDをお伝えください",
    },
  },
  en: {
    network: {
      timeout: "The request timed out. Please try again later.",
      connection: "A network connection problem occurred. Please check your internet connection and try again.",
      unknown: "An unexpected error occurred. Please try again later.",
    },
    boundary: {
      title: "An error occurred",
      retry: "Retry",
      home: "Go to Home",
      status: "Status Code",
      requestId: "Request ID",
      networkError: "Network Error",
      timeout: "Timeout",
      connectionError: "Connection Error",
      retryableMessage: "This error is retryable. Please try again later.",
    },
    validation: {
      title: "Validation Errors",
    },
    global: {
      title: "An unexpected error occurred",
      retry: "Retry",
      errorId: "Error ID",
      contactMessage: "Please provide this ID when contacting support",
    },
  },
} as const;
```

### Admin App: `frontend/admin-app/src/app/global-error.tsx`
```typescript
// 同じ内容が重複定義
const messages = {
  // ...
};
```

## 💡 提案する改善案

### オプション1: `frontend/lib/global-error-messages.ts`に共通化

```typescript
// frontend/lib/global-error-messages.ts
export const globalErrorMessages: Record<Locale, GlobalErrorMessages> = {
  ja: { /* ... */ },
  en: { /* ... */ },
};
```

```typescript
// frontend/user-app/src/app/global-error.tsx
import { globalErrorMessages } from '@/../../lib/global-error-messages';
```

### オプション2: `frontend/types/messages.d.ts`に型定義と共に配置

型定義と実装を同じ場所に配置することで、型安全性を高める。

## ⚠️ 注意事項

- **優先度**: 低（現状で動作問題なし）
- **テスト**: 既存のGlobal Error Boundaryテスト（27テスト × 2アプリ）で回帰検証
- **テンプレート最適化**: BtoCアプリケーションテンプレートとして、メンテナンス性を重視

## 📚 参考

- PR #118: https://github.com/ef-tech/laravel-next-b2c/pull/118
- Codexレビューコメント: 「Global Error の静的辞書（messages）が User/Admin の両方に重複定義されています。将来的な表現調整時のドリフト防止のため、共有モジュール（frontend/lib）への切り出しや、最小限の共通ユーティリティ化をご検討ください」

## ✅ 完了条件

- [ ] 共通モジュール化の実装
- [ ] User App/Admin Appでの動作確認
- [ ] 既存テストの全てpass（54テスト）
- [ ] 型安全性の維持

---

## Extracted Information

### Technology Stack
**Backend**: なし
**Frontend**: TypeScript, React, Next.js
**Infrastructure**: なし
**Tools**: なし

### Project Structure
Issueから抽出されたファイル・フォルダ構造:
```
frontend/user-app/src/app/global-error.tsx
frontend/admin-app/src/app/global-error.tsx
frontend/lib/global-error-messages.ts (提案)
frontend/types/messages.d.ts (提案)
```

### Development Services Configuration
なし

### Requirements Hints
Issueの分析結果から抽出された要件のヒント:
- DRY原則の適用により重複コード削減
- 保守性向上のための単一箇所管理
- 表現の一貫性保証によるドリフト防止
- 既存テスト（54テスト）の全てpass
- 型安全性の維持

### TODO Items from Issue
Issueから自動的にインポートされたTODO項目:
- [ ] 共通モジュール化の実装
- [ ] User App/Admin Appでの動作確認
- [ ] 既存テストの全てpass（54テスト）
- [ ] 型安全性の維持

---

## Introduction

本仕様は、User AppとAdmin Appの`global-error.tsx`に重複定義されている静的メッセージ辞書（`messages`）を共通モジュールに統合するリファクタリングを行うものです。

### ビジネス価値

- **保守性向上**: メッセージ表現の変更が1箇所で完結し、変更工数を削減
- **一貫性保証**: User/Admin間でのメッセージドリフトを防止し、ユーザー体験の統一性を確保
- **テンプレート品質向上**: DRY原則を適用した高品質なB2Cアプリケーションテンプレートの提供

### 現状の課題

現在、以下の4つのカテゴリのメッセージが完全に重複しています：

1. **network**: ネットワークエラーメッセージ（timeout, connection, unknown）
2. **boundary**: Error Boundary UI要素（title, retry, status等）
3. **validation**: バリデーションエラータイトル
4. **global**: 汎用エラーメッセージ（title, retry, errorId等）

各カテゴリは日本語（ja）と英語（en）の2言語に対応しており、合計で約30個のメッセージが両アプリに重複定義されています。

---

## Requirements

### Requirement 1: Global Error静的辞書の共通モジュール化

**Objective**: 開発者として、Global Error Boundaryの静的メッセージ辞書を共通モジュールに統合することで、メンテナンス性を向上させ、表現のドリフトを防止したい

#### Acceptance Criteria

1. WHEN 開発者がGlobal Errorメッセージを変更する必要がある THEN 共通モジュールは単一ファイル（`frontend/lib/global-error-messages.ts`）でメッセージ定義を提供しなければならない

2. WHERE `frontend/lib/global-error-messages.ts` THE 共通モジュールは以下の構造でメッセージを定義しなければならない:
   - `globalErrorMessages` という名前のexport const
   - `Record<Locale, GlobalErrorMessages>` 型の定義
   - 既存の4カテゴリ（network, boundary, validation, global）を含む
   - 日本語（ja）と英語（en）の2言語サポート

3. WHEN User AppまたはAdmin Appの`global-error.tsx`が読み込まれる THEN 各アプリは共通モジュールから`globalErrorMessages`をimportしなければならない

4. IF 既存のメッセージ構造（`messages[locale].network.timeout`等）が参照される THEN 共通モジュールは完全に同じ型定義とキー構造を提供しなければならない

5. WHILE リファクタリング実施中 THE 共通モジュールは既存の`as const`型アサーションを維持し、型安全性を保証しなければならない

### Requirement 2: 型定義の整備と型安全性の維持

**Objective**: TypeScript開発者として、メッセージ辞書の型定義を明確化し、コンパイル時の型チェックによってメッセージキーの誤使用を防止したい

#### Acceptance Criteria

1. WHERE `frontend/lib/global-error-messages.ts` THE 共通モジュールは`GlobalErrorMessages`型を明示的に定義しなければならない

2. WHEN 開発者が`globalErrorMessages[locale].network.timeout`のようにメッセージにアクセスする THEN TypeScriptコンパイラは型推論によって正しい文字列型を提供しなければならない

3. IF 存在しないメッセージキー（例: `globalErrorMessages.ja.invalid.key`）がアクセスされる THEN TypeScriptコンパイラはコンパイルエラーを発生させなければならない

4. WHERE `Locale` 型定義 THE 共通モジュールは`"ja" | "en"`の型エイリアスをexportしなければならない

5. WHEN User/Admin Appの`global-error.tsx`が共通モジュールをimportする THEN import文は型定義も含めて完全な型情報を取得しなければならない

### Requirement 3: User App/Admin Appの重複コード削除

**Objective**: コードベース管理者として、User AppとAdmin Appから重複する静的辞書定義を削除し、DRY原則を適用したクリーンなコードベースを維持したい

#### Acceptance Criteria

1. WHEN リファクタリングが完了する THEN `frontend/user-app/src/app/global-error.tsx`のローカル`messages`定義は完全に削除されなければならない

2. WHEN リファクタリングが完了する THEN `frontend/admin-app/src/app/global-error.tsx`のローカル`messages`定義は完全に削除されなければならない

3. WHERE User App `global-error.tsx` THE コンポーネントは`import { globalErrorMessages } from '@/../../lib/global-error-messages'`でメッセージをimportしなければならない

4. WHERE Admin App `global-error.tsx` THE コンポーネントは`import { globalErrorMessages } from '@/../../lib/global-error-messages'`でメッセージをimportしなければならない

5. WHEN 両アプリの`global-error.tsx`が共通モジュールを参照する THEN 既存の`const t = messages[locale]`パターンは`const t = globalErrorMessages[locale]`に変更されなければならない

6. IF 開発者が`detectLocale()`関数を使用する THEN 既存のロケール検出ロジックは変更されずに維持されなければならない

### Requirement 4: 既存機能の完全な動作保証

**Objective**: QAエンジニアとして、リファクタリング後も既存のGlobal Error Boundary機能が完全に動作し、エラーハンドリングに一切の影響がないことを保証したい

#### Acceptance Criteria

1. WHEN User AppでApiErrorが発生する THEN Global Error Boundaryは共通モジュールのメッセージを使用してエラー画面を表示しなければならない

2. WHEN Admin AppでNetworkErrorが発生する THEN Global Error Boundaryは共通モジュールのメッセージを使用してネットワークエラー画面を表示しなければならない

3. WHERE ロケールが日本語（ja）の場合 THE Error Boundaryは日本語メッセージ（`globalErrorMessages.ja.*`）を表示しなければならない

4. WHERE ロケールが英語（en）の場合 THE Error Boundaryは英語メッセージ（`globalErrorMessages.en.*`）を表示しなければならない

5. WHEN `reset()`関数が呼び出される THEN 既存のエラーリカバリー機能は影響を受けずに動作しなければならない

6. IF バリデーションエラーが発生する THEN `globalErrorMessages[locale].validation.title`が正しく表示されなければならない

7. WHEN Request ID（trace_id）が存在する THEN `globalErrorMessages[locale].boundary.requestId`ラベルとともに表示されなければならない

### Requirement 5: 既存テストの完全なpass

**Objective**: テストエンジニアとして、リファクタリング後も既存の全54テスト（User App 27テスト + Admin App 27テスト）が一切の修正なしにpassすることで、後方互換性を保証したい

#### Acceptance Criteria

1. WHEN User App `global-error.test.tsx`の全27テストが実行される THEN 全テストが成功しなければならない

2. WHEN Admin App `global-error.test.tsx`の全27テストが実行される THEN 全テストが成功しなければならない

3. WHERE テストケース「ApiError rendering」が実行される THE テストは共通モジュールのメッセージを正しく検証しなければならない

4. WHERE テストケース「NetworkError rendering」が実行される THE テストは共通モジュールのメッセージを正しく検証しなければならない

5. WHERE テストケース「Locale detection」が実行される THE テストは共通モジュールの日本語/英語メッセージを正しく検証しなければならない

6. IF テストが失敗する THEN テスト失敗は共通モジュール実装のバグであり、テストコード自体は修正されてはならない

7. WHEN `npm test`が実行される THEN User/Admin両アプリの全54テストが成功し、カバレッジが維持されなければならない

### Requirement 6: インポートパスの最適化と保守性向上

**Objective**: フロントエンドアーキテクトとして、モノレポ構成における共通モジュールのインポートパスを最適化し、将来的な構造変更に柔軟に対応できるようにしたい

#### Acceptance Criteria

1. WHERE User App `global-error.tsx` THE インポートパスは`@/../../lib/global-error-messages`の相対パスを使用しなければならない

2. WHERE Admin App `global-error.tsx` THE インポートパスは`@/../../lib/global-error-messages`の相対パスを使用しなければならない

3. IF 将来的に`frontend/types/`配下に移動する場合 THEN インポートパスは`@/../../types/global-error-messages`に変更可能でなければならない

4. WHEN 共通モジュールファイルが移動される THEN 影響を受けるのはUser/Admin両アプリの計2箇所のimport文のみでなければならない

5. WHERE `tsconfig.json`のpaths設定が存在する THE インポートパスはTypeScriptパスエイリアスと互換性を持たなければならない

### Requirement 7: ドキュメント化とメンテナンスガイドライン

**Objective**: プロジェクトマネージャーとして、リファクタリング内容を適切にドキュメント化し、将来の開発者がメッセージ辞書の拡張方法を理解できるようにしたい

#### Acceptance Criteria

1. WHERE `frontend/lib/global-error-messages.ts` THE ファイルの先頭にJSDocコメントでモジュールの目的と使用方法を記述しなければならない

2. WHEN 新しいメッセージカテゴリが追加される THEN 開発者は`GlobalErrorMessages`型定義を拡張し、日本語/英語両方のメッセージを提供しなければならない

3. IF メッセージ表現を変更する必要がある THEN 開発者は`frontend/lib/global-error-messages.ts`の該当箇所を編集するだけで全アプリに反映できなければならない

4. WHERE コードレビュー時 THE レビュアーは単一ファイル（`frontend/lib/global-error-messages.ts`）のみをチェックすればメッセージ変更を検証できなければならない

5. WHEN PR #119がマージされる THEN CLAUDE.mdの仕様ステータスが`completed`に更新されなければならない
