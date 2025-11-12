# Requirements Document

## Introduction

本仕様は、User AppとAdmin Appで異なっている`validLocale`型定義を統一することで、コードベース全体の一貫性を向上させることを目的とする。PR #129のレビューコメント（https://github.com/ef-tech/laravel-next-b2c/pull/129#issuecomment-3512495735）で指摘された、next-intl公式型定義（`RequestConfig.locale: string`）との整合性を確保し、型安全性を保ちつつフレームワークとの互換性を維持する。

### 背景
- **User App**: `validLocale`型を厳密な`Locale`型（`'ja' | 'en'`）として実装（2025-11-07）
- **Admin App**: `validLocale`型を`string`型として実装（2025-11-11、next-intl公式型定義準拠）
- **問題**: 両アプリで型定義が異なり、将来のリファクタリング時に違和感が出る可能性

### ビジネス価値
- コードベース全体の一貫性向上によるメンテナンス性の向上
- next-intl公式型定義準拠によるライブラリ更新時の互換性確保
- 型定義の標準化による開発者体験の向上

---

## Requirements

### Requirement 1: 型定義統一化
**Objective:** As a フロントエンド開発者, I want User AppとAdmin Appの`validLocale`型定義を統一する, so that コードベース全体の一貫性を確保し、メンテナンス性を向上できる

#### Acceptance Criteria

1. WHEN User Appの`i18n.ts`を変更する THEN User Appの`validLocale`型定義 SHALL `string`型に変更される
2. WHEN User Appの`validLocale`型定義を変更する THEN Admin Appの`validLocale`型定義（`string`型） SHALL 維持される
3. WHEN 両アプリの`validLocale`型定義を確認する THEN 両アプリの型定義 SHALL 同一（`string`型）である
4. WHERE `frontend/user-app/src/i18n.ts` THE User App SHALL 以下のコード変更を反映する:
   ```typescript
   // Before: const validLocale: Locale = ...
   // After: const validLocale: string = ...
   ```
5. WHERE `frontend/admin-app/src/i18n.ts` THE Admin App SHALL 既存の`string`型実装を維持する

---

### Requirement 2: next-intl公式型定義準拠
**Objective:** As a フロントエンド開発者, I want next-intl公式型定義（`RequestConfig.locale: string`）に準拠した実装にする, so that 将来のライブラリ更新時の互換性を確保できる

#### Acceptance Criteria

1. WHEN `validLocale`型定義を決定する THEN `validLocale`型 SHALL next-intl公式型定義の`RequestConfig.locale`型（`string`）と整合する
2. WHERE next-intl v4.4.0のRequestConfig型定義 THE User/Admin App SHALL `string`型を外部境界（routing、middleware、request config）で使用する
3. IF next-intl公式ドキュメントが`string`型を推奨する THEN User/Admin App SHALL `string`型実装を採用する

---

### Requirement 3: TypeScript型チェック検証
**Objective:** As a フロントエンド開発者, I want TypeScript型チェックを実行して型推論エラーがないことを確認する, so that 型安全性を保証できる

#### Acceptance Criteria

1. WHEN `npm run type-check`をワークスペース全体で実行する THEN TypeScript型チェック SHALL エラーなくpassする
2. WHEN Admin Appで`cd frontend/admin-app && npm run type-check`を実行する THEN Admin AppのTypeScript型チェック SHALL エラーなくpassする
3. WHEN User Appで`cd frontend/user-app && npm run type-check`を実行する THEN User AppのTypeScript型チェック SHALL エラーなくpassする
4. WHERE 型推論エラーが発生した場合 THE 実装 SHALL 型定義を修正して再検証する

---

### Requirement 4: 本番ビルド検証
**Objective:** As a フロントエンド開発者, I want 本番ビルドを実行してビルドエラーがないことを確認する, so that 実行時の品質を保証できる

#### Acceptance Criteria

1. WHEN Admin Appで`cd frontend/admin-app && npm run build`を実行する THEN Admin Appの本番ビルド SHALL エラーなく成功する
2. WHEN User Appで`cd frontend/user-app && npm run build`を実行する THEN User Appの本番ビルド SHALL エラーなく成功する
3. WHERE ビルドエラーが発生した場合 THE 実装 SHALL エラー原因を修正して再ビルドする
4. WHEN ビルド時間を測定する THEN ビルド時間 SHALL 変更前と同等（各アプリ1-3分）である

