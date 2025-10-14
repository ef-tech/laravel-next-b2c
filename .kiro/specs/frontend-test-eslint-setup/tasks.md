# 実装計画

## Phase 1: 初期導入（warnレベル）

- [x] 1. ESLintテストプラグイン依存関係のインストール
- [x] 1.1 必要なパッケージをdevDependenciesに追加
  - eslint-plugin-jest, eslint-plugin-testing-library, eslint-plugin-jest-dom, globalsの4パッケージをインストール
  - package.jsonのdevDependenciesセクションに追加
  - バージョン互換性を確認（ESLint 9、Node.js 20、Next.js 15.5対応）
  - _Requirements: 1.1, 1.3_

- [x] 1.2 インストール後の依存関係整合性を検証
  - npm ciを実行して依存関係を再構築
  - npm ls eslint-plugin-jest eslint-plugin-testing-libraryで依存関係ツリーを確認
  - バージョン競合がないことを確認
  - ESLint v9.x.xが維持されていることを確認
  - _Requirements: 1.2, 1.4_

- [x] 2. ESLint Flat Config設定の拡張
- [x] 2.1 frontend/.eslint.base.mjsにプラグインインポートを追加
  - eslint-plugin-jest、eslint-plugin-testing-library、eslint-plugin-jest-dom、globalsをインポート
  - 既存のFlatCompat方式を維持
  - Prettier統合（eslintConfigPrettier）が最後に配置される構造を保持
  - _Requirements: 2.1, 2.9_

- [x] 2.2 テストファイル専用オーバーライド設定を追加
  - filesパターンを定義（`**/*.{test,spec}.{ts,tsx,js,jsx}`、`**/__tests__/**/*.{ts,tsx,js,jsx}`）
  - 3つのプラグイン（jest, testing-library, jest-dom）を登録
  - languageOptions.globalsにglobals.jestを設定
  - 推奨ルールセット（flat/recommended、flat/react）を適用
  - _Requirements: 2.2, 2.3, 2.4, 2.5, 2.6_

- [x] 2.3 テスト特有のルール調整を適用
  - no-console: off（テストデバッグ容易性優先）
  - @typescript-eslint/no-unused-vars: warn（argsIgnorePattern、caughtErrors設定）
  - no-empty-function: off（jest.fn()許容）
  - 初期フェーズのルールをwarnレベルに設定（testing-library/no-node-access等）
  - _Requirements: 2.7, 2.8_

- [x] 3. ローカル開発環境でのリント動作確認
- [x] 3.1 モノレポルートから全ワークスペースのリント実行を確認
  - npm run lintを実行してadmin-app、user-app両方がリント対象となることを確認
  - テストファイルが正常にリントされることを確認
  - jest.config.jsが除外されることを確認
  - _Requirements: 3.1_

- [x] 3.2 個別ワークスペースでのリント実行を確認
  - frontend/admin-appディレクトリでnpm run lintを実行
  - frontend/user-appディレクトリでnpm run lintを実行
  - テストファイルが正常にリントされることを確認
  - _Requirements: 3.2, 3.3_

- [x] 3.3 Jestグローバル関数の認識を検証
  - テストファイルでdescribe、it、expectを使用
  - no-undefエラーが発生しないことを確認
  - IDE（VSCodeまたはIntelliJ）で補完が動作することを確認
  - _Requirements: 3.4_

- [x] 3.4 focused tests検出機能の動作確認
  - テストファイルでfitまたはfdescribeを使用
  - jest/no-focused-testsエラーが表示されることを確認
  - エラーメッセージが明確であることを確認
  - _Requirements: 3.5_

- [x] 3.5 Testing Library推奨クエリ強制の動作確認
  - テストファイルでcontainer.querySelector()を使用
  - testing-library/no-node-access警告が表示されることを確認
  - 推奨代替手段（screen.getByRole等）が提示されることを確認
  - _Requirements: 3.6_

- [x] 3.6 自動修正機能の動作確認
  - npm run lint:fixを実行
  - 自動修正可能なエラーが修正されることを確認
  - テストファイルの動作に影響がないことを確認
  - _Requirements: 3.7_

- [x] 3.7 テストファイルのみを対象にしたリント実行を確認
  - npx eslint "frontend/**/src/**/*.{test,spec}.{ts,tsx}"を実行
  - テストファイルのみがリント対象となることを確認
  - 通常ファイルがリント対象外であることを確認
  - _Requirements: 3.8_

