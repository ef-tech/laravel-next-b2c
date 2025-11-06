# Requirements Document

## GitHub Issue Information

**Issue**: [#113](https://github.com/ef-tech/laravel-next-b2c/issues/113) - フロントエンドエラーメッセージ多言語化対応（i18n）
**Labels**: なし
**Milestone**: なし
**Assignees**: なし

### Original Issue Description

# フロントエンドエラーメッセージ多言語化対応（i18n）

## 背景と目的

### 背景
現在のプロジェクトでは、バックエンド（Laravel）は多言語対応済みですが、フロントエンド（Next.js）のエラーメッセージが日本語ハードコーディングされています：

- **バックエンド**: `SetLocaleFromAcceptLanguage` Middleware実装済み（Accept-Language header自動検出）
- **バックエンド**: `lang/ja/errors.php`、`lang/en/errors.php` による多言語リソース管理
- **フロントエンド**: NetworkError、Error Boundary、Global Error Boundaryのエラーメッセージが日本語固定
- **統一エラーハンドリング**: RFC 7807準拠、ApiError/NetworkError実装済み（Issue #43）

### 目的
フロントエンドのエラーメッセージを多言語化し、以下を達成する：

1. **一貫した多言語体験**: バックエンド/フロントエンド双方で言語設定を統一
2. **Accept-Language連携**: ブラウザ言語設定とバックエンド言語検出の整合性保証
3. **Error Boundaries多言語化**: Client Component内での型安全なi18n実装
4. **NetworkError多言語化**: 既存クラス構造を維持したまま翻訳機能を追加
5. **軽量実装**: next-intl等の最小限ライブラリで実装、バンドルサイズ増加<20KB

---

## カテゴリー

### 主カテゴリー: Code（コード実装）

### 詳細分類:
- **Frontend/Next.js**: i18n基盤実装、翻訳リソース管理、Error Boundariesコンポーネント改修
- **Frontend/Library**: NetworkError.getDisplayMessage()多言語化、型定義追加
- **Testing**: Unit Tests（Jest）、Component Tests（React Testing Library）、E2E Tests（Playwright）
- **CI/CD**: 翻訳ファイル検証スクリプト、GitHub Actionsワークフロー更新
- **Documentation**: i18n使用ガイド、翻訳ガイドライン、トラブルシューティング

---

## スコープ

### 対象範囲

#### 1. NetworkError多言語化
**対象ファイル**: `frontend/lib/network-error.ts`

**実装内容**:
- `getDisplayMessage()` メソッドのシグネチャ拡張（翻訳関数を引数として受け取る）
- 後方互換性維持（引数なしでも動作、英語フォールバック）
- エラー種別（timeout、offline、http_5xx）のキーマッピング
- 型安全な翻訳キー参照

**翻訳対象メッセージ**:
```typescript
// Before
"リクエストがタイムアウトしました。しばらくしてから再度お試しください。"
"ネットワーク接続に問題が発生しました。インターネット接続を確認して再度お試しください。"
"予期しないエラーが発生しました。しばらくしてから再度お試しください。"

// After (翻訳キー)
errors.network.timeout
errors.network.offline
errors.network.unknown
```

#### 2. Error Boundary多言語化
**対象ファイル**:
- `frontend/user-app/src/app/error.tsx`
- `frontend/admin-app/src/app/error.tsx`

**実装内容**:
- `useTranslations()` hook統合（next-intl）
- Client Component内での型安全な翻訳関数利用
- ApiError、NetworkError、汎用Error別の表示分岐
- Request ID表示メッセージの多言語化

**翻訳対象メッセージ**:
```typescript
// 固定テキスト
"エラーが発生しました"
"ステータスコード: {status}"
"入力エラー:"
"Request ID:"
"お問い合わせの際は、このIDをお伝えください"
"再試行"
"ホームに戻る"
"開発者向け情報（本番環境では非表示）"
```

#### 3. Global Error Boundary多言語化
**対象ファイル**:
- `frontend/user-app/src/app/global-error.tsx`
- `frontend/admin-app/src/app/global-error.tsx`

**実装内容**:
- Global Error Boundary専用Provider実装（layout.tsx外で描画されるため）
- ブラウザロケール検出（`document.documentElement.lang` → `navigator.languages`）
- `<html>`、`<body>`タグを含む完全なマークアップ
- 最小限の自己完結型Provider（静的import辞書）

**翻訳対象メッセージ**:
```typescript
"予期しないエラーが発生しました"
"申し訳ございませんが、エラーが発生しました。しばらくしてから再度お試しください。"
"Error ID:"
"お問い合わせの際は、このIDをお伝えください"
"スタックトレース（開発環境のみ）"
"再試行"
```

#### 4. i18n基盤実装
**対象ディレクトリ**:
```
frontend/
├── user-app/
│   ├── messages/
│   │   ├── ja.json          # 日本語翻訳
│   │   └── en.json          # 英語翻訳
│   └── src/
│       ├── i18n.ts          # i18n設定
│       └── middleware.ts     # ロケール検出
└── admin-app/
    ├── messages/
    │   ├── ja.json
    │   └── en.json
    └── src/
        ├── i18n.ts
        └── middleware.ts
```

**実装内容**:
- next-intl統合（Next.js 15 App Router公式推奨）
- 翻訳リソース管理（JSON形式、型安全なキー参照）
- Middleware実装（Accept-Language自動検出、Cookie永続化）
- TypeScript型定義（Locale型、翻訳キー型）
- Context API統合（useTranslations hook提供）

**ロケール検出優先順位**:
1. URL パラメータ (`/ja/...`, `/en/...`)
2. Cookie (`NEXT_LOCALE`)
3. Accept-Language ヘッダー
4. デフォルト (`ja`)

#### 5. 翻訳リソース管理
**翻訳ファイル構造**:
```json
{
  "errors": {
    "network": {
      "timeout": "リクエストがタイムアウトしました",
      "offline": "ネットワーク接続に問題が発生しました",
      "http_5xx": "サーバーエラーが発生しました",
      "unknown": "予期しないエラーが発生しました"
    },
    "boundary": {
      "title": "エラーが発生しました",
      "description": "申し訳ございません。予期しないエラーが発生しました。",
      "retry": "再試行",
      "back_to_home": "ホームに戻る",
      "request_id_label": "Request ID:",
      "request_id_help": "お問い合わせの際は、このIDをお伝えください"
    },
    "global": {
      "title": "システムエラー",
      "description": "システムで重大なエラーが発生しました。",
      "reload": "ページを再読み込み",
      "error_id_label": "Error ID:",
      "error_id_help": "お問い合わせの際は、このIDをお伝えください"
    }
  }
}
```

**型安全性**:
- TypeScript型定義自動生成（翻訳キーのネストパス型チェック）
- `errors.network.timeout` のようなキーを型レベルで検証
- CI/CDでの翻訳キー整合性チェック（日本語/英語でキー一致確認）

### 対象外範囲
- ❌ **API通信エラーメッセージ**: バックエンドから返却されるRFC 7807エラーメッセージ（既にバックエンド多言語対応済み）
- ❌ **ページコンテンツ全体のi18n**: エラーメッセージ以外のUI文言は別Issue
- ❌ **動的ロケール切り替えUI**: ユーザーによる手動言語切り替え機能は別Issue
- ❌ **複数形対応**: 単数形/複数形の文法的処理は必要に応じて別Issue
- ❌ **日本語/英語以外の言語**: 中国語、韓国語等の追加は別Issue

---

## Extracted Information

### Technology Stack

**Backend**:
- Laravel 12
- SetLocaleFromAcceptLanguage Middleware（Accept-Language header自動検出）
- 多言語リソース管理（`lang/ja/errors.php`、`lang/en/errors.php`）

**Frontend**:
- Next.js 15.5
- React 19
- next-intl（i18n公式推奨ライブラリ）
- TypeScript（型安全な翻訳キー参照）

**Infrastructure**:
- Docker Compose（開発環境統合）
- GitHub Actions（CI/CD自動化）

**Tools**:
- Jest 29（Unit Tests）
- React Testing Library 16（Component Tests）
- Playwright 1.47.2（E2E Tests）
- TypeScript（型定義生成）

### Project Structure

**i18n基盤ディレクトリ**:
```
frontend/
├── user-app/
│   ├── messages/
│   │   ├── ja.json          # 日本語翻訳リソース
│   │   └── en.json          # 英語翻訳リソース
│   └── src/
│       ├── i18n.ts          # i18n設定ファイル
│       └── middleware.ts     # ロケール検出Middleware
└── admin-app/
    ├── messages/
    │   ├── ja.json
    │   └── en.json
    └── src/
        ├── i18n.ts
        └── middleware.ts
```

**改修対象ファイル**:
- `frontend/lib/network-error.ts`: NetworkError多言語化
- `frontend/user-app/src/app/error.tsx`: Error Boundary多言語化
- `frontend/admin-app/src/app/error.tsx`: Error Boundary多言語化
- `frontend/user-app/src/app/global-error.tsx`: Global Error Boundary多言語化
- `frontend/admin-app/src/app/global-error.tsx`: Global Error Boundary多言語化

**CI/CD検証スクリプト**:
- `scripts/validate-i18n-messages.js`: JSON構文チェック
- `scripts/validate-i18n-keys.js`: 日英翻訳キー整合性チェック

### Requirements Hints

Based on issue analysis:

1. **NetworkError多言語化**:
   - `getDisplayMessage()`メソッドのシグネチャ拡張（翻訳関数を引数として受け取る）
   - 後方互換性維持（引数なしでも動作、英語フォールバック）

2. **Error Boundary多言語化**:
   - `useTranslations()` hook統合（user-app、admin-app）
   - Client Component内での型安全なi18n実装

3. **Global Error Boundary多言語化**:
   - 自己完結型Provider実装、ブラウザロケール検出
   - `document.documentElement.lang` → `navigator.languages` フォールバック

4. **i18n基盤実装**:
   - next-intl統合、翻訳リソース管理（JSON）、Middleware実装（Accept-Language自動検出）
   - ロケール検出優先順位: URL → Cookie → Accept-Language → デフォルト

5. **Accept-Language連携**:
   - ブラウザ言語設定とバックエンド言語検出の整合性保証
   - Cookie（NEXT_LOCALE）とバックエンド連携

6. **翻訳リソース管理**:
   - TypeScript型定義、翻訳キー整合性チェック（CI/CD）
   - `errors.network.timeout` のような型安全なキー参照

7. **バンドルサイズ最適化**:
   - バンドルサイズ増加<20KB
   - Tree-shaking、動的import活用

8. **テスト実装**:
   - Unit Tests（NetworkError 100%カバレッジ）
   - Component Tests（Error Boundary 90%以上カバレッジ）
   - E2E Tests（Playwright、ロケールマトリックステスト）

9. **CI/CD統合**:
   - 翻訳ファイル検証スクリプト（validate-i18n-messages.js、validate-i18n-keys.js）
   - GitHub Actionsワークフロー更新（frontend-test.yml、e2e-tests.yml）

10. **後方互換性維持**:
    - NetworkError引数なしでも動作、英語フォールバック
    - 段階的移行（既存コードは動作継続）

### TODO Items from Issue

**Phase 0: 事前準備・調査（2-3時間）**
- [ ] 現状ファイル確認（network-error.ts、error.tsx、global-error.tsx）
- [ ] 技術選定（next-intl採用決定）
- [ ] 対応言語決定（日本語、英語）

**Phase 1: 基盤実装（4-6時間）**
- [ ] next-intl依存関係インストール（user-app、admin-app）
- [ ] 翻訳ファイル作成（ja.json、en.json）
- [ ] i18n設定ファイル作成（i18n.ts）
- [ ] Middleware作成（middleware.ts）
- [ ] Next.js設定更新（next.config.ts）

**Phase 2: コンポーネント実装（3-4時間）**
- [ ] NetworkError.getDisplayMessage(t)改修
- [ ] Error Boundary改修（user-app、admin-app）
- [ ] Global Error Boundary改修（user-app、admin-app）

**Phase 3: テスト実装（6-8時間）**
- [ ] NetworkError Unit Tests実装（100%カバレッジ）
- [ ] Error Boundary Component Tests実装（90%以上カバレッジ）
- [ ] E2E Tests実装（Playwright）

**Phase 4: CI/CD統合（2-3時間）**
- [ ] 翻訳ファイル検証スクリプト作成（validate-i18n-messages.js、validate-i18n-keys.js）
- [ ] GitHub Actions frontend-test.yml更新
- [ ] GitHub Actions e2e-tests.yml更新
- [ ] Makefileタスク統合（make validate-i18n、make test-i18n）

---

## Requirements

### Requirement 1: i18n基盤実装
**目的**: フロントエンド開発者として、Next.js 15 App Routerでnext-intlを活用した多言語化基盤を構築したい。これにより、エラーメッセージの翻訳リソースを一元管理でき、型安全な翻訳キー参照が可能になる。

#### 受け入れ基準

1. WHEN User AppまたはAdmin Appが起動する THEN フロントエンドアプリケーション SHALL next-intlライブラリを統合し、翻訳リソースをJSON形式で管理する
2. WHEN 開発者が翻訳リソースを参照する THEN フロントエンドアプリケーション SHALL `frontend/user-app/messages/{ja,en}.json`および`frontend/admin-app/messages/{ja,en}.json`に翻訳ファイルを配置する
3. WHEN i18n設定を初期化する THEN フロントエンドアプリケーション SHALL `frontend/user-app/src/i18n.ts`および`frontend/admin-app/src/i18n.ts`でnext-intl設定を定義する
4. WHEN ロケール検出を実行する THEN フロントエンドアプリケーション SHALL `frontend/user-app/src/middleware.ts`および`frontend/admin-app/src/middleware.ts`でAccept-Language自動検出、Cookie永続化を実装する
5. IF ロケール検出優先順位を適用する THEN フロントエンドアプリケーション SHALL URLパラメータ → Cookie (NEXT_LOCALE) → Accept-Languageヘッダー → デフォルト(ja)の順序でロケールを決定する
6. WHEN TypeScript型定義を生成する THEN フロントエンドアプリケーション SHALL 翻訳キー（例: `errors.network.timeout`）の型レベル検証を可能にする

### Requirement 2: NetworkError多言語化
**目的**: フロントエンド開発者として、NetworkErrorクラスに翻訳機能を追加したい。これにより、既存のエラーハンドリング構造を維持したまま、多言語対応のエラーメッセージを表示できる。

#### 受け入れ基準

1. WHEN NetworkError.getDisplayMessage()が呼び出される THEN NetworkErrorクラス SHALL 翻訳関数を引数として受け取り、エラー種別に応じた翻訳キーを呼び出す
2. IF エラー種別がTIMEOUTである THEN NetworkErrorクラス SHALL `errors.network.timeout`翻訳キーを参照する
3. IF エラー種別がREQUEST_FAILEDである THEN NetworkErrorクラス SHALL `errors.network.offline`翻訳キーを参照する
4. IF エラー種別がHTTP_5XXである THEN NetworkErrorクラス SHALL `errors.network.http_5xx`翻訳キーを参照する
5. IF エラー種別が未定義である THEN NetworkErrorクラス SHALL `errors.network.unknown`翻訳キーを参照する
6. WHEN 翻訳関数が渡されない場合（後方互換性） THEN NetworkErrorクラス SHALL 英語フォールバックメッセージを返す
7. WHEN TypeScript型チェックを実行する THEN NetworkErrorクラス SHALL 型安全な翻訳キー参照を保証する

### Requirement 3: Error Boundary多言語化
**目的**: エンドユーザーとして、エラー発生時に自分のブラウザ言語設定に応じたエラーメッセージを表示したい。これにより、エラー内容を理解しやすくなり、適切な対応ができる。

#### 受け入れ基準

1. WHEN User AppまたはAdmin AppのError Boundaryが描画される THEN フロントエンドアプリケーション SHALL `useTranslations()` hookを統合し、Client Component内で型安全な翻訳関数を利用する
2. WHEN ApiErrorが発生する THEN Error Boundary SHALL ステータスコード、入力エラー詳細、Request IDを多言語化して表示する
3. WHEN NetworkErrorが発生する THEN Error Boundary SHALL NetworkError.getDisplayMessage()を呼び出し、翻訳されたネットワークエラーメッセージを表示する
4. WHEN 汎用Errorが発生する THEN Error Boundary SHALL `errors.boundary.title`および`errors.boundary.description`翻訳キーを参照して表示する
5. WHEN Request IDが存在する THEN Error Boundary SHALL `errors.boundary.request_id_label`および`errors.boundary.request_id_help`翻訳キーを使用してRequest ID情報を表示する
6. WHEN ユーザーが「再試行」ボタンをクリックする THEN Error Boundary SHALL `errors.boundary.retry`翻訳キーでボタンラベルを表示し、reset()関数を実行する
7. WHEN ユーザーが「ホームに戻る」リンクをクリックする THEN Error Boundary SHALL `errors.boundary.back_to_home`翻訳キーでリンクラベルを表示し、ホームページに遷移する

### Requirement 4: Global Error Boundary多言語化
**目的**: エンドユーザーとして、アプリケーション全体でキャッチされない重大なエラーが発生した場合でも、自分のブラウザ言語設定に応じたエラーメッセージを表示したい。これにより、システムエラー発生時でも適切な案内を受けられる。

#### 受け入れ基準

1. WHEN User AppまたはAdmin AppのGlobal Error Boundaryが描画される THEN フロントエンドアプリケーション SHALL layout.tsx外で描画されるため、自己完結型Provider実装を使用する
2. WHEN ブラウザロケールを検出する THEN Global Error Boundary SHALL `document.documentElement.lang`を優先し、存在しない場合は`navigator.languages`配列の最初の言語を使用する
3. WHEN Global Error Boundaryが完全なマークアップを生成する THEN フロントエンドアプリケーション SHALL `<html>`、`<body>`タグを含む完全なHTMLを描画する
4. WHEN 翻訳辞書をロードする THEN Global Error Boundary SHALL 静的import辞書（動的import不要）を使用し、最小限のバンドルサイズを維持する
5. WHEN システムエラーメッセージを表示する THEN Global Error Boundary SHALL `errors.global.title`および`errors.global.description`翻訳キーを参照する
6. WHEN Error IDを表示する THEN Global Error Boundary SHALL `errors.global.error_id_label`および`errors.global.error_id_help`翻訳キーを使用する
7. IF 開発環境である THEN Global Error Boundary SHALL スタックトレース情報を表示し、翻訳キー`errors.global.stack_trace`を使用する
8. WHEN ユーザーが「再試行」ボタンをクリックする THEN Global Error Boundary SHALL `errors.global.reload`翻訳キーでボタンラベルを表示し、ページをリロードする

### Requirement 5: Accept-Language連携
**目的**: エンドユーザーとして、ブラウザの言語設定がバックエンドとフロントエンドで統一されることを望む。これにより、一貫した多言語体験を得られる。

#### 受け入れ基準

1. WHEN フロントエンドがAPIリクエストを送信する THEN フロントエンドアプリケーション SHALL Accept-Languageヘッダーを自動付与する
2. WHEN ロケールがCookie（NEXT_LOCALE）に保存される THEN フロントエンドアプリケーション SHALL バックエンドのSetLocaleFromAcceptLanguage Middlewareと連携し、同じロケールを使用する
3. WHEN ブラウザ言語設定が変更される THEN フロントエンドアプリケーション SHALL ロケール検出優先順位に従い、新しいロケールを適用する
4. WHEN バックエンドがエラーレスポンスを返却する THEN フロントエンドアプリケーション SHALL バックエンドの多言語エラーメッセージ（RFC 7807準拠）をそのまま表示する

### Requirement 6: 翻訳リソース管理
**目的**: フロントエンド開発者として、翻訳リソースを型安全に管理し、CI/CDで整合性を自動検証したい。これにより、翻訳漏れや型エラーを早期に検出できる。

#### 受け入れ基準

1. WHEN 翻訳ファイルを作成する THEN フロントエンドアプリケーション SHALL JSON形式で`errors.network.*`、`errors.boundary.*`、`errors.global.*`の翻訳キーを定義する
2. WHEN 日本語翻訳リソースを提供する THEN フロントエンドアプリケーション SHALL `messages/ja.json`にすべての翻訳キーに対応する日本語メッセージを記載する
3. WHEN 英語翻訳リソースを提供する THEN フロントエンドアプリケーション SHALL `messages/en.json`にすべての翻訳キーに対応する英語メッセージを記載する
4. WHEN TypeScript型定義を自動生成する THEN フロントエンドアプリケーション SHALL 翻訳キーのネストパス（例: `errors.network.timeout`）を型レベルで検証する
5. WHEN CI/CDパイプラインが実行される THEN フロントエンドアプリケーション SHALL `scripts/validate-i18n-messages.js`でJSON構文チェックを実行する
6. WHEN CI/CDパイプラインが実行される THEN フロントエンドアプリケーション SHALL `scripts/validate-i18n-keys.js`で日本語/英語の翻訳キー整合性（キー一致）を確認する
7. IF 翻訳キーが不足している THEN CI/CD検証スクリプト SHALL ビルドを失敗させ、不足しているキーをエラーメッセージに表示する

### Requirement 7: バンドルサイズ最適化
**目的**: フロントエンド開発者として、i18n導入によるバンドルサイズ増加を最小限に抑えたい。これにより、ページ読み込み速度を維持し、ユーザー体験を損なわない。

#### 受け入れ基準

1. WHEN next-intlライブラリを導入する THEN フロントエンドアプリケーション SHALL バンドルサイズ増加を20KB未満に抑える
2. WHEN 翻訳リソースをロードする THEN フロントエンドアプリケーション SHALL 動的import（`messages/{locale}.json`）を活用し、使用されないロケールを除外する
3. WHEN 本番ビルドを実行する THEN フロントエンドアプリケーション SHALL Tree-shakingにより未使用翻訳キーを除外する
4. WHEN バンドルサイズを測定する THEN フロントエンドアプリケーション SHALL next-intl導入前後の差分を記録し、20KB未満を確認する

### Requirement 8: テスト実装
**目的**: フロントエンド開発者として、i18n実装の品質を保証するため、包括的なテストスイートを作成したい。これにより、リグレッションを防ぎ、将来の変更に対する信頼性を確保できる。

#### 受け入れ基準

1. WHEN NetworkErrorのUnit Testsを実行する THEN テストスイート SHALL 各エラー種別（timeout、offline、http_5xx、unknown）で正しい翻訳キーが呼び出されることを検証し、100%カバレッジを達成する
2. WHEN NetworkErrorの後方互換性をテストする THEN テストスイート SHALL 翻訳関数が渡されない場合に英語フォールバックメッセージが返されることを検証する
3. WHEN Error BoundaryのComponent Testsを実行する THEN テストスイート SHALL React Testing Libraryで日本語/英語UI表示、reset()ボタンクリック、Request ID表示を検証し、90%以上のカバレッジを達成する
4. WHEN Global Error BoundaryのComponent Testsを実行する THEN テストスイート SHALL 日本語/英語UI表示、ブラウザロケール検出、完全なHTML生成を検証し、90%以上のカバレッジを達成する
5. WHEN E2E Testsを実行する THEN テストスイート SHALL Playwrightで `/ja/trigger-error` → 日本語UI、`/en/trigger-error` → 英語UIの表示を検証する
6. WHEN E2E Testsでネットワークエラーをシミュレートする THEN テストスイート SHALL `page.route().abort()`でネットワークエラーを発生させ、各言語で正しいメッセージが表示されることを検証する
7. WHEN E2E TestsでAccept-Languageヘッダー連携をテストする THEN テストスイート SHALL `Accept-Language: en-US`ヘッダーを設定し、英語UIが表示されることを検証する

### Requirement 9: CI/CD統合
**目的**: フロントエンド開発者として、CI/CDパイプラインでi18n関連の検証を自動化したい。これにより、翻訳ファイルの品質を継続的に保証し、手動チェックの負担を削減できる。

#### 受け入れ基準

1. WHEN CI/CDパイプラインが実行される THEN フロントエンドアプリケーション SHALL `scripts/validate-i18n-messages.js`を実行し、すべての翻訳ファイルのJSON構文をチェックする
2. WHEN CI/CDパイプラインが実行される THEN フロントエンドアプリケーション SHALL `scripts/validate-i18n-keys.js`を実行し、日本語/英語の翻訳キー整合性を確認する
3. IF JSON構文エラーが存在する THEN CI/CDパイプライン SHALL ビルドを失敗させ、エラー詳細（ファイル名、行番号）を表示する
4. IF 翻訳キーの不一致が存在する THEN CI/CDパイプライン SHALL ビルドを失敗させ、不一致キーのリストを表示する
5. WHEN GitHub Actionsワークフロー（frontend-test.yml）を更新する THEN CI/CDパイプライン SHALL 翻訳ファイル検証ステップを追加する
6. WHEN GitHub Actionsワークフロー（e2e-tests.yml）を更新する THEN CI/CDパイプライン SHALL ロケールマトリックステスト（日本語、英語）を追加する
7. WHEN Makefileタスクを統合する THEN プロジェクト SHALL `make validate-i18n`（翻訳ファイル検証）および`make test-i18n`（i18n関連テスト実行）コマンドを提供する

### Requirement 10: 後方互換性維持
**目的**: フロントエンド開発者として、既存のNetworkError利用箇所を破壊せずに、段階的にi18n対応を進めたい。これにより、リリースリスクを最小化し、安全に移行できる。

#### 受け入れ基準

1. WHEN 既存のNetworkError.getDisplayMessage()呼び出しが存在する THEN NetworkErrorクラス SHALL 引数なしでも動作し、英語フォールバックメッセージを返す
2. WHEN 新しいNetworkError.getDisplayMessage(t)呼び出しを追加する THEN NetworkErrorクラス SHALL 翻訳関数を受け取り、多言語化されたメッセージを返す
3. WHEN TypeScriptコンパイラチェックを実行する THEN フロントエンドアプリケーション SHALL 既存コードがコンパイルエラーなしでビルドされることを保証する
4. WHEN 段階的移行を実施する THEN フロントエンドアプリケーション SHALL 既存コード（引数なし）と新規コード（翻訳関数あり）が共存できる設計を維持する
5. WHEN デプロイを実行する THEN フロントエンドアプリケーション SHALL 既存機能の動作に影響を与えず、i18n機能を追加する
