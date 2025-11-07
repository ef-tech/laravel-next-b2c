# 実装タスク: フロントエンドエラーメッセージ多言語化対応（i18n）

## 概要

next-intlを使用してフロントエンドアプリケーション（User App・Admin App）のエラーメッセージを日本語・英語に対応させます。NetworkError、Error Boundary、Global Error Boundaryの多言語化を実現し、バックエンドのAccept-Language検出との整合性を保ちます。

**主要目標**:
- i18n基盤実装（next-intl統合、翻訳リソース管理）
- NetworkError多言語化（後方互換性維持）
- Error Boundary多言語化（useTranslations統合）
- Global Error Boundary多言語化（自己完結型Provider）
- テスト実装（Unit 100%、Component 90%+、E2E）
- CI/CD統合（翻訳ファイル検証スクリプト）

---

## 実装タスク

### Phase 1: i18n基盤実装

- [x] 1. next-intlライブラリ依存関係のインストールと基本設定
- [x] 1.1 next-intlパッケージをUser AppとAdmin Appにインストールする
  - User Appのpackage.jsonにnext-intl追加
  - Admin Appのpackage.jsonにnext-intl追加
  - npm installを両アプリで実行
  - インストール後のバージョン確認
  - _要件: REQ-1.1_

- [x] 1.2 共通i18n設定ファイルを作成する
  - 共通ライブラリに言語設定を定義
  - サポート言語リスト（ja、en）を設定
  - デフォルト言語（ja）を設定
  - TypeScript型定義（Locale型）を作成
  - _要件: REQ-1.3、REQ-1.6_

- [x] 2. 翻訳リソースファイルの作成と管理
- [x] 2.1 User App用翻訳ファイルを作成する
  - messagesディレクトリを作成
  - ja.jsonファイルを作成（日本語翻訳リソース）
  - en.jsonファイルを作成（英語翻訳リソース）
  - エラーメッセージ階層構造を定義（errors.network, errors.boundary, errors.validation, errors.global）
  - _要件: REQ-1.2、REQ-6.1、REQ-6.2_

- [x] 2.2 Admin App用翻訳ファイルを作成する
  - messagesディレクトリを作成
  - ja.jsonファイルを作成（日本語翻訳リソース）
  - en.jsonファイルを作成（英語翻訳リソース）
  - エラーメッセージ階層構造を定義（User Appと同一構造）
  - _要件: REQ-1.2、REQ-6.1、REQ-6.2_

- [x] 2.3 翻訳キーのTypeScript型定義を作成する
  - MessagesインターフェースをTypeScriptで定義
  - next-intlのIntlMessages型拡張
  - 型安全な翻訳キー参照を実現
  - エラー階層の型定義（network、boundary、validation、global）
  - _要件: REQ-1.5、REQ-6.5_

- [x] 3. next-intl設定とミドルウェアの実装
- [x] 3.1 User Appのi18n設定ファイルを実装する
  - i18n.tsファイルを作成
  - getRequestConfig関数を実装
  - ロケール検証ロジックを実装
  - 翻訳メッセージの動的ロード処理を実装
  - 無効なロケールのフォールバック処理を実装
  - _要件: REQ-1.3、REQ-5.3_

- [x] 3.2 User Appのミドルウェアを実装する
  - middleware.tsファイルを作成
  - next-intlのcreateMiddleware関数を統合
  - ロケール検出を有効化（localeDetection: true）
  - URLパターンマッチング設定（API除外、静的ファイル除外）
  - _要件: REQ-1.4、REQ-5.1_

- [x] 3.3 Admin Appのi18n設定ファイルを実装する
  - i18n.tsファイルを作成（User Appと同様の構成）
  - getRequestConfig関数を実装
  - ロケール検証ロジックを実装
  - 翻訳メッセージの動的ロード処理を実装
  - 無効なロケールのフォールバック処理を実装
  - _要件: REQ-1.3、REQ-5.3_

- [x] 3.4 Admin Appのミドルウェアを実装する
  - middleware.tsファイルを作成
  - next-intlのcreateMiddleware関数を統合
  - ロケール検出を有効化
  - URLパターンマッチング設定
  - _要件: REQ-1.4、REQ-5.1_

### Phase 2: NetworkError多言語化

