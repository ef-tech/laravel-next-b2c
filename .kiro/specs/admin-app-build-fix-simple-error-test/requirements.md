# Requirements Document

## はじめに

Admin Appの本番ビルド（`npm run build`）が2つの問題により失敗しています。本要件書は、これらの問題を修正し、Admin Appを本番ビルド可能な状態にすることを目的としています。

### ビジネス価値
- CI/CDパイプラインでのビルド成功を保証
- 開発環境と本番環境の挙動の一貫性確保
- Next.js App Routerのベストプラクティスに準拠
- i18n（多言語化）構造との一貫性維持

## GitHub Issue情報

**Issue**: [#124](https://github.com/ef-tech/laravel-next-b2c/issues/124) - Fix: Admin App本番ビルド失敗（simple-error-test/page.tsx構造問題）

### 発見された問題

1. **Next.js App Routerレイアウト違反**: `simple-error-test/page.tsx` がルートレイアウトなしでアプリルート直下に配置されている
2. **影響範囲**: 開発サーバーは正常動作するが、本番ビルドが失敗する

### 影響範囲
- ✅ 開発サーバー（`npm run dev`）は正常動作
- ❌ 本番ビルド（`npm run build`）が失敗
- ⚠️ CI/CDパイプラインでビルドステージが失敗
- ℹ️ mainブランチから存在する既存問題

## Requirements

### Requirement 1: Next.js App Routerレイアウト構造の修正

**Objective:** 開発者として、`simple-error-test` ページが既存のi18n構造と一貫性を持つように配置され、本番ビルドが成功することを期待する

#### Acceptance Criteria

1. **WHEN** Admin Appで本番ビルド（`npm run build`）を実行する **THEN** Admin App **SHALL** エラーなくビルドが完了する
2. **WHEN** `simple-error-test/page.tsx` を `[locale]/simple-error-test/page.tsx` に移動する **THEN** Admin App **SHALL** 既存の `[locale]/layout.tsx` を継承する
3. **WHERE** `simple-error-test` ページにアクセスする際 **THE** Admin App **SHALL** `/ja/simple-error-test` または `/en/simple-error-test` のURLでアクセス可能である
4. **WHEN** `[locale]/simple-error-test/page.tsx` が存在する **THEN** Admin App **SHALL** ルートレイアウト要件エラーを出さない
5. **WHEN** ファイル移動が完了した **THEN** Admin App **SHALL** 元の `src/app/simple-error-test/` ディレクトリを残さない

### Requirement 2: 本番ビルドの検証とテスト

**Objective:** 開発者として、修正後のAdmin Appが本番環境で正常に動作することを確認し、将来の同様の問題を防止することを期待する

#### Acceptance Criteria

1. **WHEN** 修正完了後に本番ビルド（`npm run build`）を実行する **THEN** Admin App **SHALL** ビルドプロセスが完了し、`.next/` ディレクトリにビルド成果物が生成される
2. **WHEN** 本番サーバー（`npm run start`）を起動する **THEN** Admin App **SHALL** ポート13002でアプリケーションが正常に起動する
3. **WHEN** `/ja/simple-error-test` にブラウザでアクセスする **THEN** Admin App **SHALL** Simple Error Testページが正常に表示される
4. **WHEN** `/en/simple-error-test` にブラウザでアクセスする **THEN** Admin App **SHALL** 英語ロケールでSimple Error Testページが正常に表示される
5. **WHEN** TypeScript型チェック（`npm run type-check`）を実行する **THEN** Admin App **SHALL** エラーを出さずに完了する
6. **WHEN** ESLint（`npm run lint`）を実行する **THEN** Admin App **SHALL** 新規導入されたエラーや警告を出さない

### Requirement 3: CI/CDパイプラインとの整合性

**Objective:** DevOpsエンジニアとして、修正後のコードがCI/CDパイプラインで確実にビルドされ、将来の本番ビルド失敗を防止する仕組みが整っていることを期待する

#### Acceptance Criteria

1. **WHEN** GitHub Actions CI/CDでPull Requestを作成する **THEN** CI/CD **SHALL** Admin Appの本番ビルドを自動実行する
2. **IF** Admin Appに関連するファイルが変更された場合 **THEN** CI/CD **SHALL** フロントエンドテストワークフローをトリガーする
3. **WHEN** CI/CDでビルドステップを実行する **THEN** CI/CD **SHALL** `npm run build` コマンドが成功することを検証する
4. **WHEN** CI/CDでTypeScript型チェックを実行する **THEN** CI/CD **SHALL** 型エラーが存在しないことを検証する
5. **WHERE** Issue #127（CI/CDビルドステップ追加）が実装された後 **THE** CI/CD **SHALL** 本番ビルドとTypeScript型チェックを自動実行する

### Requirement 4: ドキュメントとコードの一貫性

**Objective:** 開発者として、修正内容がドキュメントに反映され、他の開発者が同様の問題を理解し回避できることを期待する

#### Acceptance Criteria

1. **WHEN** `simple-error-test` ページの移動が完了する **THEN** Admin App **SHALL** READMEやドキュメントに新しいURLパス（`/ja/simple-error-test`）を記載する
2. **WHEN** 修正理由を記録する **THEN** 開発チーム **SHALL** コミットメッセージにIssue #124への参照を含める
3. **WHERE** プロジェクトのステアリングドキュメント（`.kiro/steering/`）を更新する際 **THE** 開発チーム **SHALL** Next.js App Routerのレイアウト要件とi18n統合のベストプラクティスを記載する

### Requirement 5: 後方互換性とリグレッションの防止

**Objective:** 開発者として、修正が既存機能を破壊せず、User Appや他のAdmin Appページに影響を与えないことを期待する

#### Acceptance Criteria

1. **WHEN** `simple-error-test` ページを移動する **THEN** Admin App **SHALL** 既存の `[locale]/` 配下のページ（`page.tsx`、`test-error/page.tsx`等）に影響を与えない
2. **WHEN** Admin Appの全Jestテストを実行する **THEN** Admin App **SHALL** 全テストがパスする（修正前のテストカバレッジを維持）
3. **WHEN** User Appの本番ビルドを実行する **THEN** User App **SHALL** ビルドが成功する（Admin Appの修正が影響しない）
4. **WHERE** 同様のファイル構造がUser Appに存在する場合 **THE** User App **SHALL** 同じレイアウト要件違反を持たない

## 技術スタック

**Frontend**: Next.js 15.5, React 19, TypeScript, App Router
**Tools**: npm, ESLint, TypeScript Compiler
**Infrastructure**: なし（フロントエンドのみの修正）

## プロジェクト構造

### 修正前
```
frontend/admin-app/src/app/
├── [locale]/
│   ├── layout.tsx           # locale用レイアウト（存在）
│   └── page.tsx             # ホームページ
├── simple-error-test/
│   └── page.tsx             # ❌ レイアウトなし（問題箇所）
├── global-error.tsx
└── layout.tsx               # ルートレイアウト（存在）
```

### 修正後
```
frontend/admin-app/src/app/
├── [locale]/
│   ├── layout.tsx           # locale用レイアウト（存在）
│   ├── page.tsx             # ホームページ
│   └── simple-error-test/   # ✅ [locale]配下に移動
│       └── page.tsx         # ✅ locale layoutを継承
├── global-error.tsx
└── layout.tsx               # ルートレイアウト（存在）
```

## 検証方法

### ローカル検証
```bash
cd frontend/admin-app

# 1. TypeScript型チェック
npm run type-check

# 2. ESLint
npm run lint

# 3. 本番ビルド
npm run build

# 4. 本番サーバー起動
npm run start

# 5. ブラウザで確認
# http://localhost:13002/ja/simple-error-test
# http://localhost:13002/en/simple-error-test
```

### CI/CD検証
```bash
# GitHub Actions自動実行
git add .
git commit -m "Fix: Admin App本番ビルド失敗修正（#124）"
git push

# Pull Request作成後、CI/CDワークフローが自動実行されることを確認
```
