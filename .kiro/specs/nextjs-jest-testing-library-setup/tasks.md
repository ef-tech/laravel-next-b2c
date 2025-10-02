# 実装タスク

## フェーズ1: 基盤構築（Week 1）

- [x] 1. テストフレームワークの依存関係をインストールする
- [x] 1.1 モノレポルート設定とワークスペース定義を追加する
  - ルートpackage.jsonにworkspaces配列を追加（admin-app/user-appを指定）
  - private: trueフィールドを設定してモノレポルートの公開を防止
  - _Requirements: 1.1_

- [x] 1.2 Jest 29とReact Testing Libraryの依存関係をインストールする
  - jest 29、jest-environment-jsdom 29をdevDependenciesに追加
  - @testing-library/react 16、@testing-library/jest-dom 6、@testing-library/user-event 14を追加
  - @types/jest 29をTypeScript型定義に追加
  - whatwg-fetch 3、msw 2、next-router-mock 0.9を追加
  - identity-obj-proxyをCSSモック用に追加
  - _Requirements: 1.1_

- [x] 1.3 ルートpackage.jsonにテストスクリプトを定義する
  - test: ルート全体テスト実行スクリプト
  - test:watch: ウォッチモード実行スクリプト
  - test:coverage: カバレッジ生成スクリプト
  - test:admin: admin-app専用テスト実行スクリプト
  - test:user: user-app専用テスト実行スクリプト
  - _Requirements: 1.2, 1.3, 1.4_

- [x] 2. Jest設定ファイルを構築する（モノレポ共通設定）
- [x] 2.1 共通Jest設定ファイルを作成する
  - jest.base.jsをルートに作成
  - testEnvironment: 'jsdom'を設定
  - setupFilesAfterEnv: jest.setup.tsを指定
  - testMatch: src配下のtest/specファイルパターンを定義
  - moduleNameMapper: @/パスエイリアスとCSSモックを設定
  - transformIgnorePatterns: node_modules除外パターンを設定
  - collectCoverageFrom: テスト対象ファイルパターンを定義
  - coverageThreshold: 全メトリクス80%を設定
  - _Requirements: 2.1, 10.1_

- [x] 2.2 ルートJest統括設定ファイルを作成する
  - jest.config.jsをルートに作成
  - projects配列にadmin-app/user-appのパスを指定
  - collectCoverageFrom: フロントエンド全体のカバレッジ対象を定義
  - _Requirements: 2.2_

- [x] 2.3 Admin AppのJest設定ファイルを作成する
  - frontend/admin-app/jest.config.jsを作成
  - next/jestのcreateJestConfigを使用してNext.js設定を自動適用
  - jest.base.jsを継承して共通設定を適用
  - displayName: 'admin-app'でテスト結果識別を設定
  - rootDir: __dirnameで相対パス解決を設定
  - setupFilesAfterEnv: ルートのjest.setup.tsを指定
  - moduleNameMapper: @/パスエイリアスを設定
  - _Requirements: 2.3_

- [x] 2.4 User AppのJest設定ファイルを作成する
  - frontend/user-app/jest.config.jsを作成
  - next/jestのcreateJestConfigを使用してNext.js設定を自動適用
  - jest.base.jsを継承して共通設定を適用
  - displayName: 'user-app'でテスト結果識別を設定
  - rootDir: __dirnameで相対パス解決を設定
  - setupFilesAfterEnv: ルートのjest.setup.tsを指定
  - moduleNameMapper: @/パスエイリアスを設定
  - _Requirements: 2.4_

- [x] 2.5 各フロントエンドアプリのpackage.jsonテストスクリプトを更新する
  - admin-app/user-appのpackage.jsonにtestスクリプトを追加
  - test:watchスクリプトをウォッチモード用に追加
  - test:coverageスクリプトをカバレッジ生成用に追加
  - _Requirements: 1.2, 1.3, 1.4_

- [x] 3. Jest共通セットアップファイルを作成する
- [x] 3.1 共通セットアップファイルの基盤を構築する
  - jest.setup.tsをルートに作成
  - @testing-library/jest-domをimportしてカスタムマッチャーを有効化
  - whatwg-fetchをimportしてfetch Polyfillを有効化
  - TextEncoder/TextDecoderのPolyfillを設定
  - _Requirements: 3.1_