---

### Requirement 5: 単体テスト検証
**Objective:** As a フロントエンド開発者, I want 単体テストを実行してロケール検証ロジックが正常動作することを確認する, so that 機能の正確性を保証できる

#### Acceptance Criteria

1. WHEN `npm test`を実行する THEN フロントエンド単体テスト SHALL 全pass（エラーなし）する
2. WHEN `npm run test:admin`を実行する THEN Admin App単体テスト SHALL 全pass（エラーなし）する
3. WHEN `npm run test:user`を実行する THEN User App単体テスト SHALL 全pass（エラーなし）する
4. WHERE i18n設定ロジック、ロケール検証、エラーメッセージ多言語化のテストケース THE テスト SHALL 正常動作を検証する

---

### Requirement 6: E2Eテスト検証
**Objective:** As a フロントエンド開発者, I want E2Eテストを実行してロケール切替動作が正常であることを確認する, so that エンドツーエンドの機能品質を保証できる

#### Acceptance Criteria

1. WHEN Docker環境を起動する（`make dev`） THEN 全サービス（user-app: 13001、admin-app: 13002、laravel-api: 13000） SHALL healthyステータスになる
2. WHEN デフォルトロケール（ja）でアクセスする THEN アプリ SHALL 日本語コンテンツを表示する
3. WHEN URL `/en/...`でアクセスする THEN アプリ SHALL 英語コンテンツを表示する
4. WHEN 不正ロケール `/invalid/...`でアクセスする THEN アプリ SHALL デフォルトロケール（ja）にフォールバックして日本語コンテンツを表示する
5. WHERE ロケール切替動作が異常な場合 THE 実装 SHALL ロケール解決ロジックを修正する

---

### Requirement 7: CI/CD自動検証
**Objective:** As a フロントエンド開発者, I want GitHub ActionsでCI/CD自動検証を実行する, so that PR時の品質を自動保証できる

#### Acceptance Criteria

1. WHEN PRを作成する（`git checkout -b fix/unify-validlocale-type`） THEN GitHub Actions SHALL `.github/workflows/frontend-test.yml`ワークフローを自動実行する
2. WHEN GitHub Actionsが実行される THEN `lint / user-app (20.x)`ジョブ SHALL Greenステータス（成功）になる
3. WHEN GitHub Actionsが実行される THEN `test / user-app (20.x)`ジョブ SHALL Greenステータス（成功）になる
4. WHEN GitHub Actionsが実行される THEN `build / user-app (20.x)`ジョブ SHALL Greenステータス（成功）になる
5. WHERE CI/CDジョブが失敗した場合 THE 実装 SHALL 失敗原因を修正して再実行する

---

### Requirement 8: 共通i18n設定確認
**Objective:** As a フロントエンド開発者, I want 共通i18n設定（`frontend/lib/i18n-config.ts`）を確認する, so that 共通設定が変更されていないことを保証できる

#### Acceptance Criteria

1. WHEN `frontend/lib/i18n-config.ts`を確認する THEN 共通i18n設定 SHALL 変更されていない（既存のまま）である
2. WHERE `frontend/lib/i18n-config.ts`の内容 THE 以下の定義 SHALL 維持される:
   ```typescript
   export const LOCALES = ['ja', 'en'] as const;
   export type Locale = (typeof LOCALES)[number]; // 'ja' | 'en'
   ```
3. IF 共通i18n設定が変更されている THEN 実装 SHALL 共通設定を元に戻す

---

### Requirement 9: Admin Appとの実装一貫性確認
**Objective:** As a フロントエンド開発者, I want User AppとAdmin Appの`i18n.ts`実装を比較する, so that 両アプリの実装が一貫していることを確認できる

#### Acceptance Criteria

1. WHEN User App `frontend/user-app/src/i18n.ts`を確認する THEN `validLocale`型定義 SHALL `string`型である
2. WHEN Admin App `frontend/admin-app/src/i18n.ts`を確認する THEN `validLocale`型定義 SHALL `string`型である
3. WHEN 両アプリの`i18n.ts`実装を比較する THEN `validLocale`型定義、ロケール検証ロジック SHALL 同一パターンで実装されている
4. WHERE 実装パターンに差異がある場合 THE 実装 SHALL 一貫性を保つように調整する

