# Implementation Plan

## 概要

Admin Appの本番ビルド失敗を修正するため、`simple-error-test/page.tsx` を `[locale]/simple-error-test/page.tsx` に移動し、既存の locale layout を継承させることで Next.js App Router のレイアウト要件を満たします。

**実装時間見積もり**: 約25分（CI/CD除く）

## タスク一覧

- [ ] 1. ファイル移動とディレクトリ構造の修正
- [ ] 1.1 simple-error-test ページを locale ディレクトリ配下に移動
  - Git を使ったファイル移動で履歴を保持
  - 新しいディレクトリ `[locale]/simple-error-test/` を作成
  - `simple-error-test/page.tsx` を `[locale]/simple-error-test/page.tsx` に移動
  - 旧ディレクトリが削除されていることを確認
  - _Requirements: 1.1, 1.2, 1.5_

- [ ] 2. ローカル環境でのビルド検証
- [ ] 2.1 TypeScript型チェックとESLint検証を実行
  - TypeScript型チェック（`npm run type-check`）を実行してエラーがないことを確認
  - ESLint（`npm run lint`）を実行して新規エラー・警告がないことを確認
  - _Requirements: 2.5, 2.6_

- [ ] 2.2 本番ビルドと本番サーバー起動を実行
  - 本番ビルド（`npm run build`）を実行してエラーなく完了することを確認
  - `.next/` ディレクトリにビルド成果物が生成されていることを確認
  - 本番サーバー（`npm run start`）を起動してポート13002で正常起動することを確認
  - _Requirements: 1.1, 2.1, 2.2_

- [ ] 2.3 ブラウザでページアクセスを検証
  - `/ja/simple-error-test` にアクセスして Error Boundary が正常に表示されることを確認
  - `/en/simple-error-test` にアクセスして英語版 Error Boundary が正常に表示されることを確認
  - エラーメッセージが locale に応じた言語で表示されることを確認
  - _Requirements: 1.3, 2.3, 2.4_

- [ ] 3. 既存機能の後方互換性テスト
- [ ] 3.1 Admin App の既存ページとテストを検証
  - Admin App の他のページ（`page.tsx`、`test-error/page.tsx`）が正常に表示されることを確認
  - Admin App の Jest テスト（`npm test`）を実行して全テストがパスすることを確認
  - テストカバレッジが修正前と同等であることを確認
  - _Requirements: 5.1, 5.2_

- [ ] 3.2 User App への影響がないことを確認
  - User App の本番ビルド（`cd frontend/user-app && npm run build`）が成功することを確認
  - User App が Admin App の修正により影響を受けていないことを確認
  - _Requirements: 5.3, 5.4_

- [ ] 4. Git コミットとブランチ作成
- [ ] 4.1 変更内容を Git コミット
  - 変更ファイルを Git にステージング
  - Issue #124 への参照を含むコミットメッセージを作成
  - コミットメッセージに修正内容と理由を明確に記載
  - _Requirements: 4.2_

- [ ] 4.2 フィーチャーブランチの作成とプッシュ
  - `fix/124/simple-error-test-layout-fix` ブランチを作成
  - リモートリポジトリにブランチをプッシュ
  - _Requirements: なし（実装手順）_

- [ ] 5. Pull Request の作成
- [ ] 5.1 GitHub CLI で Pull Request を作成
  - PR タイトルに修正内容を明確に記載
  - PR 本文に Summary とテスト計画を記載
  - 新しい URL パス（`/ja/simple-error-test`、`/en/simple-error-test`）を記載
  - Issue #124 をクローズする参照（`Close: #124`）を含める
  - _Requirements: 3.1, 4.1_

- [ ] 6. CI/CD パイプラインでのビルド確認
- [ ] 6.1 GitHub Actions の自動実行を確認
  - Pull Request 作成後に GitHub Actions が自動実行されることを確認
  - フロントエンドテストワークフローが正常に実行されることを確認
  - TypeScript 型チェック、ESLint、Jest テストが CI/CD で成功することを確認
  - _Requirements: 3.2, 3.3, 3.4_

- [ ] 6.2 CI/CD ビルドステップの成功を確認
  - CI/CD で `npm run build` コマンドが成功することを確認
  - CI/CD で TypeScript 型チェックが成功することを確認
  - PR がマージ可能な状態になることを確認
  - _Requirements: 3.3, 3.4_

## タスク実装の注意事項

### ファイル移動の実行コマンド

