# Implementation Plan

## 実装タスク概要

本実装計画は、admin-appとuser-appの2つのNext.jsアプリケーションにおいて、コードスタイルとフォーマットの統一を実現するための共通ESLint/Prettier設定の導入を段階的に実施します。

---

- [x] 1. Prettierコードフォーマット設定を導入する
- [x] 1.1 Prettierの基本設定を作成する
  - ルートディレクトリにPrettier設定ファイルを配置
  - printWidth、singleQuote、trailingComma、semi、tabWidth、endOfLine、Tailwind CSSプラグインを含む設定を定義
  - フォーマット対象外ディレクトリ（node_modules、.next、dist、build、out、coverage、.turbo、.vercel、*.min.*、backend、.kiro、.claude、.git、.husky、.idea）を除外するignore設定を作成
  - _Requirements: 1.1, 1.2_

- [x] 1.2 Prettierコマンドを統合する
  - ルートpackage.jsonにformat:checkスクリプトを追加（frontend配下のTypeScript、JavaScript、JSON、CSS、Markdownファイルのフォーマット状態をチェック）
  - ルートpackage.jsonにformatスクリプトを追加（frontend配下のすべての対象ファイルを自動フォーマット）
  - _Requirements: 1.3, 1.4_

- [x] 2. ESLint共通設定とモノレポ統合を構築する
- [x] 2.1 共通ESLint設定を作成する
  - frontendディレクトリに共通ESLint基本設定ファイルを配置
  - FlatCompatを使用してNext.js推奨設定（core-web-vitals）とTypeScript推奨設定を継承
  - カスタムルール（no-console: warn、no-debugger: warn、unused-vars: warn）を定義
  - Prettier競合ルールを無効化する設定を追加（設定配列の最後に配置）
  - 共通ignoreパターン（node_modules、.next、out、build、dist、*.min.*、next-env.d.ts）を定義
  - _Requirements: 2.1, 2.5_

- [x] 2.2 各アプリのESLint設定を共通設定に変更する
  - admin-appのESLint設定ファイルを共通設定をimportする形式に変更
  - user-appのESLint設定ファイルを共通設定をimportする形式に変更
  - 各アプリで個別のカスタマイズも可能な構造を維持
  - _Requirements: 2.2_

- [x] 2.3 npm workspacesを設定してモノレポ統合を実現する
  - ルートpackage.jsonを作成し、workspacesにadmin-appとuser-appを指定
  - ルートpackage.jsonにlintスクリプトを追加（全ワークスペースで並列実行）
  - ルートpackage.jsonにlint:fixスクリプトを追加（全ワークスペースで自動修正）
  - ルートpackage.jsonにtype-checkスクリプトを追加（全ワークスペースで型チェック）
  - _Requirements: 2.3, 2.4, 5.1, 5.5_

- [x] 2.4 各アプリのpackage.jsonスクリプトを更新する
  - admin-appのpackage.jsonにlint、lint:fix、type-checkスクリプトを定義
  - user-appのpackage.jsonにlint、lint:fix、type-checkスクリプトを定義
  - 各アプリディレクトリおよびルートから該当コマンドを実行可能にする
  - _Requirements: 5.4_

- [x] 3. 依存関係をインストールして互換性を確保する
- [x] 3.1 ルートpackage.jsonに依存関係を追加する
  - devDependenciesにeslint、eslint-config-prettier、eslint-config-next、@eslint/eslintrcを追加
  - devDependenciesにprettier、prettier-plugin-tailwindcssを追加
  - devDependenciesにhusky、lint-stagedを追加（次フェーズで使用）
  - 相互に互換性のあるバージョンを指定（ESLint 9、Prettier 3、Husky 9、lint-staged 15）
  - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [x] 3.2 依存関係をインストールして互換性を検証する
  - ルートディレクトリでnpm installを実行
  - 各アプリディレクトリでnpm installを実行
  - 共通の依存関係がルートにhoistされることを確認
  - 既存のnext、react、typescript依存関係との競合がないことを確認
  - _Requirements: 5.2, 6.5_

- [x] 4. Git Hooksによるコミット前自動チェックを設定する
- [x] 4.1 Huskyを初期化してprepareスクリプトを設定する
  - ルートpackage.jsonにprepareスクリプトでhuskyを設定
  - npx husky initを実行して.huskyディレクトリを作成
  - npm install実行時に自動的にHuskyのセットアップが実行されることを確認
  - _Requirements: 3.1, 5.3_

- [x] 4.2 pre-commitフックを作成してlint-stagedを統合する
  - .huskyディレクトリにpre-commitファイルを作成
  - pre-commitフックでnpx lint-stagedを実行するように設定
  - 実行権限を付与（chmod +x）
  - コミット前にlint-stagedが自動実行されることを確認
  - _Requirements: 3.2_

- [x] 4.3 lint-staged設定を追加してファイルタイプ別チェックを実現する
  - ルートpackage.jsonにlint-staged設定を追加
  - TypeScript/JavaScriptファイルに対してESLint自動修正（--fix --max-warnings=0）とPrettier自動フォーマットを順次実行
  - CSS、Markdown、JSONファイルに対してPrettier自動フォーマットのみを実行
  - ステージングされたファイルのみに対してチェックを実行
  - _Requirements: 3.3, 3.4_

- [ ] 4.4 エラーハンドリングと緊急回避機能を確認する
  - ESLintエラーまたはフォーマットエラー発生時にコミットが中断されることを確認
  - エラー内容（ファイルパス、行番号、ルール名）が明確に表示されることを確認
  - git commit --no-verifyで緊急時にpre-commitフックをスキップできることを確認
  - _Requirements: 3.5, 3.6_