- [x] 4. NetworkErrorクラスの多言語化対応
- [x] 4.1 getDisplayMessage()メソッドのシグネチャを拡張する
  - オプショナル引数t（翻訳関数）を追加
  - 引数なしでも動作する後方互換性を維持
  - 引数あり時に翻訳キーから翻訳メッセージを取得
  - 引数なし時に既存のハードコード日本語メッセージを返却
  - _要件: REQ-2.1、REQ-2.2、REQ-10.1、REQ-10.2_

- [x] 4.2 エラー種別ごとの翻訳キーマッピングを実装する
  - タイムアウトエラーの翻訳キー（network.timeout）
  - 接続エラーの翻訳キー（network.connection）
  - 不明なエラーの翻訳キー（network.unknown）
  - 翻訳関数がnullの場合のフォールバック処理
  - _要件: REQ-2.3、REQ-2.4、REQ-10.3_

### Phase 3: Error Boundary多言語化

- [x] 5. Error Boundaryコンポーネントの多言語化対応
- [x] 5.1 User App Error BoundaryをuseTranslationsで多言語化する
  - 'use client'ディレクティブを追加
  - useTranslations('errors')フックを統合
  - エラータイトルメッセージを翻訳キーに置き換え
  - ステータスコード表示メッセージを翻訳キーに置き換え
  - 検証エラータイトルを翻訳キーに置き換え
  - Request ID表示メッセージを翻訳キーに置き換え
  - 再試行ボタンテキストを翻訳キーに置き換え
  - ホームに戻るボタンテキストを翻訳キーに置き換え
  - _要件: REQ-3.1、REQ-3.2、REQ-3.3_

- [x] 5.2 Admin App Error BoundaryをuseTranslationsで多言語化する
  - 'use client'ディレクティブを追加
  - useTranslations('errors')フックを統合
  - User Appと同様の翻訳キー置き換えを実施
  - 全ハードコード日本語メッセージを翻訳キーに変更
  - _要件: REQ-3.1、REQ-3.2、REQ-3.3_

- [x] 5.3 Error BoundaryでNetworkErrorの翻訳メッセージを表示する
  - NetworkError.getDisplayMessage()に翻訳関数を渡す
  - 翻訳キープレフィックス（network.）を適切に処理
  - ApiErrorとNetworkErrorの表示分岐を維持
  - エラーの種別に応じた適切なメッセージ表示
  - _要件: REQ-3.4、REQ-3.5_

### Phase 4: Global Error Boundary多言語化

- [x] 6. Global Error Boundaryの自己完結型i18n実装
- [x] 6.1 User App Global Error Boundaryの多言語化を実装する
  - 'use client'ディレクティブを追加（既存）
  - 静的メッセージ辞書（ja、en）を定義 ✅
  - ブラウザロケール検出ロジックを実装（document.documentElement.lang → navigator.languages） ✅
  - useEffectフックでロケール状態を設定 ✅
  - デフォルトロケール（ja）へのフォールバック処理 ✅
  - _要件: REQ-4.1、REQ-4.2、REQ-4.3_

- [x] 6.2 User App Global Error Boundaryのメッセージを翻訳する
  - タイトルメッセージ（"予期しないエラーが発生しました"）を辞書から取得 ✅
  - Error IDラベルを辞書から取得 ✅
  - お問い合わせメッセージを辞書から取得 ✅
  - 再試行ボタンテキストを辞書から取得 ✅
  - html lang属性を動的ロケールに設定 ✅
  - _要件: REQ-4.4、REQ-4.5、REQ-4.6_

- [x] 6.3 Admin App Global Error Boundaryの多言語化を実装する
  - User Appと同様の静的辞書を定義 ✅
  - ブラウザロケール検出ロジックを実装 ✅
  - useEffectフックでロケール状態を設定 ✅
  - 全メッセージを辞書から取得 ✅
  - html lang属性を動的ロケールに設定 ✅
  - _要件: REQ-4.1、REQ-4.2、REQ-4.3、REQ-4.4、REQ-4.5、REQ-4.6_

### Phase 5: ロケール検出とAccept-Language連携

- [x] 7. ロケール検出優先順位の実装と検証
- [x] 7.1 URL Prefixロケール検出を実装する ✅
  - URLパスから言語プレフィックス（/ja、/en）を検出 ✅
  - 検出したロケールをnext-intlに渡す ✅
  - 無効なプレフィックスの場合はフォールバック ✅
  - _要件: REQ-5.1_
  - **実装済み**: middleware.ts `localePrefix: "always"` + i18n.ts ロケール検証
  - **E2Eテスト**: 14/14 PASS (URLプレフィックス検出テスト3件成功)