---

### Requirement 10: PR #129フィードバック報告
**Objective:** As a フロントエンド開発者, I want PR #129へ対応完了をフィードバック報告する, so that レビュー指摘事項が解決されたことを明示できる

#### Acceptance Criteria

1. WHEN 全検証（型チェック、ビルド、テスト、CI/CD）が成功する THEN PR #129へフィードバックコメント SHALL 投稿される
2. WHERE フィードバックコメント THE 以下の情報 SHALL 含まれる:
   - 対応完了の報告
   - User App `validLocale`型定義を`string`型に統一した旨
   - 全検証結果（型チェック、ビルド、テスト、CI/CD）のサマリー
   - 関連PRへのリンク（もし新規PR作成した場合）
3. IF レビュアーから追加質問や指摘がある THEN 実装者 SHALL 速やかに対応する

---

## Out of Scope

以下の項目は本仕様の範囲外とする:

1. **共通i18n設定の変更**: `frontend/lib/i18n-config.ts`の変更は行わない（共通設定として既に確立済み）
2. **他のi18n関連ファイルの変更**: `i18n.ts`以外のi18n関連ファイル（翻訳ファイル、メッセージファイル等）の変更は行わない
3. **新規機能追加**: ロケール検証の強化、バリデーション関数追加等の新規機能は本仕様に含まない（将来的対応として別Issue化）
4. **バックエンド側の変更**: フロントエンド専用タスクのため、バックエンド（Laravel API）の変更は行わない

---

## Assumptions and Constraints

### Assumptions（前提条件）

1. **Admin App実装の正当性**: Admin Appの`string`型実装がnext-intl公式型定義準拠のベストプラクティスである
2. **実行時動作不変**: `validLocale`は常に`'ja' | 'en'`の実行時値を持つため、型定義変更は実行時動作に影響しない
3. **ビルド成功実績**: Admin Appで本番ビルド成功が確認済みであり、User Appでも同様の型定義でビルド成功する
4. **CI/CD環境整備**: `.github/workflows/frontend-test.yml`が既に整備されており、PR時に自動実行される

### Constraints（制約条件）

1. **next-intl v4.4.0準拠**: next-intl v4.4.0の公式型定義に準拠した実装を行う
2. **TypeScript ^5対応**: TypeScript ^5の型システムを前提とする
3. **Next.js 15.5.4対応**: Next.js 15.5.4のApp Routerアーキテクチャに対応する
4. **モノレポ構成**: User App/Admin Appがモノレポ構成であることを前提とする
5. **破壊的変更禁止**: 既存のi18n動作を破壊しない、内部実装の調整のみ行う

---

## Success Criteria（成功基準）

本仕様の成功基準を以下に定義する:

### 機能成功基準

1. ✅ User Appの`validLocale`型定義が`Locale` → `string`型に変更完了
2. ✅ Admin Appの`validLocale`型定義が`string`型のまま維持
3. ✅ TypeScript型チェックが全プロジェクト（ワークスペース、Admin App、User App）でpass
4. ✅ 本番ビルドがAdmin App/User App両方で成功
5. ✅ 単体テストが全pass（`npm test`, `npm run test:admin`, `npm run test:user`）
6. ✅ E2Eテストでロケール切替動作が正常（デフォルトロケール、en、invalid）

### 品質成功基準

1. ✅ GitHub Actions `Frontend Tests`ワークフローが全job（lint, test, build）でGreen
2. ✅ コードレビュー完了（Admin App実装との整合性確認、レビュアー承認1名以上）
3. ✅ リグレッションテスト実行（機能検証: ロケール切替、エラーメッセージ多言語化、型安全性）
4. ✅ パフォーマンス検証（ビルド時間が変更前と同等、実行時パフォーマンス劣化なし）

### ドキュメント成功基準

1. ✅ PR #129 へのフィードバック報告完了（対応完了、検証結果サマリー）
2. ✅ READMEやドキュメントの更新（必要に応じて、i18n型定義の標準化について記載）

### 承認成功基準

1. ✅ レビュアー承認（1名以上）
2. ✅ CI/CDパイプライン全pass
3. ✅ マージ前の最終動作確認完了
4. ✅ mainブランチへのマージ完了