```bash
# ディレクトリ作成
mkdir -p frontend/admin-app/src/app/[locale]/simple-error-test

# Git を使ったファイル移動（履歴を保持）
git mv frontend/admin-app/src/app/simple-error-test/page.tsx \
       frontend/admin-app/src/app/[locale]/simple-error-test/page.tsx

# 旧ディレクトリ削除確認
rmdir frontend/admin-app/src/app/simple-error-test 2>/dev/null || true
```

### ローカル検証の実行コマンド

```bash
cd frontend/admin-app

# TypeScript型チェック
npm run type-check

# ESLint
npm run lint

# 本番ビルド
npm run build

# 本番サーバー起動
npm run start

# ブラウザで確認
# http://localhost:13002/ja/simple-error-test
# http://localhost:13002/en/simple-error-test
```

### Git コミットの実行コマンド

```bash
# 変更ファイルをステージング
git add frontend/admin-app/src/app/[locale]/simple-error-test/page.tsx

# コミット作成
git commit -m "Fix: 🔧 Admin App本番ビルド失敗修正（simple-error-test/page.tsx → [locale]配下に移動）

- Next.js App Routerレイアウト要件を満たすため、simple-error-test/page.tsx を [locale]/simple-error-test/page.tsx に移動
- [locale]/layout.tsx を継承し、HTML構造とNextIntlClientProviderを自動取得
- URL変更: /simple-error-test → /ja/simple-error-test, /en/simple-error-test
- i18n構造との一貫性を確保
- 本番ビルド成功を確認

Close: #124

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"
```

### Pull Request 作成コマンド

```bash
# ブランチ作成とプッシュ
git checkout -b fix/124/simple-error-test-layout-fix
git push -u origin fix/124/simple-error-test-layout-fix

# Pull Request作成
gh pr create --title "Fix: 🔧 Admin App本番ビルド失敗修正（simple-error-test/page.tsx構造問題）" \
             --body "$(cat <<'EOF'
## Summary
- Admin Appの本番ビルド失敗を修正（Next.js App Routerレイアウト要件違反）
- `simple-error-test/page.tsx` を `[locale]/simple-error-test/page.tsx` に移動
- `[locale]/layout.tsx` を継承し、HTML構造とi18n対応を自動取得
- URL変更: `/simple-error-test` → `/ja/simple-error-test`, `/en/simple-error-test`
- i18n構造との一貫性を確保

## Test plan
- [x] TypeScript型チェック成功（`npm run type-check`）
- [x] ESLint成功（`npm run lint`）
- [x] 本番ビルド成功（`npm run build`）
- [x] 本番サーバー起動成功（`npm run start`）
- [x] ブラウザアクセス確認（`/ja/simple-error-test`, `/en/simple-error-test`）
- [x] Error Boundary正常表示確認
- [x] エラーメッセージ多言語化確認
- [ ] CI/CDビルド成功確認（PR作成後）

Close: #124

🤖 Generated with [Claude Code](https://claude.com/claude-code)
EOF
)"
```

## 要件カバレッジ

### Requirement 1: Next.js App Routerレイアウト構造の修正
- **Task 1.1**: ファイル移動によりレイアウト要件を満たす（AC 1.1, 1.2, 1.3, 1.4, 1.5）

### Requirement 2: 本番ビルドの検証とテスト
- **Task 2.1**: TypeScript型チェックとESLint検証（AC 2.5, 2.6）
- **Task 2.2**: 本番ビルドとサーバー起動（AC 2.1, 2.2）
- **Task 2.3**: ブラウザアクセス検証（AC 2.3, 2.4）

### Requirement 3: CI/CDパイプラインとの整合性
- **Task 6.1**: GitHub Actions自動実行確認（AC 3.1, 3.2）
- **Task 6.2**: CI/CDビルドステップ成功確認（AC 3.3, 3.4）

### Requirement 4: ドキュメントとコードの一貫性
- **Task 5.1**: PR本文に新URLパスを記載（AC 4.1）
- **Task 4.1**: コミットメッセージにIssue #124参照（AC 4.2）

### Requirement 5: 後方互換性とリグレッションの防止
- **Task 3.1**: Admin App既存ページとテスト検証（AC 5.1, 5.2）
- **Task 3.2**: User Appへの影響確認（AC 5.3, 5.4）

## ロールバック計画

問題が発生した場合の対処方法：

```bash
# ファイルを元の場所に戻す
git mv frontend/admin-app/src/app/[locale]/simple-error-test/page.tsx \
       frontend/admin-app/src/app/simple-error-test/page.tsx

# コミットを取り消す
git reset --soft HEAD~1

# ブランチを削除
git checkout main
git branch -D fix/124/simple-error-test-layout-fix
```

**リスク評価**: 🟢 低リスク（ファイル移動のみ、内容変更なし、影響範囲1ページのみ）
