# Requirements Document

## GitHub Issue Information

**Issue**: [#2](https://github.com/ef-tech/laravel-next-b2c/issues/2) - README.md の整備（セットアップ手順、開発フロー記載）
**Labels**: なし
**Milestone**: なし
**Assignees**: なし

### Original Issue Description
新規参加者がプロジェクトに迅速にオンボーディングできるよう、包括的で実用性の高いREADME.mdを作成する。Laravel 12 + Next.js 15のモノレポ構成において、技術スタックの理解から実際の開発開始まで15分以内で完了できる導線を確立する。

**Category**: Docs - ドキュメント整備
- プロジェクトREADME作成・構造化
- 技術ドキュメント標準化
- 開発者エクスペリエンス向上

## Extracted Information

### Technology Stack
**Backend**: PHP 8.4, Laravel 12, Composer
**Frontend**: Next.js 15, React 19, TypeScript, Tailwind CSS 4
**Infrastructure**: Docker, Git, npm/pnpm, Mermaid.js
**Tools**: GitHub CLI, ESLint, Makefile/Taskfile, CI/CD

### Project Structure
```
├── backend/laravel-api/          # Laravel 12 API (PHP 8.4)
├── frontend/admin-app/           # Next.js 15 管理画面 (React 19, TS, Tailwind 4)
├── frontend/user-app/            # Next.js 15 ユーザー画面
├── scripts/                     # 共通スクリプト
├── .kiro/                      # Kiro仕様管理
└── .claude/                    # Claude Code設定
```

### Development Services Configuration
- **Laravel API**: PHP 8.4, Laravel 12 API
- **Admin App**: Next.js 15 管理画面 (React 19, TS, Tailwind 4)
- **User App**: Next.js 15 ユーザー画面

### Requirements Hints
Based on issue analysis:
- 15分以内でのローカル開発環境構築
- モノレポ構成の包括的ドキュメント化
- クイックスタートガイドの提供
- 技術スタック詳細とアーキテクチャ説明
- 環境構築の段階的手順（Docker + ネイティブ両対応）
- 日常的な開発ワークフローの標準化
- テスト・品質保証手順の明文化
- トラブルシューティングガイド作成

### TODO Items from Issue
- [ ] プロジェクト概要セクション作成
- [ ] 技術スタック一覧表作成
- [ ] 基本的な環境構築手順記述
- [ ] クイックスタートガイド作成
- [ ] アーキテクチャ図作成（Mermaid.js）
- [ ] 詳細セットアップ手順（Backend）
- [ ] 詳細セットアップ手順（Frontend）
- [ ] 開発ワークフロー標準化
- [ ] テスト実行手順整備
- [ ] コードフォーマット・リント手順
- [ ] トラブルシューティングガイド
- [ ] 環境変数管理ガイド
- [ ] Makefileコマンド統合
- [ ] 自動生成スクリプト作成
- [ ] CI/CD統合チェック
- [ ] 継続的メンテナンス仕組み確立

## 要件

### 要件1: プロジェクト概要と導入
**目標:** 新規開発者として、プロジェクトの全体像を即座に理解したい。そうすることで迅速に開発に参加できる。

#### 受入基準
1. WHEN 開発者がREADME.mdを開いた THEN プロジェクトドキュメント SHALL プロジェクト名、概要、主要な価値提案を3行以内で表示する
2. WHEN 開発者が技術構成を確認する THEN プロジェクトドキュメント SHALL Laravel 12、Next.js 15、React 19、TypeScript、Tailwind CSS 4の技術スタックを明示する
3. WHEN 開発者がアーキテクチャを理解したい THEN プロジェクトドキュメント SHALL Mermaid.jsによるシステム連携図を表示する
4. WHERE モノレポ構成の説明において プロジェクトドキュメント SHALL backend/laravel-api、frontend/admin-app、frontend/user-appの役割を明確に記述する

### 要件2: 迅速な環境構築
**目標:** 新規開発者として、15分以内でローカル開発環境を完全に構築したい。そうすることで即座にコーディングを開始できる。

#### 受入基準
1. WHEN 開発者が初回セットアップを実行する THEN プロジェクトドキュメント SHALL 前提条件（必要ソフトウェアとバージョン）を明記する
2. WHEN 開発者がクイックスタートを実行する THEN プロジェクトドキュメント SHALL リポジトリクローンから全サービス起動まで5コマンド以内で完了する手順を提供する
3. IF Docker環境を選択した THEN プロジェクトドキュメント SHALL Laravel Sailによる一括環境構築手順を提供する
4. IF ネイティブ環境を選択した THEN プロジェクトドキュメント SHALL backend、frontend個別セットアップ手順を提供する
5. WHEN 環境構築が完了した THEN プロジェクトドキュメント SHALL 各サービスのアクセスURL（API、管理画面、ユーザー画面）を明示する

### 要件3: 開発ワークフロー標準化
**目標:** 開発者として、日常的な開発作業で統一されたコマンドとプロセスを使いたい。そうすることで効率的で一貫した開発ができる。

#### 受入基準
1. WHEN 開発者が開発サーバーを起動する THEN プロジェクトドキュメント SHALL backendとfrontend各アプリケーションの起動コマンドを提供する
2. WHEN 開発者がデータベース操作を行う THEN プロジェクトドキュメント SHALL マイグレーション、シード、リセットの標準コマンドを明記する
3. WHEN 開発者がコード品質チェックを実行する THEN プロジェクトドキュメント SHALL リンター、フォーマッター、型チェックの実行方法を提供する
4. WHEN 開発者がテストを実行する THEN プロジェクトドキュメント SHALL ユニットテストとE2Eテストの実行手順を明記する
5. WHERE 統合コマンドの説明において プロジェクトドキュメント SHALL Makefile/Taskfileによるプロジェクト横断操作を文書化する

### 要件4: 環境設定管理
**目標:** 開発者として、適切な環境変数設定とポート管理をしたい。そうすることで環境間の競合を避けながら開発できる。

#### 受入基準
1. WHEN 開発者が環境変数を設定する THEN プロジェクトドキュメント SHALL .env.exampleから.envファイル生成手順を提供する
2. WHEN 開発者がポート設定を確認する THEN プロジェクトドキュメント SHALL カスタマイズされたポート設定（13000番台）を明記する
3. WHEN 開発者が必須設定を確認する THEN プロジェクトドキュメント SHALL データベース、Redis、メール等の重要な環境変数を説明する
4. IF 複数開発者が同時開発する THEN プロジェクトドキュメント SHALL ポート競合回避のための設定調整方法を提供する

### 要件5: トラブルシューティングとサポート
**目標:** 開発者として、よくある問題を自己解決したい。そうすることで開発の妨げを最小化できる。

#### 受入基準
1. WHEN 開発者がセットアップエラーに遭遇した THEN プロジェクトドキュメント SHALL よくあるエラーパターンと解決策を提供する
2. WHEN 開発者がポート競合問題に直面した THEN プロジェクトドキュメント SHALL ポート変更とサービス再起動の手順を明記する
3. WHEN 開発者がCORS問題に遭遇した THEN プロジェクトドキュメント SHALL フロントエンドとバックエンド間の通信設定を説明する
4. WHEN 開発者が依存関係エラーに直面した THEN プロジェクトドキュメント SHALL Composer/npm再インストール手順を提供する
5. WHERE エラー解決の説明において プロジェクトドキュメント SHALL 各OS（macOS、Windows、Linux）固有の注意点を記載する

### 要件6: ドキュメント品質保証
**目標:** プロジェクトメンテナーとして、ドキュメントの正確性と最新性を維持したい。そうすることで新規参加者への価値を継続的に提供できる。

#### 受入基準
1. WHEN ドキュメント更新時 THEN プロジェクトドキュメント SHALL 記載されている全コマンドが実行可能で検証済みである
2. WHEN マークダウン検証時 THEN プロジェクトドキュメント SHALL リンク切れがなく、文法的に正しい構造である
3. WHEN 新機能追加時 THEN プロジェクトドキュメント SHALL 関連する環境構築や開発手順が更新される
4. WHILE プロジェクトが活発に開発されている間 プロジェクトドキュメント SHALL 技術スタックのバージョンと現実の構成が一致する
5. WHERE 自動化可能な部分において プロジェクトドキュメント SHALL CI/CDによる継続的な品質チェックが実装される

### 要件7: 視覚的理解促進
**目標:** 開発者として、プロジェクトの構造と関係性を視覚的に理解したい。そうすることで効率的にコードベースをナビゲートできる。

#### 受入基準
1. WHEN 開発者がプロジェクト構造を確認する THEN プロジェクトドキュメント SHALL ディレクトリツリー形式でファイル構成を表示する
2. WHEN 開発者がサービス間連携を理解したい THEN プロジェクトドキュメント SHALL アーキテクチャ図でAPI呼び出しフローを可視化する
3. WHEN 開発者がCI/CDステータスを確認する THEN プロジェクトドキュメント SHALL バッジ表示でビルド状況や品質メトリクスを表示する
4. WHERE 複雑な設定説明において プロジェクトドキュメント SHALL 折りたたみ可能なセクションで詳細情報を整理する