- [x] 3.2 Next.js Image/Fontモックを設定する
  - next/imageをjest.mockで代替実装に置換（<img>タグレンダリング）
  - next/font/localをjest.mockで代替実装に置換（className: ''を返す）
  - _Requirements: 3.2, 3.3_

- [x] 3.3 Next.js Navigationモックを設定する
  - next/navigationをjest.mockでnext-router-mockに置換
  - App Router対応のuseRouter/useSearchParams/usePathnameモックを有効化
  - _Requirements: 3.4_

- [x] 3.4 MSW（Mock Service Worker）を設定する
  - msw/nodeからsetupServerをimport
  - setupServerインスタンスを作成（初期ハンドラーなし）
  - beforeAllフックでserver.listen（onUnhandledRequest: 'warn'）を実行
  - afterEachフックでserver.resetHandlersを実行
  - afterAllフックでserver.closeを実行
  - console.errorのReact警告を抑制するモックを追加
  - _Requirements: 3.5_

- [x] 4. テストユーティリティを整備する
- [x] 4.1 環境変数モックユーティリティを作成する
  - test-utils/env.tsを作成
  - setEnv関数を実装（process.envに環境変数を設定）
  - resetEnv関数を実装（process.envを元の状態に復元）
  - originalEnv変数で初期状態を保持
  - _Requirements: 5.1_

- [x] 4.2 Next.js Routerモック拡張ユーティリティを作成する
  - test-utils/router.tsを作成
  - setupRouter関数を実装（pathname/query設定用）
  - next-router-mockのsetCurrentUrl/pushを使用した実装
  - _Requirements: 5.2_

- [x] 4.3 カスタムレンダリング関数を作成する
  - test-utils/render.tsxを作成
  - カスタムrender関数を実装（将来的なProvider追加用の拡張ポイント）
  - @testing-library/reactの全エクスポートを再エクスポート
  - RenderOptions拡張用のCustomRenderOptions型を定義
  - _Requirements: 5.3_

- [x] 5. TypeScript統合設定を構築する
- [x] 5.1 テストファイル用TypeScript設定を作成する
  - tsconfig.test.jsonをルートに作成
  - tsconfig.jsonを継承してベース設定を適用
  - types配列にjest/@testing-library/jest-dom/nodeを追加
  - jsx: 'react-jsx'を設定してReact 19対応
  - include配列にテストファイルパターンを追加（*.test.ts, *.test.tsx）
  - jest.setup.ts、test-utils/**/*.tsをincludeに追加
  - _Requirements: 6.1, 6.2_

- [x] 6. 基盤動作確認テストを実施する
- [x] 6.1 空テストファイルで設定検証を実施する
  - frontend/admin-app/src/dummy.test.tsを作成（基本的なdescribe/itのみ）
  - npm testコマンドで全設定ファイルの読み込みを検証
  - jest.setup.tsの実行を検証
  - 設定エラーがないことを確認
  - _Requirements: 9.1_

- [x] 6.2 基盤動作確認後にダミーファイルを削除する
  - dummy.test.tsを削除
  - 基盤が正常動作することを確認済みとマーク
  - _Requirements: フェーズ1完了_

## フェーズ2: テストサンプル作成（Week 2）

- [ ] 7. Client Componentテストサンプルを作成する
- [ ] 7.1 テスト対象のButtonコンポーネントを作成する
  - frontend/admin-app/src/components/Button/Button.tsxを作成
  - children、onClick、variant、hrefプロパティを定義
  - Link使用時のナビゲーション機能を実装
  - primary/secondaryバリアント切り替え機能を実装
  - _Requirements: 4.1_

- [ ] 7.2 Buttonコンポーネントのテストを作成する
  - frontend/admin-app/src/components/Button/Button.test.tsxを作成
  - render/screen/fireEventをtest-utilsからimport
  - 正しいテキストでレンダリングされることをテスト
  - クリックイベントハンドラーが呼ばれることをテスト
  - Link使用時にナビゲーションが発生することをテスト（next-router-mock使用）
  - variantプロパティで異なるスタイルがレンダリングされることをテスト
  - _Requirements: 4.1_

- [ ] 8. Server Actionsテストサンプルを作成する
- [ ] 8.1 テスト対象のServer Actionsを作成する
  - frontend/admin-app/src/app/actions.tsを作成
  - 'use server'ディレクティブを追加
  - saveUser関数を実装（ユーザーデータ保存とrevalidatePath呼び出し）
  - バリデーションエラーハンドリング機能を実装
  - _Requirements: 4.2_

