# Requirements Document

## Introduction

本機能は、フロントエンドモノレポ構成における共通ライブラリ（`frontend/lib/`）のコード重複問題を解消するものです。現在、User AppとAdmin Appが同一のコード（api-client.ts、api-error.ts、network-error.ts等、合計約280行）をそれぞれコピー保持しており、合計約560行の重複コードが存在します。

この要件では、TypeScriptパスエイリアス（`@shared/*`）を使用して共通ライブラリを参照する仕組みを確立し、重複コードを削除することで、メンテナンス性の向上とバージョン整合性の保証を実現します。これにより、共通コード修正時の一元管理が可能となり、開発効率が大幅に向上します。

**ビジネス価値:**
- メンテナンス工数削減（共通コード修正が1箇所で完結）
- バージョン不整合リスクの根本解消
- コードベースの約560行削減による可読性向上
- 将来的な共通ライブラリ拡張の基盤確立

---

## Requirements

### Requirement 1: TypeScriptパスエイリアス設定

**Objective:** 開発者として、TypeScriptパスエイリアス（`@shared/*`）を使用して共通ライブラリ（`frontend/lib/`）を参照できるようにしたい。これにより、各アプリケーションから共通コードをシンプルに参照できるようにする。

#### Acceptance Criteria

1. WHEN User AppまたはAdmin Appの`tsconfig.json`が読み込まれる THEN ビルドシステムは`@shared/*`パスエイリアスを`../lib/*`に解決すること
2. WHEN 開発者が`import { ApiClient } from '@shared/api-client'`と記述する THEN TypeScriptコンパイラは`frontend/lib/api-client.ts`を正しく解決すること
3. IF tsconfig.jsonに`paths`設定で`@shared/*`エイリアスが定義されている THEN IDEは型補完とインポート解決を提供すること
4. WHERE User AppまたはAdmin Appのソースコード内 THE TypeScriptコンパイラは相対パス`../lib/*`への自動変換を実行すること

---

### Requirement 2: Next.js webpack alias設定

**Objective:** 開発者として、Next.jsビルド時にwebpack aliasが正しく機能することで、パスエイリアス解決の安定性を保証したい。これにより、開発環境と本番ビルドの両方で一貫したパス解決を実現する。

#### Acceptance Criteria

1. WHEN Next.jsがwebpack設定を読み込む THEN Next.jsは`@shared`エイリアスを`path.resolve(__dirname, '../lib')`に解決すること
2. IF next.config.tsに`webpack.resolve.alias`設定が存在する THEN 開発サーバー起動時とビルド時にエイリアスが適用されること
3. WHEN `npm run dev`または`npm run build`が実行される THEN webpackは`@shared/*`インポートを正しくバンドルすること
4. WHERE User AppまたはAdmin Appのビルドプロセス内 THE webpackは共通ライブラリファイルを適切にモジュール解決すること

---

### Requirement 3: Jest moduleNameMapper設定

**Objective:** 開発者として、Jestテスト実行時に`@shared/*`パスエイリアスが正しく解決されることで、テストコードでも共通ライブラリ参照が機能するようにしたい。これにより、ユニットテストとE2Eテストの両方でパスエイリアスの一貫性を保証する。

#### Acceptance Criteria

1. WHEN Jestが`jest.config.js`の`moduleNameMapper`を読み込む THEN Jestは`@shared/(.*)`パターンを`<rootDir>/../lib/$1`にマッピングすること
2. IF テストファイルに`import { ApiClient } from '@shared/api-client'`が記述されている THEN Jestは正しくモジュールを解決してテストを実行すること
3. WHEN `npm run test`または`npm run test:coverage`が実行される THEN 全テストが`@shared/*`インポートを正しく解決してパスすること
4. WHERE User AppまたはAdmin Appのテストスイート内 THE Jestは共通ライブラリモジュールを正常にインポートすること

---

### Requirement 4: import文の一括更新

**Objective:** 開発者として、既存の`@/lib/*`インポートを`@shared/*`に一括置換できるようにしたい。これにより、全ファイルで一貫したインポートパスを使用し、パスエイリアス移行を完了させる。

#### Acceptance Criteria

1. WHEN User AppまたはAdmin Appのソースコード内で`from '@/lib/api-client'`が検索される THEN すべての該当箇所が特定されること
2. IF import文置換スクリプトが実行される THEN `@/lib/api-client`、`@/lib/api-error`、`@/lib/network-error`の全インポートが`@shared/*`に置換されること
3. WHEN import文置換が完了する THEN TypeScript型チェック（`npm run type-check`）がエラーなく完了すること
4. WHERE User AppまたはAdmin Appの全`.ts`および`.tsx`ファイル THE インポートパスは統一的に`@shared/*`形式を使用すること
5. IF 置換対象外のアプリ固有ファイル（`api.ts`、`env.ts`）が存在する THEN これらのファイルは変更されずに保持されること