- [x] 7.2 NEXT_LOCALE Cookie永続化を実装する ✅
  - ロケール変更時にCookieを設定 ✅
  - Cookie読み取りによるロケール復元 ✅
  - Cookie有効期限の設定 ✅
  - _要件: REQ-5.2_
  - **実装済み**: next-intl middleware自動Cookie設定、E2Eテスト検証完了（L60-117）
  - **E2Eテスト**: 14/14 PASS (Cookie永続化テスト3件成功)

- [x] 7.3 Accept-Language Headerロケール検出を実装する ✅
  - ブラウザのAccept-Language headerを解析 ✅
  - @formatjs/intl-localematcherで最適なロケールを選択 ✅
  - サポート言語リストとマッチング ✅
  - バックエンドのSetLocaleFromAcceptLanguageとの整合性確認 ✅
  - _要件: REQ-5.1、REQ-5.3、REQ-5.4_
  - **実装済み**: middleware.ts `localeDetection: true`、E2Eテスト検証完了（L119-173、優先順位L198-257）
  - **E2Eテスト**: 14/14 PASS (Accept-Language検出テスト5件 + 優先順位検証テスト3件成功)
  - **修正履歴**: `.next`ビルドキャッシュクリアでタイムアウト問題解決（13 failed → 14 passed）

### Phase 6: バンドルサイズ最適化

- [x] 8. バンドルサイズ削減とパフォーマンス最適化
- [x] 8.1 翻訳ファイルの動的インポートを実装する
  - getRequestConfig内でdynamic import使用
  - 使用言語のみをロード（未使用言語は除外）
  - ビルド時のcode splittingを確認
  - _要件: REQ-7.1、REQ-7.2_
  - **完了**: `i18n.ts` で `await import(\`../messages/${validLocale}.json\`)` 実装済み

- [x] 8.2 next-intlのtree-shakingを確認する
  - 使用していない機能の除外を確認
  - バンドルサイズ分析ツールで検証
  - 目標値（20KB未満増加）との比較
  - _要件: REQ-7.3_
  - **完了**: Middleware 45.4 kB, First Load JS 102 kB, tree-shaking確認済み

- [x] 8.3 Global Error Boundaryのバンドル影響を最小化する
  - 静的辞書importによるnext-intl依存回避
  - バンドルサイズへの影響を測定
  - 自己完結型実装によるサイズ削減を確認
  - _要件: REQ-7.4_
  - **完了**: `global-error.tsx` 静的辞書実装、next-intl依存なし

### Phase 7: テスト実装

- [x] 9. NetworkError Unit Testsの実装
- [x] 9.1 後方互換性テストを実装する
  - 引数なしでgetDisplayMessage()を呼び出し
  - 日本語ハードコードメッセージが返却されることを確認
  - タイムアウト、接続エラー、不明なエラーの全パターンをテスト
  - _要件: REQ-8.1、REQ-10.4_

- [x] 9.2 i18n有効化時のテストを実装する
  - モック翻訳関数を作成
  - getDisplayMessage(mockT)で翻訳メッセージが返却されることを確認
  - タイムアウト、接続エラー、不明なエラーの全パターンをテスト
  - 翻訳キーの正確性を検証
  - _要件: REQ-8.1_

- [x] 9.3 NetworkError Unit Tests 100%カバレッジを達成する
  - 全ブランチ（if文、switch文）をカバー
  - エッジケース（null、undefined、空文字列）をテスト
  - カバレッジレポートを生成して100%確認
  - _要件: REQ-8.2_

- [x] 10. Error Boundary Component Testsの実装
- [x] 10.1 User App Error Boundary日本語ロケールテストを実装する
  - NextIntlClientProviderでラップ ✅
  - ja.jsonメッセージを使用 ✅
  - NetworkErrorで日本語メッセージが表示されることを確認 ✅ (8テスト)
  - ApiErrorでステータスコードと検証エラーが日本語表示されることを確認 ✅
  - digestがある場合にRequest IDが日本語表示されることを確認 ✅
  - _要件: REQ-8.3_
  - **実装済み**: frontend/user-app/src/app/__tests__/error.test.tsx (16テスト全成功)

- [x] 10.2 User App Error Boundary英語ロケールテストを実装する
  - NextIntlClientProviderでラップ（en locale） ✅
  - en.jsonメッセージを使用 ✅
  - NetworkErrorで英語メッセージが表示されることを確認 ✅ (8テスト)
  - ApiErrorで英語メッセージが表示されることを確認 ✅
  - 全UI要素（タイトル、ボタン、ラベル）の英語表示を検証 ✅
  - _要件: REQ-8.3_
  - **実装済み**: 同上（日本語・英語合計16テスト）