- [ ] 8.2 Server Actionsのテストを作成する
  - frontend/admin-app/src/app/actions.test.tsを作成
  - next/cacheのrevalidatePathをjest.mockでモック
  - saveUserが正常にユーザーを保存してrevalidatePathを呼ぶことをテスト
  - バリデーションエラー時に適切なエラーレスポンスを返すことをテスト
  - _Requirements: 4.2_

- [ ] 9. カスタムフックテストサンプルを作成する
- [ ] 9.1 テスト対象のuseAuthフックを作成する
  - frontend/admin-app/src/hooks/useAuth.tsを作成
  - useSearchParamsを使用したクエリパラメータ取得機能を実装
  - 非同期ユーザーデータフェッチ機能を実装
  - ローディング状態管理を実装
  - _Requirements: 4.3_

- [ ] 9.2 useAuthフックのテストを作成する
  - frontend/admin-app/src/hooks/useAuth.test.tsを作成
  - renderHook/waitForをimport
  - useSearchParamsをjest.mockでモック
  - クエリパラメータから認証トークンを取得することをテスト
  - マウント時にユーザーデータをフェッチすることをテスト（waitFor使用）
  - _Requirements: 4.3_

- [ ] 10. API Fetchテストサンプルを作成する（MSW使用）
- [ ] 10.1 テスト対象のAPI関数を作成する
  - frontend/admin-app/src/lib/api.tsを作成
  - fetchUsers関数を実装（ユーザー一覧取得API呼び出し）
  - エラーハンドリング機能を実装
  - _Requirements: 4.4_

- [ ] 10.2 API関数のテストを作成する（MSW使用）
  - frontend/admin-app/src/lib/api.test.tsを作成
  - msw（http/HttpResponse）をimport
  - jest.setupのserverインスタンスをimport
  - 成功レスポンスをモックしてユーザー一覧が取得できることをテスト
  - エラーレスポンス（4xx/5xx）をモックして適切にエラーハンドリングされることをテスト
  - _Requirements: 4.4_

- [ ] 11. テストサンプル実行検証を実施する
- [ ] 11.1 個別テストサンプルの実行を検証する
  - Button.test.tsxを単独実行して成功確認
  - actions.test.tsを単独実行して成功確認
  - useAuth.test.tsを単独実行して成功確認
  - api.test.tsを単独実行して成功確認
  - _Requirements: 9.2_

- [ ] 11.2 admin-app全体のテスト実行を検証する
  - npm test:adminコマンドで4種のテストが全て実行されることを確認
  - 全テストが成功することを確認
  - displayName: 'admin-app'がテスト結果に表示されることを確認
  - _Requirements: 9.2_

## フェーズ3: ドキュメント整備（Week 3）

- [ ] 12. テスト記述ガイドラインを作成する
- [ ] 12.1 テスト記述ガイドライン文書を作成する
  - frontend/TESTING_GUIDE.mdを作成
  - テストファイル命名規則を記載（*.test.{ts,tsx}）
  - Arrange-Act-Assertパターンの説明を記載
  - モック使用ガイドライン（jest.mock/MSW使用シーン）を記載
  - スナップショットテスト運用ルール（慎重な使用推奨）を記載
  - test-utilsの使用方法を記載
  - 4種のテストサンプルへの参照リンクを記載
  - _Requirements: 7.1_

- [ ] 13. トラブルシューティングガイドを作成する
- [ ] 13.1 トラブルシューティングガイド文書を作成する
  - frontend/TESTING_TROUBLESHOOTING.mdを作成
  - よくあるエラーと対処法を記載（モック設定忘れ、非同期未待機等）
  - 非同期テストのデバッグ方法を記載（waitFor/act警告対応）
  - モック関連の問題対処法を記載（jest.mock実行順序、MSWハンドラー設定）
  - CI/CD失敗時の対応を記載（Node.jsバージョン、メモリ不足、カバレッジ未達）
  - _Requirements: 7.2_

## フェーズ4: CI/CD統合（Week 4）

