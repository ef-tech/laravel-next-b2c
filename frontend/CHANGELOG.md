# Changelog

All notable changes to the frontend applications (User App & Admin App) will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **TypeScriptパスエイリアス `@shared/*`**: 共通ライブラリ（`frontend/lib/`）を参照するための新しいパスエイリアスを導入しました。
  - User App/Admin App両方の`tsconfig.json`、`next.config.ts`、`jest.config.js`に設定を追加
  - TypeScript型補完、Next.jsビルド、Jestテストの3環境で統一的に動作
  - 開発者体験向上: IDEの型補完とインポート解決が即座に機能

### Changed

- **import文の統一**: User App/Admin App内の共通ライブラリインポートを`@/lib/*`から`@shared/*`に統一しました。
  - 対象ファイル: `api-client.ts`, `api-error.ts`, `network-error.ts`のインポート文
  - 影響範囲: User App/Admin App全ソースコード（`.ts`, `.tsx`ファイル）
  - アプリ固有ファイル（`api.ts`, `env.ts`）は変更なし

### Removed

- **重複コード削除**: User App/Admin Appから以下の6ファイル（合計1,054行）を削除しました。
  - `frontend/user-app/src/lib/api-client.ts` (216行)
  - `frontend/user-app/src/lib/api-error.ts` (189行)
  - `frontend/user-app/src/lib/network-error.ts` (122行)
  - `frontend/admin-app/src/lib/api-client.ts` (216行)
  - `frontend/admin-app/src/lib/api-error.ts` (189行)
  - `frontend/admin-app/src/lib/network-error.ts` (122行)
  - **効果**: 共通ライブラリへの完全移行により、メンテナンス性が向上し、バージョン不整合リスクを根本解消

### Fixed

- 特になし

### Security

- **セキュリティヘッダー設定の差異**: User App/Admin Appで異なるセキュリティポリシーを採用しています。

  **User App（エンドユーザー向け）**:
  - `X-Frame-Options: SAMEORIGIN` - 同一オリジンからのフレーム埋め込みを許可（利便性重視）
  - `Referrer-Policy: strict-origin-when-cross-origin` - 標準的なReferrer制御

  **Admin App（管理者向け）**:
  - `X-Frame-Options: DENY` - フレーム埋め込みを完全に拒否（セキュリティ最優先）
  - `Referrer-Policy: no-referrer` - Referrer情報を一切送信しない（情報漏洩防止）
  - `Cross-Origin-Embedder-Policy: require-corp` - クロスオリジンリソース保護を強制
  - `Cross-Origin-Opener-Policy: same-origin` - クロスオリジンウィンドウ分離

  **理由**: Admin Appは管理者向けで機密情報を扱うため、より厳格なセキュリティポリシーを採用しています。User Appはエンドユーザー向けで、セキュリティと利便性のバランスを考慮した設定としています。

### Deprecated

- 特になし

### Performance

- **ビルドサイズ**: 重複コード削除により、約1,054行のコード削減（パスエイリアス導入によるビルドサイズ影響なし）
- **開発体験**: ホットリロード機能が共通ライブラリ変更にも対応（webpack alias設定により）

### Testing

- **テストカバレッジ維持**: 93.69%（変更前後で低下なし）
  - User App: 173テスト全パス
  - Admin App: 212テスト全パス
  - E2Eテスト: 正常動作確認
- **品質保証**: TypeScript型チェック、ESLint/Prettier、本番ビルド、すべてエラーゼロ

### Documentation

- 特になし

---

## Breaking Changes

**なし** - 本変更は完全に後方互換性を維持しています。

- ✅ 既存APIとの互換性: 変更なし
- ✅ 環境変数: 変更なし
- ✅ ビルドプロセス: 既存のまま動作
- ✅ 開発フロー: `npm run dev`、`npm run build`、`npm run test`すべて既存コマンドで動作
- ✅ デプロイメント: 追加設定不要

---

## Migration Notes

本変更は完全に透過的であり、開発者が意識する必要のある移行作業はありません。

### 新規コード記述時の推奨事項

今後、共通ライブラリを使用する際は以下のインポート形式を使用してください：

```typescript
// ✅ 推奨: @shared/* パスエイリアス
import { ApiClient } from "@shared/api-client";
import { ApiError } from "@shared/api-error";
import { NetworkError } from "@shared/network-error";

// ❌ 非推奨: 相対パス（動作はしますが、統一性のため推奨しません）
import { ApiClient } from "../../lib/api-client";
```

### アプリ固有ファイルのインポート

アプリ固有ファイル（`api.ts`, `env.ts`）は引き続き既存の`@/*`パスエイリアスを使用します：

```typescript
// ✅ アプリ固有ファイルは @/* を使用
import { api } from "@/lib/api";
import { env } from "@/lib/env";
```

---

## Contributors

- Claude Code - AI Pair Programming Assistant

---

**Date**: 2025-11-10
**Spec**: #117 frontend/lib/コード重複解消（モノレポ共通化）
