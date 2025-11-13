# Requirements Document

## GitHub Issue Information

**Issue**: [#126](https://github.com/ef-tech/laravel-next-b2c/issues/126) - Refactor: フロントエンド共通tsconfig.base.json導入（TypeScript設定の重複削減）
**Labels**: なし
**Milestone**: なし
**Assignees**: なし

---

## Introduction

フロントエンドモノレポ構成において、User App/Admin Appの`tsconfig.json`に重複している設定を`frontend/tsconfig.base.json`に集約し、保守性を向上させるリファクタリングを実施する。

PR #125で`@shared/*`パスエイリアスを導入した結果、両アプリのTypeScript設定がほぼ同一になり、DRY原則違反の状態が発生している。本要件では、TypeScriptの`extends`機能を活用して共通設定を基盤ファイルに集約し、各アプリはアプリ固有の設定（パスエイリアス、include指定）のみを保持する構成に変更する。

これにより、将来的なTypeScript設定変更時の保守性向上、User App/Admin App間の設定一貫性保証、3つ目のアプリ追加時のスケーラビリティ確保を実現する。

---

## Requirements

### Requirement 1: 共通TypeScript設定基盤の作成

**Objective:** As a フロントエンド開発者, I want モノレポ共通のTypeScript設定ファイル（tsconfig.base.json）を作成する, so that 複数アプリ間で設定を共有し保守性を向上できる

#### Acceptance Criteria

1. WHEN `frontend/tsconfig.base.json` を作成する THEN TypeScript設定システム SHALL 以下の共通compilerOptionsを含む:
   - `target`: "ES2017"
   - `lib`: ["dom", "dom.iterable", "esnext"]
   - `allowJs`: true
   - `skipLibCheck`: true
   - `strict`: true
   - `noEmit`: true
   - `esModuleInterop`: true
   - `module`: "esnext"
   - `moduleResolution`: "bundler"
   - `resolveJsonModule`: true
   - `isolatedModules`: true
   - `jsx`: "preserve"
   - `incremental`: true
   - `plugins`: [{"name": "next"}]

2. WHEN `frontend/tsconfig.base.json` を作成する THEN TypeScript設定システム SHALL `exclude`: ["node_modules"] を含む

3. WHERE `frontend/tsconfig.base.json` に設定を記述する THE TypeScript設定システム SHALL パスエイリアス設定（`paths`）を含まない（アプリ固有設定として各アプリに配置）

4. WHEN `frontend/tsconfig.base.json` が存在する THEN TypeScript設定システム SHALL JSON形式で有効な構文を保持する

### Requirement 2: User Appの設定リファクタリング

**Objective:** As a フロントエンド開発者, I want User Appのtsconfig.jsonを共通設定を継承する形にリファクタリングする, so that 設定重複を削減しアプリ固有設定のみを保持できる

#### Acceptance Criteria

1. WHEN `frontend/user-app/tsconfig.json` をリファクタリングする THEN User App設定 SHALL `"extends": "../tsconfig.base.json"` を含む

2. WHEN User App設定がtsconfig.base.jsonを継承する THEN User App設定 SHALL `compilerOptions.paths` でアプリ固有のパスエイリアスのみを定義する:
   - `"@/*": ["./src/*"]` (User App内部のsrcディレクトリ)
   - `"@shared/*": ["../lib/*"]` (モノレポ共通ライブラリ)

3. WHEN User App設定がtsconfig.base.jsonを継承する THEN User App設定 SHALL `include` でアプリ固有のファイル指定のみを保持する:
   - ["next-env.d.ts", "**/*.ts", "**/*.tsx", ".next/types/**/*.ts"]

4. WHEN User App設定がリファクタリング完了する THEN User App設定 SHALL 共通compilerOptions（target, lib, strict等）を含まない（tsconfig.base.jsonから継承）

5. WHEN User App設定がリファクタリング完了する THEN User App設定 SHALL JSON形式で有効な構文を保持する

### Requirement 3: Admin Appの設定リファクタリング

**Objective:** As a フロントエンド開発者, I want Admin Appのtsconfig.jsonを共通設定を継承する形にリファクタリングする, so that User Appと同様に設定重複を削減できる

#### Acceptance Criteria

1. WHEN `frontend/admin-app/tsconfig.json` をリファクタリングする THEN Admin App設定 SHALL `"extends": "../tsconfig.base.json"` を含む

2. WHEN Admin App設定がtsconfig.base.jsonを継承する THEN Admin App設定 SHALL `compilerOptions.paths` でアプリ固有のパスエイリアスのみを定義する:
   - `"@/*": ["./src/*"]` (Admin App内部のsrcディレクトリ)
   - `"@shared/*": ["../lib/*"]` (モノレポ共通ライブラリ)

3. WHEN Admin App設定がtsconfig.base.jsonを継承する THEN Admin App設定 SHALL `include` でアプリ固有のファイル指定のみを保持する:
   - ["next-env.d.ts", "**/*.ts", "**/*.tsx", ".next/types/**/*.ts"]

4. WHEN Admin App設定がリファクタリング完了する THEN Admin App設定 SHALL 共通compilerOptions（target, lib, strict等）を含まない（tsconfig.base.jsonから継承）

5. WHEN Admin App設定がリファクタリング完了する THEN Admin App設定 SHALL JSON形式で有効な構文を保持する

6. WHEN Admin App設定とUser App設定を比較する THEN 両設定 SHALL `extends`・`paths`・`include`の構造が同一である（値のみアプリ固有）

### Requirement 4: TypeScript型チェックの動作保証

**Objective:** As a フロントエンド開発者, I want リファクタリング後もTypeScript型チェックが正常に動作する, so that 既存の型安全性を維持できる

#### Acceptance Criteria

1. WHEN `npm run type-check` をUser Appで実行する THEN TypeScriptコンパイラ SHALL エラーなく完了する

2. WHEN `npm run type-check` をAdmin Appで実行する THEN TypeScriptコンパイラ SHALL エラーなく完了する

3. WHEN TypeScript型チェックを実行する THEN TypeScriptコンパイラ SHALL `@/*` パスエイリアスを正しく解決する

4. WHEN TypeScript型チェックを実行する THEN TypeScriptコンパイラ SHALL `@shared/*` パスエイリアスを正しく解決する

5. WHEN TypeScript型チェックを実行する THEN TypeScriptコンパイラ SHALL tsconfig.base.jsonの設定を正しく継承する

### Requirement 5: IDE型補完の動作保証

**Objective:** As a フロントエンド開発者, I want VSCodeでの型補完が正常に動作する, so that 開発体験を維持できる

#### Acceptance Criteria

1. WHEN VSCodeでUser Appのファイルを開く THEN VSCode TypeScript Language Service SHALL `@/*` パスエイリアスの型補完を提供する

2. WHEN VSCodeでUser Appのファイルを開く THEN VSCode TypeScript Language Service SHALL `@shared/*` パスエイリアスの型補完を提供する

3. WHEN VSCodeでAdmin Appのファイルを開く THEN VSCode TypeScript Language Service SHALL `@/*` パスエイリアスの型補完を提供する

4. WHEN VSCodeでAdmin Appのファイルを開く THEN VSCode TypeScript Language Service SHALL `@shared/*` パスエイリアスの型補完を提供する

5. WHEN VSCodeでtsconfig.json設定を読み込む THEN VSCode TypeScript Language Service SHALL tsconfig.base.jsonの継承設定を認識する

6. WHEN VSCodeで型エラーを表示する THEN VSCode TypeScript Language Service SHALL リファクタリング前と同等の型エラー検出精度を保持する

### Requirement 6: Jestテストの動作保証

**Objective:** As a フロントエンド開発者, I want リファクタリング後も既存のJestテストが正常に動作する, so that テストカバレッジを維持できる

#### Acceptance Criteria

1. WHEN `npm test` をUser Appで実行する THEN Jestテストランナー SHALL 全テストをパスする

2. WHEN `npm test` をAdmin Appで実行する THEN Jestテストランナー SHALL 全テストをパスする

3. WHEN Jestテストを実行する THEN Jestテストランナー SHALL `@/*` パスエイリアスを正しく解決する

4. WHEN Jestテストを実行する THEN Jestテストランナー SHALL `@shared/*` パスエイリアスを正しく解決する

5. WHEN Jestテストを実行する THEN Jestテストランナー SHALL リファクタリング前と同等のテスト実行時間を保持する（±10%以内）

### Requirement 7: Next.jsビルドの動作保証

**Objective:** As a フロントエンド開発者, I want リファクタリング後もNext.jsビルドが正常に完了する, so that 本番環境へのデプロイ可能性を維持できる

#### Acceptance Criteria

1. WHEN `npm run build` をUser Appで実行する THEN Next.jsビルドシステム SHALL エラーなく完了する

2. WHEN `npm run build` をAdmin Appで実行する THEN Next.jsビルドシステム SHALL エラーなく完了する

3. WHEN Next.jsビルドを実行する THEN Next.jsビルドシステム SHALL `.next/types/**/*.ts` の型定義ファイルを正しく認識する

4. WHEN Next.jsビルドを実行する THEN Next.jsビルドシステム SHALL tsconfig.base.jsonの設定を正しく適用する

5. WHEN Next.jsビルドを実行する THEN Next.jsビルドシステム SHALL リファクタリング前と同等のビルド時間を保持する（±10%以内）

6. WHEN Next.jsビルド成果物を確認する THEN Next.jsビルドシステム SHALL `.next/standalone/` ディレクトリを正しく生成する（Docker本番ビルド対応）

### Requirement 8: スケーラビリティの確保

**Objective:** As a フロントエンド開発者, I want 将来的に3つ目のアプリを追加する際にも共通設定を再利用できる, so that モノレポの拡張性を確保できる

#### Acceptance Criteria

1. WHEN 新しいNext.jsアプリを `frontend/new-app/` に追加する THEN 新アプリ SHALL `"extends": "../tsconfig.base.json"` で共通設定を継承できる

2. WHEN 新しいアプリがtsconfig.base.jsonを継承する THEN 新アプリ SHALL アプリ固有のパスエイリアス設定のみを追加すれば動作する

3. WHEN tsconfig.base.jsonの設定を変更する THEN 設定変更 SHALL 既存の全アプリ（User App, Admin App）と新アプリに自動的に反映される

4. WHERE tsconfig.base.jsonに共通設定を配置する THE TypeScript設定システム SHALL 各アプリで個別に設定をオーバーライド可能である（必要に応じて）

### Requirement 9: 保守性の向上

**Objective:** As a フロントエンド開発者, I want TypeScript設定の保守性を向上させる, so that 設定変更時の影響範囲を最小化できる

#### Acceptance Criteria

1. WHEN TypeScript共通設定を変更する THEN 開発者 SHALL `frontend/tsconfig.base.json` のみを編集すればよい

2. WHEN User AppまたはAdmin Appの設定ファイルを確認する THEN 開発者 SHALL アプリ固有設定（paths, include）のみが記述されていることを理解できる

3. WHEN 新しい開発者がプロジェクトに参加する THEN 新開発者 SHALL tsconfig.base.jsonを参照することでプロジェクト共通のTypeScript設定を理解できる

4. WHERE 複数アプリで同一の設定変更が必要な場合 THE TypeScript設定システム SHALL tsconfig.base.json 1箇所の変更で全アプリに反映される

5. WHEN TypeScript設定の変更履歴を確認する THEN Git履歴 SHALL 共通設定の変更とアプリ固有設定の変更が明確に分離される

### Requirement 10: 後方互換性の保証

**Objective:** As a フロントエンド開発者, I want リファクタリングが既存のワークフローを破壊しない, so that 開発効率を維持できる

#### Acceptance Criteria

1. WHEN リファクタリングを完了する THEN TypeScript設定システム SHALL リファクタリング前と同じTypeScriptバージョンで動作する

2. WHEN リファクタリングを完了する THEN TypeScript設定システム SHALL リファクタリング前と同じNext.jsバージョンで動作する

3. WHEN 既存のnpmスクリプト（`npm run dev`, `npm run build`, `npm test`, `npm run type-check`）を実行する THEN npmスクリプト SHALL リファクタリング前と同じ動作をする

4. WHEN CI/CDパイプラインを実行する THEN GitHub Actions SHALL リファクタリング前と同じ成功率でビルド・テストを完了する

5. WHEN 既存のコードベースを変更しない THEN TypeScriptコンパイラ SHALL リファクタリング前と同じ型エラーを検出する

---

## Background Context

### 現在の重複状態

**frontend/user-app/tsconfig.json** と **frontend/admin-app/tsconfig.json** は、`paths`設定を除いて完全に同一の内容を持っている：

- `compilerOptions`の15個の設定項目が重複
- `include`の4つの指定が重複
- `exclude`の指定が重複

この重複により、以下の問題が発生：
- 設定変更時に2ファイル同時修正が必要（保守性低下）
- 設定の不整合リスク（User AppとAdmin Appで設定が乖離する可能性）
- 3つ目のアプリ追加時にさらに重複が増加（スケーラビリティの欠如）

### 解決アプローチ

TypeScriptの`extends`機能を活用して、共通設定を`frontend/tsconfig.base.json`に集約：

1. **共通設定の抽出**: target, lib, strict等の15個の共通compilerOptionsをtsconfig.base.jsonに移動
2. **アプリ固有設定の保持**: paths（`@/*`, `@shared/*`）とincludeは各アプリに残す
3. **継承の適用**: User App/Admin Appで`"extends": "../tsconfig.base.json"`を使用

これにより、DRY原則を適用し、保守性・一貫性・スケーラビリティを向上させる。

### 参考資料

- [TypeScript公式ドキュメント: Extending tsconfig](https://www.typescriptlang.org/tsconfig#extends)
- PR #125: @shared/*パスエイリアス導入
- BtoCテンプレートとしてのベストプラクティス改善（Codexレビューコメント）