- [x] 10.3 Admin App Error Boundaryテストを実装する
  - User Appと同様の日本語ロケールテスト ✅
  - User Appと同様の英語ロケールテスト ✅
  - Admin App固有の表示要素があれば追加テスト ✅
  - _要件: REQ-8.3_
  - **実装済み**: frontend/admin-app/src/app/__tests__/error.test.tsx (16テスト全成功)

- [x] 10.4 Error Boundary Component Tests 90%以上カバレッジを達成する ✅
  - 全エラータイプ（NetworkError、ApiError、generic Error）をカバー ✅
  - 両ロケール（ja、en）をカバー ✅
  - digestの有無、validation errorsの有無をカバー ✅
  - エッジケース（ApiError再構築、catch節エラーハンドリング、causeなしケース）をカバー ✅
  - カバレッジレポートを生成して **93.33%達成**（User App & Admin App） ✅
  - _要件: REQ-8.4_
  - **実装状況**: 23テスト全成功（User App & Admin App）、カバレッジ93.33%達成
  - **未カバー行**: L60-63 (401リダイレクト：E2Eテストでカバー済み）のみ
  - **備考**: 目標90%を大きく超える93.33%カバレッジ達成。ブランチカバレッジ96.15%。安定した品質を確保

- [ ] 11. Global Error Boundary Component Testsの実装
- [ ] 11.1 ブラウザロケール検出テストを実装する
  - document.documentElement.langをモック（ja設定）
  - 日本語メッセージが表示されることを確認
  - navigator.languagesをモック（en設定）
  - 英語メッセージが表示されることを確認
  - デフォルトロケール（ja）へのフォールバックを確認
  - _要件: REQ-8.5_

- [ ] 11.2 Global Error Boundary両ロケールテストを実装する
  - 日本語ロケール時の全メッセージ表示確認
  - 英語ロケール時の全メッセージ表示確認
  - digestがある場合のError ID表示確認
  - html lang属性が正しく設定されることを確認
  - _要件: REQ-8.5_

- [ ] 11.3 Global Error Boundary 90%以上カバレッジを達成する
  - ロケール検出の全パス（documentElement.lang、navigator.languages、フォールバック）をカバー
  - 両ロケール（ja、en）をカバー
  - digestの有無をカバー
  - カバレッジレポートを生成して90%以上確認
  - _要件: REQ-8.6_

- [ ] 12. E2E Tests（Playwright）の実装
- [ ] 12.1 ロケール検出E2Eテストを実装する
  - Accept-Languageヘッダーをjaに設定してページアクセス
  - html lang属性がjaであることを確認
  - エラートリガー後に日本語エラーメッセージ表示を確認
  - Accept-Languageヘッダーをenに設定してページアクセス
  - html lang属性がenであることを確認
  - エラートリガー後に英語エラーメッセージ表示を確認
  - _要件: REQ-8.7_

- [ ] 12.2 NEXT_LOCALE Cookie永続化E2Eテストを実装する
  - 日本語でページアクセス
  - NEXT_LOCALE cookieがjaに設定されることを確認
  - Accept-Languageヘッダーをクリア
  - ページリロード後もhtml lang属性がjaであることを確認（Cookie優先）
  - _要件: REQ-8.7_

- [ ] 12.3 NetworkErrorタイムアウトE2Eテストを実装する
  - 日本語ロケールでAPIタイムアウトをシミュレート
  - "リクエストがタイムアウトしました"メッセージが表示されることを確認
  - 英語ロケールでAPIタイムアウトをシミュレート
  - "The request timed out"メッセージが表示されることを確認
  - _要件: REQ-8.7_

- [ ] 12.4 ApiError検証エラーE2Eテストを実装する
  - 日本語ロケールで400 Bad Requestをモック
  - "入力エラー:"タイトルと検証エラーメッセージ（日本語）が表示されることを確認
  - 英語ロケールで400 Bad Requestをモック
  - "Validation Errors:"タイトルと検証エラーメッセージ（英語）が表示されることを確認
  - _要件: REQ-8.7_

- [ ] 12.5 Global Error Boundaryブラウザロケール検出E2Eテストを実装する
  - ブラウザ言語設定をenにエミュレート
  - グローバルエラーをトリガー
  - "An unexpected error occurred"メッセージが表示されることを確認
  - "Retry"ボタンが表示されることを確認
  - _要件: REQ-8.7_

### Phase 8: CI/CD統合

- [ ] 13. 翻訳ファイル検証スクリプトの作成
- [ ] 13.1 validate-i18n-messages.jsスクリプトを作成する
  - ja.jsonとen.jsonの構造を検証
  - 必須キー（errors.network, errors.boundary等）の存在確認
  - ネストレベルの検証
  - 翻訳値が文字列であることを確認
  - スクリプト実行時のエラーハンドリング
  - _要件: REQ-9.1、REQ-9.2_

- [ ] 13.2 validate-i18n-keys.jsスクリプトを作成する
  - ja.jsonとen.jsonのキー整合性確認
  - 片方にしか存在しないキーの検出
  - キー数の一致確認
  - 不足キーのレポート出力
  - _要件: REQ-9.3_

- [ ] 13.3 翻訳ファイル検証スクリプトのUnit Testを作成する
  - 有効な翻訳ファイルでエラーなしを確認
  - 必須キー不足時にエラーをスロー
  - ネストレベル不正時にエラーをスロー
  - キー不整合時にエラーをスロー
  - _要件: REQ-9.7_

- [ ] 14. GitHub Actionsワークフロー更新
- [ ] 14.1 frontend-test.ymlに翻訳ファイル検証を追加する
  - validate-i18n-messages.jsをテストステップに追加
  - validate-i18n-keys.jsをテストステップに追加
  - 検証失敗時にワークフローを失敗させる
  - User AppとAdmin App両方の翻訳ファイルを検証
  - _要件: REQ-9.4_

- [ ] 14.2 e2e-tests.ymlにi18n E2Eテストを統合する
  - ロケール検出E2Eテストを含める
  - NetworkError多言語化E2Eテストを含める
  - Error Boundary多言語化E2Eテストを含める
  - Global Error Boundary多言語化E2Eテストを含める
  - _要件: REQ-9.5_

- [ ] 15. Makefileタスク統合
- [ ] 15.1 make validate-i18nタスクを作成する
  - validate-i18n-messages.jsを実行
  - validate-i18n-keys.jsを実行
  - 両スクリプトの結果をレポート
  - タスク実行ログを出力
  - _要件: REQ-9.6_

- [ ] 15.2 make test-i18nタスクを作成する
  - NetworkError Unit Testsを実行
  - Error Boundary Component Testsを実行
  - Global Error Boundary Component Testsを実行
  - i18n E2E Testsを実行
  - カバレッジレポートを生成
  - _要件: REQ-9.6_

---

## 要件カバレッジマトリクス

| 要件ID | タスク番号 | 説明 |
|--------|-----------|------|
| REQ-1.1 | 1.1 | next-intlライブラリ統合 |
| REQ-1.2 | 2.1, 2.2 | 翻訳リソースJSON作成 |
| REQ-1.3 | 1.2, 3.1, 3.3 | i18n設定ファイル作成 |
| REQ-1.4 | 3.2, 3.4 | Middleware実装 |
| REQ-1.5 | 2.3 | TypeScript型定義 |
| REQ-1.6 | 1.2 | 翻訳キー命名規則 |
| REQ-2.1 | 4.1 | NetworkError.getDisplayMessage()拡張 |
| REQ-2.2 | 4.1 | オプショナル引数t |
| REQ-2.3 | 4.2 | 翻訳キーマッピング |
| REQ-2.4 | 4.2 | エラー種別分岐 |
| REQ-3.1 | 5.1, 5.2 | Error Boundary 'use client' |
| REQ-3.2 | 5.1, 5.2 | useTranslations統合 |
| REQ-3.3 | 5.1, 5.2 | ハードコード削除 |
| REQ-3.4 | 5.3 | NetworkError翻訳表示 |
| REQ-3.5 | 5.3 | ApiError/NetworkError分岐 |
| REQ-4.1 | 6.1, 6.3 | Global Error Boundary静的辞書 |
| REQ-4.2 | 6.1, 6.3 | ブラウザロケール検出 |
| REQ-4.3 | 6.1, 6.3 | useEffectフック |
| REQ-4.4 | 6.2, 6.3 | メッセージ翻訳 |
| REQ-4.5 | 6.2, 6.3 | 辞書から取得 |
| REQ-4.6 | 6.2, 6.3 | html lang属性 |
| REQ-5.1 | 7.1, 7.3 | ロケール検出優先順位 |
| REQ-5.2 | 7.2 | Cookie永続化 |
| REQ-5.3 | 3.1, 3.3, 7.3 | ロケールフォールバック |
| REQ-5.4 | 7.3 | バックエンド整合性 |
| REQ-6.1 | 2.1, 2.2 | 翻訳ファイル作成 |
| REQ-6.2 | 2.1, 2.2 | JSON形式管理 |
| REQ-6.5 | 2.3 | TypeScript型定義 |
| REQ-7.1 | 8.1 | 動的インポート |
| REQ-7.2 | 8.1 | code splitting |
| REQ-7.3 | 8.2 | tree-shaking |
| REQ-7.4 | 8.3 | バンドルサイズ目標 |
| REQ-8.1 | 9.1, 9.2 | NetworkError Unit Tests |
| REQ-8.2 | 9.3 | 100%カバレッジ |
| REQ-8.3 | 10.1, 10.2, 10.3 | Error Boundary Component Tests |
| REQ-8.4 | 10.4 | 90%以上カバレッジ |
| REQ-8.5 | 11.1, 11.2 | Global Error Boundary Component Tests |
| REQ-8.6 | 11.3 | 90%以上カバレッジ |
| REQ-8.7 | 12.1, 12.2, 12.3, 12.4, 12.5 | E2E Tests |
| REQ-9.1 | 13.1 | 翻訳ファイル検証スクリプト |
| REQ-9.2 | 13.1 | 構造検証 |
| REQ-9.3 | 13.2 | キー整合性チェック |
| REQ-9.4 | 14.1 | GitHub Actions frontend-test統合 |
| REQ-9.5 | 14.2 | GitHub Actions e2e-tests統合 |
| REQ-9.6 | 15.1, 15.2 | Makefileタスク統合 |
| REQ-9.7 | 13.3 | 検証スクリプトUnit Test |
| REQ-10.1 | 4.1 | 後方互換性維持 |
| REQ-10.2 | 4.1 | 引数なし動作 |
| REQ-10.3 | 4.2 | フォールバック処理 |
| REQ-10.4 | 9.1 | 後方互換性テスト |

---

## 推奨実装順序

1. **Phase 1 (タスク1-3)**: i18n基盤実装
   - まず基本インフラを整備（next-intl、翻訳ファイル、設定）
   - 他の全フェーズの前提条件となる

2. **Phase 2 (タスク4)**: NetworkError多言語化
   - 基盤実装後すぐに実施可能
   - 他のコンポーネントから参照される

3. **Phase 3 (タスク5)**: Error Boundary多言語化
   - NetworkError多言語化に依存
   - User AppとAdmin Appを並行実装可能

4. **Phase 4 (タスク6)**: Global Error Boundary多言語化
   - 独立した実装（next-intl非依存）
   - Phase 3と並行実施可能

5. **Phase 5 (タスク7)**: ロケール検出とAccept-Language連携
   - Phase 1の基盤実装後に実施
   - Phase 3-4と並行実施可能

6. **Phase 6 (タスク8)**: バンドルサイズ最適化
   - Phase 1-5完了後に実施
   - 実装コード確定後に最適化

7. **Phase 7 (タスク9-12)**: テスト実装
   - 各フェーズ完了後に対応するテストを実施
   - Unit Tests → Component Tests → E2E Testsの順

8. **Phase 8 (タスク13-15)**: CI/CD統合
   - 全テスト実装完了後に実施
   - 最終統合フェーズ

---

## タスク完了基準

各タスクは以下の条件を満たした場合に完了とする:

- [ ] 実装コードが動作する
- [ ] TypeScript型エラーがない
- [ ] ESLintエラーがない
- [ ] 対応するテストが合格する
- [ ] カバレッジ目標を達成する（該当タスクのみ）
- [ ] コードレビュー（自己レビュー）が完了する
- [ ] コミットメッセージが適切である

---

## 実装時の注意事項

1. **後方互換性**: NetworkError.getDisplayMessage()は引数なしでも動作すること
2. **型安全性**: 全翻訳キーがTypeScriptで型チェックされること
3. **バンドルサイズ**: 最終的なバンドルサイズ増加が20KB未満であること
4. **テストカバレッジ**: Unit Tests 100%、Component Tests 90%以上を達成すること
5. **ロケール検出**: URL → Cookie → Accept-Language → Default の優先順位を守ること
6. **エラーハンドリング**: 翻訳ファイル読み込み失敗時のフォールバック処理を実装すること
7. **CI/CD統合**: 全検証スクリプトがGitHub Actionsで自動実行されること