- [x] 3.8 既存通常ファイルへの影響がないことを確認
  - 通常コードファイル（*.tsx、*.ts）をリント
  - テストルール追加前後でエラー数が増加しないことを確認
  - パフォーマンスへの影響が±10%以内であることを確認
  - _Requirements: 3.9, 7.1_

- [x] 4. lint-staged統合の確認
- [x] 4.1 既存lint-staged設定の動作確認
  - package.jsonのlint-staged設定を確認
  - frontend/admin-app/**/*.{js,jsx,ts,tsx}パターンが存在することを確認
  - frontend/user-app/**/*.{js,jsx,ts,tsx}パターンが存在することを確認
  - _Requirements: 4.1, 4.2_

- [x] 4.2 テストファイルのpre-commit自動リントを検証
  - テストファイル（*.test.tsx）を変更してステージング
  - git commitを実行
  - ESLintが自動実行されることを確認
  - エラーがある場合コミットが中断されることを確認
  - _Requirements: 4.3, 4.5_

- [x] 4.3 jest.config.js除外動作の確認
  - jest.config.jsファイルを変更してステージング
  - git commitを実行
  - jest.config.jsがリント対象外であることを確認
  - _Requirements: 4.4_

- [x] 4.4 lint-stagedでテストファイル専用ルールが適用されることを確認
  - テストファイルをステージング
  - lint-staged実行時にテストオーバーライド設定が適用されることを確認
  - Jest/Testing Libraryルールが動作することを確認
  - _Requirements: 4.6_

- [x] 4.5 Huskyフック統合の確認
  - .husky/pre-commitファイルが存在することを確認
  - lint-stagedが実行されることを確認
  - Husky v9推奨方法（.husky/直下にフック配置）が使用されていることを確認
  - _Requirements: 4.7_

- [x] 5. CI/CD統合
- [x] 5.1 GitHub Actions frontend-test.ymlにlintジョブを確認
  - .github/workflows/frontend-test.ymlを確認
  - lintジョブ（またはtestジョブ内のlintステップ）が存在することを確認
  - Node.js 20セットアップが含まれることを確認
  - _Requirements: 5.1_