---

## References（参考資料）

### Primary Sources

- **next-intl公式ドキュメント**: [App Router Setup](https://next-intl-docs.vercel.app/docs/getting-started/app-router)
- **Next.js 15.5 TypeScript設定**: [TypeScript Configuration](https://nextjs.org/docs/app/api-reference/config/typescript)
- **PR #129 レビューコメント**: https://github.com/ef-tech/laravel-next-b2c/pull/129#issuecomment-3512495735

### Related Commits

- **User App実装**: 6ee1087（`Locale`型実装、2025-11-07）
- **Admin App実装**: 810c999（`string`型実装、2025-11-11）

### Project Structure

- **共通i18n設定**: `frontend/lib/i18n-config.ts`
- **User App実装**: `frontend/user-app/src/i18n.ts`（変更対象）
- **Admin App実装**: `frontend/admin-app/src/i18n.ts`（参照実装）
- **CI/CD設定**: `.github/workflows/frontend-test.yml`

### Technology Stack

- **TypeScript**: ^5
- **Next.js**: 15.5.4
- **next-intl**: ^4.4.0
- **Node.js**: 20.x
- **Docker**: E2Eテスト環境
- **GitHub Actions**: CI/CD自動検証

---

## Risk Analysis（リスク分析）

### リスクと緩和策

| リスク | 発生確率 | 影響度 | 緩和策 |
|--------|----------|--------|--------|
| 型推論エラー | 低 | 中 | TypeScript型チェック実行、CI/CD自動検証、Admin App実装パターン踏襲 |
| ビルド失敗 | 極低 | 高 | 本番ビルド検証（ローカル + CI/CD）、Admin Appで既に検証済み |
| i18nロケール解決失敗 | 極低 | 高 | E2Eテストでロケール切替動作確認、実行時値不変のため影響なし |
| パフォーマンス劣化 | なし | - | 型定義のみの変更のため影響なし |
| CI/CD失敗 | 低 | 中 | GitHub Actionsログ分析、失敗原因修正、再実行 |

### 影響範囲

| 項目 | 影響度 | 説明 |
|------|--------|------|
| **実行時動作** | ❌ なし | `validLocale`は常に`'ja' | 'en'`（実行時値不変） |
| **型チェック** | ⚠️ 軽微 | User Appで型定義が変更されるが、Admin Appで既に検証済み |
| **ビルドエラー** | ❌ なし | Admin Appで本番ビルド成功確認済み |
| **破壊的変更** | ❌ なし | API変更なし、内部実装の調整のみ |
| **既存i18n機能** | ❌ なし | ロケール切替、翻訳、エラーメッセージ多言語化は全て正常動作 |

---

## Notes（補足）

### 実装方針

本仕様では**Option A: Admin Appの`string`型に統一**を採用する。理由は以下の通り:

1. **next-intl公式型定義準拠**: `RequestConfig.locale: string`型との整合性
2. **Admin App実装の検証済み**: 後から検証された新しい方針であり、本番ビルド成功確認済み
3. **将来のライブラリ更新対応**: next-intlバージョンアップ時の互換性確保
4. **外部境界設計**: routing、middleware、request configでは`string`型を使用するベストプラクティス

### 将来的対応（Out of Scope）

以下の機能強化は本仕様の範囲外とし、将来的に別Issueとして対応する:

1. **共通i18nモジュール強化**:
   ```typescript
   // frontend/lib/i18n-config.ts
   export const isKnownLocale = (l: string): l is Locale => ...
   export const ensureKnownLocale = (l: string, fallback: Locale): Locale => ...
   export const normalizeLocale = (l: string | undefined, fallback: Locale): string => ...
   ```

2. **内部型安全性確保**: 外部境界では`string`型、内部では`AppLocale`（`'ja' | 'en'`）に変換する設計パターン

### PR #129マージ後の対応

この変更は軽微な型定義の統一化であり、実行時の動作には影響しない。PR #129マージ後に対応することを推奨する。

---

**統一方針確立**: Admin Appの`string`型実装をnext-intl公式型定義準拠のベストプラクティスとして確立し、User Appも同様の実装に統一することで、コードベース全体の一貫性を向上させる。