---

### Requirement 5: 重複ファイルの削除

**Objective:** 開発者として、User AppとAdmin Appから重複コードファイル（api-client.ts、api-error.ts、network-error.ts）を安全に削除できるようにしたい。これにより、共通ライブラリへの完全移行を実現し、約560行のコード削減を達成する。

#### Acceptance Criteria

1. WHEN `@shared/*`パスエイリアス設定が完了し、import文更新が完了している THEN 以下の6ファイルが削除可能な状態であること:
   - `frontend/user-app/src/lib/api-client.ts`
   - `frontend/user-app/src/lib/api-error.ts`
   - `frontend/user-app/src/lib/network-error.ts`
   - `frontend/admin-app/src/lib/api-client.ts`
   - `frontend/admin-app/src/lib/api-error.ts`
   - `frontend/admin-app/src/lib/network-error.ts`
2. IF 重複ファイル削除が実行される THEN アプリ固有ファイル（`api.ts`、`env.ts`）は保持されること
3. WHEN 重複ファイル削除後にTypeScript型チェックが実行される THEN エラーが発生しないこと
4. WHERE User AppまたはAdmin Appの`src/lib/`ディレクトリ THE 共通ライブラリファイルは存在せず、アプリ固有ファイルのみが存在すること

---

### Requirement 6: テスト実行とビルド確認

**Objective:** 開発者として、パスエイリアス移行後も全テスト（Jest/E2E）とビルドが正常に動作することを確認したい。これにより、既存機能の品質を維持しながら移行を完了させる。

#### Acceptance Criteria

1. WHEN 全Jestテストが実行される（`npm run test`） THEN すべてのテストがパスし、カバレッジが既存レベル（94.73%以上）を維持すること
2. IF カバレッジレポートが生成される（`npm run test:coverage`） THEN カバレッジ率が低下していないこと
3. WHEN TypeScript型チェックが実行される（`npm run type-check`） THEN 型エラーがゼロであること
4. IF ESLint/Prettierチェックが実行される（`npm run lint && npm run format:check`） THEN エラーがゼロであること
5. WHEN User AppまたはAdmin Appのビルドが実行される（`npm run build`） THEN ビルドが成功し、エラーが発生しないこと
6. IF E2Eテストが実行される（`cd e2e && npm run test:user && npm run test:admin`） THEN 全E2Eテストがパスすること

---

### Requirement 7: 開発サーバー動作確認

**Objective:** 開発者として、パスエイリアス移行後も開発サーバーが正常に起動し、全機能が動作することを確認したい。これにより、日常開発フローへの影響がないことを保証する。

#### Acceptance Criteria

1. WHEN User App開発サーバーが起動される（`cd frontend/user-app && npm run dev`） THEN http://localhost:13001でアプリケーションが正常に表示されること
2. WHEN Admin App開発サーバーが起動される（`cd frontend/admin-app && npm run dev`） THEN http://localhost:13002でアプリケーションが正常に表示されること
3. IF APIリクエストがLaravel APIに送信される THEN リクエストが成功し、正常なレスポンスが返されること
4. WHEN ブラウザ開発者ツールのNetworkタブで確認する THEN `X-Request-ID`ヘッダーと`Accept-Language`ヘッダーが正しく付与されていること
5. IF エラーレスポンスが発生する THEN RFC 7807準拠のエラーハンドリングが正常に機能すること
6. WHERE User AppまたはAdmin Appの実行環境 THE ホットリロード機能が正常に動作し、コード変更が即座に反映されること

---

### Requirement 8: ドキュメント更新

**Objective:** 開発者として、パスエイリアス移行の変更内容が適切にドキュメント化されていることで、将来の開発者が変更履歴を理解できるようにしたい。

#### Acceptance Criteria

1. WHEN CHANGELOG.mdが更新される THEN パスエイリアス導入による変更内容が記載されること
2. IF CHANGELOG.mdに「Breaking Changes」セクションが確認される THEN 本変更は破壊的変更ではないことが明記されること
3. WHEN 変更内容が記載される THEN 以下の情報が含まれること:
   - `@shared/*`パスエイリアス導入
   - 重複ファイル削除（約560行削減）
   - テストカバレッジ維持（94.73%以上）
   - 開発環境・本番環境への影響なし