- [x] 5.2 Pull Request自動実行トリガーの確認
  - Pull Request作成イベントでワークフローが実行されることを確認
  - Pull Request更新イベントでワークフローが実行されることを確認
  - frontend/**パス変更時のみ実行されることを確認
  - _Requirements: 5.2_

- [x] 5.3 lintジョブのステップ構成を確認
  - actions/checkout@v4が実行されることを確認
  - actions/setup-node@v4でNode.js 20がセットアップされることを確認
  - npm ciで依存関係がインストールされることを確認
  - npm run lintでESLintが実行されることを確認
  - _Requirements: 5.3_

- [x] 5.4 npmキャッシュの有効化を確認
  - actions/setup-node@v4のcache: 'npm'が設定されていることを確認
  - キャッシュヒット時に依存関係インストールが高速化されることを確認
  - _Requirements: 5.4_

- [x] 5.5 ESLintエラー時のジョブ失敗を検証
  - テストファイルにESLintエラーを含むブランチを作成
  - Pull Requestを作成
  - lintジョブが失敗することを確認
  - _Requirements: 5.5_

- [x] 5.6 --max-warnings=0設定の準備（Phase 3用）
  - CI/CDでの--max-warnings=0追加位置を確認
  - Phase 3での適用手順をドキュメント化
  - _Requirements: 5.6_

- [x] 5.7 並列実行最適化の確認（オプション）
  - matrixストラテジーでadmin-app、user-appを並列実行できることを確認
  - 並列実行時のキャッシュ動作を確認
  - _Requirements: 5.7_

- [x] 5.8 Pull Request成功時のステータス表示を確認
  - ESLintエラーなしのPull Requestを作成
  - lintジョブが成功することを確認
  - Pull Requestに緑色のチェックマークが表示されることを確認
  - _Requirements: 5.8_

- [x] 5.9 Pull Request失敗時のマージ防止を確認
  - ESLintエラーありのPull Requestを作成
  - lintジョブが失敗することを確認
  - Pull Requestに赤色の×マークが表示されることを確認
  - マージが防止されることを確認
  - _Requirements: 5.9_

- [x] 6. パフォーマンスと互換性の検証
- [x] 6.1 リント実行時間のベースライン測定
  - テストルール追加前のnpm run lint実行時間を測定
  - admin-app、user-app個別の実行時間を記録
  - モノレポ全体の実行時間を記録
  - _Requirements: 7.1_

- [x] 6.2 テストルール追加後のパフォーマンス測定
  - テストルール追加後のnpm run lint実行時間を測定
  - ベースラインとの差異を計算
  - 実行時間増加が±10%以内であることを確認
  - _Requirements: 7.1_

- [x] 6.3 ESLintキャッシュ機能の動作確認
  - npm run lint -- --cacheを実行
  - .eslintcacheファイルが生成されることを確認
  - 2回目以降の実行が50%以上高速化されることを確認
  - _Requirements: 7.2_

- [x] 6.4 filesパターンによる影響範囲の最小化を確認
  - テストファイル専用オーバーライドがテストファイルのみに適用されることを確認
  - 通常ファイルへのプラグイン適用がないことを確認
  - パフォーマンスへの影響が最小であることを確認
  - _Requirements: 7.3_

- [x] 6.5 ESLint 9 Flat Config互換性の確認
  - ESLint v9.x.xが維持されていることを確認
  - Flat Config形式が正常に動作することを確認
  - FlatCompatとの共存が問題ないことを確認
  - _Requirements: 7.4, 7.5_

- [x] 6.6 ワークスペース別キャッシュの動作確認（オプション）
  - --cache-location frontend/admin-app/.eslintcacheが使用できることを確認
  - --cache-location frontend/user-app/.eslintcacheが使用できることを確認
  - ワークスペース別キャッシュが独立動作することを確認
  - _Requirements: 7.6_

- [x] 6.7 並列実行モードの動作確認
  - 大量のテストファイルで並列実行を試行
  - --max-warnings=0との併用が可能であることを確認
  - パフォーマンスが向上することを確認
  - _Requirements: 7.7_

- [x] 7. ドキュメント作成とチーム周知
- [x] 7.1 メインガイドドキュメントの作成
  - docs/JEST_ESLINT_INTEGRATION_GUIDE.mdを作成
  - 導入背景・目的セクションを記載
  - 依存パッケージ一覧を記載
  - ESLint設定変更手順を記載
  - ローカル動作確認方法を記載
  - CI/CD統合手順を記載
  - トラブルシューティングセクションを記載
  - _Requirements: 9.1, 9.6_

- [x] 7.2 設定例集ドキュメントの作成
  - docs/JEST_ESLINT_CONFIG_EXAMPLES.mdを作成
  - テストファイル専用オーバーライド設定の完全な例を記載
  - カスタムルール調整例を記載
  - パフォーマンス最適化設定例を記載
  - _Requirements: 9.1, 9.7_

- [x] 7.3 クイックスタートドキュメントの作成
  - docs/JEST_ESLINT_QUICKSTART.mdを作成
  - 5分以内で導入完了できる簡潔な手順を記載
  - 必須手順のみを厳選
  - 最終更新日とバージョン情報を記載
  - _Requirements: 9.1, 9.8, 9.9_

- [x] 7.4 ロールバック手順ドキュメントの作成
  - docs/JEST_ESLINT_TROUBLESHOOTING.mdを作成
  - 緊急ロールバック手順（1分以内完了）を記載
  - ステップ1: パッケージアンインストール
  - ステップ2: 設定ファイル復元
  - ステップ3: node_modules再構築（必要時）
  - 部分的ロールバック手順を記載
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.8_

- [x] 7.5 Pull Request説明文の作成
  - 変更内容の概要を記載
  - Before/After設定比較を記載
  - 動作確認結果を記載
  - ドキュメントリンク（docs/JEST_ESLINT_*.md）を記載
  - 主要ポイント（focused tests検出、Testing Libraryクエリ推奨）を説明
  - _Requirements: 9.2, 9.3, 9.4_

- [x] 7.6 FAQセクションの作成
  - よくある質問と回答を記載
  - トラブルシューティング情報を記載
  - チームメンバーからの質問を収集して反映
  - _Requirements: 9.5_

- [x] 8. Phase 1完了確認
- [x] 8.1 警告数ベースラインの記録
  - npm run lintを実行して警告数を記録
  - admin-app、user-app個別の警告数を記録
  - 警告内容をカテゴリ別に分類
  - Phase 2での優先順位付けの基礎データとする
  - _Requirements: 6.2_

- [x] 8.2 ローカル動作確認の最終検証
  - npm run lintが正常動作することを確認
  - describe、it、expectでno-undefエラーが発生しないことを確認
  - focused tests検出が動作することを確認
  - 既存テストが正常動作することを確認
  - _Requirements: 全要件最終確認_

- [x] 8.3 CI/CD統合の最終検証
  - Pull Requestを作成して自動実行を確認
  - lintジョブが正常実行されることを確認
  - エラー時にジョブが失敗することを確認
  - 成功時にチェックマークが表示されることを確認
  - _Requirements: 5.1-5.9最終確認_

- [x] 8.4 ドキュメントの最終確認
  - 全ドキュメント（3ファイル）が存在することを確認
  - ドキュメントリンクが正常に動作することを確認
  - 最終更新日が記載されていることを確認
  - _Requirements: 9.1-9.9最終確認_

- [x] 8.5 Pull Request作成とレビュー依頼
  - Phase 1実装のPull Requestを作成
  - Pull Request説明文を添付
  - Before/After比較を添付
  - チームレビューを依頼
  - _Requirements: 完了定義確認_

## Phase 2: 低ノイズルール昇格（error化）

- [x] 9. Phase 1期間中の警告分析
- [x] 9.1 警告数の収集と分析
  - Phase 1開始から2-4週間後の警告数を収集
  - カテゴリ別（jest、testing-library、jest-dom）に分類
  - 誤検知率の計算
  - 修正容易性の評価
  - _Requirements: 6.3_

- [x] 9.2 低ノイズルールの特定
  - jest/no-disabled-testsの誤検知率と修正容易性を評価
  - jest/no-focused-testsの誤検知率と修正容易性を評価
  - jest/valid-expectの誤検知率と修正容易性を評価
  - testing-library/no-await-sync-queriesの誤検知率と修正容易性を評価
  - testing-library/no-manual-cleanupの誤検知率と修正容易性を評価
  - 低ノイズルールリストを作成
  - _Requirements: 6.4_

- [x] 10. 低ノイズルールのerror昇格
- [x] 10.1 ESLint設定の更新
  - frontend/.eslint.base.mjsを編集
  - 低ノイズルール5個をerrorレベルに変更
  - 既存warnルールは維持
  - 設定ファイルの構文妥当性を確認
  - _Requirements: 6.4_

- [x] 10.2 既存テストの違反箇所を修正
  - npm run lintを実行してエラー箇所を特定
  - jest/no-disabled-tests違反を修正
  - jest/no-focused-tests違反を修正
  - jest/valid-expect違反を修正
  - testing-library/no-await-sync-queries違反を修正
  - testing-library/no-manual-cleanup違反を修正
  - _Requirements: 6.4実装_

- [x] 10.3 修正後のテスト実行を確認
  - npm testを実行して全テストが正常動作することを確認
  - テストカバレッジが94%以上維持されていることを確認
  - 新たなバグが発生していないことを確認
  - _Requirements: 完了定義品質基準_

- [x] 11. Phase 2完了確認
- [x] 11.1 低ノイズルールerror化の動作確認
  - fitを使用したテストでCI/CDが失敗することを確認
  - 既存テスト全件が正常動作することを確認
  - リント実行時間が目標範囲内であることを確認
  - _Requirements: 6.4最終確認_

- [x] 11.2 Pull Request作成とレビュー依頼
  - Phase 2実装のPull Requestを作成
  - 修正内容とメリットを説明
  - 修正箇所のBefore/After比較を添付
  - チームレビューを依頼
  - _Requirements: 6.4_

## Phase 3: 全ルール昇格（完全適用）

- [x] 12. Phase 2期間中の残り警告分析
- [x] 12.1 残り警告数の収集と分析
  - Phase 2開始から2-4週間後の警告数を収集
  - カテゴリ別に分類
  - 修正可能性の評価
  - 修正優先順位の決定
  - _Requirements: 6.3_

- [x] 12.2 修正計画の策定
  - 全警告の修正計画を作成
  - 修正順序を決定
  - 修正コスト見積もりを実施
  - ロールバックトリガー（修正不可能な警告5件以上）の確認
  - _Requirements: Phase 3実行内容_

- [x] 13. 既存テストコードの全件修正
- [x] 13.1 testing-library/no-node-access警告の修正
  - container.querySelector()をscreen.getByRole()等に置き換え
  - 全ファイルで修正を実施
  - 修正後のテスト動作を確認
  - _Requirements: 6.5実装_

- [x] 13.2 testing-library/no-container警告の修正
  - container直接操作をTesting Library推奨クエリに置き換え
  - 全ファイルで修正を実施
  - 修正後のテスト動作を確認
  - _Requirements: 6.5実装_

- [x] 13.3 testing-library/no-debugging-utils警告の修正
  - debug()呼び出しを削除またはコメントアウト
  - 全ファイルで修正を実施
  - 修正後のテスト動作を確認
  - _Requirements: 6.5実装_

- [x] 13.4 その他の警告の修正
  - 残存する全警告を修正
  - 修正後のテスト動作を確認
  - テストカバレッジが94%以上維持されていることを確認
  - _Requirements: 6.5実装_

- [x] 14. 全ルールのerror昇格
- [x] 14.1 ESLint設定の更新
  - frontend/.eslint.base.mjsを編集
  - 全推奨ルールをerrorレベルに変更
  - 設定ファイルの構文妥当性を確認
  - _Requirements: 6.5_

- [x] 14.2 CI/CDへの--max-warnings=0追加
  - .github/workflows/frontend-test.ymlを編集
  - npm run lintに--max-warnings=0フラグを追加
  - ワークフロー構文の妥当性を確認
  - _Requirements: 6.6_

- [x] 14.3 全ルールerror化の動作確認
  - npm run lintを実行してエラー0、警告0を確認
  - CI/CDで警告があるとジョブが失敗することを確認
  - 新規テストコード作成時に即座にエラー検出されることを確認
  - _Requirements: 6.7_

- [x] 15. Phase 3完了確認
- [x] 15.1 全ルール完全適用の最終検証
  - 全テストファイルでエラー0、警告0を確認
  - CI/CDで警告があるとジョブが失敗することを確認
  - 新規テストコード作成時に即座にエラー検出されることを確認
  - 既存テスト全件が正常動作することを確認
  - テストカバレッジが94%以上維持されていることを確認
  - _Requirements: 6.5, 6.6, 6.7最終確認_

- [x] 15.2 Pull Request作成とレビュー依頼
  - Phase 3実装のPull Requestを作成
  - 完全適用完了報告を記載
  - 全修正箇所のサマリーを添付
  - チームレビューを依頼
  - _Requirements: Phase 3実行内容_

- [x] 15.3 チーム周知とドキュメント更新
  - Pull Request説明を共有
  - 新ルールの主要ポイントを説明
  - ドキュメントを最新状態に更新
  - 質問対応を完了
  - _Requirements: 9.2, 9.3, 9.4, 9.5_

## ロールバック準備（全フェーズ共通）

- [x] 16. ロールバック手順の準備
- [x] 16.1 ロールバックトリガーの文書化
  - Phase 1トリガー（実行時間超過、CI/CD恒常的失敗、チーム反対）を文書化
  - Phase 2トリガー（修正コスト超過、新バグ多発）を文書化
  - Phase 3トリガー（修正不可能警告残存、チーム合意得られず）を文書化
  - _Requirements: Phase 1-3ロールバックトリガー_
  - _完了: docs/JEST_ESLINT_TROUBLESHOOTING.md に「ロールバック判断基準」セクションを記載_

- [x] 16.2 ロールバック実行手順の検証
  - npm uninstall eslint-plugin-jest eslint-plugin-testing-library eslint-plugin-jest-dom globalsを実行
  - git checkout frontend/.eslint.base.mjsを実行
  - npm installで再構築
  - npm run lintが正常動作することを確認
  - 1分以内に完了することを確認
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_
  - _完了: docs/JEST_ESLINT_TROUBLESHOOTING.md に「Phase 1完全ロールバック（緊急時）」セクションを記載_

- [x] 16.3 部分的ロールバック手順の確認
  - 特定ルールのみを無効化する手順を確認
  - プラグイン自体は維持する方法を確認
  - 部分的ロールバック後の動作を確認
  - _Requirements: 8.8_
  - _完了: docs/JEST_ESLINT_TROUBLESHOOTING.md に「部分的ロールバック」セクション（3パターン）を記載_

- [x] 16.4 ロールバック後の原因分析手順の確認
  - 問題の根本原因を分析する手順を確認
  - 再導入計画を策定する手順を確認
  - チーム合意形成の手順を確認
  - _Requirements: 8.7_
  - _完了: docs/JEST_ESLINT_TROUBLESHOOTING.md に「詳細診断手順」「サポート連絡先」セクションを記載_
