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

## Requirements
<!-- Will be generated in /kiro:spec-requirements phase -->