- [ ] 14. GitHub Actions CI/CD設定を構築する
- [ ] 14.1 フロントエンドテストワークフローを作成する
  - .github/workflows/frontend-test.ymlを作成
  - トリガー設定（push: main/develop, frontend/**、pull_request: main, frontend/**）
  - strategy.matrix設定（node-version: 18.x/20.x、app: admin-app/user-app）
  - _Requirements: 8.1, 8.2_

- [ ] 14.2 テストジョブステップを定義する
  - actions/checkout@v4でコードチェックアウト
  - actions/setup-node@v4でNode.js環境セットアップ
  - ルートでnpm ciを実行して依存関係インストール
  - 各アプリでnpm ciを実行して依存関係インストール
  - npm test -- --coverage --watchAll=false --maxWorkers=2でテスト実行
  - codecov/codecov-action@v3でカバレッジアップロード
  - _Requirements: 8.3_

- [ ] 14.3 カバレッジレポートジョブを定義する
  - coverage-reportジョブをtestジョブ完了後に実行（needs: test）
  - actions/download-artifact@v3でカバレッジファイルダウンロード
  - romeovs/lcov-reporter-action@v0.3.1でPRコメント生成
  - _Requirements: 8.4_

- [ ] 15. CI/CD動作検証を実施する
- [ ] 15.1 Pull Requestでワークフロートリガーを検証する
  - テスト用PRを作成してワークフロー実行を確認
  - トリガー条件（frontend/**パス）を検証
  - Node.js 18.x/20.xマトリックス実行を確認
  - admin-app/user-appマトリックス実行を確認
  - _Requirements: 8.1, 8.2_

- [ ] 15.2 カバレッジレポート生成とアップロードを検証する
  - テスト実行後にcoverage/ディレクトリが生成されることを確認
  - codecovへのアップロードが成功することを確認
  - PRコメントにカバレッジレポートが追加されることを確認
  - _Requirements: 8.3, 8.4_

## 最終検証

- [ ] 16. 全要件の動作確認を実施する
- [ ] 16.1 ルートからの全テストコマンドを検証する
  - npm testコマンドでadmin-app/user-app並列実行を確認
  - npm test:adminコマンドでadmin-appのみ実行を確認
  - npm test:userコマンドでuser-appのみ実行を確認
  - npm test:coverageコマンドでカバレッジレポート生成を確認
  - _Requirements: 9.1, 9.2, 9.3, 9.4_

- [ ] 16.2 各アプリディレクトリからのテストコマンドを検証する
  - admin-appディレクトリでnpm testを実行して成功確認
  - user-appディレクトリでnpm testを実行して成功確認
  - _Requirements: 9.5, 9.6_

- [ ] 16.3 カバレッジ閾値検証を実施する
  - カバレッジ80%以上のファイルでnpm test:coverageが成功することを確認
  - カバレッジ80%未満の状態を作成してエラーメッセージを確認
  - 閾値未達成時に該当メトリクスが表示されることを確認
  - _Requirements: 10.1, 10.2, 10.3_

- [ ] 16.4 既存品質管理ツールとの統合を検証する
  - ルートpackage.jsonのlint-staged設定にテストファイルパターンが含まれることを確認
  - .husky/pre-commitフックがlint-stagedを実行することを確認
  - npm run lintでテストファイルもESLint対象となることを確認
  - npm run formatでテストファイルもPrettier対象となることを確認
  - _Requirements: 11.1, 11.2, 11.3, 11.4_

- [ ] 17. 完了条件の最終確認を実施する
- [ ] 17.1 全Acceptance Criteriaの実装を検証する
  - requirements.mdの全11要件41 Acceptance Criteriaをチェックリスト化
  - 各Acceptance Criteriaが実装済みでテスト可能であることを確認
  - _Requirements: 全要件_

- [ ] 17.2 ドキュメント完成度を検証する
  - TESTING_GUIDE.mdが全セクション記載済みであることを確認
  - TESTING_TROUBLESHOOTING.mdが全セクション記載済みであることを確認
  - 4種のテストサンプルが正常動作することを確認
  - _Requirements: 7.1, 7.2, 4.1, 4.2, 4.3, 4.4_

- [ ] 17.3 CI/CD全パイプライン成功を検証する
  - GitHub Actionsでテストが自動実行されることを確認
  - カバレッジレポートがPRコメントに追加されることを確認
  - Node.js 18.x/20.xマトリックスが全て成功することを確認
  - admin-app/user-appマトリックスが全て成功することを確認
  - _Requirements: 8.1, 8.2, 8.3, 8.4_