- [x] 5. VSCodeエディタ統合設定を追加する
- [x] 5.1 VSCodeワークスペース設定を作成する
  - .vscodeディレクトリにsettings.jsonを作成
  - formatOnSave、codeActionsOnSave（ESLint自動修正）を有効化
  - defaultFormatterをPrettierに設定
  - eslint.workingDirectoriesにadmin-appとuser-appを指定
  - prettier.configPathに.prettierrcを指定
  - TypeScript、TypeScriptReact、JavaScript、JSON各ファイルタイプのデフォルトフォーマッターをPrettierに設定
  - _Requirements: 4.1, 4.2, 4.4_

- [x] 5.2 推奨拡張機能を設定する
  - .vscodeディレクトリにextensions.jsonを作成
  - recommendationsにESLint拡張機能（dbaeumer.vscode-eslint）を追加
  - recommendationsにPrettier拡張機能（esbenp.prettier-vscode）を追加
  - recommendationsにTailwind CSS拡張機能（bradlc.vscode-tailwindcss）を追加
  - VSCodeがプロジェクトを開いた際に推奨拡張機能のインストールを促すことを確認
  - _Requirements: 4.3_

- [ ] 6. 動作確認と統合テストを実施する
- [x] 6.1 フォーマットとリントの基本動作を確認する
  - npm run format:checkを実行して、フォーマット差分があるファイルを検出
  - npm run formatを実行して、すべての対象ファイルが自動フォーマットされることを確認
  - npm run lintを実行して、全ワークスペースのリントチェックが並列実行されることを確認（警告は許容）
  - npm run lint:fixを実行して、自動修正可能な問題が修正されることを確認
  - _Requirements: 1.3, 1.4, 2.3, 2.4_

- [ ] 6.2 型チェックとビルドの互換性を確認する
  - npm run type-checkを実行して、全ワークスペースで型チェックが正常に実行されることを確認
  - admin-appディレクトリでnpm run devを実行して、開発サーバーが正常に起動することを確認
  - user-appディレクトリでnpm run devを実行して、開発サーバーが正常に起動することを確認
  - admin-appディレクトリでnpm run buildを実行して、ビルドが成功することを確認（ESLintエラーがある場合は失敗）
  - user-appディレクトリでnpm run buildを実行して、ビルドが成功することを確認
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ] 6.3 VSCode統合機能を確認する
  - VSCodeでTypeScriptファイルを開き、保存時にPrettierとESLintが自動実行されることを確認
  - リアルタイムでESLintエラーがエディタ内に表示されることを確認
  - Tailwind CSSクラス名の補完が提供されることを確認
  - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [ ] 6.4 Git Hooksの統合テストを実施する
  - テストファイルを作成してステージングし、git commitを実行
  - pre-commitフックが起動し、lint-stagedが正しく動作することを確認
  - ESLintエラーがある場合、コミットが中断されることを確認
  - エラーを修正後、再度コミットが成功することを確認
  - Prettierによる自動フォーマットがステージングファイルに適用されることを確認
  - _Requirements: 3.2, 3.3, 3.4, 3.5_

- [ ] 6.5 段階的適用戦略を検証する
  - 初回のESLintチェック実行時に警告が発生しても、ビルドやコミットが阻害されないことを確認
  - lint-stagedで--max-warnings=0オプションが使用され、新規コミット時の品質基準が厳格に適用されることを確認
  - 既存のESLint設定ファイル（.eslintrc.*）が存在する場合、新しいeslint.config.mjsのみが使用されることを確認
  - npm run format:checkで差分を検出するが、ファイルを変更しないことを確認
  - 修正されたファイルのみに新しい基準が適用され、未修正ファイルに影響を与えないことを確認
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 7. パフォーマンスとエラーハンドリングを検証する
- [ ] 7.1 パフォーマンステストを実施する
  - 10ファイル修正時のpre-commitフック実行時間が5秒以内であることを確認
  - npm run lintの並列実行が単一実行の2倍以内の時間で完了することを確認
  - VSCodeでファイル保存からフォーマット完了まで1秒以内であることを確認
  - npm run formatで100ファイル以上を一括フォーマットし、10秒以内で完了することを確認
  - _Requirements: パフォーマンス要件（design.mdのPerformance Tests参照）_

- [ ] 7.2 エラーハンドリングを検証する
  - package.json構文エラー時に、具体的な行番号とエラー内容が表示されることを確認
  - npm install失敗時に、npm ERRORログと原因が表示されることを確認
  - .husky/pre-commitの実行権限不足時に、Permission deniedエラーとchmodコマンドがガイドされることを確認
  - ESLintルール違反時に、ファイルパス、行番号、ルール名、エラー内容が表示されることを確認
  - Prettierフォーマット不一致時に、フォーマット差分があるファイルリストが表示されることを確認
  - _Requirements: エラーハンドリング要件（design.mdのError Handling参照）_

## 実装完了条件

すべてのタスクが完了し、以下の条件を満たすこと：

1. ✅ すべての設定ファイルが作成され、リポジトリにコミット済み
2. ✅ npm installが全アプリで正常完了
3. ✅ npm run lintがエラーなしで完了（警告は許容）
4. ✅ npm run format:checkが差分なしで完了
5. ✅ コミット時に自動でlint-stagedが実行される
6. ✅ 各アプリが正常にビルド・起動できる（npm run dev、npm run build）
7. ✅ VSCodeで保存時に自動フォーマットとリアルタイムリントエラー表示が機能する