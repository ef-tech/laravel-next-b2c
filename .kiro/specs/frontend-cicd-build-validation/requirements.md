# 要件定義書

## GitHub Issue情報

**Issue**: [#127](https://github.com/ef-tech/laravel-next-b2c/issues/127) - CI/CD: フロントエンド本番ビルドステップ欠落（TypeScript型チェック・npm run build未実行）
**Labels**: enhancement, CI/CD, priority: high, frontend
**Milestone**: -
**Assignees**: -

---

## イントロダクション

現在のフロントエンドCI/CD（`.github/workflows/frontend-test.yml`）には、TypeScript型チェックと本番ビルド検証が含まれていません。Issue #124（Admin App本番ビルド失敗）の調査中に、`simple-error-test/page.tsx`のレイアウト構造問題がCI/CDで検知されず、ローカル`npm run build`で初めてエラーが発見されました。

この問題により、本番ビルドが失敗する可能性のある変更がマージされるリスクが存在します。本仕様では、**TypeScript型チェック**と**本番ビルド検証**をCI/CDパイプラインに追加することで、PR時点でビルドエラーを早期検出し、本番環境デプロイ前の品質保証を強化します。

### ビジネス価値
- **早期エラー検出**: ビルドエラーをPR時に検知し、Issue #124のような本番デプロイ直前での発見を防止
- **型安全性保証**: TypeScript型エラーの事前防止により、実行時エラーのリスク削減
- **開発効率向上**: CI/CDでの自動検証により、レビュアーの負担軽減とレビュー品質向上
- **本番環境の安定性**: デプロイ前の包括的な検証により、本番環境への不良コード混入防止

---

## 要件

### 要件1: TypeScript型チェック統合

**目的**: フロントエンドエンジニアとして、PR作成時にTypeScript型エラーを自動検知したい。これにより、型安全性が保証され、実行時エラーのリスクを最小化できる。

#### 受入基準

1. WHEN 開発者がPull Requestを作成または更新した場合 THEN frontend-test.ymlワークフローは Admin AppとUser App両方に対してTypeScript型チェック（`npm run type-check`）を実行するものとする

2. WHEN TypeScript型チェックステップが実行される場合 THEN システムは`npx tsc --noEmit`コマンドにより型エラーのみを検証し、JavaScriptファイルを生成しないものとする

3. IF TypeScript型エラーが検出された場合 THEN CI/CDパイプラインは失敗ステータスを返し、PRマージをブロックするものとする

4. WHEN TypeScript型チェックが成功した場合 THEN システムは成功ステータスをGitHub PRに報告し、次のステップに進むものとする

5. WHERE Admin AppまたはUser AppのTypeScriptファイル（`*.ts`, `*.tsx`）が変更された場合 THE frontend-test.ymlワークフローは自動的にトリガーされるものとする

6. WHEN TypeScript型チェックステップが実行される場合 THEN システムは各アプリディレクトリ（`frontend/admin-app`, `frontend/user-app`）で独立して型チェックを実行するものとする

---

### 要件2: 本番ビルド検証統合

**目的**: DevOpsエンジニアとして、PR作成時に本番ビルドが成功することを自動検証したい。これにより、ビルドエラーが本番デプロイ直前に発見される事態を防止できる。

#### 受入基準

1. WHEN 開発者がPull Requestを作成または更新した場合 THEN frontend-test.ymlワークフローは Admin AppとUser App両方に対して本番ビルド（`npm run build`）を実行するものとする

2. WHEN 本番ビルドステップが実行される場合 THEN システムは`CI=true`環境変数を設定し、CI環境に最適化されたビルドプロセスを実行するものとする

3. IF 本番ビルドが失敗した場合（ビルドエラー、レイアウト構造問題、環境変数不足等） THEN CI/CDパイプラインは失敗ステータスを返し、PRマージをブロックするものとする

4. WHEN 本番ビルドが成功した場合 THEN システムは成功ステータスをGitHub PRに報告するものとする

5. WHERE Next.jsアプリケーションコード（`frontend/admin-app/**`, `frontend/user-app/**`）が変更された場合 THE frontend-test.ymlワークフローは自動的にトリガーされ、本番ビルド検証を実行するものとする

6. WHEN 本番ビルドステップが実行される場合 THEN システムはNext.js本番最適化（minification, tree-shaking, output file tracing）を含む完全なビルドプロセスを実行するものとする

---

### 要件3: CI/CD環境設定管理

**目的**: CI/CD管理者として、フロントエンドビルドに必要な環境変数と依存関係を自動的に構成したい。これにより、ビルド環境の一貫性とテストの再現性を保証できる。

#### 受入基準

1. WHEN buildジョブが開始される場合 THEN システムは`.env.example`と`backend/laravel-api/.env.example`から`.env`ファイルを自動生成するものとする

2. WHEN 依存関係インストールステップが実行される場合 THEN システムは`npm ci`コマンドを使用し、`package-lock.json`に基づく厳密なバージョンで依存関係をインストールするものとする

3. WHERE Node.jsセットアップが実行される場合 THE システムはNode.js 20.xバージョンを使用し、npmキャッシュを有効化してインストール時間を短縮するものとする

4. IF 環境変数ファイル生成が失敗した場合 THEN システムは明確なエラーメッセージを出力し、ビルドを中断するものとする

5. WHEN buildジョブが実行される場合 THEN システムは`ubuntu-latest`ランナーを使用し、本番環境に近いLinux環境でビルド検証を行うものとする

---

### 要件4: Matrix戦略による複数アプリ並列テスト

**目的**: CI/CDエンジニアとして、Admin AppとUser Appを並列実行で検証したい。これにより、CI/CD実行時間を最小化し、開発フィードバックループを高速化できる。

#### 受入基準

1. WHEN buildジョブが実行される場合 THEN システムはGitHub Actions Matrix戦略を使用し、`admin-app`と`user-app`を並列実行するものとする

2. IF いずれかのアプリのビルドまたは型チェックが失敗した場合 THEN システムは`fail-fast: false`設定により他のアプリの検証を継続し、全ての失敗を報告するものとする

3. WHEN Matrix戦略が実行される場合 THEN システムは各アプリに対して独立したジョブログを生成し、失敗原因の特定を容易にするものとする

4. WHERE 複数のNode.jsバージョンをテストする必要がある場合 THE システムはMatrix戦略のnode-versionパラメータで複数バージョンを指定可能とするものとする（現在は20.xのみ）

---

### 要件5: 既存CI/CDワークフローとの統合

**目的**: プロジェクト管理者として、新しいbuildジョブが既存のlintジョブとtestジョブと連携して動作することを保証したい。これにより、包括的な品質ゲートを実現できる。

#### 受入基準

1. WHEN frontend-test.ymlワークフローが実行される場合 THEN システムはlint、test、buildの3つのジョブを並列実行するものとする

2. IF lint、test、buildのいずれかのジョブが失敗した場合 THEN GitHub PRステータスチェックは失敗を表示し、マージをブロックするものとする

3. WHEN 全てのジョブが成功した場合 THEN GitHub PRステータスチェックは全て緑色で表示され、マージ可能状態となるものとする

4. WHERE Concurrency設定が有効な場合 THE システムは同一PR内の古いワークフロー実行を自動キャンセルし、リソースを最適化するものとする

5. WHEN buildジョブが追加される場合 THEN 既存のlintジョブとtestジョブのステップ構成（checkout、setup、install、env）を再利用し、保守性を向上させるものとする

---

## 技術スタック

**Frontend**: TypeScript 5.x, Next.js 15.5.4, npm
**Infrastructure**: GitHub Actions, ubuntu-latest
**Tools**: TypeScript Compiler (tsc), ESLint 9.x, Jest 30.x

---

## プロジェクト構造

```
.github/workflows/frontend-test.yml    # 本仕様で修正対象
frontend/admin-app/                    # 検証対象アプリ1
frontend/user-app/                     # 検証対象アプリ2
.env.example                           # 環境変数テンプレート
backend/laravel-api/.env.example       # Laravel環境変数テンプレート
```

---

## 開発サービス構成

- **GitHub Actions Runner**: OS: ubuntu-latest, Node.js: 20.x
- **npm Scripts**:
  - `npm run type-check`: TypeScript型チェック（`tsc --noEmit`）
  - `npm run build`: Next.js本番ビルド（`next build`）
  - `npm ci`: 依存関係厳密インストール

---

## 要件ヒント（Issue #127から抽出）

- TypeScript型チェック（`npx tsc --noEmit`）をCI/CDに追加
- 本番ビルド検証（`npm run build`）をCI/CDに追加
- User AppとAdmin App両方に適用
- ビルドエラーをPR時に検知できる仕組み
- 型安全性保証のための型エラー事前防止
- 本番環境デプロイ前の品質保証強化

---

## TODOアイテム（Issue #127から）

- [ ] 新しいbuildジョブをfrontend-test.ymlに追加
- [ ] TypeScript型チェックステップ（`npx tsc --noEmit`）の実装
- [ ] 本番ビルド検証ステップ（`npm run build`）の実装
- [ ] Matrix戦略でadmin-app/user-app両方をテスト
- [ ] 環境変数ファイル（`.env`）の事前作成
- [ ] CI環境でのビルド成功確認

---

## 関連Issue

- [#124](https://github.com/ef-tech/laravel-next-b2c/issues/124) - Admin App本番ビルド失敗（simple-error-testレイアウト問題）

---

## 非機能要件

### パフォーマンス
- buildジョブはlintジョブとtestジョブと並列実行され、全体のCI/CD実行時間を最小化する
- npmキャッシュ有効化により、依存関係インストール時間を短縮する
- Matrix戦略により、2つのアプリを並列検証し、逐次実行と比較して約50%の時間短縮を実現する

### 保守性
- buildジョブの構成は既存のlintジョブとtestジョブのステップ構成を再利用し、重複を最小化する
- GitHub Actions公式アクションのバージョンを固定し（`@v4`）、予期しない破壊的変更を防止する
- ワークフロー設定はYAML Anchorや再利用可能ワークフローでさらに最適化可能とする

### 信頼性
- `fail-fast: false`設定により、一つのアプリの失敗が他のアプリの検証を中断しない
- CI環境変数（`CI=true`）により、CI特有のビルド最適化とエラーハンドリングを有効化する
- ビルドエラーログは詳細に記録され、失敗原因の迅速な特定を可能とする

---

## 受入テスト戦略

1. **正常系テスト**:
   - 型エラーなし、ビルド成功のPRを作成 → CI/CD全ジョブ成功、マージ可能

2. **異常系テスト（型エラー）**:
   - 意図的に型エラーを含むコードをPRに含める → TypeScript型チェック失敗、PRマージブロック

3. **異常系テスト（ビルドエラー）**:
   - レイアウト構造問題を含むコードをPRに含める → 本番ビルド失敗、PRマージブロック

4. **並列実行確認**:
   - Admin AppとUser App両方を変更したPRを作成 → Matrix戦略により並列実行、両方の結果が報告される

5. **既存ジョブとの連携確認**:
   - lint失敗、test成功、build成功のPRを作成 → PRステータスチェック全体が失敗表示

---

## 制約事項

- 本仕様はフロントエンド（Admin App、User App）のみを対象とし、バックエンド（Laravel API）のビルド検証は含まない
- Node.jsバージョンは20.xに固定し、複数バージョンのMatrix戦略は将来の拡張とする
- ビルド成果物（`.next/`ディレクトリ）のアーティファクトアップロードは本仕様の範囲外とする（必要に応じて将来追加）
- E2Eテストは別ワークフロー（`e2e-tests.yml`）で実行されるため、本仕様では扱わない

---

## 優先度

**High** - Issue #124のような本番ビルド失敗を防ぐため、早急な実装が必要
